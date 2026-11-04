<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
$mensaje = $_GET['msg'] ?? '';
unset($_SESSION['reset_code_visible']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - S.I.G.S.M.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mobile.css">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 500px;">
    <div class="card border-0 shadow-sm p-4">
        <h4 class="fw-bold mb-3 text-primary">¿Olvidaste tu contraseña?</h4>
        <?php if ($mensaje === 'requested'): ?>
            <div class="alert alert-info small">
                Se ha generado un código de restablecimiento y ya fue enviado a la administración del sistema.
                <div class="mt-2">Por políticas de seguridad, el código solo puede ser entregado por un administrador autorizado.</div>
            </div>
            <a href="reset_password.php" class="btn btn-primary w-100 d-inline-flex align-items-center justify-content-center">Ya tengo el código, ir a restablecer</a>
        <?php else: ?>
            <p class="text-muted small mb-3">Ingrese su correo electrónico registrado. Por políticas de seguridad, el código de restablecimiento será entregado por el Administrador.</p>
            <form action="../controllers/AuthController.php" method="POST">
                <input type="hidden" name="accion" value="forgot_password">
                <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Correo Electrónico</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Solicitar Código</button>
            </form>
        <?php endif; ?>
        <div class="text-center mt-3">
            <a href="login.php" class="text-muted small">Volver al Inicio de Sesión</a>
        </div>
    </div>
</div>
</body>
</html>