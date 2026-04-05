<?php
include '../administrador/conexion_auto.php'; 

// Configura las cabeceras para forzar la descarga del archivo CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="devolucion_detalle.csv');

// Escribe el Byte Order Mark (BOM) para compatibilidad con Excel
echo "\xEF\xBB\xBF";

// Crear un archivo temporal para la salida
$output = fopen('php://output', 'w');

// Escribe los encabezados de las columnas
fputcsv($output, array('Número de Devolución', 'Distribuidor', 'Fecha', 'Producto', 'Cantidad', 'KG', 'Motivo', 'Vencimiento', 'Observaciones'));

// Verifica si se ha pasado un ID de devolución válido en la URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    fclose($output);
    exit;
}

$id_devolucion = $_GET['id'];

// Obtiene los datos principales de la devolución
$sql_devolucion = "
    SELECT 
        d.distribuidor_numero, 
        dist.razon_social AS nombre_distribuidor,
        d.fecha_ingresa
    FROM devoluciones d
    LEFT JOIN distribuidores dist ON d.distribuidor_codigo = dist.codigo
    WHERE d.id = ?
";
$stmt = $conn->prepare($sql_devolucion);
$stmt->bind_param("i", $id_devolucion);
$stmt->execute();
$result_devolucion = $stmt->get_result();
$devolucion = $result_devolucion->fetch_assoc();
$stmt->close();

// Obtiene los productos detallados de la devolución
$sql_detalle = "
    SELECT 
        dd.cantidad,
        dd.kg,
        dd.observaciones,
        p.codigo AS codigo_producto,
        p.nombre AS nombre_producto,
        s.nombre AS nombre_sabor,
        dm.motivos AS nombre_motivo,
        dd.vencimiento
    FROM devoluciones_detalle dd
    JOIN productos p ON dd.producto_cod = p.id
    LEFT JOIN sabores s ON p.sabor = s.id
    JOIN devoluciones_motivos dm ON dd.motivos_devolucion = dm.id
    WHERE dd.devolucion = ?
";
$stmt_detalle = $conn->prepare($sql_detalle);
$stmt_detalle->bind_param("i", $id_devolucion);
$stmt_detalle->execute();
$result_detalle = $stmt_detalle->get_result();
$detalle_productos = [];
while ($row = $result_detalle->fetch_assoc()) {
    $detalle_productos[] = $row;
}
$stmt_detalle->close();
$conn->close();

if ($devolucion && !empty($detalle_productos)) {
    foreach ($detalle_productos as $producto) {
        $rowData = array(
            $devolucion['distribuidor_numero'],
            $devolucion['nombre_distribuidor'],
            $devolucion['fecha_ingresa'],
            $producto['codigo_producto'] . ' - ' . $producto['nombre_producto'] . (isset($producto['nombre_sabor']) ? ' (' . $producto['nombre_sabor'] . ')' : ''),
            $producto['cantidad'],
            $producto['kg'],
            $producto['nombre_motivo'],
            $producto['vencimiento'],
            $producto['observaciones']
        );
        fputcsv($output, $rowData);
    }
}

fclose($output);
exit;