<?php
// Configuración para Docker
$servername = $_ENV['DB_HOST'] ?? "db";
$username = $_ENV['DB_USER'] ?? "root"; 
$password = $_ENV['DB_PASSWORD'] ?? "root";
$dbname = $_ENV['DB_NAME'] ?? "sistema_devoluciones";

// Crea la conexión a la base de datos
// Habilitar reporte de errores pero capturando excepciones
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
} catch (mysqli_sql_exception $e) {
    $conn = null;
    error_log("Error de conexión a BD: " . $e->getMessage());
}

// Verifica si la conexión falló
if (!$conn || $conn->connect_error) {
    // Para llamadas AJAX, establecer flag de error sin usar die()
    $connection_failed = true;
    $connection_error = $conn ? $conn->connect_error : "Connection refused or timed out";
    
    // Solo mostrar error directo si no es una llamada AJAX y no estamos en un include
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        !isset($_SERVER['CONTENT_TYPE']) || 
        strpos($_SERVER['CONTENT_TYPE'], 'application/json') === false) {
        error_log("Database connection failed: " . $conn->connect_error);
    }
} else {
    $connection_failed = false;
    // CRÍTICO: Establecer charset a UTF-8 después de conectar
    $conn->set_charset("utf8mb4");
}
?>