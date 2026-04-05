// Inicialización y eventos para la gestión de usuarios
export function initUsuarios() {
    const btnGuardar = document.getElementById('btnGuardarUsuario');
    const searchUsuario = document.getElementById('search-usuario');
    const usuariosTableBody = document.querySelector('#usuarios-table tbody');

    // Lógica para el buscador dinámico
    if (searchUsuario && usuariosTableBody) {
        searchUsuario.addEventListener('input', function () {
            const searchText = this.value.toLowerCase();
            const rows = usuariosTableBody.querySelectorAll('tr');
            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                if (rowText.includes(searchText)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Lógica para Crear/Editar
    if (btnGuardar) {
        const newBtnGuardar = btnGuardar.cloneNode(true);
        btnGuardar.parentNode.replaceChild(newBtnGuardar, btnGuardar);
        
        newBtnGuardar.addEventListener('click', function(e) {
            e.preventDefault();
            const form = document.getElementById('form-usuario');
            const errorContainer = document.getElementById('usuario-error-container');
            
            const formData = new FormData(form);
            const id = formData.get('id');
            const password = formData.get('password');
            
            errorContainer.innerHTML = '';

            // Validaciones
            if (!formData.get('nombre') || !formData.get('rol_id')) {
                errorContainer.innerHTML = '<div class="alert alert-danger">El nombre y rol son obligatorios.</div>';
                return;
            }
            if (!id && !password) {
                errorContainer.innerHTML = '<div class="alert alert-danger">Debes ingresar una contraseña para crear el usuario.</div>';
                return;
            }

            fetch('ajax/guardar_usuario.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('usuarioModal'));
                    if (modal) modal.hide();
                    
                    // Mostrar modal de éxito o alert
                    const successModalEl = document.getElementById('successModal');
                    if (successModalEl) {
                        const successModal = bootstrap.Modal.getInstance(successModalEl) || new bootstrap.Modal(successModalEl);
                        successModal.show();
                        successModalEl.addEventListener('hidden.bs.modal', function () {
                            // Solo recargar si estamos en la vista de gestionar usuarios (para listado completo)
                            if (document.getElementById('usuarios-table')) {
                                window.loadContent('gestionar_usuarios.php');
                            }
                        }, { once: true });
                    } else {
                        alert(data.message);
                        if (document.getElementById('usuarios-table')) {
                            window.loadContent('gestionar_usuarios.php');
                        }
                    }
                } else {
                    errorContainer.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(err => {
                errorContainer.innerHTML = '<div class="alert alert-danger">Error de conexión al servidor.</div>';
                console.error(err);
            });
        });
    }

    // Delegación de eventos para Editar y Eliminar
    if (usuariosTableBody) {
        const newBody = usuariosTableBody.cloneNode(true);
        usuariosTableBody.parentNode.replaceChild(newBody, usuariosTableBody);

        newBody.addEventListener('click', function (e) {
            // Editar
            const editBtn = e.target.closest('.btn-edit');
            if (editBtn) {
                const row = editBtn.closest('tr');
                window.abrirModalUsuario({
                    id: row.getAttribute('data-id'),
                    nombre: row.getAttribute('data-nombre'),
                    rol_id: row.getAttribute('data-rol'),
                    distribuidor_codigo: row.getAttribute('data-distribuidor')
                });
            }

            // Eliminar
            const deleteBtn = e.target.closest('.btn-delete');
            if (deleteBtn) {
                const row = deleteBtn.closest('tr');
                const id = row.getAttribute('data-id');
                const nombre = row.getAttribute('data-nombre');
                
                if (confirm(`¿Estás seguro de eliminar el acceso para el usuario "${nombre}"?`)) {
                    const formData = new FormData();
                    formData.append('id', id);

                    fetch('ajax/eliminar_usuario.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            window.loadContent('gestionar_usuarios.php');
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(err => console.error(err));
                }
            }
        });
    }
}

// Función global para abrir el modal (incluso desde otras pantallas si el modal existiera en DOM)
window.abrirModalUsuario = function(datos = null) {
    const form = document.getElementById('form-usuario');
    const modalTitle = document.getElementById('modal-title-text');
    const pwdHelp = document.getElementById('usr_password_help');
    const inputPwd = document.getElementById('usr_password');
    const errorContainer = document.getElementById('usuario-error-container');
    
    if (!form) return;
    
    form.reset();
    if(errorContainer) errorContainer.innerHTML = '';

    if (datos && (datos.id || datos.distribuidor_codigo)) {
        // Modo Edición / Sugerencia (por ejemplo de un Distribuidor nuevo)
        document.getElementById('usr_id').value = datos.id || '';
        document.getElementById('usr_nombre').value = datos.nombre || '';
        document.getElementById('usr_distribuidor').value = datos.distribuidor_codigo || '';
        
        if (datos.rol_id) {
            document.getElementById('usr_rol').value = datos.rol_id;
        } else if (datos.distribuidor_codigo) {
            // Si viene sugerido de crear distribuidor, forzar "distribuidor" en el UI.
            // Asumiendo que Distribuidor es el ID=4. (Podríamos buscar la forma de inferirlo de texto)
            const opt = Array.from(document.getElementById('usr_rol').options).find(o => o.text.toLowerCase().includes('distribuidor'));
            if(opt) opt.selected = true;
        }

        if (datos.password) {
            inputPwd.value = datos.password;
        }

        if (datos.id) {
            modalTitle.textContent = "Editar Usuario";
            pwdHelp.style.display = "block";
            inputPwd.required = false;
        } else {
            modalTitle.textContent = "Crear Usuario para Nuevo Distribuidor";
            pwdHelp.style.display = "none";
            inputPwd.required = true;
        }
    } else {
        // Nuevo vacío
        document.getElementById('usr_id').value = '';
        modalTitle.textContent = "Añadir Usuario";
        pwdHelp.style.display = "none";
        inputPwd.required = true;
    }

    const modalEl = document.getElementById('usuarioModal');
    if (modalEl) {
        let modal = bootstrap.Modal.getInstance(modalEl);
        if (!modal) modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
};
