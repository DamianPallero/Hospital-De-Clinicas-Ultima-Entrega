<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Documento.php';

requerirAutenticacion();

if (!tienePermiso('ver_documentos')) {
    die("Acceso denegado.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /sigsm/views/qr_documentos.php');
    exit();
}

$tokenForm = $_POST['csrf_token'] ?? '';
if (!validarTokenCSRF($tokenForm)) {
    header('Location: /sigsm/views/qr_documentos.php?error=csrf');
    exit();
}

$docId = (int)($_POST['id_documento'] ?? 0);

$baseObj = new Database();
$bd = $baseObj->getConnection();

$prep = $bd->prepare('SELECT id_documento FROM documento WHERE id_documento = :id');
$prep->bindParam(':id', $docId, PDO::PARAM_INT);
$prep->execute();

if (!$prep->fetch()) {
    header('Location: /sigsm/views/qr_documentos.php?error=doc_not_found');
    exit();
}

$codToken = bin2hex(random_bytes(16));

$dirTemp = __DIR__ . '/../temp_qr/';

if (!asegurarCarpetaProtegida($dirTemp)) {
    header('Location: /sigsm/views/qr_documentos.php?error=permisos');
    exit();
}

file_put_contents($dirTemp . $codToken, $docId);

header('Location: /sigsm/views/qr_documentos.php?token=' . $codToken);
exit();