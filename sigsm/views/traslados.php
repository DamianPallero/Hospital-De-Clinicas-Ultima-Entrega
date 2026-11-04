<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Traslado.php';
require_once __DIR__ . '/../includes/Cache.php';

requerirAutenticacion();
sinCache();

if (!tienePermiso('ver_traslados')) {
    header('Location: /sigsm/index.php');
    exit();
}

$baseObj = new Database();
$bd = $baseObj->getConnection();
$modTras = new Traslado($bd);

$tras = $modTras->listarTodos();
$porHacer = $modTras->listarPendientes();
$choferes = $modTras->listarConductores();
$enfs = $modTras->listarEnfermeros();
$autos = $modTras->listarVehiculosDisponibles();
$vehiculosEstado = $modTras->listarVehiculos();

$permCrear = tienePermiso('crear_traslado');
$permAsignar = tienePermiso('asignar_traslado');
$permCambiarSit = tienePermiso('actualizar_estado_traslado');
$permVerVehiculos = tienePermiso('ver_vehiculos');
$permGestionVehiculos = tienePermiso('gestionar_vehiculos');

$mensaje = $_GET['msg'] ?? '';
$claseAviso = '';
$textoAviso = '';

switch ($mensaje) {
    case 'ok_crear': $claseAviso = 'success'; $textoAviso = 'Solicitud de traslado creada correctamente.'; break;
    case 'ok_asignar': $claseAviso = 'success'; $textoAviso = 'Recursos asignados correctamente.'; break;
    case 'ok_estado': $claseAviso = 'success'; $textoAviso = 'Estado del traslado actualizado.'; break;
    case 'ok_vehiculo': $claseAviso = 'success'; $textoAviso = 'Estado del vehículo actualizado correctamente.'; break;
    case 'error_validacion': $claseAviso = 'danger'; $textoAviso = 'Faltan datos obligatorios.'; break;
    case 'error_csrf': $claseAviso = 'danger'; $textoAviso = 'Error de seguridad. Intente nuevamente.'; break;
    case 'error': $claseAviso = 'danger'; $textoAviso = 'Ocurrió un error al procesar la solicitud.'; break;
}

function badgeEstado($sit) {
    $mapa = [
        'Pendiente' => 'warning',
        'Asignado' => 'info',
        'En Ruta' => 'primary',
        'Completado' => 'success',
        'Cancelado' => 'secondary',
        'Disponible' => 'success',
        'En Traslado' => 'primary',
        'En Mantenimiento' => 'warning',
        'Fuera de Servicio' => 'secondary'
    ];
    $tono = $mapa[$sit] ?? 'secondary';
    return '<span class="badge bg-' . $tono . ' bg-opacity-10 text-' . $tono . ' border border-' . $tono . ' border-opacity-25">' . htmlspecialchars($sit) . '</span>';
}

