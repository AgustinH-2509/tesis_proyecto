<?php
// Buffer primero
ob_start();

// Deshabilitar warnings para evitar interferir con JSON
ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    require_once __DIR__ . '/../../administrador/conexion_auto.php';
    
    if (!isset($conn) || $conn === null || (isset($connection_failed) && $connection_failed)) {
        throw new Exception('Error de conexión a la base de datos: ' . ($connection_error ?? 'Desconocido'));
    }

    // CRÍTICO: Asegurar charset UTF-8
    $conn->set_charset("utf8mb4");
    
    if (!isset($_POST['action']) || !isset($_POST['detalle_id']) || !isset($_POST['cantidad'])) {
        throw new Exception('Parámetros incompletos');
    }
    
    $action = $_POST['action'];
    $detalleId = intval($_POST['detalle_id']);
    $cantidad = intval($_POST['cantidad']);
    
    $conn->begin_transaction();
    
    if ($action === 'delete_rejection') {
        // Eliminar rechazo específico
        $motivo = $_POST['motivo'] ?? '';
        
        $sql = "DELETE FROM devoluciones_decisiones 
                WHERE devolucion_detalle = ? 
                AND cantidad = ? 
                AND rechazo = 1 
                AND (rechazo_motivo = (SELECT ID FROM motivos_rechazos WHERE motivo = ?) 
                     OR rechazo_motivo = ?)
                LIMIT 1";
                
        $stmt = $conn->prepare($sql);
        
        // Intentar encontrar por nombre de motivo primero, luego por ID
        $motivoId = null;
        if (is_numeric($motivo)) {
            $motivoId = intval($motivo);
        } else {
            // Buscar ID del motivo por nombre
            $motivoQuery = "SELECT ID FROM motivos_rechazos WHERE motivo = ?";
            $motivoStmt = $conn->prepare($motivoQuery);
            $motivoStmt->bind_param("s", $motivo);
            $motivoStmt->execute();
            $result = $motivoStmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $motivoId = $row['ID'];
            }
            $motivoStmt->close();
        }
        
        $stmt->bind_param("iisi", $detalleId, $cantidad, $motivo, $motivoId);
        
    } else if ($action === 'delete_acceptance') {
        // Eliminar aceptación específica
        $sql = "DELETE FROM devoluciones_decisiones 
                WHERE devolucion_detalle = ? 
                AND cantidad = ? 
                AND rechazo = 0 
                LIMIT 1";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $detalleId, $cantidad);
    } else {
        throw new Exception('Acción no válida');
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Error al ejecutar la eliminación: ' . $stmt->error);
    }
    
    $affectedRows = $stmt->affected_rows;
    $stmt->close();
    
    if ($affectedRows > 0) {
        $conn->commit();
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Decisión eliminada correctamente'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        $conn->rollback();
        ob_end_clean();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No se encontró la decisión para eliminar'
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    
    ob_end_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    
} finally {
    // NO cerrar $conn - dejar que se cierre automáticamente
}
?>
