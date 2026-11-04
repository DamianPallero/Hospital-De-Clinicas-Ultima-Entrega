<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Documento.php';

requerirAutenticacion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /sigsm/views/documentos.php');
    exit();
}

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

$op = $_POST['accion'] ?? '';
$tokenForm = $_POST['csrf_token'] ?? '';

if (!validarTokenCSRF($tokenForm)) {
    header('Location: /sigsm/views/documentos.php?msg=error');
    exit();
}

$baseObj = new Database();
$bd = $baseObj->getConnection();
$modDoc = new Documento($bd);

if ($op === 'crear') {
    if (!tienePermiso('cargar_documentos')) die("Acceso denegado.");
    
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        header('Location: /sigsm/views/documentos.php?msg=error_archivo');
        exit();
    }
    
    $fichero = $_FILES['archivo'];
    $maxBytes = 10 * 1024 * 1024;
    
    if ($fichero['size'] > $maxBytes) {
        header('Location: /sigsm/views/documentos.php?msg=error_tamano');
        exit();
    }
    
    $tipoMime = detectarMimeArchivo($fichero['tmp_name']);
    $mimes = ['application/pdf', 'image/jpeg', 'image/png'];
    
    if (!in_array(strtolower($tipoMime), $mimes, true)) {
        header('Location: /sigsm/views/documentos.php?msg=error_tipo');
        exit();
    }
    
    $sello = bin2hex(random_bytes(16));
    $dirCarga = __DIR__ . '/../uploads/';
    
    if (!is_dir($dirCarga)) {
        mkdir($dirCarga, 0755, true);
    }
    
    $rutaFin = $dirCarga . $sello;
    
    if (!move_uploaded_file($fichero['tmp_name'], $rutaFin)) {
        header('Location: /sigsm/views/documentos.php?msg=error_guardado');
        exit();
    }
    
    $modDoc->catId = (int)($_POST['id_categoria'] ?? 0);
    $modDoc->cargaId = $_SESSION['user_id'];
    $modDoc->tituloDoc = trim($_POST['titulo'] ?? '');
    $modDoc->urlArchivo = $sello;
    
    if ($modDoc->tituloDoc !== '' && $modDoc->catId > 0) {
        if (!$modDoc->crear()) {
            unlink($rutaFin);
            $mensaje = 'error';
        } else {
            $mensaje = 'ok';
        }
    } else {
        unlink($rutaFin);
        $mensaje = 'error_validacion';
    }
    
    header('Location: /sigsm/views/documentos.php?msg=' . $mensaje);
    exit();
}

if ($op === 'editar') {
    if (!tienePermiso('eliminar_documentos')) die("Acceso denegado.");
    
    $modDoc->docId = (int)($_POST['id_documento'] ?? 0);
    $modDoc->catId = (int)($_POST['id_categoria'] ?? 0);
    $modDoc->tituloDoc = trim($_POST['titulo'] ?? '');
    
    if ($modDoc->docId > 0 && $modDoc->tituloDoc !== '') {
        $modDoc->editar() ? $mensaje = 'ok' : $mensaje = 'error';
    } else {
        $mensaje = 'error_validacion';
    }
    
    header('Location: /sigsm/views/documentos.php?msg=' . $mensaje);
    exit();
}

if ($op === 'eliminar') {
    if (!tienePermiso('eliminar_documentos')) die("Acceso denegado.");
    
    $clave = (int)($_POST['id_documento'] ?? 0);
    $modDoc->docId = $clave;
    
    if ($clave > 0 && $modDoc->eliminar()) {
        $mensaje = 'eliminado';
    } else {
        $mensaje = 'error';
    }
    
    header('Location: /sigsm/views/documentos.php?msg=' . $mensaje);
    exit();
}

if ($op === 'restaurar') {
    if (!tienePermiso('eliminar_documentos')) die("Acceso denegado.");

    $clave = (int)($_POST['id_documento'] ?? 0);
    $modDoc->docId = $clave;

    if ($clave > 0 && $modDoc->restaurar()) {
        $mensaje = 'restaurado';
    } else {
        $mensaje = 'error';
    }

    header('Location: /sigsm/views/documentos.php?msg=' . $mensaje);
    exit();
}

if ($op === 'crear_categoria') {
    if (!tienePermiso('gestionar_categorias')) die("Acceso denegado.");
    
    $nom = trim($_POST['nombre'] ?? '');
    
    if ($nom !== '') {
        $modDoc->crearCategoria($nom) ? $mensaje = 'categoria_creada' : $mensaje = 'error';
    } else {
        $mensaje = 'error_validacion';
    }
    
    header('Location: /sigsm/views/documentos.php?msg=' . $mensaje);
    exit();
}

if ($op === 'editar_categoria') {
    if (!tienePermiso('gestionar_categorias')) die("Acceso denegado.");
    
    $catId = (int)($_POST['id_categoria'] ?? 0);
    $nom = trim($_POST['nombre'] ?? '');
    
    if ($catId > 0 && $nom !== '') {
        $modDoc->editarCategoria($catId, $nom) ? $mensaje = 'categoria_editada' : $mensaje = 'error';
    } else {
        $mensaje = 'error_validacion';
    }
    
    header('Location: /sigsm/views/documentos.php?msg=' . $mensaje);
    exit();
}

if ($op === 'eliminar_categoria') {
    if (!tienePermiso('gestionar_categorias')) die("Acceso denegado.");
    
    $catId = (int)($_POST['id_categoria'] ?? 0);
    
    if ($catId > 0) {
        $docsAsoc = $modDoc->contarDocumentosPorCategoria($catId);
        
        if ($docsAsoc > 0) {
            $mensaje = 'error_categoria_en_uso';
        } else {
            $modDoc->eliminarCategoria($catId) ? $mensaje = 'categoria_eliminada' : $mensaje = 'error';
        }
    } else {
        $mensaje = 'error_validacion';
    }
    
    header('Location: /sigsm/views/documentos.php?msg=' . $mensaje);
    exit();
}

header('Location: /sigsm/views/documentos.php');
exit();