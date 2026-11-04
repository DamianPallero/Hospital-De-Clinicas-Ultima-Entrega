<?php
class Usuario {
    private $conexion;

    public $usuId;
    public $rolId;
    public $nom;
    public $ape;
    public $correo;
    public $hashClave;
    public $act;

    public function __construct($bd) {
        $this->conexion = $bd;
    }

    public function listar() {
        $sql = 'SELECT u.id_usuario, u.nombre, u.apellido, u.email, u.activo, r.id_rol, r.nombre_rol 
                  FROM usuario u 
                  INNER JOIN rol r ON u.id_rol = r.id_rol 
                  ORDER BY u.apellido ASC, u.nombre ASC';
        $prep = $this->conexion->prepare($sql);
        $prep->execute();
        return $prep->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($clave) {
        $sql = 'SELECT id_usuario, id_rol, nombre, apellido, email, activo, password_hash FROM usuario WHERE id_usuario = :id LIMIT 1';
        $prep = $this->conexion->prepare($sql);
        $prep->bindParam(':id', $clave, PDO::PARAM_INT);
        $prep->execute();
        return $prep->fetch(PDO::FETCH_ASSOC);
    }

    public function listarRoles() {
        $sql = 'SELECT id_rol, nombre_rol FROM rol ORDER BY nombre_rol ASC';
        $prep = $this->conexion->prepare($sql);
        $prep->execute();
        return $prep->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear() {
        $sql = 'INSERT INTO usuario (id_rol, nombre, apellido, email, password_hash) VALUES (:id_rol, :nombre, :apellido, :email, :password_hash)';
        $prep = $this->conexion->prepare($sql);
        
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->ape = htmlspecialchars(strip_tags($this->ape));
        $this->correo = filter_var($this->correo, FILTER_SANITIZE_EMAIL);
        
        $prep->bindParam(':id_rol', $this->rolId, PDO::PARAM_INT);
        $prep->bindParam(':nombre', $this->nom);
        $prep->bindParam(':apellido', $this->ape);
        $prep->bindParam(':email', $this->correo);
        $prep->bindParam(':password_hash', $this->hashClave);
        
        return $prep->execute();
    }

    public function editar() {
        $sql = 'UPDATE usuario SET id_rol = :id_rol, nombre = :nombre, apellido = :apellido, email = :email WHERE id_usuario = :id_usuario';
        $prep = $this->conexion->prepare($sql);
        
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->ape = htmlspecialchars(strip_tags($this->ape));
        $this->correo = filter_var($this->correo, FILTER_SANITIZE_EMAIL);
        
        $prep->bindParam(':id_rol', $this->rolId, PDO::PARAM_INT);
        $prep->bindParam(':nombre', $this->nom);
        $prep->bindParam(':apellido', $this->ape);
        $prep->bindParam(':email', $this->correo);
        $prep->bindParam(':id_usuario', $this->usuId, PDO::PARAM_INT);
        
        return $prep->execute();
    }

    public function toggleEstado() {
        $sql = 'UPDATE usuario SET activo = IF(activo = 1, 0, 1) WHERE id_usuario = :id_usuario';
        $prep = $this->conexion->prepare($sql);
        $prep->bindParam(':id_usuario', $this->usuId, PDO::PARAM_INT);
        return $prep->execute();
    }

    public function cambiarPassword($claveNueva) {
        $sello = password_hash($claveNueva, PASSWORD_DEFAULT);
        $sql = 'UPDATE usuario SET password_hash = :hash WHERE id_usuario = :id_usuario';
        $prep = $this->conexion->prepare($sql);
        $prep->bindParam(':hash', $sello);
        $prep->bindParam(':id_usuario', $this->usuId, PDO::PARAM_INT);
        return $prep->execute();
    }
}