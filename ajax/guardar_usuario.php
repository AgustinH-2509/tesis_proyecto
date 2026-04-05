<?php
header('Content-Type: application/json');
require_once '../administrador/conexion_auto.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;
    $nombre = $_POST['nombre'] ?? '';
    // Password might be empty on edits if we don't want to change it
    $password = $_POST['password'] ?? ''; 
    $rol_id = isset($_POST['rol_id']) ? intval($_POST['rol_id']) : null;
    $distribuidor_codigo = isset($_POST['distribuidor_codigo']) && $_POST['distribuidor_codigo'] !== '' ? intval($_POST['distribuidor_codigo']) : null;

    if (empty($nombre) || empty($rol_id)) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios (Nombre y Rol).']);
        exit;
    }

    if (!$id && empty($password)) {
        echo json_encode(['success' => false, 'message' => 'La contraseña es obligatoria para nuevos usuarios.']);
        exit;
    }

    $conn->begin_transaction();

    try {
        if ($id) {
            // Actualizar usuario existente
            if (!empty($password)) {
                $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, password = ?, rol_id = ?, distribuidor_codigo = ? WHERE ID = ?");
                $stmt->bind_param("ssiii", $nombre, $password, $rol_id, $distribuidor_codigo, $id);
            } else {
                $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, rol_id = ?, distribuidor_codigo = ? WHERE ID = ?");
                $stmt->bind_param("siii", $nombre, $rol_id, $distribuidor_codigo, $id);
            }
        } else {
            // Crear nuevo usuario
            // Validar que el nombre no exista ya
            $check = $conn->prepare("SELECT ID FROM usuarios WHERE nombre = ?");
            $check->bind_param("s", $nombre);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                throw new Exception("El nombre de usuario ya existe en el sistema.");
            }
            $check->close();

            $stmt = $conn->prepare("INSERT INTO usuarios (nombre, password, rol_id, distribuidor_codigo) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssii", $nombre, $password, $rol_id, $distribuidor_codigo);
        }

        if (!$stmt->execute()) {
            throw new Exception("Error al guardar: " . $stmt->error);
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Usuario guardado exitosamente.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
