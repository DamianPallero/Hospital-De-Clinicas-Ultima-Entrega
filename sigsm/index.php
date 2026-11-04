<?php
require_once __DIR__ . '/includes/auth_middleware.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Documento.php';
require_once __DIR__ . '/models/Encuesta.php';
require_once __DIR__ . '/includes/Cache.php';

requerirAutenticacion();

cachePrivado(60);

if (obtenerRol() === 'Transporte') {
    header('Location: /sigsm/views/traslados.php');
    exit();
}

$baseObj = new Database();
$bd = $baseObj->getConnection();
$modDoc = new Documento($bd);
$modEncuesta = new Encuesta($bd);

$avisosHoy = tienePermiso('ver_documentos') ? $modDoc->listarNuevosHoy() : [];
$encsActivas = tienePermiso('ver_encuestas') ? $modEncuesta->listarActivas() : [];

$rolNom = obtenerRol();
$nomUsu = $_SESSION['user_name'] ?? 'Usuario';

$archivoVer = __DIR__ . '/config/portal_version.txt';
$ver = file_exists($archivoVer) ? (int)file_get_contents($archivoVer) : 1;
$servidor = $_SERVER['HTTP_HOST'] ?? 'localhost';
$urlEncuesta = "https://" . $servidor . "/sigsm/encuestas/portal.php?v=" . $ver;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <style>
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }
        @media (prefers-contrast: more) {
            .card { border: 2px solid currentColor !important; }
            .btn { border-width: 2px !important; }
        }
        :focus-visible {
            outline: 3px solid #124b7d !important;
            outline-offset: 2px !important;
        }
    </style>
    <title>S.I.G.S.M. - Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
    <a href="#main-content" class="visually-hidden-focusable skip-link position-absolute top-0 start-0 bg-primary text-white p-2 m-2" style="z-index: 1080;">
    Saltar al contenido principal
    </a>
    <nav class="navbar navbar-dark bg-primary shadow-sm flex-shrink-0">
        <div class="container-fluid px-3 px-lg-4">
            <a class="navbar-brand fw-bold mb-0" href="index.php">S.I.G.S.M.</a>
            <div class="d-flex align-items-center gap-2">
                <span class="text-white small d-none d-sm-inline"><?= e($nomUsu) ?></span>
                <span class="badge bg-light text-primary"><?= e($rolNom) ?></span>
                <a href="views/logout.php" class="btn btn-danger btn-outline-light btn-sm">Salir</a>
                <a href="views/cambiar_password.php" class="btn btn-outline-light btn-sm d-inline-flex align-items-center">Cambiar Contraseña</a>
            </div>
        </div>
    </nav>

    <main id="main-content" class="flex-grow-1 d-flex flex-column p-3 p-lg-4">
        <div class="mb-4 flex-shrink-0">
            <h4 class="fw-bold mb-1">Portal principal</h4>
        </div>

        <div class="row g-3 mb-4 flex-shrink-0">
            <?php if (tienePermiso('ver_documentos')): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <span class="badge bg-primary bg-opacity-10 text-primary w-auto mb-3">DOCUMENTOS</span>
                        <h5 class="card-title fw-bold">Gestion Documental</h5>
                        <p class="card-text text-muted small flex-grow-1">
                            Repositorio institucional de documentos clinicos.
                            <?php if (tienePermiso('cargar_documentos')): ?>Permite cargar nuevos documentos. <?php endif; ?>
                            <?php if (tienePermiso('eliminar_documentos')): ?>Permite eliminar documentos existentes.<?php endif; ?>
                        </p>
                        <a href="views/documentos.php" class="btn btn-primary w-100 mt-auto">Abrir repositorio</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (tienePermiso('ver_encuestas')): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <span class="badge bg-success bg-opacity-10 text-success w-auto mb-3">ENCUESTAS</span>
                        <h5 class="card-title fw-bold">Encuestas Activas</h5>
                        <?php if ($encsActivas): ?>
                            <ul class="list-group list-group-flush flex-grow-1 mb-3">
                                <?php foreach ($encsActivas as $encuestaFila): ?>
                                    <li class="list-group-item px-0 small"><?= e($encuestaFila['titulo']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="card-text text-muted small flex-grow-1">No hay encuestas activas en este momento.</p>
                        <?php endif; ?>
                        <?php if (tienePermiso('gestionar_encuestas')): ?>
                            <a href="views/encuestas_admin.php" class="btn text-dark btn-outline-primary w-100 mt-auto">Gestionar Encuestas y QR</a>
                        <?php else: ?>
                            <button class="btn btn-outline-primary w-100 mt-auto" data-bs-toggle="modal" data-bs-target="#qrEncuestaModal">Ver QR de Encuesta</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (tienePermiso('ver_respuestas_encuestas')): ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-purple bg-opacity-10 text-primary w-auto mb-3" style="background-color: rgba(111, 66, 193, 0.1); color: #6f42c1 !important;">RESULTADOS</span>
                            <h5 class="card-title fw-bold">Respuestas de Encuestas</h5>
                            <p class="card-text text-muted small flex-grow-1">
                            Visualice el resumen de respuestas y estadísticas de las encuestas realizadas por los pacientes.
                            </p>
                            <a href="views/ver_respuestas.php" class="btn w-100 mt-auto" style="background-color: #6f42c1; color: white;">Ver Resultados</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (tienePermiso('ver_documentos')): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <span class="badge bg-warning bg-opacity-10 text-warning w-auto mb-3">QR DOCUMENTO</span>
                        <h5 class="card-title fw-bold">QR de Documento</h5>
                        <p class="card-text text-muted small flex-grow-1">
                            Genere un codigo QR de un solo uso para acceso exclusivo a un documento especifico. El codigo se invalida tras el primer escaneo.
                        </p>
                        <a href="views/qr_documentos.php" class="btn text-dark btn-outline-warning w-100 mt-auto">Acceder al generador</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (tienePermiso('gestionar_usuarios')): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <span class="badge bg-dark bg-opacity-10 text-dark w-auto mb-3">USUARIOS</span>
                        <h5 class="card-title fw-bold">Gestión de Personal</h5>
                        <p class="card-text text-muted small flex-grow-1">
                            Administre las credenciales, roles y estados de los usuarios del sistema.
                        </p>
                        <a href="views/usuarios.php" class="btn btn-dark w-100 mt-auto">Acceder al Módulo</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (tienePermiso('ver_traslados')): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                    <span class="badge bg-info bg-opacity-10 text-info w-auto mb-3">TRASLADOS</span>
                    <h5 class="card-title fw-bold">Gestión de Traslados</h5>
                    <p class="card-text text-muted small flex-grow-1">
                        Visualice, solicite y gestione los traslados de pacientes y materiales del hospital.
                    </p>
                    <a href="views/traslados.php" class="btn text-dark btn-info text-white w-100 mt-auto">Acceder al Módulo</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="card border-0 shadow-sm flex-grow-1 d-flex flex-column">
            <div class="card-header bg-white fw-bold border-bottom flex-shrink-0">
                <div class="d-flex justify-content-between align-items-center">
                    <span>Actividad del dia</span>
                    <span class="badge bg-primary rounded-pill"><?= count($avisosHoy) ?></span>
                </div>
            </div>
            <div class="card-body overflow-auto flex-grow-1 p-0">
                <?php if (!tienePermiso('ver_documentos')): ?>
                    <div class="d-flex align-items-center justify-content-center h-100">
                        <p class="text-muted mb-0">Sin acceso a registros.</p>
                    </div>
                <?php elseif (empty($avisosHoy)): ?>
                    <div class="d-flex align-items-center justify-content-center h-100">
                        <p class="text-muted mb-0">Sin actividad registrada hoy.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($avisosHoy as $aviso): ?>
                            <a href="ver_documento.php?id=<?= (int)$aviso['id_documento'] ?>" target="_blank" class="list-group-item list-group-item-action py-3 px-4">
                                <div class="d-flex w-100 justify-content-between align-items-start">
                                    <div class="me-3">
                                        <span class="badge bg-success bg-opacity-10 text-success mb-1">Nuevo</span>
                                        <h6 class="mb-1 fw-bold"><?= e($aviso['titulo']) ?></h6>
                                        <p class="mb-0 small text-muted">
                                            <?= e($aviso['categoria'] ?? 'Sin categoria') ?> · Cargado por <?= e($aviso['usuario_nombre'] . ' ' . $aviso['usuario_apellido']) ?>
                                        </p>
                                    </div>
                                    <small class="text-muted text-nowrap"><?= e(date('H:i', strtotime($aviso['fecha_carga']))) ?></small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div class="modal fade" id="qrEncuestaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">QR de Encuesta para Impresion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-center">
                    <p class="text-muted small mb-3">Escanee para acceder al portal de encuestas. Version: <strong><?= $ver ?></strong></p>
                    <div class="border rounded p-3 d-inline-block bg-white mb-3">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= urlencode($urlEncuesta) ?>" alt="QR Encuesta" class="img-fluid">
                    </div>
                    <p class="small text-muted mb-3">Escanee para acceder a la encuesta de satisfaccion.</p>
                    <button class="btn btn-dark btn-sm" onclick="imprimirQRIndex()">Imprimir</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function imprimirQRIndex() {
        var imagenQr = document.querySelector('#qrEncuestaModal img');
        var ventanaImp = window.open('', '', 'height=400,width=400');
        ventanaImp.document.write('<html><head><title>QR Encuesta</title></head><body style="text-align:center; margin:0; padding:20px; font-family:sans-serif;">');
        ventanaImp.document.write('<img src="' + imagenQr.src + '" style="width:250px;height:250px;">');
        ventanaImp.document.write('<p style="font-size:14px; margin-top:15px; color:#333;">Escanee para acceder a la encuesta de satisfaccion.</p>');
        ventanaImp.document.write('</body></html>');
        ventanaImp.document.close();
        ventanaImp.focus();
        setTimeout(function() {
            ventanaImp.print();
            ventanaImp.close();
        }, 500);
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/session-warning.js"></script>
</body>
</html>