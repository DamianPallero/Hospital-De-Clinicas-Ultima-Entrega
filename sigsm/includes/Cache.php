<?php
function cachePublico(int $segs = 60): void {
    header('Cache-Control: public, max-age=' . $segs);
    header('Vary: Cookie');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $segs) . ' GMT');
}

function cachePrivado(int $segs = 0): void {
    if ($segs <= 0) {
        header('Cache-Control: private, no-cache, must-revalidate');
        header('Expires: 0');
    } else {
        header('Cache-Control: private, max-age=' . $segs);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $segs) . ' GMT');
    }
}

function sinCache(): void {
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}