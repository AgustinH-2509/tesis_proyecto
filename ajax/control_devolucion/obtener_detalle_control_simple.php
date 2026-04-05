<?php
// Endpoint simplificado para obtener detalles de devolución desde Control de Devoluciones
header('Content-Type: application/json');

// Deshabilitar salida de errores HTML
ini_set('display_errors', 0);
error_reporting(0);

try {
    // Incluir conexión
    include '../../administrador/conexion_auto.php';
    
    // Verificar conexión
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Obtener ID de la devolución
    $id_devolucion = $_GET['id'] ?? null;
    
    if (!$id_devolucion) {
        throw new Exception('ID de devolución no proporcionado');
    }
    
    // Validar que sea numérico
    if (!is_numeric($id_devolucion)) {
        throw new Exception('ID de devolución inválido');
    }
    
    // 1. Obtener información de la devolución
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
    
    $stmt = $conn->prepare($sql_devolucion);
    if (!$stmt) {
        throw new Exception('Error al preparar consulta de devolución');
    }
    
    $stmt->bind_param("i", $id_devolucion);
    $stmt->execute();
    $result = $stmt->get_result();
    $devolucion = $result->fetch_assoc();
    $stmt->close();
    
    if (!$devolucion) {
        throw new Exception('Devolución no encontrada');
    }
    
    // 2. Obtener productos de la devolución
    $sql_productos = "
        SELECT
            dd.ID as id,
            p.nombre AS nombre_producto,
            p.codigo AS codigo_producto,
            dd.cantidad,
            dd.kg,
            dd.vencimiento,
            dd.rechazo,
            md.motivos AS motivo_devolucion
        FROM devoluciones_detalle dd
        JOIN productos p ON dd.producto_cod = p.iD
        LEFT JOIN devoluciones_motivos md ON dd.motivos_devolucion = md.id
        WHERE dd.devolucion = ?
        ORDER BY dd.ID ASC
    ";
    
    $stmt = $conn->prepare($sql_productos);
    if (!$stmt) {
        throw new Exception('Error al preparar consulta de productos');
    }
    
    $stmt->bind_param("i", $id_devolucion);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $productos = [];
    while ($row = $result->fetch_assoc()) {
        // Agregar campos adicionales requeridos por el frontend
        $row['total_rechazado'] = 0;
        $row['total_aceptado'] = 0;
        $productos[] = $row;
    }
    $stmt->close();
    
    // Cerrar conexión
    $conn->close();
    
    // Devolver respuesta exitosa
    echo json_encode([
        'success' => true,
        'devolucion' => $devolucion,
        'productos' => $productos
    ]);
    
} catch (Exception $e) {
    // En caso de error, devolver respuesta JSON de error
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>