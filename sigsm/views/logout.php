<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
cerrarSesion();
header('Location: /sigsm/views/login.php');
exit();