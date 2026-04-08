export function initGestionarPermisos() {
    const form = document.getElementById('form-permisos');
    const alertBox = document.getElementById('permisosAlert');

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const btnSubmit = document.getElementById('btnGuardarPermisos');
            const originalText = btnSubmit.innerHTML;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';
            btnSubmit.disabled = true;

            const formData = new FormData(form);

            fetch('ajax/guardar_permisos.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btnSubmit.innerHTML = originalText;
                btnSubmit.disabled = false;
                
                if (data.success) {
                    alertBox.innerHTML = `<div class="alert alert-success mb-0">${data.message} <br> <small>Recargando sistema para aplicar menús...</small></div>`;
                    // Refrescar página para aplicar cambios a la UI principal
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    alertBox.innerHTML = `<div class="alert alert-danger mb-0">${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error("Error:", error);
                btnSubmit.innerHTML = originalText;
                btnSubmit.disabled = false;
                alertBox.innerHTML = `<div class="alert alert-danger mb-0">Ocurrió un error al contactar al servidor.</div>`;
            });
        });
    }
}
