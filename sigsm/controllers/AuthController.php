<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../includes/Captcha.php';
require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../models/Usuario.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /sigsm/views/login.php');
    exit();
}

$op = $_POST['accion'] ?? '';

if ($op === 'forgot_password' || $op === 'reset_password' || $op === 'login') {
    if (loginBloqueado()) {
        $_SESSION['login_error'] = 'Demasiados intentos fallidos. Intente nuevamente en 15 minutos.';
        header('Location: /sigsm/views/login.php');
        exit();
    }
}

if ($op === 'forgot_password') {
    $correo = trim((string)($_POST['email'] ?? ''));
    if ($correo !== '') {
        try {
            $bd = (new Database())->getConnection();
            $prep = $bd->prepare('SELECT id_usuario FROM usuario WHERE email = :email LIMIT 1');
            $prep->bindParam(':email', $correo, PDO::PARAM_STR);
            $prep->execute();
            if ($prep->fetch()) {
                $cod = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $codHash = hash('sha256', $cod);
                $_SESSION['reset_code_visible'] = $cod;
                $carp = __DIR__ . '/../config/reset_codes/';
                asegurarCarpetaProtegida($carp);
                $arch = $carp . md5($correo) . '.txt';
                file_put_contents($arch, $correo . '|' . $codHash . '|' . $cod . '|' . time());
            }
        } catch (Exception $err) {
        }
    }
    header('Location: /sigsm/views/forgot_password.php?msg=requested');
    exit();
}

if ($op === 'reset_password') {
    $correo = trim((string)($_POST['email'] ?? ''));
    $cod = trim((string)($_POST['codigo'] ?? ''));
    $nuevaClave = $_POST['nueva_password'] ?? '';
    
    $arch = __DIR__ . '/../config/reset_codes/' . md5($correo) . '.txt';
    $correcto = false;
    
    if (file_exists($arch)) {
        $datos = explode('|', file_get_contents($arch));
        $codHash = hash('sha256', $cod);
        if (count($datos) >= 4 && $datos[0] === $correo && hash_equals((string)$datos[1], $codHash) && (time() - (int)$datos[3]) < 900) {
            $correcto = true;
        }
    }
    
    if ($correcto && strlen($nuevaClave) >= 6) {
        try {
            $bd = (new Database())->getConnection();
            $prep = $bd->prepare('SELECT id_usuario FROM usuario WHERE email = :email LIMIT 1');
            $prep->bindParam(':email', $correo, PDO::PARAM_STR);
            $prep->execute();
            $cuenta = $prep->fetch(PDO::FETCH_ASSOC);
            if ($cuenta) {
                $modUsu = new Usuario($bd);
                $modUsu->usuId = $cuenta['id_usuario'];
                $modUsu->cambiarPassword($nuevaClave);
                @unlink($arch);
                reiniciarIntentosLogin();
                header('Location: /sigsm/views/login.php?reset=ok');
                exit();
            }
        } catch (Exception $err) {
        }
    }
    registrarFalloLogin();
    header('Location: /sigsm/views/reset_password.php?msg=invalid_code');
    exit();
}

$tokenForm = $_POST['csrf_token'] ?? '';
if (!validarTokenCSRF($tokenForm)) {
    $_SESSION['login_error'] = 'Token de seguridad inválido. Intente nuevamente.';
    header('Location: /sigsm/views/login.php');
    exit();
}

$respCaptcha = $_POST['captcha'] ?? '';
if (!Captcha::validar($respCaptcha)) {
    $_SESSION['login_error'] = 'Respuesta de verificación incorrecta. Intente nuevamente.';
    header('Location: /sigsm/views/login.php');
    exit();
}

$correo = trim((string)($_POST['email'] ?? ''));
$claveAcceso = $_POST['password'] ?? '';

if ($correo === '' || $claveAcceso === '') {
    $_SESSION['login_error'] = 'Email y contraseña son requeridos.';
    header('Location: /sigsm/views/login.php');
    exit();
}

try {
    $baseObj = new Database();
    $bd = $baseObj->getConnection();
    $prep = $bd->prepare('
        SELECT u.id_usuario AS id, u.nombre, u.apellido, u.email, u.password_hash, r.nombre_rol AS role_name
        FROM usuario u
        INNER JOIN rol r ON u.id_rol = r.id_rol
        WHERE u.email = :email AND u.activo = 1
        LIMIT 1
    ');
    $prep->bindParam(':email', $correo, PDO::PARAM_STR);
    $prep->execute();
    $cuenta = $prep->fetch(PDO::FETCH_ASSOC);

    if ($cuenta && !empty($cuenta['password_hash']) && password_verify($claveAcceso, $cuenta['password_hash'])) {
        reiniciarIntentosLogin();
        iniciarSesionSegura();
        session_regenerate_id(true);
        $_SESSION['authenticated'] = true;
        $_SESSION['user_id'] = $cuenta['id'];
        $_SESSION['user_name'] = trim($cuenta['nombre'] . ' ' . $cuenta['apellido']);
        $_SESSION['role_name'] = $cuenta['role_name'] ?? 'Invitado';
        $_SESSION['last_login_at'] = date('c');
        $_SESSION['last_activity'] = time();
        header('Location: /sigsm/index.php');
        exit();
    } else {
        registrarFalloLogin();
        $_SESSION['login_error'] = 'Credenciales inválidas.';
        header('Location: /sigsm/views/login.php');
        exit();
    }
} catch (Exception $err) {
    $_SESSION['login_error'] = 'Error de autenticación. Contacte al administrador.';
    header('Location: /sigsm/views/login.php');
    exit();
}