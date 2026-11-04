<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
$mensaje = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - S.I.G.S.M.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mobile.css">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 500px;">
    <div class="card border-0 shadow-sm p-4">
        <h4 class="fw-bold mb-3 text-primary">Restablecer Contraseña</h4>
        <?php if ($mensaje === 'invalid_code'): ?><div class="alert alert-danger small">Código inválido o expirado.</div>
        <?php elseif ($mensaje === 'error_coincidencia'): ?><div class="alert alert-danger small">Las contraseñas no coinciden.</div>
        <?php endif; ?>
        <form action="../controllers/AuthController.php" method="POST">
            <input type="hidden" name="accion" value="reset_password">
            <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
            <div class="mb-3">
                <label class="form-label small fw-bold">Correo Electrónico</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Código de 6 dígitos</label>
                <input type="text" name="codigo" class="form-control text-center fw-bold" required maxlength="6" pattern="\d{6}">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Nueva Contraseña</label>
                <input type="password" name="nueva_password" class="form-control" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary w-100">Restablecer Contraseña</button>
        </form>
        <div class="text-center mt-3">
            <a href="login.php" class="text-muted small">Volver al Inicio de Sesión</a>
        </div>
    </div>
</div>
</body>
</html>