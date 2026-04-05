// Este script maneja la lógica para el modal y el filtro de la tabla de distribuidores.
export function initDistributorModal() {
    const saveDistributorBtn = document.getElementById('saveDistributorBtn');
    const addDistributorForm = document.getElementById('add-distributor-form');
    const searchInput = document.getElementById('search-input');
    const distributorTableBody = document.querySelector('#distributor-table tbody');
    const addDistributorButton = document.querySelector('[data-bs-target="#addDistributorModal"]'); // Selecciona el botón para abrir el modal

    // Lógica para el buscador dinámico
    if (searchInput && distributorTableBody) {
        searchInput.addEventListener('input', function () {
            const searchText = this.value.toLowerCase();
            const rows = distributorTableBody.querySelectorAll('tr');

            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                if (rowText.includes(searchText)) {
                    row.style.display = ''; // Muestra la fila
                } else {
                    row.style.display = 'none'; // Oculta la fila
                }
            });
        });
    }

    // Inicializar modal con opciones para evitar problemas de foco
    let distributorModalInstance = null;
    if (document.getElementById('addDistributorModal')) {
        distributorModalInstance = new bootstrap.Modal(document.getElementById('addDistributorModal'), {
            backdrop: 'static',
            keyboard: false
        });
    }

    if (addDistributorButton && distributorModalInstance) {
        // Remover listeners anteriores para evitar duplicados si la función se llama varias veces (aunque loadContent debería limpiar el DOM)
        const newBtn = addDistributorButton.cloneNode(true);
        addDistributorButton.parentNode.replaceChild(newBtn, addDistributorButton);

        newBtn.addEventListener('click', function () {
            // Limpiar errores y formulario al abrir
            document.getElementById('add-distributor-form').reset();
            const errorContainer = document.getElementById('error-container');
            if (errorContainer) errorContainer.innerHTML = '';

            distributorModalInstance.show();
        });
    }

    // Lógica para guardar un nuevo distribuidor a través del modal
    if (saveDistributorBtn && addDistributorForm) {
        // Clonar botón para remover listeners viejos
        const newSaveBtn = saveDistributorBtn.cloneNode(true);
        saveDistributorBtn.parentNode.replaceChild(newSaveBtn, saveDistributorBtn);

        newSaveBtn.addEventListener('click', function (event) {
            event.preventDefault();

            const codigoInput = document.getElementById('distributor-code');
            const razonSocialInput = document.getElementById('distributor-razon-social');
            const errorContainer = document.getElementById('error-container');

            const codigo = codigoInput.value;
            const razonSocial = razonSocialInput.value;

            // Limpiar errores previos
            if (errorContainer) errorContainer.innerHTML = '';

            // 1. Validaciones Frontend
            if (!codigo || !razonSocial) {
                alert("Por favor, completa todos los campos.");
                return;
            }

            if (parseInt(codigo) < 0) {
                if (errorContainer) {
                    errorContainer.innerHTML = '<div class="alert alert-danger">El código no puede ser negativo.</div>';
                } else {
                    alert("El código no puede ser negativo.");
                }
                return;
            }

            const formData = new FormData();
            formData.append('codigo', codigo);
            formData.append('razon_social', razonSocial);

            fetch('ajax/agregar_distribuidor.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Cierro el modal de registro
                        distributorModalInstance.hide();

                        // Mostrar modal de éxito
                        const successModalEl = document.getElementById('successModal');
                        if (successModalEl) {
                            const successModal = new bootstrap.Modal(successModalEl);
                            successModal.show();

                            // Recargar al cerrar el modal de éxito
                            successModalEl.addEventListener('hidden.bs.modal', function () {
                                window.loadContent('distribuidores.php');
                            });
                        } else {
                            alert(data.message); // Fallback
                            window.loadContent('distribuidores.php');
                        }

                    } else {
                        // Manejo de errores
                        let errorHtml = `<div class="alert alert-danger">
                        <strong>Error:</strong> ${data.message}
                    </div>`;

                        // Si hay duplicados, mostrar la lista
                        if (data.duplicados && data.duplicados.length > 0) {
                            errorHtml += `<div class="card mt-2 border-danger">
                            <div class="card-header bg-danger text-white py-1">Coincidencias Encontradas</div>
                            <ul class="list-group list-group-flush">`;

                            data.duplicados.forEach(dup => {
                                errorHtml += `<li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>${dup.razon_social}</span>
                                <span class="badge bg-secondary">${dup.codigo}</span>
                            </li>`;
                            });

                            errorHtml += `</ul></div>`;
                        }

                        if (errorContainer) {
                            errorContainer.innerHTML = errorHtml;
                        } else {
                            alert(data.message);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (errorContainer) {
                        errorContainer.innerHTML = '<div class="alert alert-danger">Ocurrió un error inesperado de red.</div>';
                    }
                });
        });
    }

    // --- Lógica para EDICIÓN y ELIMINACIÓN (Event Delegation) ---
    if (distributorTableBody) {
        // Remover listeners anteriores (usando cloneNode en el padre si fuera necesario, pero la tabla se recarga)
        // Como la tabla se recarga con loadContent, los listeners se pierden, así que está bien agregar uno nuevo.
        // Pero para estar seguros de no duplicar si init se llama múltiple veces sin recarga completa:
        const newBody = distributorTableBody.cloneNode(true);
        distributorTableBody.parentNode.replaceChild(newBody, distributorTableBody);

        newBody.addEventListener('click', function (event) {
            // Manejo de botón EDITAR
            const editBtn = event.target.closest('.edit-btn');
            if (editBtn) {
                // Obtener fila y datos
                const row = editBtn.closest('tr');
                const codigo = row.getAttribute('data-codigo');
                const razonSocial = row.getAttribute('data-razon-social');

                // Llenar Modal de Edición
                document.getElementById('edit-distributor-code').value = codigo;
                document.getElementById('edit-distributor-razon-social').value = razonSocial;

                // Limpiar errores
                const editErrorContainer = document.getElementById('edit-error-container');
                if (editErrorContainer) editErrorContainer.innerHTML = '';

                // Mostrar Modal (Bootstrap lo maneja por data-bs-toggle, solo llenamos datos)
            }

            // Manejo de botón ELIMINAR (Deshabilitar)
            const disableBtn = event.target.closest('.disable-btn');
            if (disableBtn) {
                if (confirm('¿Está seguro de eliminar este distribuidor? El usuario asociado permanecerá activo.')) {
                    const row = disableBtn.closest('tr');
                    const codigo = row.getAttribute('data-codigo');

                    const formData = new FormData();
                    formData.append('codigo', codigo);

                    fetch('ajax/eliminar_distribuidor.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert(data.message);
                                window.loadContent('distribuidores.php');
                            } else {
                                alert('Error: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error al eliminar distribuidor.');
                        });
                }
            }
        });
    }

    // --- Lógica para ACTUALIZAR (Guardar Edición) ---
    const updateDistributorBtn = document.getElementById('updateDistributorBtn');
    if (updateDistributorBtn) {
        // Clonar para limpiar listeners previos
        const newUpdateBtn = updateDistributorBtn.cloneNode(true);
        updateDistributorBtn.parentNode.replaceChild(newUpdateBtn, updateDistributorBtn);

        newUpdateBtn.addEventListener('click', function (event) {
            event.preventDefault();

            const codigo = document.getElementById('edit-distributor-code').value;
            const razonSocial = document.getElementById('edit-distributor-razon-social').value;
            const errorContainer = document.getElementById('edit-error-container');

            if (!razonSocial) {
                alert("La razón social es obligatoria.");
                return;
            }

            // Limpiar errores
            if (errorContainer) errorContainer.innerHTML = '';

            const formData = new FormData();
            formData.append('codigo', codigo);
            formData.append('razon_social', razonSocial);

            fetch('ajax/editar_distribuidor.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Cerrar modal
                        const editModalEl = document.getElementById('editDistributorModal');
                        const editModal = bootstrap.Modal.getInstance(editModalEl);
                        if (editModal) editModal.hide();

                        alert(data.message);
                        window.loadContent('distribuidores.php');
                    } else {
                        // Manejo de errores (Duplicados)
                        let errorHtml = `<div class="alert alert-danger">
                        <strong>Error:</strong> ${data.message}
                    </div>`;

                        if (data.duplicados && data.duplicados.length > 0) {
                            errorHtml += `<div class="card mt-2 border-danger">
                            <div class="card-header bg-danger text-white py-1">Coincidencia</div>
                            <ul class="list-group list-group-flush">`;
                            data.duplicados.forEach(dup => {
                                errorHtml += `<li class="list-group-item">
                                ${dup.razon_social} <span class="badge bg-secondary">${dup.codigo}</span>
                            </li>`;
                            });
                            errorHtml += `</ul></div>`;
                        }

                        if (errorContainer) {
                            errorContainer.innerHTML = errorHtml;
                        } else {
                            alert(data.message);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("Error técnico al actualizar.");
                });
        });
    }
}