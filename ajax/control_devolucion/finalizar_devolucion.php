<?php
// Buffer primero
ob_start();

// Incluye el archivo de conexión a la base de datos
require_once __DIR__ . '/../../administrador/conexion_auto.php';

// CRÍTICO: Asegurar charset UTF-8
if (isset($conn) && $conn !== null) {
    $conn->set_charset("utf8mb4");
}

// Establece el encabezado para que el navegador sepa que la respuesta es JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Soporte para JSON en el cuerpo (permite que el frontend envíe JSON)
$rawInput = file_get_contents('php://input');
if ($rawInput) {
    $jsonIn = json_decode($rawInput, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        if (isset($jsonIn['id'])) {
            $_POST['id'] = $jsonIn['id'];
        }
        // Permitir recibir una referencia al JSON y buscar el id correspondiente en el log
        if (isset($jsonIn['json_referencia']) && !isset($_POST['id'])) {
            $ref = basename($jsonIn['json_referencia']);
            $logPath = __DIR__ . '/../../registros_json/log.txt';
            if (file_exists($logPath)) {
                $contents = file_get_contents($logPath);
                if (preg_match('/Devolucion\s+#(\d+)\s+-\s+json_referencia:\s*' . preg_quote($ref, '/') . '/', $contents, $m)) {
                    $_POST['id'] = $m[1];
                }
            }
        }
    }
}

// Valida que el ID de la devolución esté presente
if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de devolución no proporcionado.']);
    exit;
}

$devolucion_id = $_POST['id'];

// Inicia una transacción para garantizar la atomicidad de las operaciones
$conn->begin_transaction();

