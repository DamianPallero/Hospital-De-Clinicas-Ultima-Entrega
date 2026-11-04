<?php
require_once __DIR__ . '/../includes/auth_middleware.php';

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Encuesta.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: portal.php');
    exit();
}

$encId = (int)($_POST['id_encuesta'] ?? 0);
$resps = $_POST['respuestas'] ?? [];
$ver = (int)($_POST['v'] ?? 1);
$claveSesion = 'survey_completed_v' . $ver;

if ($encId > 0 && !empty($resps)) {
    $bd = (new Database())->getConnection();
    $modEncuesta = new Encuesta($bd);
    
    $respsLimpias = [];
    foreach ($resps as $pregId => $dato) {
        $respsLimpias[(int)$pregId] = trim((string)$dato);
    }
    
    $modEncuesta->guardarRespuestas($respsLimpias);
}

$_SESSION[$claveSesion] = true;

header("Location: gracias.php");
exit();