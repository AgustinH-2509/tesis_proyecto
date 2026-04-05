<?php
session_start();
// Asegúrate de que la ruta a tu archivo de conexión sea correcta.
include '../administrador/conexion_auto.php'; 
?>

<div class="container-fluid">
    <h1 class="mt-4">Gestión de Distribuidores</h1>
    <p>Administra los distribuidores de la compañía.</p>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="card-title mb-0">Distribuidores Habilitados</h5>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDistributorModal">
            <i class="bi bi-person-plus-fill me-2"></i>Agregar Nuevo Distribuidor
        </button>
    </div>

    <div class="mb-3">
        <input type="text" id="search-input" class="form-control" placeholder="Buscar por código o razón social...">
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="distributor-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Razón Social</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Realiza la consulta a la base de datos
                        $sql = "SELECT codigo, razon_social FROM distribuidores";
                        $result = $conn->query($sql);

                        // Verifica si hay resultados
                        if ($result->num_rows > 0) {
                            // Itera sobre cada fila de resultados
                            while ($row = $result->fetch_assoc()) {
                                // Agregamos data-codigo y data-razon-social para el filtro
                                echo "<tr data-codigo='" . htmlspecialchars($row['codigo']) . "' data-razon-social='" . htmlspecialchars($row['razon_social']) . "'>";
                                echo "<td>" . htmlspecialchars($row['codigo']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['razon_social']) . "</td>";
                                echo "<td>";
                                echo "<button class='btn btn-sm btn-warning edit-btn' data-bs-toggle='modal' data-bs-target='#editDistributorModal'>";
                                echo "<i class='bi bi-pencil-square'></i>";
                                echo "</button>";
                                echo "<button class='btn btn-sm btn-danger disable-btn' data-codigo='" . htmlspecialchars($row['codigo']) . "'>";
                                echo "<i class='bi bi-person-slash'></i>";
                                echo "</button>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3' class='text-center'>No se encontraron distribuidores.</td></tr>";
                        }
                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Agregar Distribuidor -->
<div class="modal fade" id="addDistributorModal" tabindex="-1" aria-labelledby="addDistributorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDistributorModalLabel">Agregar Nuevo Distribuidor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="add-distributor-form">
                    <div id="error-container" class="mb-3"></div>
                    <div class="mb-3">
                        <label for="distributor-code" class="form-label">Código</label>
                        <input type="number" class="form-control" id="distributor-code" placeholder="Ingrese el código" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="distributor-razon-social" class="form-label">Razón Social</label>
                        <input type="text" class="form-control" id="distributor-razon-social" placeholder="Ingrese la razón social" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveDistributorBtn">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Éxito -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">¡Éxito!</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                <p class="mt-3">Distribuidor guardado correctamente.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">Aceptar</button>
            </div>
        </div>
    </div>
</div>