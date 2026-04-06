<?php
// Buffer de salida primero
ob_start();

// Deshabilitar warnings para evitar interferir con JSON
ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

// Establece el encabezado para que el navegador sepa que la respuesta es JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Función para crear JSON temporal desde datos de BD
function crearJSONTemporalDesdeBD($devolucion_info, $productos, $distribuidor_codigo) {
    $timestamp = round(microtime(true) * 1000);
    $nombreArchivo = "temp_detalle_{$timestamp}.json";
    $rutaArchivo = __DIR__ . '/../../temp/' . $nombreArchivo;
    
    $jsonData = [
        'fecha_registro' => $devolucion_info['fecha_ingresa'] . 'T00:00:00.000Z',
        'timestamp' => $timestamp,
        'total_filas' => count($productos),
        'datos' => [],
        'tipo' => 'devolucion',
        'distribuidor_codigo' => $distribuidor_codigo,
        'numero_devolucion_raw' => intval(str_replace('-', '', $devolucion_info['distribuidor_numero'])),
        'numero_devolucion' => $devolucion_info['distribuidor_numero']
    ];
    
    foreach ($productos as $index => $producto) {
        $jsonData['datos'][] = [
            'numero_fila' => $index + 1,
            'columna_1' => $producto['codigo_producto'],
            'columna_2' => $producto['cantidad'],
            'columna_3' => $producto['kg'],
            'columna_4' => 'Normal',
            'columna_5' => $producto['vencimiento'],
            'columna_6' => '',
            'observaciones' => '',
            'id' => $producto['codigo_producto'],
            'motivoId' => 1 // Default, se podría mejorar
        ];
    }
    
    file_put_contents($rutaArchivo, json_encode($jsonData, JSON_PRETTY_PRINT));
    return $rutaArchivo;
}

// Función para eliminar JSON temporal
function eliminarJSONTemporal($ruta) {
    if (file_exists($ruta)) {
        unlink($ruta);
    }
}

// Log de depuración (temporal)
$debugLog = __DIR__ . '/../../temp/debug_detalle.log';

