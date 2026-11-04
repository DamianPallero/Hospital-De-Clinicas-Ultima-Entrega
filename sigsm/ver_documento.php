<?php
require_once __DIR__ . '/includes/auth_middleware.php';
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

requerirAutenticacion();

if (!tienePermiso('ver_documentos')) {
    http_response_code(403);
    exit('Acceso denegado.');
}

$clave = (int)($_GET['id'] ?? 0);
if ($clave <= 0) {
    http_response_code(400);
    exit('ID inválido.');
}

$permiteVerEliminados = puedeVerDocumentosEliminados();

try {
    $bd = (new Database())->getConnection();
    $sql = 'SELECT url_archivo, titulo, eliminado FROM documento WHERE id_documento = :id';
    if (!$permiteVerEliminados) {
        $sql .= ' AND eliminado = 0';
    }
    $prep = $bd->prepare($sql);
    $prep->bindParam(':id', $clave, PDO::PARAM_INT);
    $prep->execute();
    $docFila = $prep->fetch(PDO::FETCH_ASSOC);

    if (!$docFila) {
        http_response_code(404);
        exit('Documento no encontrado.');
    }

    if (!$permiteVerEliminados && (int)($docFila['eliminado'] ?? 0) === 1) {
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
        http_response_code(404);
        exit('Archivo físico no encontrado en el servidor.');
    }

    $tipoMime = detectarMimeArchivo($rutaFichero);
    $tituloArchivo = preg_replace('/[\r\n]+/', '', trim((string)$docFila['titulo']));
    $tituloArchivo = preg_replace('/[<>:"\\\/|?*]/', '', $tituloArchivo);
    $tituloArchivo = substr($tituloArchivo, 0, 120) ?: 'documento';

    header('Content-Type: ' . $tipoMime);
    header('Content-Length: ' . filesize($rutaFichero));
    header('Content-Disposition: inline; filename="' . $tituloArchivo . '"');
    header('Cache-Control: private, max-age=3600');
    header('X-Content-Type-Options: nosniff');
    
    readfile($rutaFichero);
    exit;

} catch (Exception $err) {
    http_response_code(500);
    exit('Error interno al procesar el documento.');
}