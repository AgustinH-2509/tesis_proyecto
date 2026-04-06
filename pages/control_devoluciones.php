<?php
session_start();
// Incluye el archivo de conexión a la base de datos UNA SOLA VEZ
include '../administrador/conexion_auto.php';
?>

<div class="container-fluid">
    <h1 class="mt-4">Control de Devoluciones</h1>

    <div class="card shadow mb-4">
        <div class="card-body">
            <h5 class="card-title">Filtros de Búsqueda</h5>
            <form id="filter-form" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="distribuidor" class="form-label">Distribuidor:</label>
                        <select id="distribuidor" name="distribuidor" class="form-select">
                            <option value="">Todos</option>
                            <?php
                            // Consulta para obtener solo los distribuidores con estado = 1
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
                        <label for="fecha-desde" class="form-label">Fecha Desde:</label>
                        <input type="date" id="fecha-desde" name="fecha-desde" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label for="fecha-hasta" class="form-label">Fecha Hasta:</label>
                        <input type="date" id="fecha-hasta" name="fecha-hasta" class="form-control">
                    </div>

                    <div class="col-md-2">
                        <label for="estado" class="form-label">Estado:</label>
                        <select id="estado" name="estado" class="form-select">
                            <option value="">Todos</option>
                            <?php
                            // Consulta para obtener todos los estados de la tabla 'devoluciones_estados'
                            $sql_estados = "SELECT id, estado FROM devoluciones_estados WHERE id NOT IN (5, 6, 8) ORDER BY id ASC";
                            $result_estados = $conn->query($sql_estados);

                            if ($result_estados->num_rows > 0) {
                                while ($row_estado = $result_estados->fetch_assoc()) {
                                    // Genera una opción para cada estado encontrado
                                    echo "<option value='" . htmlspecialchars($row_estado['id']) . "'>" . htmlspecialchars($row_estado['estado']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-1">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </form>

            <hr>

            <h5 class="card-title">Devoluciones Pendientes de Control</h5>
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="devoluciones-control-table">
                    <thead>
                        <tr>
                            <th>ID Devolución</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="devoluciones-control-table-body">
                        <tr><td colspan="5" class="text-center">Cargando devoluciones...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalLabel">Revisar Devolución <span id="modal-dev-id"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="review-view">
                    <h6>Información General</h6>
                    <p><strong>Cliente:</strong> <span id="modal-cliente"></span></p>
                    <p><strong>Fecha de Creación:</strong> <span id="modal-fecha"></span></p>

                    <hr>

                    <h6>Productos Devueltos</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>KG</th>
                                    <th>Motivo</th>
                                    <th>Fecha Venc.</th>
                                    <th>Estado</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody id="modal-products-table">
                                </tbody>
                        </table>
                    </div>

                    <hr>

                    <h6>Acciones de Control</h6>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success" id="finalizarBtn">Finalizar</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Cerrar la conexión al final del archivo
$conn->close();
?>