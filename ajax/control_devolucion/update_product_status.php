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

// Soporte para JSON en el cuerpo (permite que el frontend envíe JSON con datos)
$rawInput = file_get_contents('php://input');
$items = null;
if ($rawInput) {
    $jsonIn = json_decode($rawInput, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        // Mapea claves JSON a _POST para mantener la lógica existente
        if (isset($jsonIn['detalle_id'])) $_POST['detalle_id'] = $jsonIn['detalle_id'];
        if (isset($jsonIn['estado'])) $_POST['estado'] = $jsonIn['estado'];
        if (isset($jsonIn['motivo_id'])) $_POST['motivo_id'] = $jsonIn['motivo_id'];
        if (isset($jsonIn['observacion'])) $_POST['observacion'] = $jsonIn['observacion'];
        if (isset($jsonIn['vuelve_stock'])) $_POST['vuelve_stock'] = $jsonIn['vuelve_stock'];

        // Soporte para procesamiento por lotes: recibir 'datos_tabla' directamente
        if (isset($jsonIn['datos_tabla']) && is_array($jsonIn['datos_tabla']) && count($jsonIn['datos_tabla']) > 0) {
            $items = $jsonIn['datos_tabla'];
        }

        // O recibir 'json_referencia' con el nombre del archivo guardado en registros_json/
        if ($items === null && isset($jsonIn['json_referencia']) && !empty($jsonIn['json_referencia'])) {
            $ref = basename($jsonIn['json_referencia']);
            $path = __DIR__ . '/../../registros_json/' . $ref;
            if (file_exists($path)) {
                $fileContents = file_get_contents($path);
                $fileJson = json_decode($fileContents, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($fileJson['datos']) && is_array($fileJson['datos'])) {
                    $items = $fileJson['datos'];
                }
            }
        }
    }
}

// Si no se envió un lote, usar datos simples en POST (compatibilidad hacia atrás)
if ($items === null) {
    // Log para debugging
    error_log('POST data recibida: ' . print_r($_POST, true));
    
    // Valida que los datos necesarios estén presentes
    if (!isset($_POST['detalle_id']) || !isset($_POST['estado'])) {
        error_log('Faltan parámetros - detalle_id: ' . (isset($_POST['detalle_id']) ? $_POST['detalle_id'] : 'NO') . ', estado: ' . (isset($_POST['estado']) ? $_POST['estado'] : 'NO'));
        echo json_encode(['success' => false, 'message' => 'Faltan parámetros requeridos.']);
        exit;
    }

    $items = [[
        'detalle_id' => $_POST['detalle_id'],
        'estado' => $_POST['estado'],
        'motivo_id' => $_POST['motivo_id'] ?? null,
        'observacion' => $_POST['observacion'] ?? null,
        'cantidad_rechazada' => $_POST['cantidad_rechazada'] ?? null,
        'vuelve_stock' => $_POST['vuelve_stock'] ?? 0
    ]];
}

// Verificar conexión antes de iniciar transacción
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

// Inicia una transacción para garantizar la atomicidad de las operaciones en lote
if (!$conn->begin_transaction()) {
    echo json_encode(['success' => false, 'message' => 'Error al iniciar transacción.']);
    exit;
}

