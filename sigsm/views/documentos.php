<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Documento.php';
require_once __DIR__ . '/../includes/Cache.php';

requerirAutenticacion();
sinCache();

if (!tienePermiso('ver_documentos')) {
    header('Location: /sigsm/index.php');
    exit();
}

$baseObj = new Database();
$bd = $baseObj->getConnection();
$modDoc = new Documento($bd);
$mensaje = $_GET['msg'] ?? '';
$busqueda = trim((string)($_GET['buscar'] ?? ''));
$filtroEstado = 'activos';
$filtroCategoria = (int)($_GET['categoria'] ?? 0);
$docs = $modDoc->listar($busqueda, $filtroEstado, $filtroCategoria, false);
$cats = $modDoc->listarCategorias();
$permCargar = tienePermiso('cargar_documentos');
$permAdmin = tienePermiso('eliminar_documentos');
$permCat = tienePermiso('gestionar_categorias');
$rolNom = obtenerRol();
$nomUsu = $_SESSION['user_name'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title>Gestión Documental - S.I.G.S.M.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mobile.css">
    <style>
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }

        @media (prefers-contrast: more) {
            .card {
                border: 2px solid currentColor !important;
            }

            .btn {
                border-width: 2px !important;
            }
        }

        :focus-visible {
            outline: 3px solid #124b7d !important;
            outline-offset: 2px !important;
        }

        .table-sm th,
        .table-sm td {
            padding: 0.6rem 0.75rem;
            vertical-align: middle;
        }

        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            font-weight: 600;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
    <nav class="navbar navbar-dark bg-primary shadow-sm flex-shrink-0">
        <div class="container-fluid px-3 px-lg-4">
            <a class="navbar-brand fw-bold mb-0" href="index.php">S.I.G.S.M. - Documentos</a>
            <div class="d-flex align-items-center gap-2">
                <span class="text-white small d-none d-sm-inline"><?= e($nomUsu) ?></span>
                <span class="badge bg-light text-primary"><?= e($rolNom) ?></span>
                <a href="../../index.php" class="btn text-dark btn-sm btn-outline-light btn-info d-inline-flex align-items-center">
                    Volver al portal
                </a>
                <a href="logout.php" class="btn btn-sm btn-outline-light btn-danger text-light d-inline-flex align-items-center justify-content-center">
                    Salir
                </a>
                <a href="cambiar_password.php" class="btn btn-outline-light btn-sm d-inline-flex align-items-center">Cambiar Contraseña</a>
            </div>
        </div>
    </nav>

    <main class="flex-grow-1 d-flex flex-column p-3 p-lg-4">
        <?php if (in_array($mensaje, ['ok', 'eliminado', 'restaurado', 'categoria_creada', 'categoria_editada', 'categoria_eliminada'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm py-2 mb-3 flex-shrink-0">
                <?php
                $avisosExito = [
                    'ok' => 'Documento cargado correctamente.',
                    'eliminado' => 'Documento marcado como eliminado correctamente.',
                    'restaurado' => 'Documento restaurado correctamente.',
                    'categoria_creada' => 'Categoría creada correctamente.',
                    'categoria_editada' => 'Categoría actualizada correctamente.',
                    'categoria_eliminada' => 'Categoría eliminada correctamente.'
                ];
                echo $avisosExito[$mensaje] ?? 'Operación realizada correctamente.';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($mensaje === 'error_categoria_en_uso'): ?>
            <div class="alert alert-warning alert-dismissible fade show shadow-sm py-2 mb-3 flex-shrink-0">
                No se puede eliminar la categoría porque tiene documentos asociados.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif (in_array($mensaje, ['error_archivo', 'error_tamano', 'error_tipo', 'error_guardado', 'error', 'error_validacion'])): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm py-2 mb-3 flex-shrink-0 d-inline-flex align-items-center justify-content-between">
                <?php
                $fallosExtra = [
                    'error_archivo' => 'No se seleccionó ningún archivo o hubo un error al subirlo.',
                    'error_tamano' => 'El archivo supera el tamaño máximo permitido (10MB).',
                    'error_tipo' => 'Formato no permitido. Solo se aceptan PDF, JPG y PNG.',
                    'error_guardado' => 'Error al guardar el archivo en el servidor.',
                    'error_validacion' => 'Faltan datos obligatorios.',
                    'error' => 'Ocurrió un error al procesar la solicitud.'
                ];
                echo $fallosExtra[$mensaje] ?? 'Error desconocido.';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($permCat): ?>
            <div class="card border-0 shadow-sm mb-3 flex-shrink-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Gestión de Categorías</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#crearCategoriaModal">
                        + Nueva Categoría
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Nombre</th>
                                    <th>Documentos</th>
                                    <th class="text-end pe-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($cats)): ?>
                                    <tr><td colspan="3" class="text-center py-3 text-muted">No hay categorías registradas.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($cats as $catFila): ?>
                                        <?php $cant = $modDoc->contarDocumentosPorCategoria($catFila['id_categoria']); ?>
                                        <tr>
                                            <td class="ps-4 fw-medium"><?= e($catFila['nombre']) ?></td>
                                            <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= $cant ?></span></td>
                                            <td class="text-end pe-4">
                                                <button class="btn btn-sm btn-outline-secondary me-1" data-bs-toggle="modal" data-bs-target="#editarCategoriaModal"
                                                    data-id="<?= (int)$catFila['id_categoria'] ?>"
                                                    data-nombre="<?= e($catFila['nombre']) ?>">Editar</button>
                                                <?php if ($cant === 0): ?>
                                                    <form action="../controllers/DocumentoController.php" method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar esta categoría?');">
                                                        <input type="hidden" name="accion" value="eliminar_categoria">
                                                        <input type="hidden" name="id_categoria" value="<?= (int)$catFila['id_categoria'] ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted small">No disponible</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-3 flex-shrink-0 gap-3 flex-wrap">
            <h4 class="fw-bold mb-0">Documentos Registrados</h4>
            <?php if ($permCargar): ?>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#crearModal">
                    + Cargar Documento
                </button>
            <?php endif; ?>
        </div>

        <div class="card border-0 shadow-sm mb-3 flex-shrink-0">
            <div class="card-body">
                <form id="documentFiltersForm" method="GET" class="row g-2 align-items-end" action="documentos.php">
                    <div class="col-md-5 col-lg-6">
                        <label for="buscar_documento" class="form-label small fw-bold mb-1">Buscar documentos</label>
                        <input type="search" id="buscar_documento" name="buscar" class="form-control" value="<?= e($busqueda) ?>" placeholder="Título, categoría o ID" autocomplete="off">
                    </div>
                    <div class="col-md-4">
                        <label for="categoria_documento" class="form-label small fw-bold mb-1">Categoría</label>
                        <select id="categoria_documento" name="categoria" class="form-select">
                            <option value="0" <?= $filtroCategoria === 0 ? 'selected' : '' ?>>Todas</option>
                            <?php foreach ($cats as $catFila): ?>
                                <option value="<?= (int)$catFila['id_categoria'] ?>" <?= $filtroCategoria === (int)$catFila['id_categoria'] ? 'selected' : '' ?>><?= e($catFila['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm flex-grow-1 d-flex flex-column overflow-hidden">
            <div class="card-body p-0 d-flex flex-column flex-grow-1 overflow-hidden">
                <div class="table-responsive flex-grow-1 overflow-auto">
                    <table class="table table-hover align-middle mb-0 table-sm">
                        <thead class="sticky-top">
                            <tr>
                                <th class="ps-4 d-none d-md-table-cell">ID</th>
                                <th>Título</th>
                                <th class="d-none d-lg-table-cell">Categoría</th>
                                <th class="d-none d-lg-table-cell">Fecha</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($docs)): ?>
                                <tr id="documentEmptyState"><td colspan="5" class="text-center py-5 text-muted">No hay documentos que coincidan con la búsqueda y el filtro seleccionados.</td></tr>
                            <?php else: ?>
                                <?php foreach ($docs as $docFila): ?>
                                    <?php $estaEliminado = (int)($docFila['eliminado'] ?? 0) === 1; ?>
                                    <tr class="<?= $estaEliminado ? 'table-secondary' : '' ?>"
                                        data-document-row
                                        data-id="<?= (int)$docFila['id_documento'] ?>"
                                        data-titulo="<?= e($docFila['titulo']) ?>"
                                        data-categoria="<?= e($docFila['categoria'] ?? 'Sin categoría') ?>"
                                        data-categoria-id="<?= (int)($docFila['id_categoria'] ?? 0) ?>"
                                        data-eliminado="<?= $estaEliminado ? '1' : '0' ?>">
                                        <td class="ps-4 fw-medium d-none d-md-table-cell text-muted">#<?= (int)$docFila['id_documento'] ?></td>
                                        <td>
                                            <div class="fw-bold small"><?= e($docFila['titulo']) ?></div>
                                        </td>
                                        <td class="d-none d-lg-table-cell"><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= e($docFila['categoria'] ?? 'Sin categoría') ?></span></td>
                                        <td class="text-muted small d-none d-lg-table-cell"><?= e(date('d/m/Y H:i', strtotime($docFila['fecha_carga']))) ?></td>
                                        <td class="text-end pe-4">
                                            <div class="table-actions d-flex flex-column flex-md-row justify-content-end gap-2">
                                                <a href="../ver_documento.php?id=<?= (int)$docFila['id_documento'] ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center">Ver</a>
                                                <?php if ($permAdmin): ?>
                                                    <?php if (!$estaEliminado): ?>
                                                        <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#editModal"
                                                            data-id="<?= (int)$docFila['id_documento'] ?>"
                                                            data-titulo="<?= e($docFila['titulo']) ?>"
                                                            data-categoria="<?= (int)$docFila['id_categoria'] ?>">Editar</button>
                                                        <form action="../controllers/DocumentoController.php" method="POST" onsubmit="return confirm('¿Está seguro de eliminar este documento?');">
                                                            <input type="hidden" name="accion" value="eliminar">
                                                            <input type="hidden" name="id_documento" value="<?= (int)$docFila['id_documento'] ?>">
                                                            <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center">Eliminar</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form action="../controllers/DocumentoController.php" method="POST" onsubmit="return confirm('¿Desea restaurar este documento?');">
                                                            <input type="hidden" name="accion" value="restaurar">
                                                            <input type="hidden" name="id_documento" value="<?= (int)$docFila['id_documento'] ?>">
                                                            <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-success d-inline-flex align-items-center justify-content-center">Restaurar</button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('documentFiltersForm');
            if (!form) return;

            const buscarInput = document.getElementById('buscar_documento');
            const categoriaSelect = document.getElementById('categoria_documento');
            const rows = Array.from(document.querySelectorAll('[data-document-row]'));
            const emptyState = document.getElementById('documentEmptyState');
            let timer = null;

            const normalize = (value) => (value ?? '').toString().trim().toLowerCase();

            function applyFilters() {
                const params = new URLSearchParams();
                const buscar = normalize(buscarInput?.value ?? '');
                const categoria = categoriaSelect?.value ?? '0';

                if (buscar) params.set('buscar', buscar);
                if (categoria && categoria !== '0') params.set('categoria', categoria);

                const query = params.toString();
                const url = `${window.location.pathname}${query ? `?${query}` : ''}`;
                if (window.location.search !== (query ? `?${query}` : '')) {
                    history.replaceState({}, '', url);
                }

                let visibleRows = 0;
                rows.forEach((row) => {
                    const rowText = `${normalize(row.dataset.titulo)} ${normalize(row.dataset.categoria)} ${normalize(row.dataset.id)}`;
                    const matchesSearch = !buscar || rowText.includes(buscar);
                    const matchesCategoria = categoria === '0' || row.dataset.categoriaId === categoria;
                    const showRow = matchesSearch && matchesCategoria;

                    row.hidden = !showRow;
                    if (showRow) visibleRows += 1;
                });

                if (emptyState) {
                    emptyState.hidden = visibleRows !== 0;
                }
            }

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                applyFilters();
            });

            buscarInput?.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(applyFilters, 250);
            });

            categoriaSelect?.addEventListener('change', applyFilters);

            applyFilters();
        });
    </script>

    <?php if ($permCat): ?>
        <div class="modal fade modal-fullscreen-sm-down" id="crearCategoriaModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="../controllers/DocumentoController.php" method="POST">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title fw-bold">Crear Nueva Categoría</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="accion" value="crear_categoria">
                            <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Nombre de la Categoría</label>
                                <input type="text" name="nombre" class="form-control" required maxlength="100">
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary btn-sm px-4">Crear</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade modal-fullscreen-sm-down" id="editarCategoriaModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="../controllers/DocumentoController.php" method="POST">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title fw-bold">Editar Categoría</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="accion" value="editar_categoria">
                            <input type="hidden" name="id_categoria" id="edit_cat_id">
                            <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Nombre de la Categoría</label>
                                <input type="text" name="nombre" id="edit_cat_nombre" class="form-control" required maxlength="100">
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary btn-sm px-4">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($permCargar): ?>
        <div class="modal fade modal-fullscreen-sm-down" id="crearModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form action="../controllers/DocumentoController.php" method="POST" enctype="multipart/form-data">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title fw-bold">Cargar Nuevo Documento</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="accion" value="crear">
                            <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="titulo" class="form-label small fw-bold">Título</label>
                                    <input type="text" class="form-control" id="titulo" name="titulo" required maxlength="255">
                                </div>
                                <div class="col-12">
                                    <label for="archivo" class="form-label small fw-bold">Archivo (PDF o Imagen)</label>
                                    <div class="border rounded-3 p-4 text-center bg-light" id="dropzone" style="cursor: pointer; border-style: dashed !important;">
                                        <p class="mb-1 fw-bold">Arrastre un archivo aquí o haga clic para seleccionar</p>
                                        <small class="text-muted">Formatos: PDF, JPG, PNG. Máx 10MB.</small>
                                        <input type="file" name="archivo" id="archivo" class="d-none" accept=".pdf,.jpg,.jpeg,.png" required>
                                        <p class="mt-2 mb-0 text-primary fw-bold d-none" id="file-name"></p>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="id_categoria" class="form-label small fw-bold">Categoría</label>
                                    <select class="form-select" id="id_categoria" name="id_categoria" required>
                                        <option value="">Seleccione una categoría</option>
                                        <?php foreach ($cats as $catFila): ?>
                                            <option value="<?= (int)$catFila['id_categoria'] ?>"><?= e($catFila['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary btn-sm px-4">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($permAdmin): ?>
        <div class="modal fade modal-fullscreen-sm-down" id="editModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="../controllers/DocumentoController.php" method="POST">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title fw-bold">Editar Metadatos</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="accion" value="editar">
                            <input type="hidden" name="id_documento" id="edit_id">
                            <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Título</label>
                                <input type="text" name="titulo" id="edit_titulo" class="form-control" required maxlength="255">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Categoría</label>
                                <select class="form-select" id="edit_categoria" name="id_categoria" required>
                                    <?php foreach ($cats as $catFila): ?>
                                        <option value="<?= (int)$catFila['id_categoria'] ?>"><?= e($catFila['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary btn-sm px-4">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            <?php if ($permCat): ?>
                var modalCategoria = document.getElementById('editarCategoriaModal');
                if (modalCategoria) {
                    modalCategoria.addEventListener('show.bs.modal', function (evento) {
                        var boton = evento.relatedTarget;
                        modalCategoria.querySelector('#edit_cat_id').value = boton.getAttribute('data-id');
                        modalCategoria.querySelector('#edit_cat_nombre').value = boton.getAttribute('data-nombre');
                    });
                }
            <?php endif; ?>

            <?php if ($permAdmin): ?>
                var modalEdicion = document.getElementById('editModal');
                if (modalEdicion) {
                    modalEdicion.addEventListener('show.bs.modal', function (evento) {
                        var boton = evento.relatedTarget;
                        modalEdicion.querySelector('#edit_id').value = boton.getAttribute('data-id');
                        modalEdicion.querySelector('#edit_titulo').value = boton.getAttribute('data-titulo');
                        modalEdicion.querySelector('#edit_categoria').value = boton.getAttribute('data-categoria');
                    });
                }
            <?php endif; ?>

            <?php if ($permCargar): ?>
                var zonaDrop = document.getElementById('dropzone');
                var entradaArchivo = document.getElementById('archivo');
                var nombreArchivo = document.getElementById('file-name');

                if (zonaDrop && entradaArchivo) {
                    zonaDrop.addEventListener('click', (evento) => {
                        if (evento.target === zonaDrop || evento.target.tagName === 'P' || evento.target.tagName === 'SMALL') {
                            entradaArchivo.click();
                        }
                    });

                    zonaDrop.addEventListener('dragover', (evento) => {
                        evento.preventDefault();
                        zonaDrop.classList.add('border-primary', 'bg-white');
                    });

                    zonaDrop.addEventListener('dragleave', () => {
                        zonaDrop.classList.remove('border-primary', 'bg-white');
                    });

                    zonaDrop.addEventListener('drop', (evento) => {
                        evento.preventDefault();
                        zonaDrop.classList.remove('border-primary', 'bg-white');
                        if (evento.dataTransfer.files.length > 0) {
                            entradaArchivo.files = evento.dataTransfer.files;
                            updateFileName();
                        }
                    });

                    entradaArchivo.addEventListener('change', updateFileName);

                    function updateFileName() {
                        if (entradaArchivo.files.length > 0) {
                            nombreArchivo.textContent = entradaArchivo.files[0].name;
                            nombreArchivo.classList.remove('d-none');
                        } else {
                            nombreArchivo.classList.add('d-none');
                        }
                    }
                }
            <?php endif; ?>
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
