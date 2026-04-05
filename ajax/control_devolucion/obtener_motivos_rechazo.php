<?php
// CRÍTICAMENTE IMPORTANTE: Establecer headers Y limpiar buffer PRIMERO
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Desabilitar salida de errores directa
ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

// Iniciar limpieza de output buffer
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

try {
    // Incluir conexión
    require_once __DIR__ . '/../../administrador/conexion_auto.php';
    
    // Verificar conexión
    if (!isset($conn) || $conn === null) {
        throw new Exception('Conexión no disponible');
    }
    
    if (isset($connection_failed) && $connection_failed) {
        throw new Exception('Fallo de conexión');
    }

    // CRÍTICO: Establecer charset a UTF-8
    $conn->set_charset("utf8mb4");

    // Consulta para obtener motivos
    $sql = "SELECT id, motivo FROM motivos_rechazos WHERE estado = 1 ORDER BY id ASC";
    $result = $conn->query($sql);

    if (!$result) {
        // Intentar sin filtro
        $sql = "SELECT id, motivo FROM motivos_rechazos ORDER BY id ASC";
        $result = $conn->query($sql);
        
        if (!$result) {
            throw new Exception('Query error');
        }
    }

    $motivos = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $motivos[] = [
                'id' => (int)$row['id'],
                'nombre' => (string)$row['motivo']
            ];
        }
    }

    // Limpiar buffer completamente
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Enviar JSON puro con UTF-8
    echo json_encode($motivos, JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    // Limpiar buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Enviar array vacío para que el frontend no falle
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit;
}