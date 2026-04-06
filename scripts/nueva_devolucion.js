// scripts/nueva_devolucion.js
// TEMPORAL: Comentado para evitar conflictos
// import { guardarTablaEnJSONySQA, guardarSoloJSON } from './guardar_tabla_json.js';
// Última actualización: 2024-12-02 - Modal de éxito implementado

export function initNuevaDevolucion() {
    console.log('¡La función initNuevaDevolucion se ha cargado!');
    
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
    
    // Esperar un momento para que el DOM esté completamente cargado
    setTimeout(() => {
        const productForm = document.getElementById('product-form');
        const returnTableBody = document.getElementById('return-table-body');
        const btnAgregar = document.getElementById('btn-agregar');
        const btnGuardarEnviar = document.getElementById('btn-guardar-enviar');
        const selectDistribuidor = document.getElementById('codigo_distribuidor');
        const numeroDevolucionSpan = document.getElementById('numero_devolucion');
        const nombreClienteSpan = document.getElementById('nombre_cliente');
        
        // Debug: verificar que los elementos existen
        console.log('🔍 Elementos encontrados (después del delay):');
        console.log('🔍 - selectDistribuidor:', !!selectDistribuidor, selectDistribuidor?.id);
        console.log('🔍 - productForm:', !!productForm);
        console.log('🔍 - returnTableBody:', !!returnTableBody);
        console.log('🔍 - numeroDevolucionSpan:', !!numeroDevolucionSpan);
        console.log('🔍 - nombreClienteSpan:', !!nombreClienteSpan);
        
        if (!selectDistribuidor) {
            console.error('🚨 ERROR: No se encontró el elemento selectDistribuidor después del delay');
            return;
        }
        
        let filaEnEdicion = null;

        const actualizarBotonGuardar = () => {
            if (returnTableBody && returnTableBody.rows.length > 0) {
                btnGuardarEnviar?.classList.remove('d-none');
            } else {
                btnGuardarEnviar?.classList.add('d-none');
            }
        };

        const formatearFecha = (fecha) => {
            if (!fecha) return '';
            const [year, month, day] = fecha.split('-');
            return `${day}/${month}/${year}`;
        };

        const desformatearFecha = (fecha) => {
            if (!fecha) return '';
            const [day, month, year] = fecha.split('/');
            return `${year}-${month}-${day}`;
        };

        // Event listener para el selector de distribuidor
        selectDistribuidor.addEventListener('change', function() {
            const distribuidorCodigo = this.value;
            const distribuidorNombre = this.options[this.selectedIndex].text;
            
            console.log('🔍 DISTRIBUIDOR SELECCIONADO:');
            console.log('🔍 Código:', distribuidorCodigo);
            console.log('🔍 Nombre:', distribuidorNombre);
            
            // Verificar que el elemento existe y tiene valor
            if (!distribuidorCodigo || distribuidorCodigo === '' || distribuidorCodigo === null) {
                console.error('🚨 ERROR: distribuidorCodigo está vacío!');
                alert('Error: No se pudo obtener el código del distribuidor seleccionado');
                return;
            }
            
            if (nombreClienteSpan) {
                nombreClienteSpan.textContent = distribuidorNombre.split(' - ')[1] || distribuidorNombre;
            }
            
            const formData = new FormData();
            formData.append('distribuidor_codigo', distribuidorCodigo);
            
            console.log('🔍 Enviando solicitud para obtener número de devolución...');

            fetch('ajax/obtener_devolucion.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('🔍 Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('🔍 Response data completa:', data);
                
                if (!data.success && data.debug) {
                    console.log('🔍 Debug info del servidor:', data.debug);
                }
                
                if (data.success) {
                    if (numeroDevolucionSpan) {
                        numeroDevolucionSpan.textContent = data.numero_devolucion;
                    }
                    if (productForm) {
                        productForm.style.pointerEvents = 'auto';
                        productForm.style.opacity = '1';
                        const codigoProducto = productForm.querySelector('#codigo_producto');
                        if (codigoProducto) {
                            codigoProducto.focus();
                        }
                    }
                } else {
                    console.error('🚨 Error del servidor:', data.message);
                    if (data.debug) {
                        console.error('🚨 Debug info del servidor:', data.debug);
                    }
                    alert('Error del servidor: ' + data.message);
                }
            })
            .catch(error => {
                console.error('🔍 ERROR en obtener_devolucion:', error);
                alert('Error al obtener el número de devolución: ' + error.message);
            });
        });

        // Funciones para manejo de productos
        const editarFila = (boton) => {
            const fila = boton.closest('tr');
            const celdas = fila.querySelectorAll('td');
            
            if (filaEnEdicion && filaEnEdicion !== fila) {
                cancelarEdicionFila(filaEnEdicion);
            }
            
            const cantidad = celdas[1].textContent.trim();
            const kg = celdas[2].textContent.trim();
            const vencimiento = celdas[4].textContent.trim();
            
            celdas[1].innerHTML = `<input type="number" class="form-control form-control-sm" value="${cantidad}" min="1">`;
            celdas[2].innerHTML = `<input type="text" class="form-control form-control-sm" value="${kg}">`;
            celdas[4].innerHTML = `<input type="date" class="form-control form-control-sm" value="${desformatearFecha(vencimiento)}">`;
            
            const celdaAcciones = celdas[celdas.length - 1];
            celdaAcciones.innerHTML = `
                <button type="button" class="btn btn-success btn-sm" onclick="guardarEdicionFila(this)">
                    <i class="bi bi-check"></i>
                </button>
                <button type="button" class="btn btn-secondary btn-sm ms-1" onclick="cancelarEdicionFila(this.closest('tr'))">
                    <i class="bi bi-x"></i>
                </button>
            `;
            
            filaEnEdicion = fila;
        };

        const guardarEdicionFila = (boton) => {
            const fila = boton.closest('tr');
            const inputs = fila.querySelectorAll('input');
            
            const cantidad = parseInt(inputs[0].value);
            const kg = inputs[1].value.trim();
            const vencimientoRaw = inputs[2].value;
            
            if (isNaN(cantidad) || cantidad <= 0) {
                alert('La cantidad debe ser un número mayor a 0');
                return;
            }
            
            const vencimientoFormateado = formatearFecha(vencimientoRaw);
            
            const celdas = fila.querySelectorAll('td');
            celdas[1].textContent = cantidad;
            celdas[2].textContent = kg;
            celdas[4].textContent = vencimientoFormateado;
            
            const celdaAcciones = celdas[celdas.length - 1];
            celdaAcciones.innerHTML = `
                <button type="button" class="btn btn-warning btn-sm editar-fila me-2" onclick="editarFila(this)">
                    <i class="bi bi-pencil"></i>
                </button>
                <button type="button" class="btn btn-danger btn-sm eliminar-fila" onclick="eliminarFila(this)">
                    <i class="bi bi-trash"></i>
                </button>
            `;
            
            filaEnEdicion = null;
        };

        const cancelarEdicionFila = (fila) => {
            const celdas = fila.querySelectorAll('td');
            
            celdas[1].textContent = fila.dataset.originalCantidad || '';
            celdas[2].textContent = fila.dataset.originalKg || '';
            celdas[4].textContent = fila.dataset.originalVencimiento || '';
            
            const celdaAcciones = celdas[celdas.length - 1];
            celdaAcciones.innerHTML = `
                <button type="button" class="btn btn-warning btn-sm editar-fila me-2" onclick="editarFila(this)">
                    <i class="bi bi-pencil"></i>
                </button>
                <button type="button" class="btn btn-danger btn-sm eliminar-fila" onclick="eliminarFila(this)">
                    <i class="bi bi-trash"></i>
                </button>
            `;
            
            filaEnEdicion = null;
        };

        const eliminarFila = (boton) => {
            const fila = boton.closest('tr');
            if (filaEnEdicion === fila) {
                filaEnEdicion = null;
            }
            fila.remove();
            actualizarBotonGuardar();
        };

        const agregarProducto = () => {
            const codigoSelect = document.getElementById('codigo_producto');
            const cantidadInput = document.getElementById('cantidad');
            const kgInput = document.getElementById('kg');
            const motivoSelect = document.getElementById('motivo');
            const vencimientoInput = document.getElementById('vencimiento');
            const observacionesInput = document.getElementById('observaciones');

            if (!codigoSelect.value || codigoSelect.value === '') {
                alert('Selecciona un producto');
                codigoSelect.focus();
                return;
            }

            if (!cantidadInput.value || parseInt(cantidadInput.value) <= 0) {
                alert('Ingresa una cantidad válida');
                cantidadInput.focus();
                return;
            }

            const productoSeleccionado = codigoSelect.options[codigoSelect.selectedIndex];
            const familiaId = productoSeleccionado.dataset.tipo;
            
            // Validacion específica para familia Queso (ID 6)
            if (familiaId === '6') {
                const bgValidatorClass = parseInt(kgInput.value) > 0 || parseFloat(kgInput.value) > 0;
                if (!kgInput.value || !bgValidatorClass) {
                    alert('El campo KG es obligatorio y debe ser mayor a 0 para la familia de Quesos.');
                    kgInput.focus();
                    return;
                }
            }

            if (!motivoSelect.value || motivoSelect.value === '') {
                alert('Selecciona un motivo');
                motivoSelect.focus();
                return;
            }

            const productoTexto = productoSeleccionado.text;
            const productoCodigo = codigoSelect.value;
            const productoId = productoSeleccionado.dataset.id;
            
            // VALIDACIÓN CRÍTICA Y DEBUG DETALLADO
            console.log('🔍 DEBUGGING DETALLADO:');
            console.log('- Option seleccionado:', productoSeleccionado);
            console.log('- HTML completo del option:', productoSeleccionado.outerHTML);
            console.log('- Código del producto (value):', productoCodigo, typeof productoCodigo);
            console.log('- ID único (data-id):', productoId, typeof productoId);
            console.log('- Dataset completo:', productoSeleccionado.dataset);
            
            if (!productoId || productoId === 'undefined' || productoId === productoCodigo) {
                console.error('❌ ERROR CRÍTICO: data-id no es válido');
                alert('ERROR: El sistema no puede obtener el ID único del producto.\ndata-id: ' + productoId + '\nCódigo: ' + productoCodigo);
                return;
            }
            
            console.log('✅ IDs válidos - Código:', productoCodigo, 'ID único:', productoId);
            
            const cantidad = parseInt(cantidadInput.value);
            const kg = kgInput.value || '';
            const motivoSeleccionado = motivoSelect.options[motivoSelect.selectedIndex];
            const motivoTexto = motivoSeleccionado.text;
            const motivoId = motivoSelect.value;
            const vencimiento = formatearFecha(vencimientoInput.value);
            const observaciones = observacionesInput.value || '';

            const fila = document.createElement('tr');
            fila.dataset.id = productoId; // Usar directamente productoId que ya está validado
            fila.dataset.motivoId = motivoId;
            fila.dataset.observaciones = observaciones;
            fila.dataset.originalCantidad = cantidad;
            fila.dataset.originalKg = kg;
            fila.dataset.originalVencimiento = vencimiento;
            
            // DEBUG: Verificar que dataset.id se asignó correctamente
            console.log('🔍 DEBUG FILA CREADA:');
            console.log('- fila.dataset.id:', fila.dataset.id);
            console.log('- productoId original:', productoId);
            console.log('- ¿Son iguales?', fila.dataset.id === productoId);
            
            fila.innerHTML = `
                <td>${productoTexto}</td>
                <td>${cantidad}</td>
                <td>${kg}</td>
                <td>${motivoTexto}</td>
                <td>${vencimiento}</td>
                <td class="text-truncate" title="${observaciones}">${observaciones}</td>
                <td>
                    <button type="button" class="btn btn-warning btn-sm editar-fila me-2" onclick="editarFila(this)">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="btn btn-danger btn-sm eliminar-fila" onclick="eliminarFila(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;

            returnTableBody.appendChild(fila);

            codigoSelect.value = '';
            cantidadInput.value = '';
            kgInput.value = '';
            motivoSelect.value = '';
            vencimientoInput.value = '';
            observacionesInput.value = '';

            actualizarBotonGuardar();
            codigoSelect.focus();
        };

        // Hacer funciones globales para los onclick
        window.editarFila = editarFila;
        window.guardarEdicionFila = guardarEdicionFila;
        window.cancelarEdicionFila = cancelarEdicionFila;
        window.eliminarFila = eliminarFila;

        if (btnAgregar) {
            btnAgregar.addEventListener('click', agregarProducto);
        }

        // Evento para guardar y enviar - NUEVA LÓGICA SIMPLIFICADA
        if (btnGuardarEnviar) {
            btnGuardarEnviar.addEventListener('click', async () => {
                console.log('🚀 Botón guardar presionado');
                
                const rows = returnTableBody.querySelectorAll('tr');
                console.log('🚀 Filas encontradas:', rows.length);
                
                if (rows.length === 0) {
                    createConfirmationModal({
                        type: 'danger',
                        icon: '📋',
                        title: 'Tabla Vacía',
                        message: 'No hay productos agregados a la devolución. Debe agregar al menos un producto antes de poder guardar.',
                        confirmText: 'Entendido',
                        confirmIcon: '<i class="bi bi-info-circle"></i>',
                        cancelText: 'Cerrar'
                    });
                    return;
                }

                const distribuidor_codigo = selectDistribuidor.value;
                const numero_devolucion_text = numeroDevolucionSpan.textContent;
                const numero_devolucion_raw = parseInt(numero_devolucion_text.split('-')[1]);
                
                // Obtener nombre del distribuidor para el modal
                const distributorName = selectDistribuidor.options[selectDistribuidor.selectedIndex]?.text || 'Distribuidor';
                
                const confirmed = await createConfirmationModal({
                    type: 'primary',
                    icon: '💾',
                    title: 'Guardar Nueva Devolución',
                    message: '¿Confirma que desea guardar esta nueva devolución?',
                    details: `<strong>Distribuidor:</strong> ${distributorName}<br><strong>Número de Devolución:</strong> ${numero_devolucion_text}<br><strong>Productos a devolver:</strong> ${rows.length}<br><br>📋 Una vez guardada, la devolución estará disponible para ser procesada en el control de devoluciones.`,
                    confirmText: 'Sí, Guardar Devolución',
                    confirmIcon: '<i class="bi bi-save"></i>',
                    cancelText: 'Cancelar'
                });

                if (!confirmed) {
                    return;
                }
                
                console.log('🚀 Datos extraídos:');
                console.log('  - distribuidor_codigo:', distribuidor_codigo);
                console.log('  - numero_devolucion_text:', numero_devolucion_text);
                console.log('  - numero_devolucion_raw:', numero_devolucion_raw);
                
                // Crear el JSON como lo espera el backend
                const jsonData = {
                    fecha_registro: new Date().toISOString(),
                    timestamp: Date.now(),
                    total_filas: rows.length,
                    datos: [],
                    tipo: 'devolucion',
                    distribuidor_codigo: distribuidor_codigo,
                    numero_devolucion_raw: numero_devolucion_raw,
                    numero_devolucion: numero_devolucion_text
                };

                // Extraer datos de cada fila
                rows.forEach((row, index) => {
                    const celdas = row.querySelectorAll('td');
                    
                    // DEBUG ESPECÍFICO DEL PROBLEMA
                    console.log(`🔍 FILA ${index + 1} DEBUG:`);
                    console.log('- row.dataset.id:', row.dataset.id);
                    console.log('- typeof row.dataset.id:', typeof row.dataset.id);
                    console.log('- row.dataset completo:', row.dataset);
                    
                    const productoData = {
                        numero_fila: index + 1,
                        columna_1: row.dataset.id, // ID del producto
                        columna_2: celdas[1].textContent, // Cantidad
                        columna_3: celdas[2].textContent, // Kg
                        columna_4: celdas[3].textContent, // Motivo texto
                        columna_5: celdas[4].textContent, // Vencimiento
                        columna_6: '',
                        observaciones: row.dataset.observaciones || '',
                        id: row.dataset.id,
                        motivoId: row.dataset.motivoId
                    };
                    
                    console.log(`🚨 PRODUCTO ${index + 1} - COLUMNA_1 = "${productoData.columna_1}" (tipo: ${typeof productoData.columna_1})`);
                    console.log(`📦 Producto ${index + 1}: ID=${productoData.id}, Cantidad=${productoData.columna_2}`);
                    jsonData.datos.push(productoData);
                });

                console.log('🚀 JSON completo a enviar:', jsonData);
                console.log('🚀 JSON string:', JSON.stringify(jsonData, null, 2));
                
                // DEBUGGING ESPECÍFICO PARA EL PROBLEMA
                console.log('🔍 VERIFICANDO COLUMNA_1 EN JSON:');
                jsonData.datos.forEach((item, index) => {
                    console.log(`Producto ${index + 1}: columna_1 = "${item.columna_1}" (tipo: ${typeof item.columna_1})`);
                });

                // Validaciones antes de enviar
                if (!jsonData.distribuidor_codigo) {
                    alert('Error: Falta código de distribuidor');
                    return;
                }
                if (!jsonData.numero_devolucion_raw) {
                    alert('Error: Falta número de devolución');
                    return;
                }
                if (jsonData.datos.length === 0) {
                    alert('Error: No hay productos en los datos');
                    return;
                }

                console.log('🚀 Enviando JSON directamente...');

                // TEST SIMPLE: Primero verificar que tenemos elementos
                console.group('🧪 TEST DE DATOS');
                console.log('✅ returnTableBody existe:', !!returnTableBody);
                console.log('✅ rows encontradas:', rows.length);
                console.log('✅ selectDistribuidor existe:', !!selectDistribuidor);
                console.log('✅ distribuidor_codigo:', distribuidor_codigo);
                console.log('✅ numeroDevolucionSpan existe:', !!numeroDevolucionSpan);
                console.log('✅ numero_devolucion_text:', numero_devolucion_text);
                console.log('✅ numero_devolucion_raw:', numero_devolucion_raw);
                console.log('✅ datos array length:', jsonData.datos.length);
                console.groupEnd();

                console.log('🧪 Enviando datos REALES al guardar_devolucion.php:', jsonData);

                fetch('ajax/guardar_devolucion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(jsonData)
                })
                .then(response => response.json())
                .then(data => {
                    console.log('✅ Respuesta del servidor:', data);
                    
                    if (data.success) {
                        // Crear modal de éxito
                        const successModal = createConfirmationModal({
                            type: 'success',
                            icon: '🎉',
                            title: '¡Devolución Guardada Exitosamente!',
                            message: `La devolución ha sido registrada correctamente y está lista para ser procesada.`,
                            confirmText: 'Entendido',
                            confirmIcon: '<i class="bi bi-check-circle"></i>',
                            cancelText: 'Cerrar'
                        });
                        
                        successModal.then(() => {
                            // Limpiar formulario
                            returnTableBody.innerHTML = '';
                            actualizarBotonGuardar();
                            selectDistribuidor.value = '';
                            if (nombreClienteSpan) nombreClienteSpan.textContent = '';
                            if (numeroDevolucionSpan) numeroDevolucionSpan.textContent = '';
                            if (productForm) {
                                productForm.style.pointerEvents = 'none';
                                productForm.style.opacity = '0.6';
                            }
                        });
                    } else {
                        console.error('❌ Error del servidor:', data);
                        console.log('📋 Debug steps:', data.debug_steps);
                        
                        // Modal de error más profesional
                        createConfirmationModal({
                            type: 'danger',
                            icon: '❌',
                            title: 'Error al Guardar Devolución',
                            message: 'No se pudo guardar la devolución debido a un error del sistema.',
                            confirmText: 'Entendido',
                            confirmIcon: '<i class="bi bi-exclamation-triangle"></i>',
                            cancelText: 'Cerrar'
                        });
                    }
                })
                .catch(error => {
                    console.error('❌ Error de conexión:', error);
                    alert('ERROR DE CONEXIÓN:\n' + error.message + '\n\nRevisa la consola (F12) para más detalles.');
                });
            });
        }

        // Inicializar estado del formulario
        if (productForm) {
            productForm.style.pointerEvents = 'none';
            productForm.style.opacity = '0.6';
        }
        actualizarBotonGuardar();
        
        // Carga automática para distribuidor pre-seleccionado
        if (selectDistribuidor && selectDistribuidor.value) {
            console.log('🚀 Distribuidor detectado al inicio, cargando datos automáticamente...');
            // Disparar el evento change manualmente
            const event = new Event('change');
            selectDistribuidor.dispatchEvent(event);
        }
        
    }, 100);
}