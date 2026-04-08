<?php
// Iniciar sesión y establecer cabeceras
session_start();
header('Content-Type: application/json; charset=utf-8');

// Buffer de salida para evitar contaminación por errores PHP
ob_start();

try {
    require_once __DIR__ . '/../administrador/conexion_auto.php';
    
    if (!isset($conn)) {
        throw new Exception('Error de conexión a la base de datos.');
    }

    // Asegurar UTF-8
    $conn->set_charset("utf8mb4");

    // Obtener parámetros
    $distribuidor_codigo = $_POST['distribuidor'] ?? '';
    $fecha_desde = $_POST['fecha-desde'] ?? '';
    $fecha_hasta = $_POST['fecha-hasta'] ?? '';
    $estado_id = $_POST['estado'] ?? '';

    // Consulta base
    $sql = "
        SELECT 
            d.ID,
            d.distribuidor_numero,
            dist.razon_social as nombre_distribuidor,
            d.fecha_ingresa,
            de.estado as nombre_estado,
            (SELECT COUNT(*) FROM devoluciones_detalle dd WHERE dd.devolucion = d.ID) as total_productos
        FROM devoluciones d
        JOIN distribuidores dist ON d.distribuidor_codigo = dist.codigo
        JOIN devoluciones_estados de ON d.estado = de.id
        WHERE 1=1
    ";

    $params = [];
    $types = "";

    // Filtros dinámicos
    if (!empty($distribuidor_codigo)) {
        $sql .= " AND d.distribuidor_codigo = ?";
        $params[] = $distribuidor_codigo;
        $types .= "s";
    }

    if (!empty($fecha_desde)) {
        $sql .= " AND d.fecha_ingresa >= ?";
        $params[] = $fecha_desde;
        $types .= "s";
    }

    if (!empty($fecha_hasta)) {
        $sql .= " AND d.fecha_ingresa <= ?";
        $params[] = $fecha_hasta;
        $types .= "s";
    }

    if (!empty($estado_id)) {
        $sql .= " AND d.estado = ?";
        $params[] = $estado_id;
        $types .= "i";
    }

    $sql .= " ORDER BY d.fecha_ingresa DESC, d.ID DESC";

    $stmt = $conn->prepare($sql);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) $conn->close();
}
