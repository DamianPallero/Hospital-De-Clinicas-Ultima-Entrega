<?php
class Documento {
    private $conexion;

    public $docId;
    public $catId;
    public $cargaId;
    public $tituloDoc;
    public $urlArchivo;

    public function __construct($bd) {
        $this->conexion = $bd;
    }

    public function listarNuevosHoy() {
        $sql = '
            SELECT d.id_documento, d.titulo, d.fecha_carga,
                   c.nombre AS categoria,
                   u.nombre AS usuario_nombre, u.apellido AS usuario_apellido
            FROM documento d
            LEFT JOIN categoria_documento c ON d.id_categoria = c.id_categoria
            LEFT JOIN usuario u ON d.id_usuario_carga = u.id_usuario
            WHERE DATE(d.fecha_carga) = CURDATE()
              AND d.eliminado = 0
            ORDER BY d.fecha_carga DESC
        ';
        $prep = $this->conexion->prepare($sql);
        $prep->execute();
        return $prep->fetchAll();
    }

    public function listarRecientes($tope = 5) {
        $sql = '
            SELECT d.id_documento, d.titulo, d.url_archivo, d.fecha_carga, c.nombre AS categoria
            FROM documento d
            LEFT JOIN categoria_documento c ON d.id_categoria = c.id_categoria
            WHERE d.eliminado = 0
            ORDER BY d.fecha_carga DESC
            LIMIT :limit
        ';
        $prep = $this->conexion->prepare($sql);
        $prep->bindValue(':limit', (int)$tope, PDO::PARAM_INT);
        $prep->execute();
        return $prep->fetchAll();
    }

    public function listar($busqueda = '', $estado = 'activos', $categoriaId = 0, $permiteEliminados = false) {
        $sql = '
            SELECT d.id_documento, d.titulo, d.url_archivo, d.fecha_carga, d.id_categoria, d.eliminado, c.nombre AS categoria
            FROM documento d
            LEFT JOIN categoria_documento c ON d.id_categoria = c.id_categoria
            WHERE 1 = 1
        ';
        $params = [];

        $busqueda = trim((string)$busqueda);
        if ($busqueda !== '') {
            $sql .= ' AND (LOWER(COALESCE(d.titulo, "")) LIKE LOWER(:buscar) OR LOWER(COALESCE(c.nombre, "")) LIKE LOWER(:buscar) OR CAST(d.id_documento AS CHAR) LIKE :buscar)';
            $params[':buscar'] = '%' . $busqueda . '%';
        }

        if ((int)$categoriaId > 0) {
            $sql .= ' AND d.id_categoria = :id_categoria';
            $params[':id_categoria'] = (int)$categoriaId;
        }

        if (!$permiteEliminados) {
            $sql .= ' AND d.eliminado = 0';
        } elseif ($estado === 'activos') {
            $sql .= ' AND d.eliminado = 0';
        } elseif ($estado === 'eliminados') {
            // Se cargan todos los registros cuando el usuario tiene permiso para ver eliminados,
            // para que el filtro en vivo pueda mostrar/ocultar activos y eliminados sin perder filas.
        }

        $sql .= ' ORDER BY d.eliminado ASC, d.fecha_carga DESC';

        $prep = $this->conexion->prepare($sql);
        foreach ($params as $clave => $valor) {
            $prep->bindValue($clave, $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $prep->execute();
        return $prep->fetchAll();
    }

    public function listarCategorias() {
        $sql = 'SELECT id_categoria, nombre FROM categoria_documento ORDER BY nombre ASC';
        $prep = $this->conexion->prepare($sql);
        $prep->execute();
        return $prep->fetchAll();
    }

    public function contarDocumentosPorCategoria($catId) {
        $sql = 'SELECT COUNT(*) as total FROM documento WHERE id_categoria = :id AND eliminado = 0';
        $prep = $this->conexion->prepare($sql);
        $prep->bindParam(':id', $catId, PDO::PARAM_INT);
        $prep->execute();
        $salida = $prep->fetch(PDO::FETCH_ASSOC);
        return $salida['total'] ?? 0;
    }

    public function crear() {
        $sql = 'INSERT INTO documento (id_categoria, id_usuario_carga, titulo, url_archivo, fecha_carga) VALUES (:id_categoria, :id_usuario_carga, :titulo, :url_archivo, NOW())';
        $prep = $this->conexion->prepare($sql);
        $this->tituloDoc = htmlspecialchars(strip_tags($this->tituloDoc));
        $this->urlArchivo = preg_replace('/[^a-f0-9]/i', '', $this->urlArchivo);
        $prep->bindParam(':id_categoria', $this->catId, PDO::PARAM_INT);
        $prep->bindParam(':id_usuario_carga', $this->cargaId, PDO::PARAM_INT);
        $prep->bindParam(':titulo', $this->tituloDoc);
        $prep->bindParam(':url_archivo', $this->urlArchivo);
        return $prep->execute();
    }

    public function editar() {
        $sql = 'UPDATE documento SET titulo = :titulo, id_categoria = :id_categoria WHERE id_documento = :id_documento';
        $prep = $this->conexion->prepare($sql);
        $this->tituloDoc = htmlspecialchars(strip_tags($this->tituloDoc));
        $prep->bindParam(':titulo', $this->tituloDoc);
        $prep->bindParam(':id_categoria', $this->catId, PDO::PARAM_INT);
        $prep->bindParam(':id_documento', $this->docId, PDO::PARAM_INT);
        return $prep->execute();
    }

    public function eliminar() {
        $sql = 'UPDATE documento SET eliminado = 1, fecha_eliminacion = NOW() WHERE id_documento = :id';
        $prep = $this->conexion->prepare($sql);
        $prep->bindParam(':id', $this->docId, PDO::PARAM_INT);
        return $prep->execute();
    }

    public function restaurar() {
        $sql = 'UPDATE documento SET eliminado = 0, fecha_eliminacion = NULL WHERE id_documento = :id';
        $prep = $this->conexion->prepare($sql);
        $prep->bindParam(':id', $this->docId, PDO::PARAM_INT);
        return $prep->execute();
    }

    public function crearCategoria($nom) {
        $sql = 'INSERT INTO categoria_documento (nombre) VALUES (:nombre)';
        $prep = $this->conexion->prepare($sql);
        $nom = htmlspecialchars(strip_tags($nom));
        $prep->bindParam(':nombre', $nom);
        return $prep->execute();
    }

    public function editarCategoria($catId, $nom) {
        $sql = 'UPDATE categoria_documento SET nombre = :nombre WHERE id_categoria = :id';
        $prep = $this->conexion->prepare($sql);
        $nom = htmlspecialchars(strip_tags($nom));
        $prep->bindParam(':nombre', $nom);
        $prep->bindParam(':id', $catId, PDO::PARAM_INT);
        return $prep->execute();
    }

    public function eliminarCategoria($catId) {
        $sql = 'DELETE FROM categoria_documento WHERE id_categoria = :id';
        $prep = $this->conexion->prepare($sql);
        $prep->bindParam(':id', $catId, PDO::PARAM_INT);
        return $prep->execute();
    }

    public function obtenerCategoriaPorId($catId) {
        $sql = 'SELECT id_categoria, nombre FROM categoria_documento WHERE id_categoria = :id LIMIT 1';
        $prep = $this->conexion->prepare($sql);
        $prep->bindParam(':id', $catId, PDO::PARAM_INT);
        $prep->execute();
        return $prep->fetch(PDO::FETCH_ASSOC);
    }
}