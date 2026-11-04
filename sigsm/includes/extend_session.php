<?php
require_once __DIR__ . '/auth_middleware.php';
requerirAutenticacion();
$_SESSION['last_activity'] = time();
http_response_code(204);