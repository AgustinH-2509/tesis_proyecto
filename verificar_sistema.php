<?php
include 'administrador/conexion_auto.php'; 

if ($connection_failed) {
    die("❌ Error de conexión: " . $connection_error);
}

echo "✅ Conexión a base de datos: OK<br><br>";

// Verificar tabla productos
$sql = "SHOW COLUMNS FROM productos";
$result = $conn->query($sql);
echo "<h3>📋 Estructura tabla productos:</h3>";
if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr><td>".$row['Field']."</td><td>".$row['Type']."</td><td>".$row['Null']."</td><td>".$row['Key']."</td></tr>";
    }
    echo "</table><br>";
} else {
    echo "❌ Error al verificar tabla productos<br>";
}

// Verificar tabla devoluciones
$sql = "SHOW COLUMNS FROM devoluciones";
$result = $conn->query($sql);
echo "<h3>📋 Estructura tabla devoluciones:</h3>";
if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr><td>".$row['Field']."</td><td>".$row['Type']."</td><td>".$row['Null']."</td><td>".$row['Key']."</td></tr>";
    }
    echo "</table><br>";
} else {
    echo "❌ Error al verificar tabla devoluciones<br>";
}

// Verificar tabla devoluciones_detalle
$sql = "SHOW COLUMNS FROM devoluciones_detalle";
$result = $conn->query($sql);
echo "<h3>📋 Estructura tabla devoluciones_detalle:</h3>";
if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr><td>".$row['Field']."</td><td>".$row['Type']."</td><td>".$row['Null']."</td><td>".$row['Key']."</td></tr>";
    }
    echo "</table><br>";
} else {
    echo "❌ Error al verificar tabla devoluciones_detalle<br>";
}

// Contar registros
echo "<h3>📊 Conteo de registros:</h3>";
$tables = ['productos', 'distribuidores', 'devoluciones', 'devoluciones_detalle', 'devoluciones_motivos'];
foreach ($tables as $table) {
    $sql = "SELECT COUNT(*) as count FROM $table";
    $result = $conn->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
        echo "• $table: " . $row['count'] . " registros<br>";
    }
}

// Verificar algunos productos ejemplo
echo "<h3>🔍 Productos ejemplo (primeros 5):</h3>";
$sql = "SELECT iD, codigo, nombre FROM productos WHERE estado = 1 ORDER BY codigo LIMIT 5";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID único</th><th>Código</th><th>Nombre</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr><td>".$row['iD']."</td><td>".$row['codigo']."</td><td>".$row['nombre']."</td></tr>";
    }
    echo "</table><br>";
}

$conn->close();
echo "<h2>✅ Sistema listo para pruebas</h2>";
?>