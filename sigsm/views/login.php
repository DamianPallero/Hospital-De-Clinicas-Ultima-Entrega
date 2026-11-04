<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/Captcha.php';
require_once __DIR__ . '/../includes/Cache.php';
iniciarSesionSegura();
sinCache();

if (esAutenticado()) {
    $limite = 1800;
    $ult = $_SESSION['last_activity'] ?? null;
    $ahora = time();
    if ($ult === null) {
        $_SESSION['last_activity'] = $ahora;
        header('Location: /sigsm/index.php');
        exit();
    }
    if (($ahora - (int)$ult) <= $limite) {
        $_SESSION['last_activity'] = $ahora;
        header('Location: /sigsm/index.php');
        exit();
    } else {
        cerrarSesion();
        $_GET['expired'] = 1;
    }
}

$errorLogin = $_SESSION['login_error'] ?? null;
$exitoLogin = $_GET['reset'] ?? null;
unset($_SESSION['login_error']);

if (empty($errorLogin) && !empty($_GET['expired'])) {
    $errorLogin = 'Su sesión ha expirado por inactividad. Por favor inicie sesión de nuevo.';
}

if (empty($errorLogin)) {
    $errorLogin = '';
}

$pregCaptcha = Captcha::generar();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>S.I.G.S.M. - Acceso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mobile.css">
    <style>
        :root {
            --primary: #0d6efd;
            --primary-dark: #0b5ed7;
            --text: #1f2937;
            --muted: #6c757d;
            --line: #dfe9f4;
            --surface: #ffffff;
            --surface-soft: #f8f9fa;
        }
        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            margin: 0;
            padding: 1rem;
            color: var(--text);
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: linear-gradient(180deg, var(--surface-soft), #f4f7fb);
        }
        .auth-card {
            width: min(100%, 420px);
            padding: 2.5rem 2rem;
            border: 1px solid var(--line);
            border-radius: 20px;
            background: var(--surface);
            box-shadow: 0 10px 30px rgba(17, 31, 49, .06);
        }
        .brand {
            margin-bottom: 2rem;
            text-align: center;
        }
        .brand h1 {
            margin-bottom: .25rem;
            color: var(--primary);
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .brand p {
            color: var(--muted);
            font-size: 0.9rem;
            margin: 0;
        }
        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text);
            margin-bottom: 0.4rem;
        }
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border-color: var(--line);
            background-color: var(--surface);
            color: var(--text);
            font-size: 0.95rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            -webkit-text-fill-color: var(--text);
        }
        .form-control::placeholder {
            color: var(--muted);
            opacity: 1;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, .12);
            color: var(--text);
        }
        
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0px 1000px #fff inset;
            -webkit-text-fill-color: var(--text);
            transition: background-color 5000s ease-in-out 0s;
        }

        .btn-primary {
            width: 100%;
            padding: 0.8rem;
            border: 0;
            border-radius: 10px;
            background: var(--primary);
            font-weight: 600;
            font-size: 1rem;
            margin-top: 0.5rem;
        }
        .btn-primary:hover,
        .btn-primary:focus,
        .btn-primary:active {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        .error-text {
            display: block;
            min-height: 16px;
            color: #b42318;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        .input-error {
            border-color: #d9534f !important;
            background: #fff8f8 !important;
        }
        
        .captcha-box {
            background: #f8fafc;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 1.25rem;
            text-align: center;
            margin: 1.5rem 0;
        }
        .captcha-question {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        .captcha-input-wrapper {
            max-width: 140px;
            margin: 0 auto;
        }

        .accessibility-note {
            margin: 1.5rem 0 0;
            text-align: center;
            font-size: 0.75rem;
            color: var(--muted);
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }
        :focus-visible {
            outline: 3px solid var(--primary) !important;
            outline-offset: 2px !important;
        }
        .form-check-input:focus {
            box-shadow: none;
        }
        .form-check-input:focus-visible {
            outline: 2px solid var(--primary) !important;
            outline-offset: -2px !important;
        }
    </style>
</head>
<body>
    <main class="auth-card">
        <div class="brand">
            <h1>Hospital de Clínicas</h1>
            <p>S.I.G.S.M. · Acceso del personal</p>
        </div>
        
        <?php if ($errorLogin): ?>
            <div class="alert alert-danger py-2 small" role="alert">
                <?= e($errorLogin) ?>
            </div>
        <?php endif; ?>

        <?php if ($exitoLogin): ?>
            <div class="alert alert-success py-2 small" role="alert">
                Contraseña restablecida correctamente. Por favor, inicie sesión.
            </div>
        <?php endif; ?>
        
        <form id="loginForm" action="/sigsm/controllers/AuthController.php" method="POST" novalidate>
            <input type="hidden" name="accion" value="login">
            <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
            
            <div class="mb-3">
                <label class="form-label" for="email">Correo electrónico</label>
                <input
                    class="form-control"
                    type="email"
                    id="email"
                    name="email"
                    autocomplete="email"
                    aria-describedby="emailHelp"
                    required
                >
                <small id="emailHelp" class="error-text" aria-live="polite"></small>
            </div>
            
            <div class="mb-3">
                <label class="form-label" for="password">Contraseña</label>
                <input
                    class="form-control"
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    aria-describedby="passHelp"
                    required
                >
                <small id="passHelp" class="error-text" aria-live="polite"></small>
            </div>
            
            <div class="captcha-box">
                <div class="captcha-question">
                    ¿Cuánto es <?= e($pregCaptcha) ?>?
                </div>
                <div class="captcha-input-wrapper">
                    <input
                        class="form-control text-center"
                        type="number"
                        id="captcha"
                        name="captcha"
                        placeholder="Respuesta"
                        aria-label="Respuesta de la operación matemática"
                        required
                        min="-99"
                        max="99"
                    >
                </div>
                <small id="captchaHelp" class="error-text mt-2" aria-live="polite"></small>
            </div>
            
            <button class="btn btn-primary" type="submit">
                Ingresar
            </button>

            <div class="text-center mt-3">
                <a href="forgot_password.php" class="text-muted small text-decoration-none">¿Olvidaste tu contraseña?</a>
            </div>
        </form>
        
        <p class="accessibility-note">
            Acceso restringido al personal autorizado.
        </p>
    </main>
    
    <script src="../assets/js/validaciones.js"></script>
</body>
</html>