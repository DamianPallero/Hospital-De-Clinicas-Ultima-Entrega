<?php
function asegurarCarpetaProtegida($rutaDir) {
    if (!is_dir($rutaDir)) {
        if (!mkdir($rutaDir, 0755, true)) {
            return false;
        }
    }
    
    $rutaHt = $rutaDir . '/.htaccess';
    if (!file_exists($rutaHt)) {
        $texto = '<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Order deny,allow
    Deny from all
</IfModule>';
        
        if (file_put_contents($rutaHt, $texto) === false) {
            return false;
        }
    }
    
    return true;
}