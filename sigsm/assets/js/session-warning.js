(function() {
    const limiteSesion = 1800;
    const avisoAntes = 120;
    const intervalo = 30000;

    function checkSession() {
        const ultimaActividad = parseInt(document.body.dataset.lastActivity || '0', 10);
        const transcurrido = Math.floor(Date.now() / 1000) - ultimaActividad;
        const restante = limiteSesion - transcurrido;

        if (restante <= avisoAntes && restante > 0 && !document.getElementById('session-warning-modal')) {
            showWarning(restante);
        } else if (restante <= 0) {
            window.location.href = '/sigsm/views/login.php?expired=1';
        }
    }

    function showWarning(segs) {
        const minutos = Math.floor(segs / 60);
        const modalAviso = document.createElement('div');
        modalAviso.id = 'session-warning-modal';
        modalAviso.className = 'modal fade show';
        modalAviso.style.display = 'block';
        modalAviso.setAttribute('role', 'alertdialog');
        modalAviso.setAttribute('aria-modal', 'true');
        modalAviso.setAttribute('aria-labelledby', 'session-warning-title');
        modalAviso.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-warning">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title fw-bold" id="session-warning-title">Sesión por expirar</h5>
                    </div>
                    <div class="modal-body">
                        <p>Su sesión expirará en <strong id="countdown">${minutos} minuto${minutos !== 1 ? 's' : ''}</strong>.</p>
                        <p class="mb-0">Haga clic en "Continuar trabajando" para mantener su sesión activa.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" id="extend-session">Continuar trabajando</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modalAviso);
        document.body.insertAdjacentHTML('beforeend', '<div class="modal-backdrop fade show"></div>');

        document.getElementById('extend-session').addEventListener('click', function() {
            fetch('/sigsm/includes/extend_session.php')
                .then(() => {
                    document.body.dataset.lastActivity = Math.floor(Date.now() / 1000);
                    modalAviso.remove();
                    document.querySelector('.modal-backdrop')?.remove();
                });
        });

        document.getElementById('countdown').focus();
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.body.dataset.lastActivity = Math.floor(Date.now() / 1000);
        setInterval(checkSession, intervalo);

        ['click', 'keydown', 'mousemove'].forEach(evento => {
            document.addEventListener(evento, () => {
                document.body.dataset.lastActivity = Math.floor(Date.now() / 1000);
            }, { passive: true });
        });
    });
})();