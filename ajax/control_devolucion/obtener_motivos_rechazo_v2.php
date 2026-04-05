<?php
// DEBUG: Endpoint de motivos - versión 2 con diagnostico mejorado

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Limpiar buffers completamente
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

try {
    require_once __DIR__ . '/administrador/conexion_auto.php';
    
    if (!isset($conn) || $conn === null) {
        throw new Exception('Conexión no disponible');
    }
    
    if (isset($connection_failed) && $connection_failed) {
        throw new Exception('Fallo de conexión');
    }

    // Verificar encoding de la conexión
    $conn->set_charset("utf8mb4");

    // Consulta
    $sql = "SELECT id, motivo FROM motivos_rechazos WHERE estado = 1 ORDER BY id ASC";
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception('Query error: ' . $conn->error);
    }

    $motivos = [];
    while ($row = $result->fetch_assoc()) {
        // Asegurar encoding UTF-8
        $motivos[] = [
            'id' => (int)$row['id'],
            'nombre' => mb_convert_encoding($row['motivo'], 'UTF-8', 'UTF-8')
        ];
    }

    // Limpiar buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Enviar JSON con opciones correctas
    echo json_encode($motivos, JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit;
}
