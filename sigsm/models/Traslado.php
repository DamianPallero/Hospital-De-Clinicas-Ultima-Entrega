<?php
class Traslado {
    private $conexion;

    public $trasId;
    public $choferId;
    public $enfId;
    public $vehId;
    public $inicio;
    public $fin;
    public $horaSale;
    public $horaLlega;
    public $sitTras;
    public $oxigeno;
    public $aislamiento;
    public $usaAscensor;
    public $notas;

    public function __construct($bd) {
        $this->conexion = $bd;
    }

    public function listarTodos() {
        $sql = '
            SELECT t.id_traslado, t.origen, t.destino, t.fecha_hora_salida, t.fecha_hora_llegada,
                   t.estado_traslado, t.necesita_oxigeno, t.tipo_aislamiento,
                   t.requiere_ascensor_escalera, t.observaciones,
                   c.nombre AS conductor_nombre, c.apellido AS conductor_apellido,
                   e.nombre AS enfermero_nombre, e.apellido AS enfermero_apellido,
                   v.patente, v.tipo AS vehiculo_tipo
            FROM traslado t
            LEFT JOIN conductor c ON t.id_conductor = c.id_conductor
            LEFT JOIN enfermero e ON t.id_enfermero = e.id_enfermero
            LEFT JOIN vehiculo v ON t.id_vehiculo = v.id_vehiculo
            ORDER BY t.fecha_hora_salida DESC
        ';
        $prep = $this->conexion->prepare($sql);
        $prep->execute();
        return $prep->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPendientes() {
        $sql = '
            SELECT t.id_traslado, t.origen, t.destino, t.fecha_hora_salida,
                   t.estado_traslado, t.necesita_oxigeno, t.tipo_aislamiento,
                   t.requiere_ascensor_escalera, t.observaciones
            FROM traslado t
            WHERE t.estado_traslado = "Pendiente"
            ORDER BY t.fecha_hora_salida ASC
        ';
        $prep = $this->conexion->prepare($sql);
        $prep->execute();
        return $prep->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($clave) {
        $sql = '
            SELECT t.*, 
                   c.nombre AS conductor_nombre, c.apellido AS conductor_apellido,
                   e.nombre AS enfermero_nombre, e.apellido AS enfermero_apellido,
                   v.patente, v.tipo AS vehiculo_tipo
            FROM traslado t
            LEFT JOIN conductor c ON t.id_conductor = c.id_conductor
            LEFT JOIN enfermero e ON t.id_enfermero = e.id_enfermero
            LEFT JOIN vehiculo v ON t.id_vehiculo = v.id_vehiculo
            WHERE t.id_traslado = :id LIMIT 1
        ';
        $prep = $this->conexion->prepare($sql);
        $prep->bindParam(':id', $clave, PDO::PARAM_INT);
        $prep->execute();
        return $prep->fetch(PDO::FETCH_ASSOC);
    }

    public function crear() {
        $sql = 'INSERT INTO traslado (origen, destino, fecha_hora_salida, estado_traslado, necesita_oxigeno, tipo_aislamiento, requiere_ascensor_escalera, observaciones) 
                  VALUES (:origen, :destino, NOW(), "Pendiente", :necesita_oxigeno, :tipo_aislamiento, :requiere_ascensor_escalera, :observaciones)';
        $prep = $this->conexion->prepare($sql);

        $this->inicio = htmlspecialchars(strip_tags($this->inicio));
        $this->fin = htmlspecialchars(strip_tags($this->fin));
        $this->aislamiento = htmlspecialchars(strip_tags($this->aislamiento));
        $this->usaAscensor = htmlspecialchars(strip_tags($this->usaAscensor));
        $this->notas = htmlspecialchars(strip_tags($this->notas ?? ''));

        $prep->bindParam(':origen', $this->inicio);
        $prep->bindParam(':destino', $this->fin);
        $prep->bindParam(':necesita_oxigeno', $this->oxigeno, PDO::PARAM_INT);
        $prep->bindParam(':tipo_aislamiento', $this->aislamiento);
        $prep->bindParam(':requiere_ascensor_escalera', $this->usaAscensor);
        $prep->bindParam(':observaciones', $this->notas);

        return $prep->execute();
    }

    public function asignarRecursos() {
        $sql = 'UPDATE traslado SET id_conductor = :id_conductor, id_enfermero = :id_enfermero, id_vehiculo = :id_vehiculo, estado_traslado = "Asignado" WHERE id_traslado = :id_traslado';
        $prep = $this->conexion->prepare($sql);
        $prep->bindParam(':id_conductor', $this->choferId, PDO::PARAM_INT);
        $prep->bindParam(':id_enfermero', $this->enfId, PDO::PARAM_INT);
        $prep->bindParam(':id_vehiculo', $this->vehId, PDO::PARAM_INT);
        $prep->bindParam(':id_traslado', $this->trasId, PDO::PARAM_INT);

        $resultado = $prep->execute();
        if ($resultado && $this->vehId > 0) {
            $this->actualizarEstadoVehiculo($this->vehId, 'En Traslado');
        }
        return $resultado;
    }

    public function actualizarEstado($nuevoSit) {
        $sitsValidas = ['Pendiente', 'Asignado', 'En Ruta', 'Completado', 'Cancelado'];
        if (!in_array($nuevoSit, $sitsValidas, true)) {
            return false;
        }

        $sql = 'UPDATE traslado SET estado_traslado = :estado';
        if ($nuevoSit === 'Completado') {
            $sql .= ', fecha_hora_llegada = NOW()';
        }
        $sql .= ' WHERE id_traslado = :id';

        $prep = $this->conexion->prepare($sql);
        $prep->bindParam(':estado', $nuevoSit);
        $prep->bindParam(':id', $this->trasId, PDO::PARAM_INT);
        $resultado = $prep->execute();

        if ($resultado) {
            $vehiculoActual = $this->obtenerVehiculoPorTraslado($this->trasId);
            if ($vehiculoActual !== null && ($nuevoSit === 'Completado' || $nuevoSit === 'Cancelado')) {
                $this->actualizarEstadoVehiculo($vehiculoActual, 'Disponible');
            } elseif ($vehiculoActual !== null && ($nuevoSit === 'Asignado' || $nuevoSit === 'En Ruta' || $nuevoSit === 'Pendiente')) {
                $this->actualizarEstadoVehiculo($vehiculoActual, 'En Traslado');
            }
        }

        return $resultado;
    }

    private function obtenerVehiculoPorTraslado($idTraslado) {
        $sql = 'SELECT id_vehiculo FROM traslado WHERE id_traslado = :id LIMIT 1';
        $prep = $this->conexion->prepare($sql);
        $prep->bindParam(':id', $idTraslado, PDO::PARAM_INT);
        $prep->execute();
        $fila = $prep->fetch(PDO::FETCH_ASSOC);
        return $fila['id_vehiculo'] ?? null;
    }

    public function crearVehiculo($patente, $tipo, $estado = 'Disponible') {
        $estadosValidos = ['Disponible', 'En Traslado', 'En Mantenimiento', 'Fuera de Servicio'];
        if (!in_array($estado, $estadosValidos, true)) {
            return false;
        }

        $sql = 'INSERT INTO vehiculo (patente, tipo, estado) VALUES (:patente, :tipo, :estado)';
        $prep = $this->conexion->prepare($sql);
        $prep->bindParam(':patente', $patente);
        $prep->bindParam(':tipo', $tipo);
        $prep->bindParam(':estado', $estado);
        return $prep->execute();
    }

    public function listarConductores() {
        $prep = $this->conexion->prepare('SELECT id_conductor, nombre, apellido FROM conductor WHERE estado = "Activo" ORDER BY apellido');
        $prep->execute();
        return $prep->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarEnfermeros() {
        $prep = $this->conexion->prepare('SELECT id_enfermero, nombre, apellido FROM enfermero WHERE estado = "Activo" ORDER BY apellido');
        $prep->execute();
        return $prep->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPersonalTransporte() {
        $sql = '(
            SELECT id_conductor AS id_personal, "Conductor" AS tipo_personal, nombre, apellido, licencia AS documento, estado
            FROM conductor
        )
        UNION ALL
        (
            SELECT id_enfermero AS id_personal, "Enfermero" AS tipo_personal, nombre, apellido, matricula AS documento, estado
            FROM enfermero
        )
        ORDER BY tipo_personal, apellido, nombre';

        $prep = $this->conexion->prepare($sql);
        $prep->execute();
        return $prep->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crearPersonalTransporte($tipo, $nombre, $apellido, $documento, $estado = 'Activo') {
        $tipo = strtolower(trim((string)$tipo));
        $nombre = trim((string)$nombre);
        $apellido = trim((string)$apellido);
        $documento = trim((string)$documento);
        $estado = trim((string)$estado);

        if (!in_array($tipo, ['conductor', 'enfermero'], true) || $nombre === '' || $apellido === '' || $documento === '') {
            return false;
        }

        if ($tipo === 'conductor') {
            $estadoValido = in_array($estado, ['Activo', 'De Licencia', 'Inactivo'], true) ? $estado : 'Activo';
            $sql = 'INSERT INTO conductor (nombre, apellido, licencia, estado) VALUES (:nombre, :apellido, :documento, :estado)';
            $prep = $this->conexion->prepare($sql);
            $prep->bindParam(':nombre', $nombre);
            $prep->bindParam(':apellido', $apellido);
            $prep->bindParam(':documento', $documento);
            $prep->bindParam(':estado', $estadoValido);
            return $prep->execute();
        }

        $estadoValido = in_array($estado, ['Activo', 'Inactivo'], true) ? $estado : 'Activo';
        $sql = 'INSERT INTO enfermero (nombre, apellido, matricula, estado) VALUES (:nombre, :apellido, :documento, :estado)';
        $prep = $this->conexion->prepare($sql);
        $prep->bindParam(':nombre', $nombre);
        $prep->bindParam(':apellido', $apellido);
        $prep->bindParam(':documento', $documento);
        $prep->bindParam(':estado', $estadoValido);
        return $prep->execute();
    }

    public function actualizarPersonalTransporte($tipo, $id, $nombre, $apellido, $documento, $estado = 'Activo') {
        $tipo = strtolower(trim((string)$tipo));
        $id = (int)$id;
        $nombre = trim((string)$nombre);
        $apellido = trim((string)$apellido);
        $documento = trim((string)$documento);
        $estado = trim((string)$estado);

        if ($id <= 0 || !in_array($tipo, ['conductor', 'enfermero'], true) || $nombre === '' || $apellido === '' || $documento === '') {
            return false;
        }

        if ($tipo === 'conductor') {
            $estadoValido = in_array($estado, ['Activo', 'De Licencia', 'Inactivo'], true) ? $estado : 'Activo';
            $sql = 'UPDATE conductor SET nombre = :nombre, apellido = :apellido, licencia = :documento, estado = :estado WHERE id_conductor = :id';
            $prep = $this->conexion->prepare($sql);
            $prep->bindParam(':nombre', $nombre);
            $prep->bindParam(':apellido', $apellido);
            $prep->bindParam(':documento', $documento);
            $prep->bindParam(':estado', $estadoValido);
            $prep->bindParam(':id', $id, PDO::PARAM_INT);
            return $prep->execute();
        }

        $estadoValido = in_array($estado, ['Activo', 'Inactivo'], true) ? $estado : 'Activo';
        $sql = 'UPDATE enfermero SET nombre = :nombre, apellido = :apellido, matricula = :documento, estado = :estado WHERE id_enfermero = :id';
        $prep = $this->conexion->prepare($sql);
        $prep->bindParam(':nombre', $nombre);
        $prep->bindParam(':apellido', $apellido);
        $prep->bindParam(':documento', $documento);
        $prep->bindParam(':estado', $estadoValido);
        $prep->bindParam(':id', $id, PDO::PARAM_INT);
        return $prep->execute();
    }

    public function eliminarPersonalTransporte($tipo, $id) {
        $tipo = strtolower(trim((string)$tipo));
        $id = (int)$id;

        if ($id <= 0 || !in_array($tipo, ['conductor', 'enfermero'], true)) {
            return false;
        }

        $campo = $tipo === 'conductor' ? 'id_conductor' : 'id_enfermero';
        $tabla = $tipo === 'conductor' ? 'conductor' : 'enfermero';
        $sql = "DELETE FROM {$tabla} WHERE {$campo} = :id";
        $prep = $this->conexion->prepare($sql);
        $prep->bindParam(':id', $id, PDO::PARAM_INT);
        return $prep->execute();
    }

    public function listarVehiculos() {
        $prep = $this->conexion->prepare('SELECT id_vehiculo, patente, tipo, estado FROM vehiculo ORDER BY tipo, patente');
        $prep->execute();
        return $prep->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarVehiculosDisponibles() {
        $prep = $this->conexion->prepare('SELECT id_vehiculo, patente, tipo, estado FROM vehiculo WHERE estado = "Disponible" ORDER BY tipo, patente');
        $prep->execute();
        return $prep->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarEstadoVehiculo($idVehiculo, $nuevoEstado) {
        $estadosValidos = ['Disponible', 'En Traslado', 'En Mantenimiento', 'Fuera de Servicio'];
        if (!in_array($nuevoEstado, $estadosValidos, true)) {
            return false;
        }

        $sql = 'UPDATE vehiculo SET estado = :estado WHERE id_vehiculo = :id';
        $prep = $this->conexion->prepare($sql);
        $prep->bindParam(':estado', $nuevoEstado);
        $prep->bindParam(':id', $idVehiculo, PDO::PARAM_INT);
        return $prep->execute();
    }
}