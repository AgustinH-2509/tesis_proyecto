<?php
session_start();
include '../administrador/conexion_auto.php';
?>

<div class="container-fluid">
    <h1 class="mt-4">Informes de Devoluciones</h1>
    <p>Genera reportes detallados sobre las devoluciones por período, distribuidor y estado.</p>

    <div class="card shadow mb-4">
        <div class="card-body">
            <h5 class="card-title">Filtros de Informe</h5>
            <form id="report-form" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="distribuidor-reporte" class="form-label">Distribuidor:</label>
                        <select id="distribuidor-reporte" name="distribuidor" class="form-select">
                            <option value="">TODOS LOS DISTRIBUIDORES</option>
                            <?php
                            $sql = "SELECT codigo, razon_social FROM distribuidores WHERE estado = 1 ORDER BY razon_social ASC";
                            $result = $conn->query($sql);
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($row['codigo']) . "'>" . htmlspecialchars($row['codigo'] . ' - ' . $row['razon_social']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="fecha-desde-reporte" class="form-label">Fecha Desde:</label>
                        <input type="date" id="fecha-desde-reporte" name="fecha-desde" class="form-control" required>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="fecha-hasta-reporte" class="form-label">Fecha Hasta:</label>
                        <input type="date" id="fecha-hasta-reporte" name="fecha-hasta" class="form-control" required>
                    </div>

                    <div class="col-md-2">
                        <label for="estado-reporte" class="form-label">Estado:</label>
                        <select id="estado-reporte" name="estado" class="form-select">
                            <option value="">TODOS LOS ESTADOS</option>
                            <?php
                            $sql_ed = "SELECT id, estado FROM devoluciones_estados ORDER BY id ASC";
                            $res_ed = $conn->query($sql_ed);
                            if ($res_ed->num_rows > 0) {
                                while ($row_ed = $res_ed->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($row_ed['id']) . "'>" . htmlspecialchars($row_ed['estado']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="col-12 col-md-1">
                        <button type="submit" class="btn btn-primary w-100" title="Generar Informe">
                            <i class="bi bi-file-earmark-bar-graph"></i>
                        </button>
                    </div>
                </div>
            </form>

            <hr>

            <div id="report-results-container" style="display: none;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Resultados del Informe</h5>
                    <button class="btn btn-outline-success btn-sm" id="exportReportExcel">
                        <i class="bi bi-file-earmark-excel"></i> Exportar
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-bordered" id="report-results-table">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Distribuidor</th>
                                <th>N° Dev.</th>
                                <th>Estado</th>
                                <th>Productos</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="report-results-body">
                            <!-- Los datos se cargarán aquí -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver detalles desde el informe -->
<div class="modal fade" id="reportDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles de Devolución</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="report-modal-content">
            </div>
        </div>
    </div>
</div>

<?php 
$conn->close();
?>