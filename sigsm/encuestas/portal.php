<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Encuesta.php';

$ver = isset($_GET['v']) ? (int)$_GET['v'] : 1;
$claveSesion = 'survey_completed_v' . $ver;

if (isset($_SESSION[$claveSesion])) {
    unset($_SESSION[$claveSesion]);
}

$bd = (new Database())->getConnection();
$modEncuesta = new Encuesta($bd);
$encs = $modEncuesta->listarActivas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encuestas de Satisfacción - Hospital de Clínicas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mobile.css">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="text-center mb-4">
            <h1 class="h3 fw-bold text-primary">Hospital de Clínicas</h1>
            <p class="text-muted">Su opinión nos ayuda a mejorar.</p>
        </div>
        
        <p class="text-center text-muted mb-4">Seleccione el servicio que recibió recientemente:</p>
        <div class="row g-3">
            <?php if (empty($encs)): ?>
                <div class="col-12">
                    <div class="alert alert-warning text-center">No hay encuestas disponibles en este momento.</div>
                </div>
            <?php else: ?>
                <?php foreach ($encs as $encFila): ?>
                    <div class="col-12 col-sm-6">
                        <a href="tomar.php?id=<?= (int)$encFila['id_encuesta'] ?>&v=<?= $ver ?>" class="text-decoration-none">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body text-center p-4">
                                    <h5 class="card-title fw-bold text-dark"><?= htmlspecialchars($encFila['titulo']) ?></h5>
                                    <span class="btn btn-primary mt-2 d-inline-flex align-items-center">Iniciar Encuesta</span>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>