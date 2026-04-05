<?php
// Verificación del sistema
echo "<h1>Verificación del Sistema</h1>";

echo "<h2>PHP</h2>";
echo "Versión: " . phpversion() . "<br>";
echo "Extensión MySQLi: " . (extension_loaded('mysqli') ? '✅ Cargada' : '❌ No cargada') . "<br>";
echo "Extensión PDO: " . (extension_loaded('pdo') ? '✅ Cargada' : '❌ No cargada') . "<br>";

echo "<h2>Servidor Web</h2>";
echo "Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

echo "<h2>Configuración PHP</h2>";
echo "error_reporting: " . error_reporting() . "<br>";
echo "display_errors: " . ini_get('display_errors') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";

echo "<h2>Base de Datos</h2>";
require_once 'administrador/conexion_auto.php';

if (!isset($conn) || $conn === null) {
    echo "❌ ERROR: Conexión no disponible<br>";
} else if (isset($connection_failed) && $connection_failed) {
    echo "❌ ERROR: Fallo de conexión: " . $connection_error . "<br>";
} else {
    echo "✅ Conexión exitosa<br>";
    echo "Host: " . $conn->host_info . "<br>";
    echo "Versión: " . $conn->server_version . "<br>";
    
    // Verificar tablas
    $tables = ['motivos_rechazos', 'devoluciones', 'devoluciones_detalle', 'distribuidores', 'productos'];
    echo "<h3>Tablas</h3>";
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        $exists = ($result && $result->num_rows > 0) ? '✅' : '❌';
        echo "$exists $table<br>";
    }
    
    // Verificar datos en motivos_rechazos
    echo "<h3>Datos en motivos_rechazos</h3>";
    $result = $conn->query("SELECT COUNT(*) as count FROM motivos_rechazos");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Total de registros: " . $row['count'] . "<br>";
    }
    
    $result = $conn->query("SELECT COUNT(*) as count FROM motivos_rechazos WHERE estado = 1");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Con estado = 1: " . $row['count'] . "<br>";
    }
}
?>
