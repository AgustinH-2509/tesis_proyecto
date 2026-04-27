import { TablePaginator } from './paginador.js';

export function initInformes() {
    const reportForm = document.getElementById('report-form');
    const resultsContainer = document.getElementById('report-results-container');
    const resultsBody = document.getElementById('report-results-body');
    const exportBtn = document.getElementById('exportReportExcel');
    const detailModal = new bootstrap.Modal(document.getElementById('reportDetailModal'));
    const modalContent = document.getElementById('report-modal-content');

    let paginator = null;

    if (reportForm) {
        reportForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const btnSubmit = reportForm.querySelector('button[type="submit"]');
            const originalText = btnSubmit.innerHTML;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            btnSubmit.disabled = true;

            const formData = new FormData(reportForm);

            try {
                const response = await fetch('ajax/obtener_informe.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    renderReportTable(result.data);
                    resultsContainer.style.display = 'block';
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ocurrió un error al generar el informe.');
            } finally {
                btnSubmit.innerHTML = originalText;
                btnSubmit.disabled = false;
            }
        });
    }

    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            alert('Funcionalidad de exportación Excel preparada. Pendiente definición de formato final del informe.');
            // Aquí se podría redirigir a un generador de Excel PHP con los mismos parámetros
        });
    }

    function renderReportTable(data) {
        resultsBody.innerHTML = '';
        if (data.length === 0) {
            resultsBody.innerHTML = '<tr><td colspan="6" class="text-center">No se encontraron resultados para los filtros seleccionados.</td></tr>';
            return;
        }

        data.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${row.fecha_ingresa}</td>
                <td>${row.nombre_distribuidor}</td>
                <td>${row.distribuidor_numero}</td>
                <td>${row.nombre_estado}</td>
                <td>${row.total_productos} ítems</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary view-detail-btn" data-id="${row.ID}">
                        <i class="bi bi-eye"></i>
                    </button>
                </td>
            `;
            resultsBody.appendChild(tr);
        });

        // Eventos para ver detalles
        document.querySelectorAll('.view-detail-btn').forEach(btn => {
            btn.addEventListener('click', async function () {
                const id = this.getAttribute('data-id');
                await loadDevolucionDetalle(id);
            });
        });

        if (!paginator) {
            paginator = new TablePaginator('report-results-table', null, 20);
        } else {
            paginator.updateRows();
        }
    }

    async function loadDevolucionDetalle(id) {
        modalContent.innerHTML = '<div class="text-center p-3"><div class="spinner-border"></div></div>';
        detailModal.show();

        try {
            const response = await fetch(`pages/ver_devolucion.php?id=${id}`);
            const html = await response.text();
            modalContent.innerHTML = html;
        } catch (error) {
            modalContent.innerHTML = '<div class="alert alert-danger">Error al cargar detalles.</div>';
        }
    }
}
