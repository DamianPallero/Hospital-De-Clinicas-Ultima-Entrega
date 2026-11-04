<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Encuesta.php';
require_once __DIR__ . '/../includes/Cache.php';

requerirAutenticacion();
sinCache();

if (!tienePermiso('ver_respuestas_encuestas')) {
    header('Location: /sigsm/index.php');
    exit();
}

$baseObj = new Database();
$bd = $baseObj->getConnection();
$modEncuesta = new Encuesta($bd);

$salidas = $modEncuesta->obtenerResumenRespuestas();

$grupos = [];
foreach ($salidas as $registro) {
    if (!is_array($registro)) continue;
    
    $encuestaFila = (string) ($registro['encuesta_titulo'] ?? 'Sin título');
    $pregFilaTexto = (string) ($registro['texto_pregunta'] ?? 'Sin pregunta');
    $clase = (string) ($registro['tipo_respuesta'] ?? '');
    $respTexto = $registro['respuesta_texto'] ?? 'Sin respuesta';
    $cantTotal = (int) ($registro['total'] ?? 0);
    
    if ($cantTotal <= 0) continue;

    if (!isset($grupos[$encuestaFila])) {
        $grupos[$encuestaFila] = [];
    }
    if (!isset($grupos[$encuestaFila][$pregFilaTexto])) {
        $grupos[$encuestaFila][$pregFilaTexto] = ['tipo' => $clase, 'respuestas' => []];
    }
    
    $claveResp = (string) $respTexto;
    $grupos[$encuestaFila][$pregFilaTexto]['respuestas'][$claveResp] = 
        (int) ($grupos[$encuestaFila][$pregFilaTexto]['respuestas'][$claveResp] ?? 0) + $cantTotal;
}

$rolNom = obtenerRol();
$nomUsu = $_SESSION['user_name'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title>Resultados de Encuestas - S.I.G.S.M.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mobile.css">
    <style>
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }
        @media (prefers-contrast: more) {
            .card { border: 2px solid currentColor !important; }
            .btn { border-width: 2px !important; }
        }
        :focus-visible { outline: 3px solid #124b7d !important; outline-offset: 2px !important; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

<nav class="navbar navbar-dark bg-primary shadow-sm flex-shrink-0">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand fw-bold mb-0" href="index.php">S.I.G.S.M. - Resultados</a>
        <div class="d-flex align-items-center gap-2">
            <span class="text-white small d-none d-sm-inline"><?= e($nomUsu) ?></span>
            <span class="badge bg-light text-primary"><?= e($rolNom) ?></span>
            <a href="../index.php" class="btn text-dark btn-info btn-outline-light btn-sm d-inline-flex align-items-center">Volver al Portal</a>
            <a href="logout.php" class="btn btn-danger btn-outline-light btn-sm d-inline-flex align-items-center">Salir</a>
            <a href="cambiar_password.php" class="btn btn-outline-light btn-sm d-inline-flex align-items-center">Cambiar Contraseña</a>
        </div>
    </div>
</nav>

<main class="flex-grow-1 d-flex flex-column p-3 p-lg-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-shrink-0">
        <h4 class="fw-bold mb-0">Resultados de Encuestas</h4>
    </div>

    <?php if (empty($grupos)): ?>
        <div class="card border-0 shadow-sm flex-grow-1 d-flex align-items-center justify-content-center">
            <div class="text-center p-5">
                <div class="text-muted mb-3" style="font-size: 3rem;">&#128203;</div>
                <h5 class="fw-bold text-muted">Aún no hay respuestas registradas</h5>
                <p class="text-muted small">Los resultados de las encuestas aparecerán aquí una vez que los pacientes comiencen a responder.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($grupos as $tituloEnc => $pregs): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white fw-bold text-primary py-3">
                            <?= e($tituloEnc) ?>
                        </div>
                        <div class="card-body">
                            <?php foreach ($pregs as $textoPreg => $datos): ?>
                                <div class="mb-4 pb-3 border-bottom">
                                    <h6 class="fw-bold mb-3"><?= e($textoPreg) ?></h6>
                                    
                                    <?php if (($datos['tipo'] ?? '') === 'escala_1_a_5'): ?>
                                        <?php
                                        $datosValor = array_values($datos['respuestas'] ?? []);
                                        $maximo = !empty($datosValor) ? max($datosValor) : 1;
                                        $maximo = $maximo > 0 ? $maximo : 1;
                                        ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <small class="text-muted">1 (Malo)</small>
                                            <small class="text-muted">5 (Excelente)</small>
                                        </div>
                                        <div class="d-flex gap-1">
                                            <?php for ($pos = 1; $pos <= 5; $pos++): 
                                                $cant = (int) ($datos['respuestas'][$pos] ?? 0);
                                                $ancho = $maximo > 0 ? (($cant / $maximo) * 100) : 0;
                                            ?>
                                                <div class="text-center flex-fill">
                                                    <div class="bg-primary bg-opacity-10 rounded-top" style="height: <?= max($ancho, 5) ?>px;"></div>
                                                    <small class="fw-bold d-block"><?= $cant ?></small>
                                                    <small class="text-muted"><?= $pos ?></small>
                                                </div>
                                            <?php endfor; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="row g-2">
                                            <?php foreach (($datos['respuestas'] ?? []) as $respFila => $cant): ?>
                                                <div class="col-6 col-md-4">
                                                    <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                                        <span class="fw-bold"><?= e((string) $respFila) ?></span>
                                                        <span class="badge bg-primary rounded-pill"><?= (int) $cant ?></span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>