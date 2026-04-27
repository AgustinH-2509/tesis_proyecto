// scripts/historial.js
import { TablePaginator } from './paginador.js';

export function initHistorial() {
    const filterForm = document.getElementById('filter-form');
    const distribuidorSelect = document.getElementById('distribuidor');
    const devolucionesTableBody = document.getElementById('devoluciones-table-body');
    const devolucionDetalleModal = new bootstrap.Modal(document.getElementById('devolucionDetalleModal'));
    const modalBodyContent = document.getElementById('modal-body-content');
    const exportarExcelBtn = document.getElementById('exportarExcelBtn');

    let paginator = null;
    let currentDevolucionId = null;

    const fetchDevoluciones = async (distribuidorCodigo) => {
        const formData = new FormData();
        // Envía el código del distribuidor. Si está vacío, el script PHP obtendrá todas las devoluciones.
        formData.append('distribuidor_codigo', distribuidorCodigo);

        try {
            const response = await fetch('ajax/obtener_historial.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();
            renderTable(data.devoluciones);
        } catch (error) {
            console.error('Error al obtener devoluciones:', error);
            devolucionesTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error al cargar las devoluciones.</td></tr>';
        }
    };

    const fetchDetalleDevolucion = async (id) => {
        try {
            const response = await fetch(`pages/ver_devolucion.php?id=${id}`);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const htmlContent = await response.text();
            modalBodyContent.innerHTML = htmlContent;

            currentDevolucionId = id;

            devolucionDetalleModal.show();
        } catch (error) {
            console.error('Error al obtener los detalles de la devolución:', error);
            modalBodyContent.innerHTML = "<p class='text-danger'>No se pudo cargar el detalle. Inténtalo de nuevo.</p>";
            devolucionDetalleModal.show();
        }
    };

    const renderTable = (devoluciones) => {
        devolucionesTableBody.innerHTML = '';
        if (devoluciones.length === 0) {
            devolucionesTableBody.innerHTML = '<tr><td colspan="5" class="text-center">No se encontraron devoluciones.</td></tr>';
            return;
        }

        devoluciones.forEach(dev => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${dev.distribuidor_numero}</td>
                <td>${dev.nombre_distribuidor}</td>
                <td>${dev.fecha_ingresa}</td>
                <td>${dev.nombre_estado}</td>
                <td>
                    <button class="btn btn-sm btn-info view-btn" data-devolucion-id="${dev.id}">Ver Detalles</button>
                </td>
            `;
            devolucionesTableBody.appendChild(row);
        });

        devolucionesTableBody.querySelectorAll('.view-btn').forEach(button => {
            button.addEventListener('click', async function(event) {
                event.preventDefault();
                const id = this.getAttribute('data-devolucion-id');
                if (id) {
                    await fetchDetalleDevolucion(id);
                }
            });
        });

        if (!paginator) {
            paginator = new TablePaginator('devoluciones-table', null, 10);
        } else {
            paginator.updateRows();
        }
    };

    if (filterForm) {
        filterForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const selectedDistributor = distribuidorSelect.value;
            fetchDevoluciones(selectedDistributor);
        });
    }

    if (exportarExcelBtn) {
        exportarExcelBtn.addEventListener('click', function() {
            if (currentDevolucionId) {
                const url = `ajax/exportar_devolucion.php?id=${currentDevolucionId}`;
                window.location.href = url;
            } else {
                alert('No se ha seleccionado una devolución para exportar.');
            }
        });
    }

    // Llama a la función al cargar la página para mostrar todas las devoluciones por defecto
    fetchDevoluciones(distribuidorSelect.value || '');
}