<?php
require_once '../administrador/conexion_auto.php';

header('Content-Type: application/json');

try {
    // Verificar productos disponibles con múltiples estructuras posibles
    $sql_options = [
        "SELECT iD as id, nombre as descripcion FROM productos ORDER BY iD LIMIT 20",
        "SELECT iD as id, COALESCE(nombre, CONCAT('Producto ', iD)) as descripcion FROM productos ORDER BY iD LIMIT 20"
    ];
    
    $productos = [];
    $sql_productos_usado = '';
    
    foreach ($sql_options as $sql) {
        try {
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                $sql_productos_usado = $sql;
                while ($row = $result->fetch_assoc()) {
                    $productos[] = $row;
                }
                break;
            }
        } catch (Exception $e) {
            continue;
        }
    }
    
    // Verificar estructura de la tabla productos
    $describe_sql = "DESCRIBE productos";
    $describe_result = $conn->query($describe_sql);
    
    $estructura = [];
    if ($describe_result) {
        while ($row = $describe_result->fetch_assoc()) {
            $estructura[] = $row;
        }
    }
    
    // Verificar foreign key constraints
    $fk_sql = "SELECT 
        CONSTRAINT_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_NAME = 'devoluciones_detalle' 
    AND TABLE_SCHEMA = DATABASE()
    AND REFERENCED_TABLE_NAME IS NOT NULL";
    
    $constraints = [];
    $fk_result = $conn->query($fk_sql);
    if ($fk_result) {
        while ($row = $fk_result->fetch_assoc()) {
            $constraints[] = $row;
        }
    }
    
    echo json_encode([
        'success' => true,
        'productos_disponibles' => $productos,
        'estructura_productos' => $estructura,
        'foreign_keys' => $constraints,
        'sql_productos_usado' => $sql_productos_usado,
        'total_productos' => count($productos)
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>