<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Documento.php';
requerirAutenticacion();
if (!tienePermiso('ver_documentos')) {
    header('Location: /sigsm/index.php');
    exit();
}
$baseObj = new Database();
$bd = $baseObj->getConnection();
$modDoc = new Documento($bd);
$docs = $modDoc->listar();
$tokenNuevo = $_GET['token'] ?? null;
$urlQr = null;
$local = false;

if ($tokenNuevo) {
    $tokenNuevo = preg_replace('/[^a-f0-9]/i', '', $tokenNuevo);
    if ($tokenNuevo) {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $servidor = $_SERVER['HTTP_HOST'] ?? 'localhost';
        if (strpos($servidor, 'localhost') !== false || strpos($servidor, '127.0.0.1') !== false) {
            $local = true;
        }
        $urlQr = $proto . "://" . $servidor . "/sigsm/ver_documento_qr.php?token=" . $tokenNuevo;
    }
}

$rolNom = obtenerRol();
$nomUsu = $_SESSION['user_name'] ?? 'Usuario';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title>Generación de QR - S.I.G.S.M.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mobile.css">
    <style>
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }
        @media (prefers-contrast: more) {
            .card { border: 2px solid currentColor !important; }
            .btn { border-width: 2px !important; }
        }
        :focus-visible { outline: 3px solid #124b7d !important; outline-offset: 2px !important; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

<nav class="navbar navbar-dark bg-primary shadow-sm flex-shrink-0">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand fw-bold mb-0" href="index.php">S.I.G.S.M. - Administración de QR</a>
        <div class="d-flex align-items-center gap-2">
            <span class="text-white small d-none d-sm-inline"><?= e($nomUsu) ?></span>
            <span class="badge bg-light text-primary"><?= e($rolNom) ?></span>
            <a href="../index.php" class="btn text-dark btn-info btn-outline-light btn-sm d-inline-flex align-items-center">Volver al Portal</a>
            <a href="logout.php" class="btn btn-danger btn-outline-light btn-sm d-inline-flex align-items-center">Salir</a>
            <a href="cambiar_password.php" class="btn btn-outline-light btn-sm d-inline-flex align-items-center">Cambiar Contraseña</a>
        </div>
    </div>
</nav>

<main class="flex-grow-1 d-flex flex-column p-3 p-lg-4">
    <div class="mx-auto w-100" style="max-width: 1200px;">
        
        <?php if ($urlQr): ?>
            <?php if ($local): ?>
                <div class="alert alert-warning shadow-sm mb-3 flex-shrink-0">
                    <strong>Atención:</strong> Generaste este QR accediendo desde <code>localhost</code>. Tu teléfono no podrá abrir este enlace porque "localhost" en tu teléfono se refiere al propio teléfono. 
                    <br><br>
                    Para que funcione, debes acceder a esta página usando la IP local de tu computadora (ej. <code>https://192.168.1.50/sigsm/views/qr_documentos.php</code>) y generar el QR nuevamente.
                </div>
            <?php endif; ?>
            
            <div class="card border-0 shadow-sm mb-4 flex-shrink-0">
                <div class="card-body text-center">
                    <h5 class="card-title fw-bold">Código QR Generado</h5>
                    <p class="text-muted small mb-3">Este código es de un solo uso. Se invalidará automáticamente tras el primer escaneo.</p>
                    <div class="border rounded p-3 d-inline-block bg-white mb-3">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($urlQr) ?>" alt="QR Code" class="img-fluid">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Enlace directo:</label>
                        <input type="text" class="form-control text-center" value="<?= e($urlQr) ?>" readonly onclick="this.select();">
                    </div>
                    <p class="small text-muted mb-0">
                        Host detectado: <strong><?= e($servidor) ?></strong>.
                        Asegúrese de que el teléfono esté conectado a la misma red Wi-Fi.
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm mb-4 flex-shrink-0">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3">Seleccionar Documento</h5>
                <form action="../controllers/QRController.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-8">
                            <label for="id_documento" class="form-label small fw-bold">Documento</label>
                            <select class="form-select" id="id_documento" name="id_documento" required>
                                <option value="" selected disabled>Seleccione un documento...</option>
                                <?php foreach ($docs as $docFila): ?>
                                    <option value="<?= (int)$docFila['id_documento'] ?>">
                                        <?= e($docFila['titulo']) ?> (<?= e($docFila['categoria'] ?? 'Sin categoría') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <button type="submit" class="btn btn-primary w-100">Generar QR</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm flex-grow-1 d-flex flex-column overflow-hidden">
            <div class="card-header bg-white py-3 fw-bold d-flex justify-content-between align-items-center">
                <span>Documentos Disponibles</span>
                <span class="badge bg-primary rounded-pill"><?= count($docs) ?></span>
            </div>
            <div class="card-body p-0 d-flex flex-column flex-grow-1 overflow-hidden">
                <div class="table-responsive flex-grow-1 overflow-auto">
                    <table class="table table-hover align-middle mb-0 table-sm">
                        <thead class="sticky-top bg-light">
                            <tr>
                                <th class="ps-4">Título</th>
                                <th class="d-none d-md-table-cell">Categoría</th>
                                <th class="d-none d-md-table-cell">Fecha de Carga</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($docs)): ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted">No hay documentos disponibles.</td></tr>
                            <?php else: ?>
                                <?php foreach ($docs as $docFila): ?>
                                    <tr>
                                        <td class="ps-4 fw-medium small"><?= e($docFila['titulo']) ?></td>
                                        <td class="d-none d-md-table-cell"><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= e($docFila['categoria'] ?? 'Sin categoría') ?></span></td>
                                        <td class="text-muted small d-none d-md-table-cell"><?= e(date('d/m/Y H:i', strtotime($docFila['fecha_carga']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>