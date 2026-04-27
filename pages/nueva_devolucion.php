<?php
session_start();
include '../administrador/conexion_auto.php'; 

// Prepara y ejecuta la consulta para obtener productos ordenados por 'codigo'
$sql_productos = "
    (SELECT p.iD as id, p.codigo, p.nombre, s.nombre as sabor, p.tipo
    FROM productos p
    JOIN sabores s ON p.sabor = s.ID
    WHERE p.estado = 1)
    UNION
    (SELECT iD as id, codigo, nombre, NULL AS sabor, tipo
    FROM productos
    WHERE sabor IS NULL AND estado = 1)
    ORDER BY codigo
";

$result_productos = $conn->query($sql_productos);
$productos = [];
if ($result_productos->num_rows > 0) {
    while ($row = $result_productos->fetch_assoc()) {
        $productos[] = $row;
    }
}

// Consulta para obtener la lista de distribuidores
$sql_distribuidores = "SELECT codigo, razon_social FROM distribuidores WHERE estado = 1";
$result_distribuidores = $conn->query($sql_distribuidores);
$distribuidores = [];
if ($result_distribuidores->num_rows > 0) {
    while ($row = $result_distribuidores->fetch_assoc()) {
        $distribuidores[] = $row;
    }
}
// NUEVO: Consulta para obtener los motivos de devolución
$sql_motivos = "SELECT id, motivos FROM devoluciones_motivos WHERE (estado IS NULL OR estado = 1) AND es_devolucion = 1";
$result_motivos = $conn->query($sql_motivos);
$motivos = [];
if ($result_motivos->num_rows > 0) {
    while ($row = $result_motivos->fetch_assoc()) {
        $motivos[] = $row;
    }
}
$conn->close();
?>

<div class="container-fluid">
    <h1 class="mt-4">Nueva Devolución</h1>
    <p>Usa este formulario para registrar una nueva devolución.</p>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="codigo_distribuidor" class="form-label">Distribuidor:</label>
                    <?php 
                    $dist_codigo_fijo = $_SESSION['distribuidor_codigo'] ?? null;
                    ?>
                    <select id="codigo_distribuidor" name="codigo_distribuidor" class="form-select" <?php echo $dist_codigo_fijo ? 'disabled' : ''; ?>>
                        <option value="" disabled <?php echo !$dist_codigo_fijo ? 'selected' : ''; ?>>Selecciona un distribuidor</option>
                        <?php foreach ($distribuidores as $distribuidor): ?>
                            <option value="<?php echo htmlspecialchars($distribuidor['codigo']); ?>" <?php echo ($dist_codigo_fijo == $distribuidor['codigo']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($distribuidor['codigo'] . ' - ' . $distribuidor['razon_social']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($dist_codigo_fijo): ?>
                        <input type="hidden" name="codigo_distribuidor_hidden" id="codigo_distribuidor_hidden" value="<?php echo htmlspecialchars($dist_codigo_fijo); ?>">
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex flex-column">
                    <span>Cliente: <strong id="nombre_cliente"></strong></span>
                    <span>Número de Devolución: <strong id="numero_devolucion"></strong></span>
                </div>
                <button type="button" class="btn btn-primary d-none" id="btn-guardar-enviar">Guardar y Enviar</button>
            </div>
            
            <form id="product-form">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="codigo_producto" class="form-label">Código de Producto:</label>
                        <select id="codigo_producto" name="codigo_producto" class="form-select">
                            <option value="" disabled selected>Selecciona un producto</option>
                            <?php foreach ($productos as $producto): ?>
                                <?php
                                $displayText = htmlspecialchars($producto['codigo']) . ' - ' . htmlspecialchars($producto['nombre']);
                                if (isset($producto['sabor']) && $producto['sabor'] != null) {
                                    $displayText .= ' (' . strtoupper(substr(htmlspecialchars($producto['sabor']), 0, 1)) . ')';
                                }
                                ?>
                                <option value="<?php echo htmlspecialchars($producto['codigo']); ?>" data-id="<?php echo htmlspecialchars($producto['id']); ?>" data-tipo="<?php echo htmlspecialchars($producto['tipo']); ?>">
                                    <?php echo $displayText; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="cantidad" class="form-label">Cantidad:</label>
                        <input type="number" id="cantidad" name="cantidad" min="0" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label for="kg" class="form-label">KG:</label>
                        <input type="number" id="kg" name="kg" class="form-control" min="0">
                    </div>
                    <div class="col-md-4">
                        <label for="motivo" class="form-label">Motivo de Devolución:</label>
                        <select id="motivo" name="motivo" class="form-select" required>
                            <option value="" disabled selected>Selecciona un motivo</option>
                            <?php foreach ($motivos as $motivo): ?>
                                <option value="<?php echo htmlspecialchars($motivo['motivos']); ?>" data-id="<?php echo htmlspecialchars($motivo['id']); ?>">
                                    <?php echo htmlspecialchars($motivo['motivos']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="vencimiento" class="form-label">Fecha de Vencimiento:</label>
                        <input type="date" id="vencimiento" name="vencimiento" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label for="observaciones" class="form-label">Observaciones:</label>
                        <textarea id="observaciones" name="observaciones" class="form-control" rows="1"></textarea>
                    </div>
                    <div class="col-12 col-md-auto d-flex align-items-end">
                        <button type="button" id="btn-agregar" class="btn btn-success w-100">Agregar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card shadow">
        <div class="card-body">
            <h3>Productos a Devolver</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>KG</th>
                            <th>Motivo</th>
                            <th>Fecha Venc.</th>
                            <th>Observaciones</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="return-table-body">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Éxito -->
<div class="modal fade" id="modalExito" tabindex="-1" aria-labelledby="modalExitoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-success">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalExitoLabel">
                    Éxito
                </h5>
            </div>
            <div class="modal-body text-center">
                <div class="mb-3">
                    <!-- Icono removido para profesionalismo -->
                </div>
                <h4 class="text-success mb-3">Devolución Guardada</h4>
                <p class="mb-0" id="mensajeExito">La devolución ha sido guardada correctamente en el sistema.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                    <i class="fas fa-thumbs-up me-1"></i>Entendido
                </button>
            </div>
        </div>
    </div>
</div>