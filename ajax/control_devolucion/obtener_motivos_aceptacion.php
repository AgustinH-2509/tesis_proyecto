<?php
// Buffer de salida primero
ob_start();

// Deshabilitar warnings
ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

require_once __DIR__ . '/../../administrador/conexion_auto.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($conn) || $conn === null) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $conn->set_charset("utf8mb4");

    // Para la fase inicial, los motivos de aceptación son los mismos que los de devolución
    $sql = "SELECT id, motivos AS nombre FROM devoluciones_motivos WHERE estado IS NULL OR estado = 1 ORDER BY motivos ASC";
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Error en la consulta: " . $conn->error);
    }

    $motivos = [];
    while ($row = $result->fetch_assoc()) {
        $motivos[] = $row;
    }

    ob_end_clean();
    echo json_encode($motivos, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
