<?php
session_start();
include '../administrador/conexion_auto.php'; 

// Verifica si se ha pasado un ID de devolución válido en la URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Si no hay un ID válido, redirige al historial de devoluciones
    header('Location: historial.php');
    exit;
}

$id_devolucion = $_GET['id'];

// Obtiene los datos principales de la devolución
$sql_devolucion = "
    SELECT 
        d.id,
        d.distribuidor_codigo, 
        dist.razon_social AS nombre_distribuidor,
        d.distribuidor_numero,
        d.fecha_ingresa,
        d.usuario_ingresa,
        u.nombre AS nombre_usuario
    FROM devoluciones d
    LEFT JOIN distribuidores dist ON d.distribuidor_codigo = dist.codigo
    LEFT JOIN usuarios u ON d.usuario_ingresa = u.ID
    WHERE d.id = ?
";
$stmt = $conn->prepare($sql_devolucion);
$stmt->bind_param("i", $id_devolucion);
$stmt->execute();
$result_devolucion = $stmt->get_result();
$devolucion = $result_devolucion->fetch_assoc();
$stmt->close();

if (!$devolucion) {
    echo "<div class='container mt-5'><p class='alert alert-danger'>Devolución no encontrada.</p></div>";
    exit;
}

$sql_detalle = "
    SELECT 
        dd.id,
        dd.cantidad,
        dd.kg,
        dd.observaciones,
        p.codigo AS codigo_producto,
        p.nombre AS nombre_producto,
        s.nombre AS nombre_sabor,
        dm.motivos AS nombre_motivo,
        dd.vencimiento,
        -- suma de cantidades rechazadas (donde rechazo = 1)
        COALESCE((SELECT SUM(dr.cantidad) FROM devoluciones_decisiones dr WHERE dr.devolucion_detalle = dd.id AND dr.rechazo = 1), 0) AS total_rechazado,
        -- lista de rechazos: cantidad::motivo::observacion::vuelve_stock separadas por ||
        (SELECT GROUP_CONCAT(CONCAT(dr.cantidad, '::', COALESCE(mr.motivo, dr.rechazo_motivo), '::', REPLACE(IFNULL(dr.rechazo_observacion, ''), '\n', ' '), '::', IFNULL(dr.vuelve_stock, 0)) SEPARATOR '||') 
         FROM devoluciones_decisiones dr LEFT JOIN motivos_rechazos mr ON dr.rechazo_motivo = mr.id WHERE dr.devolucion_detalle = dd.id AND dr.rechazo = 1) AS rechazos_raw,
        -- suma de cantidades aceptadas (donde rechazo = 0)
        COALESCE((SELECT SUM(dr.cantidad) FROM devoluciones_decisiones dr WHERE dr.devolucion_detalle = dd.id AND dr.rechazo = 0), 0) AS total_aceptado,
        -- lista de aceptados: cantidad::motivo::observacion::vuelve_stock
        (SELECT GROUP_CONCAT(CONCAT(dr.cantidad, '::', COALESCE(dm.motivos, 'Aceptado sin motivo especifico'), '::', REPLACE(IFNULL(dr.rechazo_observacion, ''), '\n', ' '), '::', IFNULL(dr.vuelve_stock, 0)) SEPARATOR '||') 
         FROM devoluciones_decisiones dr LEFT JOIN devoluciones_motivos dm ON dr.aceptacion_motivo = dm.id WHERE dr.devolucion_detalle = dd.id AND dr.rechazo = 0) AS aceptados_raw
    FROM devoluciones_detalle dd
    JOIN productos p ON dd.producto_cod = p.id
    LEFT JOIN sabores s ON p.sabor = s.id
    JOIN devoluciones_motivos dm ON dd.motivos_devolucion = dm.id
    WHERE dd.devolucion = ?
";
$stmt_detalle = $conn->prepare($sql_detalle);
$stmt_detalle->bind_param("i", $id_devolucion);
$stmt_detalle->execute();
$result_detalle = $stmt_detalle->get_result();
$detalle_productos = [];
while ($row = $result_detalle->fetch_assoc()) {
    $detalle_productos[] = $row;
}
$stmt_detalle->close();
$conn->close();
?>

