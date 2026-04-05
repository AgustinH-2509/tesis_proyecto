<?php
header('Content-Type: application/json');
require_once '../administrador/conexion_auto.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID de usuario no proporcionado.']);
        exit;
    }

    // Prevenir auto-eliminación o eliminación del super admin
    session_start();
    if (isset($_SESSION['id']) && $_SESSION['id'] == $id) {
        echo json_encode(['success' => false, 'message' => 'No puedes eliminar tu propio usuario.']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM usuarios WHERE ID = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Usuario eliminado correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
