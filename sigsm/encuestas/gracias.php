<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gracias - Hospital de Clínicas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mobile.css">
</head>
<body class="bg-light">
    <div class="container py-5 text-center" style="max-width: 500px;">
        <div class="card border-0 shadow-sm p-4">
            <h1 class="h3 fw-bold text-primary mb-3">¡Gracias por su tiempo!</h1>
            <p class="text-muted mb-4">Sus respuestas han sido registradas de forma anónima y nos ayudarán a mejorar la calidad de nuestros servicios.</p>
            <p class="small text-muted" id="close-msg" style="display: none;">Por favor, cierre esta ventana o pestaña.</p>
        </div>
    </div>
    <script>
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };

        window.open('', '_self', '');
        window.close();
        
        setTimeout(function() {
            var mensaje = document.getElementById('close-msg');
            if(mensaje) mensaje.style.display = 'block';
        }, 1000);
    </script>
</body>
</html>