try {
    $processed = 0;
    foreach ($items as $item) {
        $detalle_id = $item['detalle_id'] ?? null;
        $estado = isset($item['estado']) ? (int)$item['estado'] : null; // 1 = Rechazado, 0 = Aceptado
        $motivo_id = $item['motivo_id'] ?? ($item['motivos_devolucion'] ?? null);
        $observacion = $item['observacion'] ?? ($item['observaciones'] ?? null);
        $vuelve_stock = isset($item['vuelve_stock']) ? (int)$item['vuelve_stock'] : 0;

        // Validaciones mejoradas
        if ($detalle_id === null || !is_numeric($detalle_id)) {
            throw new Exception("detalle_id inválido o faltante en el item.");
        }
        if ($estado === null || !in_array($estado, [0, 1])) {
            throw new Exception("Estado inválido en el item. Debe ser 0 (aceptado) o 1 (rechazado).");
        }

        // 1. Obtener la información del detalle original
        $sql_get_original = "SELECT * FROM devoluciones_detalle WHERE ID = ?";
        $stmt_get = $conn->prepare($sql_get_original);
        $stmt_get->bind_param("i", $detalle_id);
        $stmt_get->execute();
        $detalle_original = $stmt_get->get_result()->fetch_assoc();
        $stmt_get->close();

        if (!$detalle_original) {
            throw new Exception("Detalle de devolución no encontrado para id: {$detalle_id}.");
        }

        // 2. Lógica para el manejo de la tabla devoluciones_rechazos (INSERT o DELETE)
        if ($estado === 1) { // Rechazo
            if (!isset($motivo_id)) {
                throw new Exception("Se requiere un motivo de rechazo para detalle_id: {$detalle_id}.");
            }

            // Insertar un registro de rechazo con cantidad rechazada
            $cantidad_rechazada = $item['cantidad_rechazada'] ?? $item['cantidad'] ?? 0;
            if ($cantidad_rechazada <= 0) {
                throw new Exception("Cantidad rechazada debe ser mayor a 0 para detalle_id: {$detalle_id}.");
            }

            // Validación: obtener suma actual de procesados (rechazos + aceptados) para este detalle
            $sql_sum_proc = "SELECT COALESCE(SUM(cantidad), 0) AS total_procesado FROM devoluciones_decisiones WHERE devolucion_detalle = ?";
            $stmt_sum = $conn->prepare($sql_sum_proc);
            $stmt_sum->bind_param("i", $detalle_id);
            $stmt_sum->execute();
            $result_sum = $stmt_sum->get_result()->fetch_assoc();
            $stmt_sum->close();

            $total_procesado_actual = (int)($result_sum['total_procesado'] ?? 0);
            $cantidad_disponible = $detalle_original['cantidad'] - $total_procesado_actual;

            // La cantidad rechazada no puede exceder la cantidad disponible
            if ($cantidad_rechazada > $cantidad_disponible) {
                throw new Exception("Cantidad rechazada ({$cantidad_rechazada}) no puede exceder la cantidad disponible ({$cantidad_disponible}).");
            }

            // Insertar el rechazo
            $sql_insert = "INSERT INTO devoluciones_decisiones (devolucion_detalle, producto, cantidad, rechazo_motivo, rechazo_observacion, rechazo, vuelve_stock) VALUES (?, ?, ?, ?, ?, 1, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            if (!$stmt_insert) {
                throw new Exception("Error al preparar inserción de rechazo: " . $conn->error);
            }
            $stmt_insert->bind_param(
                "isissi",
                $detalle_id,
                $detalle_original['producto_cod'],
                $cantidad_rechazada,
                $motivo_id,
                $observacion,
                $vuelve_stock
            );

            if (!$stmt_insert->execute()) {
                error_log('Warning: no se pudo insertar rechazo para detalle_id ' . $detalle_id . ' - ' . $stmt_insert->error);
            }
            $stmt_insert->close();

            // Marcar el detalle (por lo menos parcial)
            $estado_para_detalle = 1;

        } else { // Aceptación
            if (!isset($motivo_id)) {
                throw new Exception("Se requiere un motivo de aceptación para detalle_id: {$detalle_id}.");
            }

            $cantidad_aceptada = $item['cantidad_rechazada'] ?? $item['cantidad'] ?? 0;
            if ($cantidad_aceptada <= 0) {
                throw new Exception("Cantidad aceptada debe ser mayor a 0.");
            }

            $sql_sum_proc = "SELECT COALESCE(SUM(cantidad), 0) AS total_procesado FROM devoluciones_decisiones WHERE devolucion_detalle = ?";
            $stmt_sum = $conn->prepare($sql_sum_proc);
            $stmt_sum->bind_param("i", $detalle_id);
            $stmt_sum->execute();
            $result_sum = $stmt_sum->get_result()->fetch_assoc();
            $stmt_sum->close();

            $total_procesado_actual = (int)($result_sum['total_procesado'] ?? 0);
            $cantidad_disponible = $detalle_original['cantidad'] - $total_procesado_actual;

            if ($cantidad_aceptada > $cantidad_disponible) {
                throw new Exception("Cantidad aceptada ({$cantidad_aceptada}) excesiva frente al disponible ({$cantidad_disponible}).");
            }

            $sql_insert_aceptado = "INSERT INTO devoluciones_decisiones (devolucion_detalle, producto, cantidad, aceptacion_motivo, rechazo_observacion, rechazo, vuelve_stock) VALUES (?, ?, ?, ?, ?, 0, ?)";
            $stmt_insert_aceptado = $conn->prepare($sql_insert_aceptado);
            $stmt_insert_aceptado->bind_param(
                "isissi",
                $detalle_id,
                $detalle_original['producto_cod'],
                $cantidad_aceptada,
                $motivo_id,
                $observacion,
                $vuelve_stock
            );

            if (!$stmt_insert_aceptado->execute()) {
                throw new Exception("Error al insertar aceptación: " . $stmt_insert_aceptado->error);
            }
            $stmt_insert_aceptado->close();
            
            $estado_para_detalle = 0;
        }

        // 3. Actualizar el estado de la fila en devoluciones_detalle
        // Si hay rechazos, marcar como rechazado (1); si no, aceptado (0)
        $estado_final_detalle = isset($estado_para_detalle) ? $estado_para_detalle : $estado;
        $sql_update_detalle = "UPDATE devoluciones_detalle SET rechazo = ? WHERE ID = ?";
        $stmt_update_detalle = $conn->prepare($sql_update_detalle);
        $stmt_update_detalle->bind_param("ii", $estado_final_detalle, $detalle_id);
        if (!$stmt_update_detalle->execute()) {
            throw new Exception("Error al actualizar el estado de la devolución para id: {$detalle_id}.");
        }
        $stmt_update_detalle->close();

        $processed++;
    }

    // Confirma la transacción si todo ha ido bien
    $conn->commit();

    // Limpiar buffer y enviar respuesta exitosa
    ob_end_clean();
    echo json_encode(['success' => true, 'message' => 'Procesados: ' . $processed], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Si algo falla, revierte la transacción
    if (isset($conn)) {
        $conn->rollback();
    }
    
    // Limpiar buffer y enviar respuesta de error
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    error_log('Error en update_product_status.php: ' . $e->getMessage());
    
} finally {
    // NO cerrar $conn - dejar que se cierre automáticamente
}
?>