<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Encuesta.php';

$clave = (int)($_GET['id'] ?? 0);
$ver = (int)($_GET['v'] ?? 1);
$claveSesion = 'survey_completed_v' . $ver;

$completo = !empty($_SESSION[$claveSesion]);

$bd = (new Database())->getConnection();
$modEncuesta = new Encuesta($bd);

$encuestaFila = $modEncuesta->obtenerPorId($clave);
if (!$encuestaFila || $encuestaFila['estado'] != 1) {
    die("<div class='container mt-5'><div class='alert alert-danger text-center'>Esta encuesta no está disponible o ha finalizado.</div></div>");
}

$pregs = $modEncuesta->obtenerPreguntas($clave);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($encuestaFila['titulo']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mobile.css">
</head>
<body class="bg-light">
    <div class="container py-4" style="max-width: 600px;">
        <?php if ($completo): ?>
            <div class="card border-0 shadow-sm p-4 text-center">
                <h4 class="text-warning fw-bold">Acceso Restringido</h4>
                <p class="text-muted mb-0">Usted ya ha completado esta encuesta. Para responder nuevamente, debe escanear el código QR impreso desde su dispositivo.</p>
            </div>
        <?php else: ?>
            <h2 class="h4 fw-bold text-primary mb-4"><?= htmlspecialchars($encuestaFila['titulo']) ?></h2>
            
            <?php if (empty($pregs)): ?>
                <div class="alert alert-info">Esta encuesta aún no tiene preguntas configuradas.</div>
            <?php else: ?>
                <form action="procesar.php" method="POST">
                    <input type="hidden" name="id_encuesta" value="<?= (int)$encuestaFila['id_encuesta'] ?>">
                    <input type="hidden" name="v" value="<?= $ver ?>">
                    <?php foreach ($pregs as $indice => $pregFila): ?>
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body">
                                <label class="form-label fw-bold mb-2"><?= ($indice + 1) . '. ' . htmlspecialchars($pregFila['texto_pregunta']) ?></label>
                                
                                <?php if ($pregFila['tipo_respuesta'] === 'si_no'): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="respuestas[<?= (int)$pregFila['id_pregunta'] ?>]" value="Sí" required>
                                        <label class="form-check-label">Sí</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="respuestas[<?= (int)$pregFila['id_pregunta'] ?>]" value="No" required>
                                        <label class="form-check-label">No</label>
                                    </div>
                                <?php else: ?>
                                    <div class="d-flex justify-content-between gap-2">
                                        <?php for ($pos = 1; $pos <= 5; $pos++): ?>
                                            <div class="form-check text-center flex-fill">
                                                <input class="form-check-input" type="radio" name="respuestas[<?= (int)$pregFila['id_pregunta'] ?>]" value="<?= $pos ?>" id="p<?= (int)$pregFila['id_pregunta'] ?>_<?= $pos ?>" required>
                                                <label class="form-check-label fw-bold" for="p<?= (int)$pregFila['id_pregunta'] ?>_<?= $pos ?>"><?= $pos ?></label>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold">Enviar Respuestas</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        window.addEventListener('pageshow', function (evento) {
            if (evento.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>