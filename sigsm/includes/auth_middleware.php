<?php
if (!function_exists('esConexionSegura')) {
    function esConexionSegura(): bool {
        $protocolo = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? $_SERVER['HTTPS'] ?? '');
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $sslForwarded = !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on';
        return $https || $protocolo === 'https' || $sslForwarded || (($_SERVER['SERVER_PORT'] ?? 80) == 443);
    }
}

if (!function_exists('e')) {
    function e($datoV) {
        return htmlspecialchars($datoV ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('aplicarHeadersSeguridad')) {
    function aplicarHeadersSeguridad(): void {
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
            header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; connect-src 'self'; frame-ancestors 'none'; object-src 'none'; base-uri 'self';");
            if (esConexionSegura()) {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            }
        }
    }
}

if (!function_exists('forzarHTTPS')) {
    function forzarHTTPS(): void {
        if (headers_sent()) {
            return;
        }

        $forzar = getenv('APP_FORCE_HTTPS');
        $forzar = $forzar !== false ? filter_var($forzar, FILTER_VALIDATE_BOOLEAN) : false;

        if (!$forzar) {
            return;
        }

        $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
        $hostSinPuerto = preg_replace('/:\d+$/', '', $host);

        if ($host === '' || in_array($host, ['localhost', '127.0.0.1', '[::1]'], true) || filter_var($hostSinPuerto, FILTER_VALIDATE_IP) !== false) {
            return;
        }

        if (!esConexionSegura()) {
            $ruta = $_SERVER['REQUEST_URI'] ?? '/';
            header('Location: https://' . $hostSinPuerto . $ruta, true, 301);
            exit();
        }
    }
}

if (!function_exists('iniciarSesionSegura')) {
    function iniciarSesionSegura(): void {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.cache_limiter', 'nocache');

            $seguro = esConexionSegura();
            $optsCookie = session_get_cookie_params();
            session_set_cookie_params([
                'lifetime' => 1800,
                'path' => $optsCookie['path'] ?? '/',
                'domain' => $optsCookie['domain'] ?? '',
                'secure' => $seguro,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_start();
        }
    }
}

aplicarHeadersSeguridad();
if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'cli-server') {
    forzarHTTPS();
}

if (session_status() === PHP_SESSION_NONE) {
    iniciarSesionSegura();
}

function generarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validarTokenCSRF($codToken) {
    return !empty($codToken) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $codToken);
}

function obtenerPermisosPorRol($rolNom) {
    $rolNom = (string)$rolNom;
    $mapa = [
        'Administrador General' => [
            'ver_documentos', 'cargar_documentos', 'eliminar_documentos',
            'ver_encuestas', 'gestionar_encuestas', 'ver_respuestas_encuestas',
            'ver_traslados', 'crear_traslado', 'actualizar_estado_traslado', 'asignar_traslado',
            'ver_vehiculos', 'gestionar_vehiculos', 'gestionar_personal_transporte',
            'gestionar_usuarios',
            'gestionar_categorias',
            'gestionar_backups',
        ],
        'Enfermería' => [
            'ver_documentos', 'cargar_documentos',
            'ver_encuestas',
            'ver_traslados', 'crear_traslado', 'ver_vehiculos',
        ],
        'Médico' => [
            'ver_documentos', 'cargar_documentos',
            'ver_encuestas',
            'ver_traslados', 'crear_traslado', 'ver_vehiculos',
        ],
        'IT' => [
            'ver_documentos',
            'ver_encuestas', 'gestionar_encuestas', 'ver_respuestas_encuestas', 'gestionar_categorias',
            'ver_traslados', 'actualizar_estado_traslado', 'ver_vehiculos', 'gestionar_vehiculos', 'gestionar_personal_transporte',
            'gestionar_backups',
        ],
        'Transporte' => [
            'ver_traslados', 'actualizar_estado_traslado', 'ver_vehiculos', 'gestionar_vehiculos',
        ],
    ];
    return $mapa[$rolNom] ?? [];
}

function tienePermiso($permiso) {
    if (empty($_SESSION['role_name'])) {
        return false;
    }
    $permisos = obtenerPermisosPorRol($_SESSION['role_name']);
    return in_array($permiso, $permisos, true);
}

function obtenerRol() {
    return $_SESSION['role_name'] ?? 'Invitado';
}

if (!function_exists('puedeGestionarBackups')) {
    function puedeGestionarBackups(): bool {
        $rol = obtenerRol();
        return in_array($rol, ['Administrador General', 'IT'], true);
    }
}

if (!function_exists('puedeVerDocumentosEliminados')) {
    function puedeVerDocumentosEliminados(): bool {
        $rol = obtenerRol();
        return in_array($rol, ['Administrador General', 'IT'], true);
    }
}

if (!function_exists('obtenerIntentosLogin')) {
    function obtenerIntentosLogin(): array {
        $ip = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $clave = 'login_attempts_' . $ip;
        $datos = $_SESSION[$clave] ?? ['count' => 0, 'locked_until' => 0];

        if (!is_array($datos)) {
            $datos = ['count' => 0, 'locked_until' => 0];
        }

        $ahora = time();
        if (($datos['locked_until'] ?? 0) > 0 && $datos['locked_until'] <= $ahora) {
            $datos = ['count' => 0, 'locked_until' => 0];
            $_SESSION[$clave] = $datos;
        }

        return $datos;
    }
}

if (!function_exists('registrarFalloLogin')) {
    function registrarFalloLogin(): void {
        $ip = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $clave = 'login_attempts_' . $ip;
        $datos = obtenerIntentosLogin();
        $datos['count'] = (int)($datos['count'] ?? 0) + 1;

        if ($datos['count'] >= 5) {
            $datos['locked_until'] = time() + 900;
            $datos['count'] = 5;
        }

        $_SESSION[$clave] = $datos;
    }
}

if (!function_exists('loginBloqueado')) {
    function loginBloqueado(): bool {
        $datos = obtenerIntentosLogin();
        return ($datos['locked_until'] ?? 0) > time();
    }
}

if (!function_exists('reiniciarIntentosLogin')) {
    function reiniciarIntentosLogin(): void {
        $ip = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $clave = 'login_attempts_' . $ip;
        unset($_SESSION[$clave]);
    }
}

if (!function_exists('esAutenticado')) {
    function esAutenticado(): bool {
        iniciarSesionSegura();
        return !empty($_SESSION['authenticated']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('cerrarSesion')) {
    function cerrarSesion(): void {
        iniciarSesionSegura();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $parametros = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $parametros['path'], $parametros['domain'], $parametros['secure'], $parametros['httponly']);
        }
        session_destroy();
    }
}

if (!function_exists('requerirAutenticacion')) {
    function requerirAutenticacion(): void {
        iniciarSesionSegura();
        if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
            header('Location: /sigsm/views/login.php');
            exit();
        }
        $limite = 1800;
        $ult = $_SESSION['last_activity'] ?? null;
        $ahora = time();
        if ($ult === null || ($ahora - (int)$ult) > $limite) {
            cerrarSesion();
            header('Location: /sigsm/views/login.php?expired=1');
            exit();
        }
        $_SESSION['last_activity'] = $ahora;
    }
}