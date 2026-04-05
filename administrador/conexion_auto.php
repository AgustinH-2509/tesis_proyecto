<?php
// Auto-detectar entorno y cargar conexión apropiada

// Verificar si estamos en Docker (existe variable de entorno DB_HOST)
if (isset($_ENV['DB_HOST']) || getenv('DB_HOST')) {
    // Estamos en Docker
    include_once __DIR__ . '/conexion_docker.php';
} else {
    // Estamos en servidor local/remoto
    include_once __DIR__ . '/conexion.php';
}
?>