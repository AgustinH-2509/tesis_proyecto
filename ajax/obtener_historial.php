<?php
include '../administrador/conexion_auto.php';

header('Content-Type: application/json');

// Lógica para obtener distribuidores si se solicita
if (isset($_GET['action']) && $_GET['action'] == 'get_distribuidores') {
    $sql = "SELECT codigo, razon_social FROM distribuidores WHERE estado = 1 ORDER BY razon_social ASC";
    $result = $conn->query($sql);
    $distribuidores = [];
    while ($row = $result->fetch_assoc()) {
        $distribuidores[] = $row;
    }
    echo json_encode(['distribuidores' => $distribuidores]);
    $conn->close();
    exit;
}

// Lógica para obtener historial de devoluciones
if (isset($_POST['distribuidor_codigo'])) {
    $distribuidor_codigo = $_POST['distribuidor_codigo'];

    // Construye la consulta de forma condicional
    $sql = "
        SELECT
            d.id,
            d.distribuidor_codigo,
            dist.razon_social AS nombre_distribuidor,
            d.distribuidor_numero,
            d.fecha_ingresa,
            dest.estado AS nombre_estado
        FROM devoluciones d
        LEFT JOIN distribuidores dist ON d.distribuidor_codigo = dist.codigo
        LEFT JOIN devoluciones_estados dest ON d.estado=dest.id
    ";

    // Si se ha seleccionado un distribuidor, agrega la cláusula WHERE
    if (!empty($distribuidor_codigo)) {
        $sql .= " WHERE d.distribuidor_codigo = ?";
    }

    $sql .= " ORDER BY d.fecha_ingresa DESC";

    $stmt = $conn->prepare($sql);

    // Si se ha seleccionado un distribuidor, enlaza el parámetro
    if (!empty($distribuidor_codigo)) {
        $stmt->bind_param("s", $distribuidor_codigo);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $devoluciones = [];
    while ($row = $result->fetch_assoc()) {
        $devoluciones[] = $row;
    }

    echo json_encode(['devoluciones' => $devoluciones]);
    $stmt->close();
    $conn->close();
    exit;
}

echo json_encode(['error' => 'No se ha especificado ninguna acción.']);