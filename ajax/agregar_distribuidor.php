<?php
header('Content-Type: application/json');
require_once '../administrador/conexion_auto.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = $_POST['codigo'] ?? '';
    $razon_social = $_POST['razon_social'] ?? '';

    if ($codigo < 0) {
        echo json_encode(['success' => false, 'message' => 'El código no puede ser negativo']);
        exit;
    }

    // Verificar duplicados
    $stmt_check = $conn->prepare("SELECT codigo, razon_social FROM distribuidores WHERE codigo = ? OR razon_social = ?");
    $stmt_check->bind_param("ss", $codigo, $razon_social);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        $duplicados = [];
        while ($row = $result_check->fetch_assoc()) {
            $duplicados[] = $row;
        }
        echo json_encode([
            'success' => false, 
            'message' => 'El código de distribuidor o nombre ya existe en el sistema',
            'duplicados' => $duplicados
        ]);
        $stmt_check->close();
        exit;
    }
    $stmt_check->close();

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // 1. Insertar Distribuidor
        $stmt = $conn->prepare("INSERT INTO distribuidores (codigo, razon_social, estado) VALUES (?, ?, 1)");
        if (!$stmt) throw new Exception("Error preparando inserción de distribuidor: " . $conn->error);
        
        $stmt->bind_param("ss", $codigo, $razon_social);
        if (!$stmt->execute()) throw new Exception("Error al insertar distribuidor: " . $stmt->error);
        $stmt->close();

        // 2. Preparar datos de sugerencia para el frontend
        $nombre_sugerido = mb_strtoupper($razon_social, 'UTF-8');
        $reemplazos = [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N'
        ];
        $nombre_sugerido = strtr($nombre_sugerido, $reemplazos);
        $password_sugerida = $codigo;

        // Confirmar transacción (solo guardó el distribuidor)
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Distribuidor guardado correctamente. Por favor completa el alta del usuario.',
            'sugerencias' => [
                'nombre' => $nombre_sugerido,
                'password' => $password_sugerida,
                'distribuidor_codigo' => $codigo
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
