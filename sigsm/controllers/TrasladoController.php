<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Traslado.php';

requerirAutenticacion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /sigsm/views/traslados.php');
    exit();
}

$tokenForm = $_POST['csrf_token'] ?? '';
if (!validarTokenCSRF($tokenForm)) {
    header('Location: /sigsm/views/traslados.php?msg=error_csrf');
    exit();
}

$baseObj = new Database();
$bd = $baseObj->getConnection();
$modTras = new Traslado($bd);
$op = $_POST['accion'] ?? '';
$mensaje = 'error';

if ($op === 'crear' && tienePermiso('crear_traslado')) {
    $modTras->inicio = trim($_POST['origen'] ?? '');
    $modTras->fin = trim($_POST['destino'] ?? '');
    $modTras->horaSale = date('Y-m-d H:i:s');
    $modTras->oxigeno = isset($_POST['necesita_oxigeno']) ? 1 : 0;
    $modTras->aislamiento = trim($_POST['tipo_aislamiento'] ?? 'Ninguno');
    $modTras->usaAscensor = trim($_POST['requiere_ascensor_escalera'] ?? 'Ninguno');
    $modTras->notas = trim($_POST['observaciones'] ?? '');

    if ($modTras->inicio !== '' && $modTras->fin !== '' && $modTras->horaSale !== '') {
        $mensaje = $modTras->crear() ? 'ok_crear' : 'error';
    } else {
        $mensaje = 'error_validacion';
    }
}

if ($op === 'asignar' && tienePermiso('asignar_traslado')) {
    $modTras->trasId = (int)($_POST['id_traslado'] ?? 0);
    $modTras->choferId = (int)($_POST['id_conductor'] ?? 0);
    $modTras->enfId = (int)($_POST['id_enfermero'] ?? 0);
    $modTras->vehId = (int)($_POST['id_vehiculo'] ?? 0);

    if ($modTras->trasId > 0 && $modTras->choferId > 0 && $modTras->enfId > 0 && $modTras->vehId > 0) {
        $mensaje = $modTras->asignarRecursos() ? 'ok_asignar' : 'error';
    } else {
        $mensaje = 'error_validacion';
    }
}

if ($op === 'actualizar_estado' && tienePermiso('actualizar_estado_traslado')) {
    $modTras->trasId = (int)($_POST['id_traslado'] ?? 0);
    $nuevoSit = $_POST['nuevo_estado'] ?? '';

    if ($modTras->trasId > 0 && $nuevoSit !== '') {
        $mensaje = $modTras->actualizarEstado($nuevoSit) ? 'ok_estado' : 'error';
    }
}

if ($op === 'crear_vehiculo' && tienePermiso('gestionar_vehiculos')) {
    $patente = trim($_POST['patente'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $estado = trim($_POST['estado'] ?? 'Disponible');

    if ($patente !== '' && $tipo !== '') {
        $mensaje = $modTras->crearVehiculo($patente, $tipo, $estado) ? 'ok_vehiculo' : 'error';
    } else {
        $mensaje = 'error_validacion';
    }
}

if ($op === 'actualizar_estado_vehiculo' && tienePermiso('gestionar_vehiculos')) {
    $idVehiculo = (int)($_POST['id_vehiculo'] ?? 0);
    $nuevoEstadoVehiculo = trim($_POST['estado_vehiculo'] ?? '');
    $estadosValidos = ['Disponible', 'En Traslado', 'En Mantenimiento', 'Fuera de Servicio'];

    if ($idVehiculo > 0 && in_array($nuevoEstadoVehiculo, $estadosValidos, true)) {
        $mensaje = $modTras->actualizarEstadoVehiculo($idVehiculo, $nuevoEstadoVehiculo) ? 'ok_vehiculo' : 'error';
    } else {
        $mensaje = 'error_validacion';
    }
}

if ($op === 'crear_personal_transporte' && tienePermiso('gestionar_personal_transporte')) {
    $tipoPersonal = trim($_POST['tipo_personal'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $documento = trim($_POST['documento'] ?? '');
    $estado = trim($_POST['estado'] ?? 'Activo');

    if (in_array($tipoPersonal, ['conductor', 'enfermero'], true) && $nombre !== '' && $apellido !== '' && $documento !== '') {
        $mensaje = $modTras->crearPersonalTransporte($tipoPersonal, $nombre, $apellido, $documento, $estado) ? 'ok_personal' : 'error';
    } else {
        $mensaje = 'error_validacion';
    }
}

if ($op === 'editar_personal_transporte' && tienePermiso('gestionar_personal_transporte')) {
    $tipoPersonal = trim($_POST['tipo_personal'] ?? '');
    $idPersonal = (int)($_POST['id_personal'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $documento = trim($_POST['documento'] ?? '');
    $estado = trim($_POST['estado'] ?? 'Activo');

    if ($idPersonal > 0 && in_array($tipoPersonal, ['conductor', 'enfermero'], true) && $nombre !== '' && $apellido !== '' && $documento !== '') {
        $mensaje = $modTras->actualizarPersonalTransporte($tipoPersonal, $idPersonal, $nombre, $apellido, $documento, $estado) ? 'ok_personal' : 'error';
    } else {
        $mensaje = 'error_validacion';
    }
}

if ($op === 'eliminar_personal_transporte' && tienePermiso('gestionar_personal_transporte')) {
    $tipoPersonal = trim($_POST['tipo_personal'] ?? '');
    $idPersonal = (int)($_POST['id_personal'] ?? 0);

    if ($idPersonal > 0 && in_array($tipoPersonal, ['conductor', 'enfermero'], true)) {
        $mensaje = $modTras->eliminarPersonalTransporte($tipoPersonal, $idPersonal) ? 'ok_personal_eliminado' : 'error';
    } else {
        $mensaje = 'error_validacion';
    }
}

header('Location: /sigsm/views/traslados.php?msg=' . $mensaje);
exit();