try {
    // 1. Primero, registrar automáticamente las cantidades restantes como aceptadas
    $sql_detalles = "
        SELECT 
            dd.ID,
            dd.cantidad as cantidad_original,
            dd.producto_cod,
            dd.observaciones,
            COALESCE(SUM(CASE WHEN dr.rechazo = 1 THEN dr.cantidad ELSE 0 END), 0) as cantidad_rechazada
        FROM devoluciones_detalle dd
        LEFT JOIN devoluciones_decisiones dr ON dd.ID = dr.devolucion_detalle
        WHERE dd.devolucion = ?
        GROUP BY dd.ID, dd.cantidad, dd.producto_cod, dd.observaciones
    ";
    
    $stmt_detalles = $conn->prepare($sql_detalles);
    $stmt_detalles->bind_param("i", $devolucion_id);
    $stmt_detalles->execute();
    $detalles_result = $stmt_detalles->get_result();
    
    while ($detalle = $detalles_result->fetch_assoc()) {
        $detalle_id = $detalle['ID'];
        $cantidad_original = (int)$detalle['cantidad_original'];
        $cantidad_rechazada = (int)$detalle['cantidad_rechazada'];
        $cantidad_restante = $cantidad_original - $cantidad_rechazada;
        
        // Si hay cantidad restante, registrarla como aceptada
        if ($cantidad_restante > 0) {
            // Verificar si ya existe un registro de aceptación para este detalle
            $sql_check_aceptado = "SELECT COUNT(*) as count FROM devoluciones_decisiones WHERE devolucion_detalle = ? AND rechazo = 0";
            $stmt_check = $conn->prepare($sql_check_aceptado);
            $stmt_check->bind_param("i", $detalle_id);
            $stmt_check->execute();
            $check_result = $stmt_check->get_result()->fetch_assoc();
            $stmt_check->close();
            
            // Solo insertar si no existe ya un registro de aceptación
            if ((int)$check_result['count'] === 0) {
                $sql_insert_aceptado = "INSERT INTO devoluciones_decisiones (devolucion_detalle, producto, cantidad, rechazo_motivo, rechazo_observacion, rechazo) VALUES (?, ?, ?, ?, ?, 0)";
                $stmt_insert_aceptado = $conn->prepare($sql_insert_aceptado);
                
                $motivo_aceptacion = null;
                $observacion_aceptacion = 'Cantidad restante aceptada automáticamente al finalizar';
                
                $stmt_insert_aceptado->bind_param(
                    "isiss",
                    $detalle_id,
                    $detalle['producto_cod'],
                    $cantidad_restante,
                    $motivo_aceptacion,
                    $observacion_aceptacion
                );
                
                if (!$stmt_insert_aceptado->execute()) {
                    throw new Exception("Error al registrar aceptación automática para detalle ID: {$detalle_id}");
                }
                $stmt_insert_aceptado->close();
            }
        }
    }
    $stmt_detalles->close();

    // 2. Contar detalles totales y analizar su estado para determinar el estado final
    $sql_count = "
        SELECT
            dd.ID,
            dd.cantidad as cantidad_original,
            COALESCE(SUM(CASE WHEN dr.rechazo = 1 THEN dr.cantidad ELSE 0 END), 0) as cantidad_rechazada,
            COALESCE(SUM(CASE WHEN dr.rechazo = 0 THEN dr.cantidad ELSE 0 END), 0) as cantidad_aceptada
        FROM devoluciones_detalle dd
        LEFT JOIN devoluciones_decisiones dr ON dd.ID = dr.devolucion_detalle
        WHERE dd.devolucion = ?
        GROUP BY dd.ID, dd.cantidad
    ";
    
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param("i", $devolucion_id);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    
    $total_detalles = 0;
    $detalles_completamente_rechazados = 0;
    $detalles_parcialmente_rechazados = 0;
    $detalles_completamente_aceptados = 0;
    
    while ($row = $result_count->fetch_assoc()) {
        $total_detalles++;
        $original = (int)$row['cantidad_original'];
        $rechazada = (int)$row['cantidad_rechazada'];
        $aceptada = (int)$row['cantidad_aceptada'];
        
        if ($rechazada === $original) {
            // Completamente rechazado
            $detalles_completamente_rechazados++;
        } else if ($rechazada > 0 && $aceptada > 0) {
            // Parcialmente rechazado
            $detalles_parcialmente_rechazados++;
        } else if ($aceptada === $original) {
            // Completamente aceptado
            $detalles_completamente_aceptados++;
        }
    }
    $stmt_count->close();

    // 3. Determinar el estado final de la devolución
    if ($total_detalles === 0) {
        $nuevo_estado = 5; // Aprobada (caso extraño, pero safe)
    } else if ($detalles_parcialmente_rechazados > 0) {
        $nuevo_estado = 8; // Rechazada parcialmente (si hay al menos uno parcial)
    } else if ($detalles_completamente_rechazados === $total_detalles) {
        $nuevo_estado = 6; // Rechazada completamente
    } else {
        $nuevo_estado = 5; // Aprobada (todos aceptados)
    }

    // 4. Actualizar el estado en la tabla 'devoluciones'
    $sql_update = "UPDATE devoluciones SET estado = ? WHERE ID = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ii", $nuevo_estado, $devolucion_id);

    if ($stmt_update->execute()) {
        $conn->commit();
        
        // Obtener el nombre del estado para el mensaje
        $sql_estado_nombre = "SELECT estado FROM devoluciones_estados WHERE id = ?";
        $stmt_estado = $conn->prepare($sql_estado_nombre);
        $stmt_estado->bind_param("i", $nuevo_estado);
        $stmt_estado->execute();
        $estado_result = $stmt_estado->get_result()->fetch_assoc();
        $stmt_estado->close();
        
        $estado_nombre = $estado_result['estado'] ?? 'Desconocido';
        
        // Limpiar buffer y enviar respuesta exitosa
        ob_end_clean();
        echo json_encode([
            'success' => true, 
            'message' => "Devolución finalizada correctamente. Estado: {$estado_nombre}",
            'estado_final' => $nuevo_estado,
            'estado_nombre' => $estado_nombre
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception("Error al actualizar el estado final de la devolución: " . $stmt_update->error);
    }

    $stmt_update->close();

} catch (Exception $e) {
    // Si algo falla, revierte la transacción
    $conn->rollback();
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);

} finally {
    // NO cerrar $conn - dejar que se cierre automáticamente
}
