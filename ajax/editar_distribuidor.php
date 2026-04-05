<?php
header('Content-Type: application/json');
require_once '../administrador/conexion_auto.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = $_POST['codigo'] ?? '';
    $razon_social = $_POST['razon_social'] ?? '';

    if (empty($codigo) || empty($razon_social)) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
        exit;
    }

    // Verificar duplicados de razón social (excluyendo el propio distribuidor)
    $stmt_check = $conn->prepare("SELECT codigo, razon_social FROM distribuidores WHERE razon_social = ? AND codigo != ?");
    $stmt_check->bind_param("ss", $razon_social, $codigo);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        $duplicados = [];
        while ($row = $result_check->fetch_assoc()) {
            $duplicados[] = $row;
        }
        echo json_encode([
            'success' => false, 
            'message' => 'La razón social ya existe en otro distribuidor',
            'duplicados' => $duplicados
        ]);
        $stmt_check->close();
        exit;
    }
    $stmt_check->close();

    $stmt = $conn->prepare("UPDATE distribuidores SET razon_social = ? WHERE codigo = ?");
    if ($stmt) {
        $stmt->bind_param("ss", $razon_social, $codigo);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Distribuidor actualizado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar distribuidor: ' . $stmt->error]);
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
