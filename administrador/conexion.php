<?php

$servername = "sql209.infinityfree.com";
$username = "if0_39915287"; // Reemplaza con tu usuario de la base de datos
$password = "Asgore935"; // Reemplaza con tu contraseña de la base de datos
$dbname = "if0_39915287_sistema";

// Crea la conexión a la base de datos
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica si la conexión falló
if ($conn->connect_error) {
    // Para llamadas AJAX, establecer flag de error sin usar die()
    $connection_failed = true;
    $connection_error = $conn->connect_error;
    
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