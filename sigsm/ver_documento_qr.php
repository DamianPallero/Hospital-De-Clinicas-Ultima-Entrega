<?php
require_once __DIR__ . '/config/Database.php';

function detectarMimeArchivo($rutaArchivo) {
    if (!is_string($rutaArchivo) || $rutaArchivo === '') {
        return 'application/octet-stream';
    }

    if (class_exists('finfo')) {
        $infoMime = @finfo_open(FILEINFO_MIME_TYPE);
        if ($infoMime !== false) {
            $tipo = @finfo_file($infoMime, $rutaArchivo);
            @finfo_close($infoMime);
            if (is_string($tipo) && $tipo !== '') {
                return $tipo;
            }
        }
    }

    if (function_exists('mime_content_type')) {
        $tipo = @mime_content_type($rutaArchivo);
        if (is_string($tipo) && $tipo !== '') {
            return $tipo;
        }
    }

    $ext = strtolower(pathinfo($rutaArchivo, PATHINFO_EXTENSION));
    $mapa = [
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];

    return $mapa[$ext] ?? 'application/octet-stream';
}

$codToken = $_GET['token'] ?? '';
$codToken = preg_replace('/[^a-f0-9]/i', '', $codToken);

if (!$codToken) {
    http_response_code(400);
    exit('Token invalido.');
}

$dirTemp = __DIR__ . '/temp_qr/';
$tokenDoc = $dirTemp . $codToken;

function invalidarTokenQr($rutaToken) {
    if (is_file($rutaToken)) {
        @unlink($rutaToken);
    }
}

if (!file_exists($tokenDoc)) {
    http_response_code(410);
    exit('El codigo QR ha sido utilizado o ha expirado.');
}

$docId = (int)file_get_contents($tokenDoc);
invalidarTokenQr($tokenDoc);

if ($docId <= 0) {
    http_response_code(400);
    exit('Documento invalido.');
}

try {
    $bd = (new Database())->getConnection();
    $prep = $bd->prepare('SELECT url_archivo, titulo FROM documento WHERE id_documento = :id AND eliminado = 0');
    $prep->bindParam(':id', $docId, PDO::PARAM_INT);
    $prep->execute();
    $docFila = $prep->fetch(PDO::FETCH_ASSOC);

    if (!$docFila) {
        invalidarTokenQr($tokenDoc);
        http_response_code(404);
        exit('Documento no encontrado.');
    }

    $sello = trim((string)($docFila['url_archivo'] ?? ''));
    $rutaFichero = null;
    $rutaBase = __DIR__ . '/uploads';
    $candidatos = [];

    if ($sello !== '') {
        $rutaSimple = preg_replace('/[^A-Za-z0-9._-]+/', '', basename(parse_url($sello, PHP_URL_PATH) ?: $sello));
        $candidatos[] = $rutaBase . '/' . $sello;
        $candidatos[] = $rutaBase . '/' . $rutaSimple;
        if (preg_match('/^[a-f0-9]{32}$/i', $sello)) {
            $candidatos[] = $rutaBase . '/' . strtolower($sello);
        }
    }

    foreach (array_unique(array_values($candidatos)) as $posibleRuta) {
        if (is_readable($posibleRuta) && !is_dir($posibleRuta)) {
            $rutaFichero = $posibleRuta;
            break;
        }
    }

    if ($rutaFichero === null || !is_readable($rutaFichero)) {
        invalidarTokenQr($tokenDoc);
        http_response_code(404);
        exit('Archivo no encontrado en el servidor.');
    }

    $tipoMime = detectarMimeArchivo($rutaFichero);
    $tituloArchivo = preg_replace('/[\r\n]+/', '', trim((string)$docFila['titulo']));
    $tituloArchivo = preg_replace('/[<>:"\\\/|?*]/', '', $tituloArchivo);
    $tituloArchivo = substr($tituloArchivo, 0, 120) ?: 'documento';

    header('Content-Type: ' . $tipoMime);
    header('Content-Length: ' . filesize($rutaFichero));
    header('Content-Disposition: inline; filename="' . $tituloArchivo . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('X-Content-Type-Options: nosniff');
    
    readfile($rutaFichero);
    exit;

} catch (Exception $err) {
    http_response_code(500);
    exit('Error interno.');
}