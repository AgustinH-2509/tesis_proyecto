<?php
// Asegúrate de que la ruta a tu archivo de conexión sea correcta.
include '../administrador/conexion_auto.php'; 

header('Content-Type: application/json');

// Verificar conexión
if (isset($connection_failed) && $connection_failed) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

// Recibir el código del distribuidor de la solicitud POST o JSON.
$distribuidorCodigo = '';

// Leer input una sola vez
$rawInput = file_get_contents('php://input');

// Intentar obtener de POST tradicional
if (isset($_POST['distribuidor_codigo']) && !empty($_POST['distribuidor_codigo'])) {
    $distribuidorCodigo = $_POST['distribuidor_codigo'];
} else if (!empty($rawInput)) {
    // Intentar obtener de JSON en el cuerpo de la petición
    $jsonData = json_decode($rawInput, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($jsonData['distribuidor_codigo'])) {
        $distribuidorCodigo = $jsonData['distribuidor_codigo'];
    } else {
        // También intentar desde URL encoded
        parse_str($rawInput, $parsedData);
        if (isset($parsedData['distribuidor_codigo'])) {
            $distribuidorCodigo = $parsedData['distribuidor_codigo'];
        }
    }
}

$response = [
    'success' => false,
    'message' => 'Error desconocido.',
    'debug' => [
        'method' => $_SERVER['REQUEST_METHOD'],
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not_set',
        'post_data' => $_POST,
        'raw_input' => substr($rawInput, 0, 100) . (strlen($rawInput) > 100 ? '...' : ''),
        'input_length' => strlen($rawInput),
        'distribuidor_received' => $distribuidorCodigo
    ]
];

if (!empty($distribuidorCodigo)) {
    // Consulta para obtener el último número de devolución para el distribuidor seleccionado.
    $stmt = $conn->prepare("SELECT MAX(distribuidor_numero) AS ultimo_numero FROM devoluciones WHERE distribuidor_codigo = ?");
    $stmt->bind_param("s", $distribuidorCodigo);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    $siguienteNumero = ($row['ultimo_numero'] !== null) ? $row['ultimo_numero'] + 1 : 1;

    $response = [
        'success' => true,
        'numero_devolucion' => $distribuidorCodigo . '-' . str_pad($siguienteNumero, 3, '0', STR_PAD_LEFT),
        'siguiente_numero_raw' => $siguienteNumero
    ];
} else {
    $response['message'] = 'Código de distribuidor no proporcionado.';
}

echo json_encode($response);
?>