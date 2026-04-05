<?php
// Headers primero
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Función para enviar respuesta y terminar
function sendJsonResponse($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    // Incluir conexión
    require_once '../administrador/conexion_auto.php';
    
    // Verificar conexión básica
    if (!isset($conn)) {
        sendJsonResponse([
            'success' => false,
            'error' => 'Variable $conn no definida',
            'productos' => []
        ]);
    }
    
    if ($conn === null) {
        sendJsonResponse([
            'success' => false,
            'error' => 'Conexión es null',
            'productos' => []
        ]);
    }
    
    // Verificar errores de conexión
    if (isset($connection_failed) && $connection_failed) {
        sendJsonResponse([
            'success' => false,
            'error' => "Error de conexión: " . ($connection_error ?? 'Error desconocido'),
            'productos' => []
        ]);
    }
    
    // Test simple primero - verificar si la tabla existe
    $test_query = "SHOW TABLES LIKE 'productos'";
    $test_result = $conn->query($test_query);
    
    if (!$test_result || $test_result->num_rows === 0) {
        sendJsonResponse([
            'success' => false,
            'error' => 'Tabla productos no existe',
            'productos' => []
        ]);
    }
    
    // Consulta principal
    $sql = "SELECT iD as id, nombre as descripcion FROM productos WHERE estado = 1 ORDER BY nombre LIMIT 20";
    $result = $conn->query($sql);
    
    if (!$result) {
        sendJsonResponse([
            'success' => false,
            'error' => 'Error en consulta: ' . $conn->error,
            'productos' => [],
            'sql' => $sql
        ]);
    }
    
    $productos = [];
    while ($row = $result->fetch_assoc()) {
        $productos[] = [
            'id' => $row['id'],
            'descripcion' => $row['descripcion'] ?? "Producto {$row['id']}"
        ];
    }
    
    // Respuesta exitosa
    sendJsonResponse([
        'success' => true,
        'productos' => $productos,
        'total' => count($productos),
        'sql_usado' => $sql
    ]);

} catch (Exception $e) {
    sendJsonResponse([
        'success' => false,
        'error' => $e->getMessage(),
        'productos' => [],
        'file' => basename(__FILE__),
        'line' => $e->getLine()
    ]);
}
?>