<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Encuesta.php';

requerirAutenticacion();
if (!tienePermiso('gestionar_encuestas')) {
    header('Location: /sigsm/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /sigsm/views/encuestas_admin.php');
    exit();
}

$tokenForm = $_POST['csrf_token'] ?? '';
if (!validarTokenCSRF($tokenForm)) {
    header('Location: /sigsm/views/encuestas_admin.php?error=csrf');
    exit();
}

$baseObj = new Database();
$bd = $baseObj->getConnection();
$modEncuesta = new Encuesta($bd);
$op = $_POST['accion'] ?? '';

if ($op === 'crear_encuesta') {
    $tituloDoc = trim($_POST['titulo'] ?? '');
    if ($tituloDoc !== '') {
        $modEncuesta->crear($tituloDoc);
    }
}

if ($op === 'agregar_pregunta') {
    $encId = (int)($_POST['id_encuesta'] ?? 0);
    $textoBase = trim($_POST['texto'] ?? '');
    $clase = $_POST['tipo'] ?? 'escala_1_a_5';
    if ($encId > 0 && $textoBase !== '') {
        $modEncuesta->agregarPregunta($encId, $textoBase, $clase);
    }
}

if ($op === 'toggle_estado') {
    $clave = (int)($_POST['id_encuesta'] ?? 0);
    if ($clave > 0) {
        $modEncuesta->toggleEstado($clave);
    }
}

if ($op === 'rotar_version_qr') {
    $archivoVer = __DIR__ . '/../config/portal_version.txt';
    $archivoHora = __DIR__ . '/../config/portal_version_time.txt';
    
    $verActual = (file_exists($archivoVer) && is_writable($archivoVer)) ? (int)file_get_contents($archivoVer) : 0;
    
    if (is_writable(__DIR__ . '/../config')) {
        file_put_contents($archivoVer, (string)($verActual + 1));
        file_put_contents($archivoHora, (string)time());
    }
}

header('Location: /sigsm/views/encuestas_admin.php');
exit();