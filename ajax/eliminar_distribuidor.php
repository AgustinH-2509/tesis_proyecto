<?php
header('Content-Type: application/json');
require_once '../administrador/conexion_auto.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = $_POST['codigo'] ?? '';

    if (empty($codigo)) {
        echo json_encode(['success' => false, 'message' => 'Falta el código del distribuidor']);
        exit;
    }

    // Eliminación lógica: estado = 0
    $stmt = $conn->prepare("UPDATE distribuidores SET estado = 0 WHERE codigo = ?");
    if ($stmt) {
        $stmt->bind_param("s", $codigo);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Distribuidor eliminado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar distribuidor: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
