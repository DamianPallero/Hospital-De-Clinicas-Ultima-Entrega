<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Usuario.php';
requerirAutenticacion();

$op = $_POST['accion'] ?? '';

if ($op !== 'cambiar_password_propio' && !tienePermiso('gestionar_usuarios')) {
    header('Location: /sigsm/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /sigsm/views/usuarios.php');
    exit();
}

$tokenForm = $_POST['csrf_token'] ?? '';
if (!validarTokenCSRF($tokenForm)) {
    $destRedir = ($op === 'cambiar_password_propio') ? '/sigsm/views/cambiar_password.php?msg=error_csrf' : '/sigsm/views/usuarios.php?msg=error_csrf';
    header('Location: ' . $destRedir);
    exit();
}

$baseObj = new Database();
$bd = $baseObj->getConnection();
$modUsu = new Usuario($bd);
$mensaje = 'error';

if ($op === 'cambiar_password_propio') {
    $clave = $_SESSION['user_id'];
    $claveActual = $_POST['password_actual'] ?? '';
    $nuevaClave = $_POST['nueva_password'] ?? '';
    $confirma = $_POST['confirmar_password'] ?? '';
    
    if ($nuevaClave !== $confirma) {
        header('Location: /sigsm/views/cambiar_password.php?msg=error_coincidencia'); exit();
    }
    if (strlen($nuevaClave) < 6) {
        header('Location: /sigsm/views/cambiar_password.php?msg=error_longitud'); exit();
    }
    
    $prep = $bd->prepare('SELECT password_hash FROM usuario WHERE id_usuario = :id LIMIT 1');
    $prep->bindParam(':id', $clave, PDO::PARAM_INT);
    $prep->execute();
    $cuenta = $prep->fetch(PDO::FETCH_ASSOC);
    
    if ($cuenta && password_verify($claveActual, $cuenta['password_hash'])) {
        $modUsu->usuId = $clave;
        $modUsu->cambiarPassword($nuevaClave);
        header('Location: /sigsm/views/cambiar_password.php?msg=ok'); exit();
    } else {
        header('Location: /sigsm/views/cambiar_password.php?msg=error_actual'); exit();
    }
}

if ($op === 'crear') {
    $modUsu->rolId = (int)($_POST['id_rol'] ?? 0);
    $modUsu->nom = trim($_POST['nombre'] ?? '');
    $modUsu->ape = trim($_POST['apellido'] ?? '');
    $modUsu->correo = trim($_POST['email'] ?? '');
    $claveAcceso = $_POST['password'] ?? '';

    if ($modUsu->rolId > 0 && $modUsu->nom !== '' && $modUsu->correo !== '' && strlen($claveAcceso) >= 6) {
        $modUsu->hashClave = password_hash($claveAcceso, PASSWORD_DEFAULT);
        $mensaje = $modUsu->crear() ? 'ok_crear' : 'error_email_duplicado';
    } else {
        $mensaje = 'error_validacion';
    }
}

if ($op === 'editar') {
    $modUsu->usuId = (int)($_POST['id_usuario'] ?? 0);
    $modUsu->rolId = (int)($_POST['id_rol'] ?? 0);
    $modUsu->nom = trim($_POST['nombre'] ?? '');
    $modUsu->ape = trim($_POST['apellido'] ?? '');
    $modUsu->correo = trim($_POST['email'] ?? '');

    if ($modUsu->usuId > 0 && $modUsu->rolId > 0 && $modUsu->nom !== '') {
        $mensaje = $modUsu->editar() ? 'ok_editar' : 'error_email_duplicado';
    }
}

if ($op === 'toggle_estado') {
    $clave = (int)($_POST['id_usuario'] ?? 0);
    if ($clave > 0 && $clave != $_SESSION['user_id']) {
        $modUsu->usuId = $clave;
        $mensaje = $modUsu->toggleEstado() ? 'ok_toggle' : 'error';
    } else {
        $mensaje = 'error_propio';
    }
}

if ($op === 'reset_password') {
    $clave = (int)($_POST['id_usuario'] ?? 0);
    $claveAcceso = $_POST['password'] ?? '';
    
    if ($clave > 0 && strlen($claveAcceso) >= 6) {
        $modUsu->usuId = $clave;
        $mensaje = $modUsu->cambiarPassword($claveAcceso) ? 'ok_password' : 'error';
    } else {
        $mensaje = 'error_validacion';
    }
}

if ($op === 'cambiar_password_propio') {
    $clave = $_SESSION['user_id'];
    $claveActual = $_POST['password_actual'] ?? '';
    $nuevaClave = $_POST['nueva_password'] ?? '';
    $confirma = $_POST['confirmar_password'] ?? '';
    
    if ($nuevaClave !== $confirma) {
        header('Location: /sigsm/views/cambiar_password.php?msg=error_coincidencia'); exit();
    }
    if (strlen($nuevaClave) < 6) {
        header('Location: /sigsm/views/cambiar_password.php?msg=error_longitud'); exit();
    }
    
    $persona = $modUsu->obtenerPorId($clave);
    if ($persona && password_verify($claveActual, $persona['password_hash'])) {
        $modUsu->usuId = $clave;
        $modUsu->cambiarPassword($nuevaClave);
        header('Location: /sigsm/views/cambiar_password.php?msg=ok'); exit();
    } else {
        header('Location: /sigsm/views/cambiar_password.php?msg=error_actual'); exit();
    }
}

header('Location: /sigsm/views/usuarios.php?msg=' . $mensaje);
exit();