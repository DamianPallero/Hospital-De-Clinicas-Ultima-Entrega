<?php
class Encuesta {
    private $conexion;

    public function __construct($bd) {
        $this->conexion = $bd;
    }

    public function listarActivas() {
        $prep = $this->conexion->prepare('SELECT `id_encuesta`, `titulo` FROM `encuesta` WHERE `estado` = 1 ORDER BY `titulo` ASC');
        $prep->execute();
        return $prep->fetchAll();
    }

    public function listarTodas() {
        $prep = $this->conexion->prepare('SELECT `id_encuesta`, `titulo`, `estado` FROM `encuesta` ORDER BY `estado` DESC, `titulo` ASC');
        $prep->execute();
        return $prep->fetchAll();
    }

    public function obtenerPorId($clave) {
        $prep = $this->conexion->prepare('SELECT `id_encuesta`, `titulo`, `estado` FROM `encuesta` WHERE `id_encuesta` = :id LIMIT 1');
        $prep->bindParam(':id', $clave, PDO::PARAM_INT);
        $prep->execute();
        return $prep->fetch();
    }

    public function crear($tituloDoc) {
        $prep = $this->conexion->prepare('INSERT INTO `encuesta` (`titulo`) VALUES (:titulo)');
        $prep->bindParam(':titulo', $tituloDoc);
        return $prep->execute();
    }

    public function toggleEstado($clave) {
        $prep = $this->conexion->prepare('UPDATE `encuesta` SET `estado` = IF(`estado` = 1, 0, 1) WHERE `id_encuesta` = :id');
        $prep->bindParam(':id', $clave, PDO::PARAM_INT);
        return $prep->execute();
    }

    public function obtenerPreguntas($encId) {
        $prep = $this->conexion->prepare('SELECT `id_pregunta`, `texto_pregunta`, `tipo_respuesta` FROM `pregunta_encuesta` WHERE `id_encuesta` = :id ORDER BY `id_pregunta` ASC');
        $prep->bindParam(':id', $encId, PDO::PARAM_INT);
        $prep->execute();
        return $prep->fetchAll();
    }

    public function agregarPregunta($encId, $textoBase, $clase) {
        $prep = $this->conexion->prepare('INSERT INTO `pregunta_encuesta` (`id_encuesta`, `texto_pregunta`, `tipo_respuesta`) VALUES (:id_encuesta, :texto, :tipo)');
        $prep->bindParam(':id_encuesta', $encId, PDO::PARAM_INT);
        $prep->bindParam(':texto', $textoBase);
        $prep->bindParam(':tipo', $clase);
        return $prep->execute();
    }

    public function guardarRespuestas($resps) {
        $prep = $this->conexion->prepare('INSERT INTO `respuesta_encuesta` (`id_pregunta`, `respuesta_texto`) VALUES (:id_pregunta, :respuesta_texto)');
        foreach ($resps as $pregId => $dato) {
            $prep->bindValue(':id_pregunta', (int)$pregId, PDO::PARAM_INT);
            $prep->bindValue(':respuesta_texto', $dato, PDO::PARAM_STR);
            $prep->execute();
        }
    }

    public function obtenerResumenRespuestas() {
        $sql = 'SELECT `e`.`titulo` AS `encuesta_titulo`, `p`.`id_pregunta`, `p`.`texto_pregunta`, `p`.`tipo_respuesta`, `r`.`respuesta_texto`, COUNT(*) as `total` ' .
                 'FROM `respuesta_encuesta` `r` ' .
                 'INNER JOIN `pregunta_encuesta` `p` ON `r`.`id_pregunta` = `p`.`id_pregunta` ' .
                 'INNER JOIN `encuesta` `e` ON `p`.`id_encuesta` = `e`.`id_encuesta` ' .
                 'GROUP BY `e`.`titulo`, `p`.`id_pregunta`, `p`.`texto_pregunta`, `p`.`tipo_respuesta`, `r`.`respuesta_texto` ' .
                 'ORDER BY `e`.`titulo` ASC, `p`.`id_pregunta` ASC';
        
        try {
            $prep = $this->conexion->prepare($sql);
            $prep->execute();
            return $prep->fetchAll();
        } catch (PDOException $err) {
            return []; 
        }
    }
}