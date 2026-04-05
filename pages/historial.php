<?php
session_start();
include '../administrador/conexion_auto.php'; 

// Prepara la consulta para obtener los distribuidores habilitados
$sql_distribuidores = "SELECT codigo, razon_social FROM distribuidores WHERE estado = 1 ORDER BY razon_social ASC";
$result_distribuidores = $conn->query($sql_distribuidores);

$distribuidores = [];
if ($result_distribuidores->num_rows > 0) {
    while ($row = $result_distribuidores->fetch_assoc()) {
        $distribuidores[] = $row;
    }
}
$conn->close();
?>

<div class="container-fluid">
    <h1 class="mt-4">Historial de Devoluciones</h1>
    <p>Selecciona un distribuidor para ver su historial de devoluciones.</p>

    <div class="card shadow mb-4">
        <div class="card-body">
            <h5 class="card-title">Filtros de Búsqueda</h5>
            <form id="filter-form" class="mb-4">
                <div class="row g-3 align-items-end"> 
                    <div class="col-md-9">
                        <label for="distribuidor" class="form-label">Distribuidor:</label>
                        <select id="distribuidor" name="distribuidor" class="form-select">
                            <option value="">Selecciona un distribuidor...</option>
                            <?php foreach ($distribuidores as $distribuidor): ?>
                                <option value="<?php echo htmlspecialchars($distribuidor['codigo']); ?>">
                                    <?php echo htmlspecialchars($distribuidor['codigo'] . ' - ' . $distribuidor['razon_social']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-12 col-md-3"> 
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                    </div>
                </div>
            </form>

            <hr>

            <h5 class="card-title">Resultados de la Búsqueda</h5>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID Devolución</th>
                            <th>Distribuidor</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="devoluciones-table-body">
                        <tr>
                            <td colspan="5" class="text-center">Selecciona un distribuidor y presiona buscar para ver los resultados.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="devolucionDetalleModal" tabindex="-1" aria-labelledby="devolucionDetalleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="devolucionDetalleModalLabel">Detalle de la Devolución</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="modal-body-content">
        </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="exportarExcelBtn">Exportar a Excel</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>