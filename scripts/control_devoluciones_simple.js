// Control de Devoluciones - Versión Completa basada en el servidor
export function initControlDevoluciones() {
    console.log('Control de Devoluciones - Iniciando versión completa...');
    
    // Función helper para mostrar notificaciones sin pausar
    const showNotification = (message, type = 'info') => {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.body.appendChild(alertDiv);
        setTimeout(() => alertDiv.remove(), 3000);
    };

    const filterForm = document.getElementById('filter-form');
    const devolucionesTableBody = document.getElementById('devoluciones-control-table-body');
    const reviewModal = new bootstrap.Modal(document.getElementById('reviewModal'));
    const modalDevId = document.getElementById('modal-dev-id');
    const modalCliente = document.getElementById('modal-cliente');
    const modalFecha = document.getElementById('modal-fecha');
    const modalProductsTable = document.getElementById('modal-products-table');

    let currentDevolucionId = null;

    // Finalizar botón dentro del modal
    const finalizarBtn = document.getElementById('finalizarBtn');
    if (finalizarBtn) {
        finalizarBtn.addEventListener('click', async function() {
            if (!currentDevolucionId) {
                showNotification('No hay devolución seleccionada', 'error');
                return;
            }
            
            const confirmed = await createConfirmationModal({
                type: 'success',
                icon: '🏁',
                title: 'Finalizar Devolución',
                message: '¿Está seguro que desea finalizar esta devolución?',
                details: 'Esta acción actualizará automáticamente el estado de la devolución y procesará todas las decisiones de aceptación/rechazo. No se podrá revertir.',
                confirmText: 'Sí, Finalizar',
                confirmIcon: '<i class="bi bi-check-circle"></i>',
                cancelText: 'No, Cancelar'
            });

            if (confirmed) {
                finalizarDevolucion(currentDevolucionId);
            }
        });
    }

    // Función para obtener las devoluciones del servidor con filtros
    const fetchDevoluciones = async () => {
        const formData = new FormData(filterForm); 

        try {
            const response = await fetch('ajax/control_devolucion/obtener_devoluciones_control.php', {
                method: 'POST',
                body: formData
            });
            const data = await parseJSONResponse(response);
            renderTable(data.devoluciones);
        } catch (error) {
            console.error('Error al obtener las devoluciones:', error);
            devolucionesTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error al cargar las devoluciones.</td></tr>';
        }
    };

    // Helper para parsear respuesta y capturar cuerpos no-JSON
    const parseJSONResponse = async (response) => {
        const text = await response.text();
        try {
            // Intentar parsear directamente
            return JSON.parse(text);
        } catch (e) {
            // Si falla, intentar extraer JSON de respuesta que puede tener warnings PHP
            const jsonMatch = text.match(/({.*})\s*$/s);
            if (jsonMatch) {
                try {
                    return JSON.parse(jsonMatch[1]);
                } catch (e2) {
                    console.error('No se pudo extraer JSON válido:', jsonMatch[1]);
                }
            }
            console.error('Respuesta no JSON recibida desde', response.url, '>>', text);
            throw new Error('Respuesta inválida del servidor: ' + text);
        }
    };

    // Función para crear modales de confirmación dinámicos
    const createConfirmationModal = (config) => {
        return new Promise((resolve) => {
            // Eliminar modal existente si existe
            const existingModal = document.getElementById('dynamicConfirmModal');
            if (existingModal) {
                existingModal.remove();
            }

            // Crear nuevo modal
            const modalHTML = `
                <div class="modal fade" id="dynamicConfirmModal" tabindex="-1" aria-labelledby="dynamicConfirmModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header ${config.type === 'danger' ? 'bg-danger text-white' : config.type === 'success' ? 'bg-success text-white' : 'bg-primary text-white'}">
                                <h5 class="modal-title" id="dynamicConfirmModalLabel">
                                    ${config.icon || ''} ${config.title}
                                </h5>
                                <button type="button" class="btn-close ${config.type === 'danger' || config.type === 'success' || config.type === 'primary' ? 'btn-close-white' : ''}" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                ${config.message}
                                ${config.details ? `<div class="mt-3 p-3 bg-light rounded"><small class="text-muted">${config.details}</small></div>` : ''}
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle"></i> ${config.cancelText || 'Cancelar'}
                                </button>
                                <button type="button" class="btn ${config.type === 'danger' ? 'btn-danger' : config.type === 'success' ? 'btn-success' : 'btn-primary'}" id="confirmActionBtn">
                                    ${config.confirmIcon || ''} ${config.confirmText || 'Confirmar'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Agregar modal al DOM
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            // Inicializar modal de Bootstrap
            const modal = new bootstrap.Modal(document.getElementById('dynamicConfirmModal'));
            
            // Configurar eventos
            let isConfirmed = false;
            const confirmBtn = document.getElementById('confirmActionBtn');
            confirmBtn.addEventListener('click', () => {
                isConfirmed = true;
                modal.hide();
            });

            // Resolver al terminar de ocultarse (así esperamos la animación)
            document.getElementById('dynamicConfirmModal').addEventListener('hidden.bs.modal', () => {
                document.getElementById('dynamicConfirmModal').remove();
                // Limpieza de seguridad por si Bootstrap se traba
                document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                document.body.classList.remove('modal-open');
                document.body.style.paddingRight = '';
                
                resolve(isConfirmed);
            });

            // Mostrar modal
            modal.show();
        });
    };

    // Renderiza la tabla de devoluciones
    const renderTable = (devoluciones) => {
        devolucionesTableBody.innerHTML = '';
        if (devoluciones.length === 0) {
            devolucionesTableBody.innerHTML = '<tr><td colspan="5" class="text-center">No se encontraron devoluciones que coincidan con los filtros.</td></tr>';
            return;
        }

        devoluciones.forEach(dev => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${dev.distribuidor_numero}</td>
                <td>${dev.nombre_distribuidor}</td>
                <td>${dev.fecha_ingresa}</td>
                <td><span class="badge ${dev.nombre_estado === 'Recibida' ? 'bg-warning' : 'bg-primary'}">${dev.nombre_estado}</span></td>
                <td>
                    <button class="btn btn-sm btn-primary review-btn" data-devolucion-id="${dev.id}">Revisar</button>
                </td>
            `;
            devolucionesTableBody.appendChild(row);
        });

        devolucionesTableBody.querySelectorAll('.review-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-devolucion-id');
                fetchDetalleDevolucion(id);
            });
        });
    };

    // Obtiene y muestra el detalle de la devolución en el modal
    const fetchDetalleDevolucion = async (id) => {
        try {
            const response = await fetch(`ajax/control_devolucion/obtener_detalle_control.php?id=${id}`);
            const data = await parseJSONResponse(response);

            if (data.success) {
                currentDevolucionId = id;
                modalDevId.textContent = `#${id}`;
                modalCliente.textContent = `${data.devolucion.distribuidor_numero} - ${data.devolucion.nombre_distribuidor}`;
                modalFecha.textContent = data.devolucion.fecha_ingresa;

                // Limpiar tabla de productos
                modalProductsTable.innerHTML = '';

                data.productos.forEach(product => {
                    const row = document.createElement('tr');
                    
                    // Calcular cantidades disponibles
                    const cantidadOriginal = parseInt(product.cantidad) || 0;
                    const totalRechazado = parseInt(product.total_rechazado) || 0;
                    const totalAceptado = parseInt(product.total_aceptado) || 0;
                    const cantidadDisponible = cantidadOriginal - totalRechazado - totalAceptado;
                    
                    // Determinar estado actual del producto
                    let estadoTexto = '';
                    let estadoClase = 'bg-warning';
                    
                    if (totalRechazado > 0 && totalAceptado > 0) {
                        estadoTexto = `Mixto: ${totalAceptado} aceptado, ${totalRechazado} rechazado`;
                        estadoClase = 'bg-info';
                    } else if (totalRechazado > 0) {
                        if (cantidadDisponible > 0) {
                            estadoTexto = `Parcial: ${totalRechazado} rechazado, ${cantidadDisponible} disponible`;
                            estadoClase = 'bg-warning';
                        } else {
                            estadoTexto = `Rechazado completo (${totalRechazado})`;
                            estadoClase = 'bg-danger';
                        }
                    } else if (totalAceptado > 0) {
                        if (cantidadDisponible > 0) {
                            estadoTexto = `Parcial: ${totalAceptado} aceptado, ${cantidadDisponible} disponible`;
                            estadoClase = 'bg-info';
                        } else {
                            estadoTexto = `Aceptado completo (${totalAceptado})`;
                            estadoClase = 'bg-success';
                        }
                    } else {
                        estadoTexto = `Pendiente: ${cantidadDisponible} disponible (original ${cantidadOriginal})`;
                        estadoClase = 'bg-warning';
                    }

                    row.innerHTML = `
                        <td>${product.nombre_producto}</td>
                        <td><strong>${cantidadDisponible}</strong> <small class="text-muted">(original ${cantidadOriginal})</small></td>
                        <td>${product.kg || 0}</td>
                        <td>${product.motivo_devolucion || 'N/A'}</td>
                        <td>${product.vencimiento || 'N/A'}</td>
                        <td><span class="badge ${estadoClase}">${estadoTexto}</span></td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-success accept-product-btn" 
                                        data-detalle-id="${product.id}" data-cantidad-original="${product.cantidad}">
                                    Aceptar
                                </button>
                                <button type="button" class="btn btn-sm btn-danger reject-product-btn" 
                                        data-detalle-id="${product.id}" data-cantidad-original="${product.cantidad}">
                                    Rechazar
                                </button>
                            </div>
                        </td>
                    `;
                    modalProductsTable.appendChild(row);

                    // Si hay historial de decisiones, mostrarlos como filas separadas debajo
                    if (product.rechazos_raw || product.aceptados_raw) {
                        const historialRow = document.createElement('tr');
                        historialRow.classList.add('table-light', 'border-0');
                        historialRow.innerHTML = `
                            <td colspan="7" class="p-0" style="background-color: #f8f9fa;">
                                <div class="p-3" style="border-left: 4px solid #6c757d;">
                                    <h6 class="mb-3 text-dark">
                                        <i class="bi bi-clock-history"></i> Historial de Decisiones
                                    </h6>
                                </div>
                            </td>
                        `;
                        modalProductsTable.appendChild(historialRow);

                        // Mostrar rechazos si existen
                        if (product.rechazos_raw && product.rechazos_raw.trim()) {
                            const rechazos = product.rechazos_raw.split('||');
                            rechazos.forEach((rechazo, index) => {
                                if (rechazo.trim()) {
                                    const [cantidad, motivo, observacion] = rechazo.split('::');
                                    const rechazoRow = document.createElement('tr');
                                    rechazoRow.classList.add('table-light');
                                    rechazoRow.innerHTML = `
                                        <td class="text-center py-2" style="background-color: #fff5f5;">
                                            <i class="bi bi-x-circle-fill text-danger"></i>
                                        </td>
                                        <td class="py-2" style="background-color: #fff5f5;">
                                            <span class="badge bg-danger">${cantidad || 0} unidades</span>
                                        </td>
                                        <td class="py-2" style="background-color: #fff5f5;">
                                            <strong>RECHAZADO</strong>
                                        </td>
                                        <td class="py-2" style="background-color: #fff5f5;">
                                            <strong class="text-danger">${motivo || 'Sin motivo'}</strong>
                                        </td>
                                        <td class="py-2" style="background-color: #fff5f5;">
                                            ${observacion && observacion !== 'N/A' ? `<small class="text-muted">📝 ${observacion}</small>` : '<small class="text-muted">Sin observaciones</small>'}
                                        </td>
                                        <td class="py-2" style="background-color: #fff5f5;">
                                            <span class="badge bg-danger">Rechazado</span>
                                        </td>
                                        <td class="py-2" style="background-color: #fff5f5;">
                                            <small class="text-muted">Decisión registrada</small>
                                            <br>
                                            <button class="btn btn-xs btn-outline-danger mt-1 delete-reject-btn" 
                                                    data-detalle-id="${product.id}" 
                                                    data-cantidad="${cantidad || 0}" 
                                                    data-motivo="${motivo || 'Sin motivo'}"
                                                    title="Eliminar este rechazo">
                                                <i class="bi bi-trash3-fill"></i> Eliminar
                                            </button>
                                        </td>
                                    `;
                                    modalProductsTable.appendChild(rechazoRow);
                                }
                            });
                        }

                        // Mostrar aceptados si existen
                        if (product.aceptados_raw && product.aceptados_raw.trim()) {
                            const aceptados = product.aceptados_raw.split('||');
                            aceptados.forEach((aceptado, index) => {
                                if (aceptado.trim()) {
                                    const [cantidad, motivo, observacion] = aceptado.split('::');
                                    const aceptadoRow = document.createElement('tr');
                                    aceptadoRow.classList.add('table-light');
                                    aceptadoRow.innerHTML = `
                                        <td class="text-center py-2" style="background-color: #f0fff4;">
                                            <i class="bi bi-check-circle-fill text-success"></i>
                                        </td>
                                        <td class="py-2" style="background-color: #f0fff4;">
                                            <span class="badge bg-success">${cantidad || 0} unidades</span>
                                        </td>
                                        <td class="py-2" style="background-color: #f0fff4;">
                                            <strong>ACEPTADO</strong>
                                        </td>
                                        <td class="py-2" style="background-color: #f0fff4;">
                                            <strong class="text-success">${motivo || 'Aceptado'}</strong>
                                        </td>
                                        <td class="py-2" style="background-color: #f0fff4;">
                                            ${observacion && observacion !== 'N/A' ? `<small class="text-muted">📝 ${observacion}</small>` : '<small class="text-muted">Sin observaciones</small>'}
                                        </td>
                                        <td class="py-2" style="background-color: #f0fff4;">
                                            <span class="badge bg-success">Aceptado</span>
                                        </td>
                                        <td class="py-2" style="background-color: #f0fff4;">
                                            <small class="text-muted">Decisión registrada</small>
                                            <br>
                                            <button class="btn btn-xs btn-outline-danger mt-1 delete-accept-btn" 
                                                    data-detalle-id="${product.id}" 
                                                    data-cantidad="${cantidad || 0}" 
                                                    title="Eliminar esta aceptación">
                                                <i class="bi bi-trash3-fill"></i> Eliminar
                                            </button>
                                        </td>
                                    `;
                                    modalProductsTable.appendChild(aceptadoRow);
                                }
                            });
                        }
                    }
                });

                reviewModal.show();
            } else {
                showNotification('Error al cargar el detalle: ' + (data.error || 'Error desconocido'), 'error');
            }
        } catch (error) {
            console.error('Error al obtener el detalle:', error);
            showNotification('Hubo un error al cargar el detalle de la devolución.', 'error');
        }
    };

    // Manejador para los botones de acción del producto
    modalProductsTable.addEventListener('click', async function(event) {
        if (event.target.classList.contains('reject-product-btn')) {
            const row = event.target.closest('tr');
            const detalleId = event.target.getAttribute('data-detalle-id');
            const cantidadOriginal = parseInt(event.target.getAttribute('data-cantidad-original'), 10) || 0;
            
            console.log('🔍 Botón Rechazar presionado para detalle:', detalleId);
            
            // Buscar la celda que muestra la cantidad disponible actual
            const cantidadCell = row.querySelector('td:nth-child(2)');
            let cantidadDisponible = cantidadOriginal;
            
            if (cantidadCell) {
                const cantidadText = cantidadCell.textContent.trim();
                console.log('🔍 Texto de cantidad:', cantidadText);
                
                const match = cantidadText.match(/^(\d+)/);
                cantidadDisponible = match ? parseInt(match[1]) : cantidadOriginal;
                console.log('🔍 Cantidad disponible calculada:', cantidadDisponible);
            }

            if (cantidadDisponible <= 0) {
                showNotification('No hay cantidad disponible para rechazar en este producto', 'warning');
                return;
            }

            // Crear formulario de rechazo con la misma estructura que "Otro"
            const newRow = document.createElement('tr');
            newRow.classList.add('motivo-rechazo-row');
            const uniqueId = `${detalleId}-${Date.now()}`;
            newRow.innerHTML = `
                <td colspan="7" class="p-3" style="background-color: #f8f9fa; border-left: 4px solid #dc3545;">
                    <div class="row g-2 align-items-center">
                        <div class="col-auto">
                            <label for="cantidad-${uniqueId}" class="form-label mb-0 fw-bold text-danger">Cantidad a Rechazar:</label>
                        </div>
                        <div class="col-auto">
                            <input type="number" id="cantidad-${uniqueId}" class="form-control form-control-sm" style="width: 100px;" value="1" min="1" max="${cantidadDisponible}" data-detalle-id="${detalleId}">
                        </div>
                        <div class="col-auto">
                            <label for="motivo-${uniqueId}" class="form-label mb-0 fw-bold">Motivo:</label>
                        </div>
                        <div class="col-auto">
                            <select id="motivo-${uniqueId}" class="form-select form-select-sm" style="width: 200px;">
                                <option value="">Seleccione un motivo</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <label for="observacion-${uniqueId}" class="form-label mb-0 fw-bold">Observación:</label>
                        </div>
                        <div class="col-auto">
                            <input type="text" id="observacion-${uniqueId}" class="form-control form-control-sm" placeholder="Observación opcional" style="width: 200px;">
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-sm btn-success save-reject-btn" data-detalle-id="${detalleId}" data-unique-id="${uniqueId}" data-cantidad-original="${cantidadDisponible}">
                                <i class="bi bi-check-circle"></i> Guardar
                            </button>
                            <button class="btn btn-sm btn-primary add-another-reject-btn ms-1" data-detalle-id="${detalleId}" data-unique-id="${uniqueId}" data-cantidad-original="${cantidadDisponible}" title="Guardar y agregar otro rechazo">
                                <i class="bi bi-plus-lg"></i> Otro
                            </button>
                            <button class="btn btn-sm btn-outline-danger remove-reject-btn ms-1" data-unique-id="${uniqueId}">
                                <i class="bi bi-x-circle"></i> Cancelar
                            </button>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12">
                            <small class="text-muted">💡 Disponible para rechazo: <strong>${cantidadDisponible} unidades</strong></small>
                        </div>
                    </div>
                </td>
            `;

            // Agregar el formulario después de la fila del producto
            row.parentNode.insertBefore(newRow, row.nextSibling);

            // Cargar motivos de rechazo
            const motivoSelect = document.getElementById(`motivo-${uniqueId}`);
            try {
                const response = await fetch('ajax/control_devolucion/obtener_motivos_rechazo.php');
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const text = await response.text();
                
                if (!text || text.trim() === '') {
                    console.warn('Respuesta vacía de obtener_motivos_rechazo.php');
                    throw new Error('Respuesta vacía del servidor');
                }
                
                const motivos = JSON.parse(text);
                
                if (Array.isArray(motivos)) {
                    if (motivos.length === 0) {
                        console.warn('Array vacío de motivos');
                        showNotification('⚠️ No hay motivos disponibles en el sistema', 'warning');
                    } else {
                        motivos.forEach(motivo => {
                            const option = document.createElement('option');
                            option.value = motivo.id;
                            option.textContent = motivo.nombre;
                            motivoSelect.appendChild(option);
                        });
                        showNotification(`Formulario creado. Disponible: ${cantidadDisponible} unidades`, 'info');
                    }
                } else if (motivos.error) {
                    throw new Error(motivos.error);
                } else {
                    throw new Error('Formato de respuesta inválido: ' + typeof motivos);
                }
            } catch (error) {
                console.error('❌ Error al cargar motivos:', error);
                console.error('Stack completo:', error.stack);
                showNotification(`Error al cargar motivos: ${error.message}`, 'error');
            }
            
        } else if (event.target.classList.contains('accept-product-btn')) {
            const row = event.target.closest('tr');
            const detalleId = event.target.getAttribute('data-detalle-id');
            const cantidadOriginal = parseInt(event.target.getAttribute('data-cantidad-original'), 10) || 0;
            
            console.log('🔍 Botón Aceptar presionado para detalle:', detalleId);
            
            // Buscar la celda que muestra la cantidad disponible actual
            const cantidadCell = row.querySelector('td:nth-child(2)');
            let cantidadDisponible = cantidadOriginal;
            
            if (cantidadCell) {
                const cantidadText = cantidadCell.textContent.trim();
                const match = cantidadText.match(/^(\d+)/);
                cantidadDisponible = match ? parseInt(match[1]) : cantidadOriginal;
            }

            if (cantidadDisponible <= 0) {
                showNotification('No hay cantidad disponible para aceptar en este producto', 'warning');
                return;
            }

            // Crear formulario de aceptación
            const newRow = document.createElement('tr');
            newRow.classList.add('motivo-aceptacion-row');
            const uniqueId = `accept-${detalleId}-${Date.now()}`;
            newRow.innerHTML = `
                <td colspan="7" class="p-3" style="background-color: #f0fff4; border-left: 4px solid #198754;">
                    <div class="row g-2 align-items-center">
                        <div class="col-auto">
                            <label for="cantidad-${uniqueId}" class="form-label mb-0 fw-bold text-success">Cantidad a Aceptar:</label>
                        </div>
                        <div class="col-auto">
                            <input type="number" id="cantidad-${uniqueId}" class="form-control form-control-sm border-success" style="width: 100px;" value="${cantidadDisponible}" min="1" max="${cantidadDisponible}" data-detalle-id="${detalleId}">
                        </div>
                        <div class="col-auto">
                            <label for="motivo-${uniqueId}" class="form-label mb-0 fw-bold text-success">Motivo:</label>
                        </div>
                        <div class="col-auto">
                            <select id="motivo-${uniqueId}" class="form-select form-select-sm border-success" style="width: 200px;">
                                <option value="">Seleccione un motivo</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <label for="observacion-${uniqueId}" class="form-label mb-0 fw-bold text-success">Observación:</label>
                        </div>
                        <div class="col-auto">
                            <input type="text" id="observacion-${uniqueId}" class="form-control form-control-sm border-success" placeholder="Observación opcional" style="width: 200px;">
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-sm btn-success save-accept-btn" data-detalle-id="${detalleId}" data-unique-id="${uniqueId}" data-cantidad-original="${cantidadDisponible}">
                                <i class="bi bi-check-circle"></i> Guardar
                            </button>
                            <button class="btn btn-sm btn-outline-success add-another-accept-btn ms-1" data-detalle-id="${detalleId}" data-unique-id="${uniqueId}" data-cantidad-original="${cantidadDisponible}" title="Guardar y agregar otra aceptación">
                                <i class="bi bi-plus-lg"></i> Otro
                            </button>
                            <button class="btn btn-sm btn-outline-danger remove-accept-btn ms-1" data-unique-id="${uniqueId}">
                                <i class="bi bi-x-circle"></i> Cancelar
                            </button>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12">
                            <small class="text-muted">💡 Disponible para aceptar: <strong>${cantidadDisponible} unidades</strong></small>
                        </div>
                    </div>
                </td>
            `;

            // Agregar el formulario después de la fila del producto
            row.parentNode.insertBefore(newRow, row.nextSibling);

            // Cargar motivos de aceptación (usamos mismos motivos de devolucion como pidió el usuario)
            const motivoSelect = document.getElementById(`motivo-${uniqueId}`);
            try {
                // Podemos reutilizar motivos de devolución consultando directamente a BD
                const response = await fetch('ajax/control_devolucion/obtener_motivos_aceptacion.php');
                
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                
                const text = await response.text();
                const motivos = JSON.parse(text);
                
                if (Array.isArray(motivos)) {
                    motivos.forEach(motivo => {
                        const option = document.createElement('option');
                        option.value = motivo.id;
                        option.textContent = motivo.motivos || motivo.nombre;
                        motivoSelect.appendChild(option);
                    });
                    showNotification('Formulario de aceptación creado', 'info');
                } else {
                    throw new Error(motivos.error || 'Respuesta inválida');
                }
            } catch (error) {
                console.error('❌ Error al cargar motivos:', error);
                showNotification(`Error al cargar motivos: ${error.message}`, 'error');
            }
        }
    });

    // Manejador para botones dinámicos de rechazo
    modalProductsTable.addEventListener('click', async function(event) {
        if (event.target.classList.contains('save-reject-btn') || event.target.classList.contains('add-another-reject-btn') || 
            event.target.classList.contains('save-accept-btn') || event.target.classList.contains('add-another-accept-btn')) {
            
            const isAcceptance = event.target.classList.contains('save-accept-btn') || event.target.classList.contains('add-another-accept-btn');
            const esOtro = event.target.classList.contains('add-another-reject-btn') || event.target.classList.contains('add-another-accept-btn');
            
            const accionTxt = isAcceptance ? 'Aceptar' : 'Rechazar';
            const accionTxtL = isAcceptance ? 'aceptar' : 'rechazar';
            const estado = isAcceptance ? 0 : 1;

            console.log(`🔍 Botón presionado: ${esOtro ? 'Otro' : 'Guardar'} [${accionTxt}]`);
            
            const detalleId = event.target.getAttribute('data-detalle-id');
            const uniqueId = event.target.getAttribute('data-unique-id');
            
            const cantidadInput = document.getElementById(`cantidad-${uniqueId}`);
            const motivoSelect = document.getElementById(`motivo-${uniqueId}`);
            const observacionInput = document.getElementById(`observacion-${uniqueId}`);
            
            const cantidad = parseInt(cantidadInput.value, 10);
            const motivoId = motivoSelect.value;
            const observacion = observacionInput.value.trim();
            
            if (!cantidad || cantidad <= 0) {
                showNotification(`Debe especificar una cantidad válida para ${accionTxtL}`, 'error');
                cantidadInput.focus();
                return;
            }
            
            if (!motivoId) {
                showNotification(`Debe seleccionar un motivo de ${isAcceptance ? 'aceptación' : 'rechazo'}`, 'error');
                motivoSelect.focus();
                return;
            }

            const motivoTexto = motivoSelect.options[motivoSelect.selectedIndex].text;
            
            const modalConfig = {
                type: isAcceptance ? 'success' : 'danger',
                icon: isAcceptance ? '✅' : '❌',
                title: esOtro ? `Guardar y Continuar ${isAcceptance ? 'Aceptando' : 'Rechazando'}` : `Guardar ${isAcceptance ? 'Aceptación' : 'Rechazo'}`,
                message: `¿Confirma que desea ${accionTxtL} ${cantidad} unidad${cantidad > 1 ? 'es' : ''} de este producto?`,
                details: `<strong>Motivo:</strong> ${motivoTexto}<br>${observacion ? `<strong>Observación:</strong> ${observacion}` : '<em>Sin observaciones</em>'}${esOtro ? `<br><br>📋 <strong>Después de guardar se creará un nuevo formulario para ${accionTxtL} cantidad adicional.</strong>` : ''}`,
                confirmText: esOtro ? 'Sí, Guardar y Continuar' : `Sí, Guardar ${isAcceptance ? 'Aceptación' : 'Rechazo'}`,
                confirmIcon: isAcceptance ? '<i class="bi bi-check-circle"></i>' : '<i class="bi bi-x-circle"></i>',
                cancelText: 'Cancelar'
            };
            
            const confirmed = await createConfirmationModal(modalConfig);
            if (!confirmed) return;
            
            event.target.disabled = true;
            event.target.innerHTML = '<i class="bi bi-hourglass-split"></i> Guardando...';
            
            try {
                const success = await updateProductStatus(detalleId, estado, motivoId, observacion, cantidad, uniqueId);
                
                if (success) {
                    if (esOtro) {
                        console.log('🔍 Ejecutando lógica del botón Otro');
                        
                        // Para el botón Otro: guardar los datos necesarios antes de recargar
                        const cantidadOriginal = parseInt(event.target.getAttribute('data-cantidad-original'));
                        console.log('🔍 Cantidad original:', cantidadOriginal);
                        
                        showNotification(`${accionTxt} guardado. Verificando cantidad disponible...`, 'success');
                        
                        // Recargar datos primero
                        console.log('🔍 Recargando datos...');
                        await fetchDetalleDevolucion(currentDevolucionId);
                        
                        // Esperar un poco para que la recarga termine y luego verificar disponibilidad
                        setTimeout(async () => {
                            console.log('🔍 Buscando producto actualizado...');
                            // Buscar el producto actualizado en la tabla
                            const updatedRows = document.querySelectorAll('[data-detalle-id]');
                            let targetRow = null;
                            
                            for (let row of updatedRows) {
                                const rowId = row.getAttribute('data-detalle-id');
                                if (rowId === detalleId.toString()) {
                                    targetRow = row.closest('tr');
                                    break;
                                }
                            }
                            
                            if (targetRow) {
                                const cantidadCell = targetRow.querySelector('td:nth-child(2)');
                                if (cantidadCell) {
                                    const cantidadText = cantidadCell.textContent.trim();
                                    const match = cantidadText.match(/^(\d+)/);
                                    const cantidadDisponible = match ? parseInt(match[1]) : 0;
                                    
                                    if (cantidadDisponible > 0) {
                                        // Crear formulario de continuación con la cantidad disponible correcta
                                        const newRow = document.createElement('tr');
                                        newRow.classList.add(isAcceptance ? 'motivo-aceptacion-row' : 'motivo-rechazo-row');
                                        const uniqueId = `${isAcceptance ? 'accept-' : ''}${detalleId}-${Date.now()}`;
                                        
                                        const bgClass = isAcceptance ? '#f0fff4' : '#f8f9fa';
                                        const borderClass = isAcceptance ? '#198754' : '#dc3545';
                                        const textClass = isAcceptance ? 'text-success' : 'text-danger';
                                        const btnMainClass = isAcceptance ? 'btn-success save-accept-btn' : 'btn-success save-reject-btn';
                                        const btnOtroClass = isAcceptance ? 'btn-outline-success add-another-accept-btn' : 'btn-primary add-another-reject-btn';
                                        const btnCancelClass = isAcceptance ? 'btn-outline-danger remove-accept-btn' : 'btn-outline-danger remove-reject-btn';

                                        newRow.innerHTML = `
                                            <td colspan="7" class="p-3" style="background-color: ${bgClass}; border-left: 4px solid ${borderClass};">
                                                <div class="row g-2 align-items-center">
                                                    <div class="col-auto">
                                                        <label for="cantidad-${uniqueId}" class="form-label mb-0 fw-bold ${textClass}">Cantidad a ${accionTxt}:</label>
                                                    </div>
                                                    <div class="col-auto">
                                                        <input type="number" id="cantidad-${uniqueId}" class="form-control form-control-sm" style="width: 100px;" value="${cantidadDisponible}" min="1" max="${cantidadDisponible}" data-detalle-id="${detalleId}">
                                                    </div>
                                                    <div class="col-auto">
                                                        <label for="motivo-${uniqueId}" class="form-label mb-0 fw-bold">Motivo:</label>
                                                    </div>
                                                    <div class="col-auto">
                                                        <select id="motivo-${uniqueId}" class="form-select form-select-sm" style="width: 200px;">
                                                            <option value="">Seleccione un motivo</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-auto">
                                                        <label for="observacion-${uniqueId}" class="form-label mb-0 fw-bold">Observación:</label>
                                                    </div>
                                                    <div class="col-auto">
                                                        <input type="text" id="observacion-${uniqueId}" class="form-control form-control-sm" placeholder="Observación opcional" style="width: 200px;">
                                                    </div>
                                                    <div class="col-auto">
                                                        <button class="btn btn-sm ${btnMainClass}" data-detalle-id="${detalleId}" data-unique-id="${uniqueId}" data-cantidad-original="${cantidadDisponible}">
                                                            <i class="bi bi-check-circle"></i> Guardar
                                                        </button>
                                                        <button class="btn btn-sm ${btnOtroClass} ms-1" data-detalle-id="${detalleId}" data-unique-id="${uniqueId}" data-cantidad-original="${cantidadDisponible}" title="Guardar y agregar otro">
                                                            <i class="bi bi-plus-lg"></i> Otro
                                                        </button>
                                                        <button class="btn btn-sm ${btnCancelClass} ms-1" data-unique-id="${uniqueId}">
                                                            <i class="bi bi-x-circle"></i> Cancelar
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="row mt-2">
                                                    <div class="col-12">
                                                        <small class="text-muted">💡 Disponible para ${accionTxtL}: <strong>${cantidadDisponible} unidades</strong></small>
                                                    </div>
                                                </div>
                                            </td>
                                        `;
                                        
                                        targetRow.parentNode.insertBefore(newRow, targetRow.nextSibling);
                                        
                                        const motivoSelect = document.getElementById(`motivo-${uniqueId}`);
                                        try {
                                            const endpointValue = isAcceptance ? 'obtener_motivos_aceptacion.php' : 'obtener_motivos_rechazo.php';
                                            const response = await fetch(`ajax/control_devolucion/${endpointValue}`);
                                            
                                            if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                                            const text = await response.text();
                                            const motivos = JSON.parse(text);
                                            
                                            if (Array.isArray(motivos)) {
                                                motivos.forEach(motivo => {
                                                    const option = document.createElement('option');
                                                    option.value = motivo.id;
                                                    option.textContent = motivo.motivos || motivo.nombre;
                                                    motivoSelect.appendChild(option);
                                                });
                                            } else if (motivos.error) {
                                                throw new Error(motivos.error);
                                            }
                                        } catch (error) {
                                            console.error('Error al cargar motivos (segundo):', error);
                                        }
                                        
                                        showNotification(`Nuevo formulario de ${accionTxtL} creado. Disponible: ${cantidadDisponible} unidades`, 'info');
                                    } else {
                                        showNotification(`No hay más cantidad disponible para ${accionTxtL} en este producto`, 'warning');
                                    }
                                } else {
                                    showNotification('No se pudo verificar la cantidad disponible', 'warning');
                                }
                            } else {
                                showNotification('No se pudo encontrar el producto actualizado', 'warning');
                            }
                        }, 1000);
                        
                    } else {
                        // Para el botón Guardar normal: simplemente recargar
                        await fetchDetalleDevolucion(currentDevolucionId);
                        showNotification(`${accionTxt} guardado exitosamente`, 'success');
                    }
                } else {
                    // Restaurar botón en caso de error
                    if (esOtro) {
                        event.target.innerHTML = '<i class="bi bi-plus-lg"></i> Otro';
                    } else {
                        event.target.innerHTML = '<i class="bi bi-check-circle"></i> Guardar';
                    }
                    event.target.disabled = false;
                }
            } catch (error) {
                console.error(`Error en el manejo de ${accionTxtL}:`, error);
                showNotification(`Error al procesar el ${accionTxtL}`, 'error');
                
                if (esOtro) {
                    event.target.innerHTML = '<i class="bi bi-plus-lg"></i> Otro';
                } else {
                    event.target.innerHTML = '<i class="bi bi-check-circle"></i> Guardar';
                }
                event.target.disabled = false;
            }
        } else if (event.target.classList.contains('remove-reject-btn') || event.target.classList.contains('remove-accept-btn')) {
            const uniqueId = event.target.getAttribute('data-unique-id');
            const row = document.querySelector(`#cantidad-${uniqueId}`).closest('tr');
            row.remove();
            showNotification('Formulario cancelado', 'info');
        } else if (event.target.classList.contains('delete-reject-btn')) {
            // Eliminar rechazo existente
            const detalleId = event.target.getAttribute('data-detalle-id');
            const cantidad = event.target.getAttribute('data-cantidad');
            const motivo = event.target.getAttribute('data-motivo');
            
            const confirmed = await createConfirmationModal({
                type: 'danger',
                icon: '🗑️',
                title: 'Eliminar Rechazo',
                message: '¿Está seguro que desea eliminar este registro de rechazo?',
                details: `<strong>Cantidad:</strong> ${cantidad} unidad${cantidad > 1 ? 'es' : ''}<br><strong>Motivo:</strong> ${motivo}<br><br>⚠️ <strong>Esta acción no se puede deshacer.</strong>`,
                confirmText: 'Sí, Eliminar',
                confirmIcon: '<i class="bi bi-trash3-fill"></i>',
                cancelText: 'No, Conservar'
            });
            
            if (confirmed) {
                try {
                    const success = await deleteRejection(detalleId, cantidad, motivo);
                    if (success) {
                        await fetchDetalleDevolucion(currentDevolucionId);
                        showNotification('Rechazo eliminado exitosamente', 'success');
                    } else {
                        showNotification('Error al eliminar el rechazo', 'error');
                    }
                } catch (error) {
                    console.error('Error al eliminar rechazo:', error);
                    showNotification('Error al eliminar el rechazo', 'error');
                }
            }
        } else if (event.target.classList.contains('delete-accept-btn')) {
            // Eliminar aceptación existente
            if (confirm('¿Está seguro que desea eliminar esta aceptación? Esta acción no se puede deshacer.')) {
                const detalleId = event.target.getAttribute('data-detalle-id');
                const cantidad = event.target.getAttribute('data-cantidad');
                
                try {
                    const success = await deleteAcceptance(detalleId, cantidad);
                    if (success) {
                        await fetchDetalleDevolucion(currentDevolucionId);
                        showNotification('Aceptación eliminada exitosamente', 'success');
                    } else {
                        showNotification('Error al eliminar la aceptación', 'error');
                    }
                } catch (error) {
                    console.error('Error al eliminar aceptación:', error);
                    showNotification('Error al eliminar la aceptación', 'error');
                }
            }
        }
    });

    // Función para actualizar el estado de un producto individual
    const updateProductStatus = async (detalleId, estado, motivoId = null, observacion = '', cantidadRechazada = null, uniqueId = null) => {
        console.log('Enviando datos:', { detalleId, estado, motivoId, observacion, cantidadRechazada });
        
        const formData = new FormData();
        formData.append('detalle_id', detalleId);
        formData.append('estado', estado);
        if (motivoId) formData.append('motivo_id', motivoId);
        if (observacion) formData.append('observacion', observacion);
        if (cantidadRechazada) formData.append('cantidad_rechazada', cantidadRechazada);

        try {
            const response = await fetch('ajax/control_devolucion/update_product_status.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await parseJSONResponse(response);
            
            if (data.success) {
                showNotification(data.message || 'Estado del producto actualizado correctamente', 'success');
                
                if (uniqueId) {
                    // Eliminar el formulario de rechazo después de guardarlo
                    const row = document.querySelector(`#cantidad-${uniqueId}`).closest('tr');
                    if (row) row.remove();
                }
                
                // Recargar el detalle para mostrar cambios
                await fetchDetalleDevolucion(currentDevolucionId);
                return true;
            } else {
                showNotification('Error: ' + (data.message || 'Error desconocido'), 'error');
                return false;
            }
        } catch (error) {
            console.error('Error al actualizar el estado del producto:', error);
            showNotification('Error al actualizar el estado del producto', 'error');
            return false;
        }
    };
    
    // Nueva función para finalizar la devolución
    const finalizarDevolucion = async (id) => {
        const formData = new FormData();
        formData.append('id', id);

        try {
            const response = await fetch('ajax/control_devolucion/finalizar_devolucion.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await parseJSONResponse(response);
            
            if (data.success) {
                showNotification(data.message || 'Devolución finalizada correctamente', 'success');
                reviewModal.hide();
                fetchDevoluciones(); // Recargar la lista
            } else {
                showNotification('Error: ' + (data.message || 'Error desconocido'), 'error');
            }
        } catch (error) {
            console.error('Error al finalizar la devolución:', error);
            showNotification('Error al finalizar la devolución', 'error');
        }
    };

    // Manejar el formulario de filtro
    filterForm.addEventListener('submit', function(event) {
        event.preventDefault();
        fetchDevoluciones();
    });

    // Carga inicial de devoluciones al entrar a la página
    fetchDevoluciones();
    
    // Función para eliminar un rechazo específico
    const deleteRejection = async (detalleId, cantidad, motivo) => {
        const formData = new FormData();
        formData.append('detalle_id', detalleId);
        formData.append('cantidad', cantidad);
        formData.append('motivo', motivo);
        formData.append('action', 'delete_rejection');
        
        try {
            const response = await fetch('ajax/control_devolucion/delete_decision.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await parseJSONResponse(response);
            return data.success;
        } catch (error) {
            console.error('Error al eliminar rechazo:', error);
            return false;
        }
    };
    
    // Función para eliminar una aceptación específica
    const deleteAcceptance = async (detalleId, cantidad) => {
        const formData = new FormData();
        formData.append('detalle_id', detalleId);
        formData.append('cantidad', cantidad);
        formData.append('action', 'delete_acceptance');
        
        try {
            const response = await fetch('ajax/control_devolucion/delete_decision.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await parseJSONResponse(response);
            return data.success;
        } catch (error) {
            console.error('Error al eliminar aceptación:', error);
            return false;
        }
    };
    
    console.log('Control de Devoluciones - Inicializado correctamente con funcionalidad completa');
}