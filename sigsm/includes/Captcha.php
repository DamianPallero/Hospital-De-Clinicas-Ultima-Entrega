<?php
class Captcha {
    public static function generar() {
        $numA = rand(1, 10);
        $numB = rand(1, 10);
        $opers = ['+', '-'];
        $oper = $opers[array_rand($opers)];
        
        switch ($oper) {
            case '+':
                $respTexto = $numA + $numB;
                break;
            case '-':
                if ($numA < $numB) {
                    $temporal = $numA;
                    $numA = $numB;
                    $numB = $temporal;
                }
                $respTexto = $numA - $numB;
                break;
        }
        
        $pregFilaTexto = "$numA $oper $numB";
        $_SESSION['captcha_respuesta'] = $respTexto;
        $_SESSION['captcha_tiempo'] = time();
        
        return $pregFilaTexto;
    }
    
    public static function validar($respUsu) {
        if (!isset($_SESSION['captcha_respuesta']) || !isset($_SESSION['captcha_tiempo'])) {
            return false;
        }
        
        if (time() - $_SESSION['captcha_tiempo'] > 300) {
            unset($_SESSION['captcha_respuesta'], $_SESSION['captcha_tiempo']);
            return false;
        }
        
        $respOk = $_SESSION['captcha_respuesta'];
        unset($_SESSION['captcha_respuesta'], $_SESSION['captcha_tiempo']);
        
        return (int)$respUsu === (int)$respOk;
    }
}