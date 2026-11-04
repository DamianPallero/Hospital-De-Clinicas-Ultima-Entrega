<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Usuario.php';
requerirAutenticacion();

$mensaje = $_GET['msg'] ?? '';
$nomUsu = $_SESSION['user_name'] ?? 'Usuario';
$rolNom = obtenerRol();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - S.I.G.S.M.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mobile.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary shadow-sm flex-shrink-0">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand fw-bold mb-0" href="../index.php">S.I.G.S.M. - Cambiar Contraseña</a>
        <div class="d-flex align-items-center gap-2">
            <span class="text-white small d-none d-sm-inline"><?= e($nomUsu) ?></span>
            <span class="badge bg-light text-primary"><?= e($rolNom) ?></span>
            <a href="logout.php" class="btn btn-danger btn-outline-light btn-sm">Salir</a>
        </div>
    </div>
</nav>
<div class="container py-5" style="max-width: 500px;">
    <?php if ($mensaje === 'ok'): ?><div class="alert alert-success">Contraseña actualizada correctamente.</div>
    <?php elseif ($mensaje === 'error_actual'): ?><div class="alert alert-danger">La contraseña actual es incorrecta.</div>
    <?php elseif ($mensaje === 'error_coincidencia'): ?><div class="alert alert-danger">Las nuevas contraseñas no coinciden.</div>
    <?php elseif ($mensaje === 'error_longitud'): ?><div class="alert alert-danger">La nueva contraseña debe tener al menos 6 caracteres.</div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm p-4">
        <h4 class="fw-bold mb-4">Cambiar mi Contraseña</h4>
        <form action="../controllers/UsuarioController.php" method="POST">
            <input type="hidden" name="accion" value="cambiar_password_propio">
            <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
            <div class="mb-3">
                <label class="form-label small fw-bold">Contraseña Actual</label>
                <input type="password" name="password_actual" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Nueva Contraseña</label>
                <input type="password" name="nueva_password" class="form-control" required minlength="6">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Confirmar Nueva Contraseña</label>
                <input type="password" name="confirmar_password" class="form-control" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary w-100">Actualizar Contraseña</button>
        </form>
    </div>
    <div class="text-center mt-3">
        <a href="../index.php" class="text-muted small">Volver al Portal</a>
    </div>
</div>
</body>
</html>