<div class="container-fluid">
    <h1 class="mt-4">Detalles de la Devolución</h1>
    <p>Información detallada sobre la devolución seleccionada.</p>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Información General</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Número de Devolución:</strong> <?php echo htmlspecialchars($devolucion['distribuidor_numero']); ?></p>
                    <p><strong>Distribuidor:</strong> <?php echo htmlspecialchars($devolucion['nombre_distribuidor'] . ' (' . $devolucion['distribuidor_codigo'] . ')'); ?></p>
            
                </div>
                <div class="col-md-6">
                    <p><strong>Fecha de Registro:</strong> <?php echo htmlspecialchars($devolucion['fecha_ingresa']); ?></p>
                    <p><strong>Usuario:</strong> <?php echo htmlspecialchars(($devolucion['nombre_usuario'] ?? 'Desconocido') . ' (' . $devolucion['usuario_ingresa'] . ')'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Productos Devueltos</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad Disponible</th>
                            <th>KG</th>
                            <th>Motivo Devolución</th>
                            <th>Vencimiento</th>
                            <th>Historial de Decisiones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($detalle_productos)): ?>
                            <?php foreach ($detalle_productos as $producto): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($producto['codigo_producto'] . ' - ' . $producto['nombre_producto'] . (isset($producto['nombre_sabor']) ? ' (' . $producto['nombre_sabor'] . ')' : '')); ?></td>
                                <?php
                                    $cantidadOriginal = (int)($producto['cantidad'] ?? 0);
                    $totalRechazado = (int)($producto['total_rechazado'] ?? 0);
                    $totalAceptado = (int)($producto['total_aceptado'] ?? 0);
                    $cantidadRestante = max(0, $cantidadOriginal - $totalRechazado - $totalAceptado);
                                ?>
                                <td><strong><?php echo $cantidadRestante; ?></strong> <small class="text-muted">(original <?php echo $cantidadOriginal; ?>)</small></td>
                                <td><?php echo htmlspecialchars($producto['kg']); ?></td>
                                <td><?php echo htmlspecialchars($producto['nombre_motivo']); ?></td>
                                <td><?php echo htmlspecialchars($producto['vencimiento']); ?></td>
                                <td>
                                    <?php if (!empty($producto['observaciones']) && $producto['observaciones'] !== 'N/A'): ?>
                                        <div class="mb-2">
                                            <strong>📝 Observación inicial:</strong> 
                                            <?php echo htmlspecialchars($producto['observaciones']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (empty($producto['aceptados_raw']) && empty($producto['rechazos_raw'])): ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-clock-fill"></i> Pendiente de evaluación
                                        </span>
                                    <?php else: ?>
                                        <small class="text-muted">Ver historial de decisiones abajo</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <?php if (!empty($producto['aceptados_raw']) || !empty($producto['rechazos_raw'])): ?>
                                <tr class="table-light">
                                    <td colspan="6" class="p-3" style="background-color: #f8f9fa; border-left: 4px solid #6c757d;">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0 text-dark">
                                                <i class="bi bi-clock-history"></i> Historial de Decisiones para: <?php echo htmlspecialchars($producto['nombre_producto']); ?>
                                            </h6>
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseHistorial<?php echo $producto['id']; ?>" aria-expanded="false" aria-controls="collapseHistorial<?php echo $producto['id']; ?>">
                                                <i class="bi bi-eye"></i> Ver Historial
                                            </button>
                                        </div>
                                        <div class="collapse" id="collapseHistorial<?php echo $producto['id']; ?>">
                                            <div class="table-responsive">
                                                <?php $user_rol = strtolower($_SESSION['rol'] ?? 'laboratorio'); ?>
                                                <table class="table table-sm table-bordered mb-0">
                                                    <thead class="table-secondary">
                                                        <tr>
                                                            <th width="15%">Estado</th>
                                                            <th width="15%">Cantidad</th>
                                                            <?php if ($user_rol === 'laboratorio' || $user_rol === 'prueba'): ?>
                                                                <th width="20%">Motivo</th>
                                                                <th width="35%">Observación</th>
                                                                <th width="15%">Vuelve Stock</th>
                                                            <?php elseif ($user_rol === 'administracion'): ?>
                                                                <th width="70%">Vuelve Stock</th>
                                                            <?php endif; ?>
                                                        </tr>
                                                    </thead>
                                                <tbody>
                                                    <?php if (!empty($producto['aceptados_raw'])): ?>
                                                        <?php
                                                            $parts = explode('||', $producto['aceptados_raw']);
                                                            foreach ($parts as $p) {
                                                                if (trim($p)) {
                                                                    $cols = explode('::', $p);
                                                                    $cCant = $cols[0] ?? '';
                                                                    $cMot = $cols[1] ?? 'Sin motivo';
                                                                    $cObs = $cols[2] ?? '';
                                                                    $cVuelveStock = (isset($cols[3]) && $cols[3] == 1) ? 'Sí' : 'No';
                                                        ?>
                                                        <tr style="background-color: #f0fff4;">
                                                            <td class="text-center align-middle">
                                                                <i class="bi bi-check-circle-fill text-success"></i>
                                                                <br><span class="badge bg-success">Aceptado</span>
                                                            </td>
                                                            <td class="text-center align-middle">
                                                                <strong><?php echo htmlspecialchars($cCant); ?> unidades</strong>
                                                            </td>
                                                            <?php if ($user_rol === 'laboratorio' || $user_rol === 'prueba'): ?>
                                                                <td class="align-middle"><?php echo htmlspecialchars($cMot); ?></td>
                                                                <td class="align-middle">
                                                                    <?php if (!empty($cObs) && $cObs !== 'N/A'): ?>
                                                                        <?php echo htmlspecialchars($cObs); ?>
                                                                    <?php else: ?>
                                                                        <small class="text-muted">Sin observaciones</small>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td class="align-middle text-center"><?php echo $cVuelveStock; ?></td>
                                                            <?php elseif ($user_rol === 'administracion'): ?>
                                                                <td class="align-middle text-center"><?php echo $cVuelveStock; ?></td>
                                                            <?php endif; ?>
                                                        </tr>
                                                        <?php
                                                                }
                                                            }
                                                        ?>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($producto['rechazos_raw'])): ?>
                                                        <?php
                                                            $parts = explode('||', $producto['rechazos_raw']);
                                                            foreach ($parts as $p) {
                                                                if (trim($p)) {
                                                                    $cols = explode('::', $p);
                                                                    $cCant = $cols[0] ?? '';
                                                                    $cMot = $cols[1] ?? 'Sin motivo';
                                                                    $cObs = $cols[2] ?? '';
                                                                    $cVuelveStock = (isset($cols[3]) && $cols[3] == 1) ? 'Sí' : 'No';
                                                        ?>
                                                        <tr style="background-color: #fff5f5;">
                                                            <td class="text-center align-middle">
                                                                <i class="bi bi-x-circle-fill text-danger"></i>
                                                                <br><span class="badge bg-danger">Rechazado</span>
                                                            </td>
                                                            <td class="text-center align-middle">
                                                                <strong><?php echo htmlspecialchars($cCant); ?> unidades</strong>
                                                            </td>
                                                            <?php if ($user_rol === 'laboratorio' || $user_rol === 'prueba'): ?>
                                                                <td class="align-middle"><strong class="text-danger"><?php echo htmlspecialchars($cMot); ?></strong></td>
                                                                <td class="align-middle">
                                                                    <?php if (!empty($cObs) && $cObs !== 'N/A'): ?>
                                                                        <?php echo htmlspecialchars($cObs); ?>
                                                                    <?php else: ?>
                                                                        <small class="text-muted">Sin observaciones</small>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td class="align-middle text-center"><?php echo $cVuelveStock; ?></td>
                                                            <?php elseif ($user_rol === 'administracion'): ?>
                                                                <td class="align-middle text-center"><?php echo $cVuelveStock; ?></td>
                                                            <?php endif; ?>
                                                        </tr>
                                                        <?php
                                                                }
                                                            }
                                                        ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No hay productos registrados para esta devolución.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