try {
    // Capturar cualquier salida del archivo de conexión
    include __DIR__ . '/../../administrador/conexion_auto.php';
    
    // Si hubo error de conexión
    if (!isset($conn) || $conn === null || (isset($connection_failed) && $connection_failed)) {
        throw new Exception('Error de conexión a la base de datos: ' . ($connection_error ?? 'Desconocido'));
    }

    // CRÍTICO: Asegurar charset UTF-8
    $conn->set_charset("utf8mb4");
    
    // Soporte para recibir ID vía GET, POST tradicional o JSON en el cuerpo
    $inputRaw = file_get_contents('php://input');
    if ($inputRaw) {
        $jsonIn = json_decode($inputRaw, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($jsonIn['id'])) {
            $_REQUEST['id'] = $jsonIn['id'];
        }
        // Soporte para json_referencia: buscar id en el log
        if (json_last_error() === JSON_ERROR_NONE && isset($jsonIn['json_referencia']) && !isset($_REQUEST['id'])) {
            $ref = basename($jsonIn['json_referencia']);
            $logPath = __DIR__ . '/../../registros_json/log.txt';
            if (file_exists($logPath)) {
                $contents = file_get_contents($logPath);
                if (preg_match('/Devolucion\s+#(\d+)\s+-\s+json_referencia:\s*' . preg_quote($ref, '/') . '/', $contents, $m)) {
                    $_REQUEST['id'] = $m[1];
                }
            }
        }
    }
    
    // Valida que el ID de la devolución esté presente
    if (!isset($_GET['id']) && !isset($_REQUEST['id'])) {
        echo json_encode(['success' => false, 'error' => 'ID de devolución no proporcionado.']);
        exit;
    }
    
    $id_devolucion = $_GET['id'] ?? $_REQUEST['id'];
    
    // Log de depuración
    $debugMsg = date('Y-m-d H:i:s') . " - Iniciando obtener_detalle_control.php (standalone) para ID: $id_devolucion\n";
    file_put_contents($debugLog, $debugMsg, FILE_APPEND | LOCK_EX);
    
    // Inicia una transacción
    $conn->begin_transaction();
    
    // 1. Obtiene la información principal de la devolución y el distribuidor
    $sql_devolucion = "
        SELECT
            d.distribuidor_numero,
            d.distribuidor_codigo,
            dist.razon_social AS nombre_distribuidor,
            d.fecha_ingresa
        FROM devoluciones d
        JOIN distribuidores dist ON d.distribuidor_codigo = dist.codigo
        WHERE d.ID = ?
    ";
    $stmt_devolucion = $conn->prepare($sql_devolucion);
    if (!$stmt_devolucion) {
        throw new Exception("Error preparando consulta de devolución: " . $conn->error);
    }
    $stmt_devolucion->bind_param("i", $id_devolucion);
    if (!$stmt_devolucion->execute()) {
        throw new Exception("Error ejecutando consulta de devolución: " . $stmt_devolucion->error);
    }
    $devolucion_info = $stmt_devolucion->get_result()->fetch_assoc();
    $stmt_devolucion->close();
    
    if (!$devolucion_info) {
        // Con el nuevo sistema de JSON temporal, las devoluciones siempre están en BD
        echo json_encode(['success' => false, 'error' => 'Devolución no encontrada en la base de datos.']);
        $conn->rollback();
        exit;
    }
    
    // 2. Obtiene los detalles de los productos de BD
    $sql_productos = "
        SELECT
            dd.ID as id,
            p.nombre AS nombre_producto,
            p.codigo AS codigo_producto,
            dd.cantidad,
            dd.kg,
            dd.vencimiento,
            dd.rechazo,
            md.motivos AS motivo_devolucion,
            COALESCE((SELECT SUM(dr.cantidad) FROM devoluciones_rechazos dr WHERE dr.devolucion_detalle = dd.ID AND dr.rechazo = 1), 0) AS total_rechazado,
            (SELECT GROUP_CONCAT(CONCAT(dr.cantidad, '::', COALESCE(mr.motivo, dr.rechazo_motivo), '::', REPLACE(IFNULL(dr.rechazo_observacion, ''), '\n', ' ')) SEPARATOR '||') 
             FROM devoluciones_rechazos dr LEFT JOIN motivos_rechazos mr ON dr.rechazo_motivo = mr.ID 
             WHERE dr.devolucion_detalle = dd.ID AND dr.rechazo = 1) AS rechazos_raw,
            COALESCE((SELECT SUM(dr.cantidad) FROM devoluciones_rechazos dr WHERE dr.devolucion_detalle = dd.ID AND dr.rechazo = 0), 0) AS total_aceptado,
            (SELECT GROUP_CONCAT(CONCAT(dr.cantidad, '::', COALESCE(dm.motivos, 'Aceptado sin motivo especifico'), '::', REPLACE(IFNULL(dr.rechazo_observacion, ''), '\n', ' ')) SEPARATOR '||') 
             FROM devoluciones_rechazos dr LEFT JOIN devoluciones_motivos dm ON dr.aceptacion_motivo = dm.id 
             WHERE dr.devolucion_detalle = dd.ID AND dr.rechazo = 0) AS aceptados_raw
        FROM devoluciones_detalle dd
        JOIN productos p ON dd.producto_cod = p.iD
        LEFT JOIN devoluciones_motivos md ON dd.motivos_devolucion = md.id
        WHERE dd.devolucion = ?
        ORDER BY dd.ID ASC
    ";
    $stmt_productos = $conn->prepare($sql_productos);
    if (!$stmt_productos) {
        throw new Exception("Error preparando consulta de productos: " . $conn->error);
    }
    $stmt_productos->bind_param("i", $id_devolucion);
    if (!$stmt_productos->execute()) {
        throw new Exception("Error ejecutando consulta de productos: " . $stmt_productos->error);
    }
    $productos_result = $stmt_productos->get_result();
    
    $productos = [];
    while ($row = $productos_result->fetch_assoc()) {
        // Asegurar tipos
        $row['cantidad'] = (int)$row['cantidad'];
        $row['total_rechazado'] = (int)($row['total_rechazado'] ?? 0);
        $row['total_aceptado'] = (int)($row['total_aceptado'] ?? 0);
        $row['rechazos_raw'] = $row['rechazos_raw'] ?? '';
        $row['aceptados_raw'] = $row['aceptados_raw'] ?? '';
        $productos[] = $row;
    }
    $stmt_productos->close();
    
    // Si todo va bien, confirma la transacción
    $conn->commit();
    
    // Limpiar buffer y devolver datos
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'devolucion' => $devolucion_info,
        'productos' => $productos
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Si algo falla, revierte la transacción
    if (isset($conn) && $conn) {
        $conn->rollback();
    }
    
    // Limpiar buffer y devolver error
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);

} finally {
    // NO cerrar $conn - dejar que se cierre automáticamente
}
?>