$rolNom = obtenerRol();
$nomUsu = $_SESSION['user_name'] ?? 'Usuario';
$totalRuta = count(array_filter($tras, fn($elemT) => $elemT['estado_traslado'] === 'En Ruta'));
$totalListos = count(array_filter($tras, fn($elemT) => $elemT['estado_traslado'] === 'Completado'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title>Gestión de Traslados - S.I.G.S.M.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mobile.css?v=20261101-1">
    <style>
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }
        @media (prefers-contrast: more) {
            .card { border: 2px solid currentColor !important; }
            .btn { border-width: 2px !important; }
        }
        :focus-visible { outline: 3px solid #124b7d !important; outline-offset: 2px !important; }
        
        .table-sm th, .table-sm td {
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
        <a class="navbar-brand fw-bold mb-0" href="index.php">S.I.G.S.M. - Traslados</a>
        <div class="d-flex align-items-center gap-2">
            <span class="text-white small d-none d-sm-inline"><?= e($nomUsu) ?></span>
            <span class="badge bg-light text-primary"><?= e($rolNom) ?></span>
            <a href="../../index.php" class="btn text-dark btn-sm btn-outline-light btn-info d-inline-flex align-items-center">
                Volver al portal
            </a>
            <a href="logout.php" class="btn btn-sm btn-outline-light btn-danger text-light d-inline-flex align-items-center">
                Salir
            </a>
            <a href="cambiar_password.php" class="btn btn-outline-light btn-sm d-inline-flex align-items-center">Cambiar Contraseña</a>
        </div>
    </div>
</nav>

<main class="flex-grow-1 d-flex flex-column p-3 p-lg-4">
    <?php if ($mensaje && $textoAviso): ?>
        <div class="alert alert-<?= $claseAviso ?> alert-dismissible fade show shadow-sm py-2 mb-3 flex-shrink-0">
            <?= e($textoAviso) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar alerta"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-shrink-0">
        <div>
            <h4 class="fw-bold mb-0">Gestión de Traslados</h4>
            <small class="text-muted">
                Pendientes: <span class="fw-bold text-warning"><?= count($porHacer) ?></span> · 
                En Ruta: <span class="fw-bold text-primary"><?= $totalRuta ?></span> · 
                Completados: <span class="fw-bold text-success"><?= $totalListos ?></span>
            </small>
        </div>
        <?php if ($permCrear): ?>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#crearModal">
                + Nueva Solicitud
            </button>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm flex-grow-1 d-flex flex-column overflow-hidden">
        <div class="card-body p-0 d-flex flex-column flex-grow-1 overflow-hidden">
            <div class="table-responsive flex-grow-1 overflow-auto">
                <table class="table table-hover align-middle mb-0 table-sm">
                    <thead class="sticky-top">
                        <tr>
                            <th class="ps-4 d-none d-md-table-cell">ID</th>
                            <th>Ruta</th>
                            <th class="d-none d-lg-table-cell">Salida</th>
                            <th class="d-none d-xl-table-cell">Equipo</th>
                            <th>Estado</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tras)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">No hay traslados registrados.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tras as $elemT): ?>
                                <tr>
                                    <td class="ps-4 fw-medium d-none d-md-table-cell text-muted">#<?= (int)$elemT['id_traslado'] ?></td>
                                    <td>
                                        <div class="fw-bold small"><?= e($elemT['origen']) ?></div>
                                        <div class="text-muted small">&darr; <?= e($elemT['destino']) ?></div>
                                    </td>
                                    <td class="small text-muted d-none d-lg-table-cell"><?= e(date('d/m/Y H:i', strtotime($elemT['fecha_hora_salida']))) ?></td>
                                    <td class="small d-none d-xl-table-cell">
                                        <?php if ($elemT['conductor_nombre']): ?>
                                            <div><?= e($elemT['conductor_nombre'] . ' ' . $elemT['conductor_apellido']) ?></div>
                                            <div class="text-muted"><?= e($elemT['enfermero_nombre'] . ' ' . $elemT['enfermero_apellido']) ?></div>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">Sin asignar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= badgeEstado($elemT['estado_traslado']) ?></td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex gap-1 justify-content-end flex-wrap">
                                            <?php if ($elemT['estado_traslado'] === 'Pendiente' && $permAsignar): ?>
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#asignarModal"
                                                        data-id="<?= (int)$elemT['id_traslado'] ?>"
                                                        data-origen="<?= e($elemT['origen']) ?>"
                                                        data-destino="<?= e($elemT['destino']) ?>">Asignar</button>
                                            <?php endif; ?>
                                            <?php if ($permCambiarSit): ?>
                                                <?php if ($elemT['estado_traslado'] === 'Asignado'): ?>
                                                    <form action="../controllers/TrasladoController.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="accion" value="actualizar_estado">
                                                        <input type="hidden" name="id_traslado" value="<?= (int)$elemT['id_traslado'] ?>">
                                                        <input type="hidden" name="nuevo_estado" value="En Ruta">
                                                        <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                                                        <button type="submit" class="btn btn-sm btn-primary">Iniciar</button>
                                                    </form>
                                                <?php elseif ($elemT['estado_traslado'] === 'En Ruta'): ?>
                                                    <form action="../controllers/TrasladoController.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="accion" value="actualizar_estado">
                                                        <input type="hidden" name="id_traslado" value="<?= (int)$elemT['id_traslado'] ?>">
                                                        <input type="hidden" name="nuevo_estado" value="Completado">
                                                        <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                                                        <button type="submit" class="btn btn-sm btn-success">Completar</button>
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

    <?php if ($permVerVehiculos): ?>
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white border-bottom fw-bold d-flex justify-content-between align-items-center">
            <span>Estado de la flota</span>
            <?php if ($permGestionVehiculos): ?>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoVehiculoModal">+ Nuevo vehículo</button>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-sm">
                    <thead>
                        <tr>
                            <th class="ps-4">Patente</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <?php if ($permGestionVehiculos): ?>
                                <th class="text-end pe-4">Actualizar</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehiculosEstado as $veh): ?>
                            <tr>
                                <td class="ps-4 fw-medium"><?= e($veh['patente']) ?></td>
                                <td><?= e($veh['tipo']) ?></td>
                                <td><?= badgeEstado($veh['estado']) ?></td>
                                <?php if ($permGestionVehiculos): ?>
                                    <td class="text-end pe-4">
                                        <form action="../controllers/TrasladoController.php" method="POST" class="d-flex justify-content-end align-items-center gap-2">
                                            <input type="hidden" name="accion" value="actualizar_estado_vehiculo">
                                            <input type="hidden" name="id_vehiculo" value="<?= (int)$veh['id_vehiculo'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                                            <select class="form-select form-select-sm" name="estado_vehiculo" aria-label="Estado del vehículo">
                                                <?php foreach (['Disponible', 'En Traslado', 'En Mantenimiento', 'Fuera de Servicio'] as $estadoOpt): ?>
                                                    <option value="<?= e($estadoOpt) ?>" <?= $veh['estado'] === $estadoOpt ? 'selected' : '' ?>><?= e($estadoOpt) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</main>

<?php if ($permGestionVehiculos): ?>
<div class="modal fade modal-fullscreen-sm-down" id="nuevoVehiculoModal" tabindex="-1" aria-labelledby="nuevoVehiculoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="../controllers/TrasladoController.php" method="POST">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="nuevoVehiculoModalLabel">Nuevo Vehículo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear_vehiculo">
                    <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                    <div class="mb-3">
                        <label for="nuevo_patente" class="form-label small fw-bold">Patente</label>
                        <input type="text" class="form-control" id="nuevo_patente" name="patente" required placeholder="Ej: ABC123">
                    </div>
                    <div class="mb-3">
                        <label for="nuevo_tipo" class="form-label small fw-bold">Tipo</label>
                        <input type="text" class="form-control" id="nuevo_tipo" name="tipo" required placeholder="Ej: Ambulancia Básica">
                    </div>
                    <div class="mb-3">
                        <label for="nuevo_estado_vehiculo" class="form-label small fw-bold">Estado inicial</label>
                        <select class="form-select" id="nuevo_estado_vehiculo" name="estado">
                            <option value="Disponible">Disponible</option>
                            <option value="En Traslado">En Traslado</option>
                            <option value="En Mantenimiento">En Mantenimiento</option>
                            <option value="Fuera de Servicio">Fuera de Servicio</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">Guardar vehículo</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($permCrear): ?>
<div class="modal fade modal-fullscreen-sm-down" id="crearModal" tabindex="-1" aria-labelledby="crearModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="../controllers/TrasladoController.php" method="POST">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="crearModalLabel">Nueva Solicitud de Traslado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear">
                    <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="origen" class="form-label small fw-bold">Origen</label>
                            <input type="text" class="form-control" id="origen" name="origen" required placeholder="Ej: Hospital de Clínicas - UTI">
                        </div>
                        <div class="col-md-6">
                            <label for="destino" class="form-label small fw-bold">Destino</label>
                            <input type="text" class="form-control" id="destino" name="destino" required placeholder="Ej: Centro de Diálisis Norte">
                        </div>
                        <div class="col-md-6">
                            <label for="tipo_aislamiento" class="form-label small fw-bold">Tipo de Aislamiento</label>
                            <select class="form-select" id="tipo_aislamiento" name="tipo_aislamiento" required>
                                <option value="Ninguno">Ninguno</option>
                                <option value="Contacto">Contacto</option>
                                <option value="Gotículas">Gotículas</option>
                                <option value="Aéreo">Aéreo</option>
                                <option value="Estricto">Estricto</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="requiere_ascensor_escalera" class="form-label small fw-bold">Requiere Ascensor/Escalera</label>
                            <select class="form-select" id="requiere_ascensor_escalera" name="requiere_ascensor_escalera" required>
                                <option value="Ninguno">Ninguno</option>
                                <option value="Ascensor">Ascensor</option>
                                <option value="Escalera">Escalera</option>
                                <option value="Camilla y Ascensor">Camilla y Ascensor</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check mt-1">
                                <input class="form-check-input shadow-none" type="checkbox" id="oxigeno" name="necesita_oxigeno" value="1" style="width:1rem; height:1rem; min-width:1rem; min-height:1rem; max-width:1rem; max-height:1rem; margin-top:0; margin-right:0.5rem; border-radius:0.25rem; appearance:auto; -webkit-appearance:auto;">
                                <label class="form-check-label fw-bold small" for="oxigeno">Requiere soporte de oxígeno</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="observaciones" class="form-label small fw-bold">Observaciones</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="3" placeholder="Información clínica y logística adicional"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">Crear Solicitud</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($permAsignar): ?>
<div class="modal fade modal-fullscreen-sm-down" id="asignarModal" tabindex="-1" aria-labelledby="asignarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="../controllers/TrasladoController.php" method="POST">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="asignarModalLabel">Asignar Recursos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="asignar">
                    <input type="hidden" name="id_traslado" id="asignar_id">
                    <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                    <div class="alert alert-info small mb-3 py-2">
                        <strong>Ruta:</strong> <span id="asignar_ruta"></span>
                    </div>
                    <div class="mb-3">
                        <label for="id_conductor" class="form-label small fw-bold">Conductor</label>
                        <select class="form-select" id="id_conductor" name="id_conductor" required>
                            <option value="">Seleccione un conductor</option>
                            <?php foreach ($choferes as $datoC): ?>
                                <option value="<?= (int)$datoC['id_conductor'] ?>"><?= e($datoC['apellido'] . ', ' . $datoC['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="id_enfermero" class="form-label small fw-bold">Enfermero/a</label>
                        <select class="form-select" id="id_enfermero" name="id_enfermero" required>
                            <option value="">Seleccione un enfermero</option>
                            <?php foreach ($enfs as $err): ?>
                                <option value="<?= (int)$err['id_enfermero'] ?>"><?= e($err['apellido'] . ', ' . $err['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="id_vehiculo" class="form-label small fw-bold">Vehículo</label>
                        <select class="form-select" id="id_vehiculo" name="id_vehiculo" required>
                            <option value="">Seleccione un vehículo</option>
                            <?php foreach ($autos as $datoV): ?>
                                <option value="<?= (int)$datoV['id_vehiculo'] ?>"><?= e($datoV['patente']) ?> - <?= e($datoV['tipo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">Asignar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalAsignar = document.getElementById('asignarModal');
    if (modalAsignar) {
        modalAsignar.addEventListener('show.bs.modal', function (evento) {
            var boton = evento.relatedTarget;
            modalAsignar.querySelector('#asignar_id').value = boton.getAttribute('data-id');
            modalAsignar.querySelector('#asignar_ruta').textContent = boton.getAttribute('data-origen') + ' → ' + boton.getAttribute('data-destino');
        });
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>