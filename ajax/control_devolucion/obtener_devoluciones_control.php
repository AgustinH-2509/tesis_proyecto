<?php
// Establece el encabezado para que el navegador sepa que la respuesta es JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Limpieza de salida
ob_start();

try {
    // Incluye el archivo de conexión a la base de datos
    require_once __DIR__ . '/../../administrador/conexion_auto.php';
    
    // Verificar conexión
    if (!isset($conn) || $conn === null) {
        throw new Exception('Conexión a base de datos no disponible');
    }
    
    if (isset($connection_failed) && $connection_failed) {
        throw new Exception('Error de conexión: ' . ($connection_error ?? 'Desconocido'));
    }

    // CRÍTICO: Asegurar charset UTF-8
    $conn->set_charset("utf8mb4");

    // Soporte para recibir filtros vía JSON en el cuerpo o por POST tradicional
    $rawInput = file_get_contents('php://input');
    if ($rawInput) {
        $jsonIn = json_decode($rawInput, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($jsonIn['distribuidor'])) $_POST['distribuidor'] = $jsonIn['distribuidor'];
            if (isset($jsonIn['fecha-desde'])) $_POST['fecha-desde'] = $jsonIn['fecha-desde'];
            if (isset($jsonIn['fecha-hasta'])) $_POST['fecha-hasta'] = $jsonIn['fecha-hasta'];
            if (isset($jsonIn['estado'])) $_POST['estado'] = $jsonIn['estado'];
        }
    }

    // Obtiene los valores del formulario o asigna un valor vacío si no existen
    $distribuidor_codigo = $_POST['distribuidor'] ?? '';
    $fecha_desde = $_POST['fecha-desde'] ?? '';
    $fecha_hasta = $_POST['fecha-hasta'] ?? '';
    $estado_id = $_POST['estado'] ?? '';

    // La consulta base para las devoluciones
    $sql = "
        SELECT
            d.ID as id,
            d.distribuidor_numero,
            dist.razon_social AS nombre_distribuidor,
            d.fecha_ingresa,
            e.estado AS nombre_estado
        FROM devoluciones d
        JOIN distribuidores dist ON d.distribuidor_codigo = dist.codigo
        JOIN devoluciones_estados e ON d.estado = e.id
        WHERE 1=1
    ";

    $params = [];
    $types = '';

    // Agrega filtros a la consulta solo si se han proporcionado
    if (!empty($distribuidor_codigo)) {
        $sql .= " AND d.distribuidor_codigo = ?";
        $params[] = $distribuidor_codigo;
        $types .= 's';
    }

    if (!empty($fecha_desde)) {
        $sql .= " AND d.fecha_ingresa >= ?";
        $params[] = $fecha_desde;
        $types .= 's';
    }

    if (!empty($fecha_hasta)) {
        $sql .= " AND d.fecha_ingresa <= ?";
        $params[] = $fecha_hasta;
        $types .= 's';
    }

    if (!empty($estado_id)) {
        $sql .= " AND e.id = ?";
        $params[] = $estado_id;
        $types .= 'i';
    }

    $sql .= " ORDER BY d.fecha_ingresa DESC";

    // Prepara y ejecuta la consulta
    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $devoluciones = [];
    while ($row = $result->fetch_assoc()) {
        $devoluciones[] = [
            'id' => (int)$row['id'],
            'distribuidor_numero' => (int)$row['distribuidor_numero'],
            'nombre_distribuidor' => (string)$row['nombre_distribuidor'],
            'fecha_ingresa' => (string)$row['fecha_ingresa'],
            'nombre_estado' => (string)$row['nombre_estado']
        ];
    }

    // Limpieza de buffer y respuesta exitosa
    ob_end_clean();
    echo json_encode(['devoluciones' => $devoluciones], JSON_UNESCAPED_UNICODE);
    
    // Cerrar recursos
    $stmt->close();

} catch (Exception $e) {
    // Limpieza de buffer y respuesta de error
    ob_end_clean();
    http_response_code(400);
    echo json_encode([
        'devoluciones' => [],
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} finally {
    // NO cerrar $conn - dejar que se cierre automáticamente
}