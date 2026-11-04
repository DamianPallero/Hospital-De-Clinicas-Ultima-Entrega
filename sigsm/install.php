<?php
require_once __DIR__ . '/includes/Security.php';

$dirs = [
    __DIR__ . '/uploads',
    __DIR__ . '/temp_qr',
    __DIR__ . '/config'
];

$fallos = [];

foreach ($dirs as $dir) {
    if (!asegurarCarpetaProtegida($dir)) {
        $fallos[] = "No se pudo asegurar: $dir";
    }
}

if (empty($fallos)) {
    echo "<h2>Instalación completada</h2>";
    echo "<p>Todas las carpetas críticas han sido aseguradas correctamente.</p>";
    echo "<p><a href='index.php'>Ir al portal</a></p>";
} else {
    echo "<h2>Errores en la instalación</h2>";
    echo "<ul>";
    foreach ($fallos as $fallo) {
        echo "<li>$fallo</li>";
    }
    echo "</ul>";
    echo "<p>Verifique los permisos de escritura del servidor web.</p>";
}