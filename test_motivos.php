<?php
// Archivo de prueba para verificar obtener_motivos_rechazo.php

echo "<h1>Test: obtener_motivos_rechazo.php</h1>";

echo "<h2>1. Verificar conexión AJAX directamente:</h2>";
echo "<pre>";
echo "URL: ajax/control_devolucion/obtener_motivos_rechazo.php\n\n";

$url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . '/htdocs/ajax/control_devolucion/obtener_motivos_rechazo.php';
echo "Dirección completa: " . $url . "\n";
echo "</pre>";

echo "<h2>2. Intentar obtener datos via PHP:</h2>";
echo "<pre>";

require_once 'administrador/conexion_auto.php';

if (!isset($conn) || $conn === null) {
    echo "❌ ERROR: Conexión no disponible\n";
} else if (isset($connection_failed) && $connection_failed) {
    echo "❌ ERROR: Fallo de conexión: " . $connection_error . "\n";
} else {
    echo "✅ Conexión exitosa\n\n";
    
    $sql = "SELECT id, motivo FROM motivos_rechazos WHERE estado = 1 ORDER BY id ASC";
    echo "Ejecutando: " . $sql . "\n\n";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        echo "❌ ERROR en query: " . $conn->error . "\n";
    } else {
        echo "Filas encontradas: " . $result->num_rows . "\n\n";
        
        if ($result->num_rows > 0) {
            echo "Datos:\n";
            while ($row = $result->fetch_assoc()) {
                echo "  ID: " . $row['id'] . ", Motivo: " . $row['motivo'] . "\n";
            }
            echo "\nJSON que debería devolver:\n";
            $result->data_seek(0);
            $motivos = [];
            while ($row = $result->fetch_assoc()) {
                $motivos[] = [
                    'id' => (int)$row['id'],
                    'nombre' => (string)$row['motivo']
                ];
            }
            echo json_encode($motivos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            echo "❌ No hay motivos con estado = 1\n";
            
            echo "\nIntentando sin filtro:\n";
            $sql2 = "SELECT id, motivo FROM motivos_rechazos LIMIT 5";
            $result2 = $conn->query($sql2);
            if ($result2 && $result2->num_rows > 0) {
                while ($row = $result2->fetch_assoc()) {
                    echo "  ID: " . $row['id'] . ", Motivo: " . $row['motivo'] . ", Estado: " . ($row['estado'] ?? 'N/A') . "\n";
                }
            }
        }
    }
}

echo "</pre>";

echo "<h2>3. Hacer prueba desde el navegador:</h2>";
echo "<button onclick=\"fetch('ajax/control_devolucion/obtener_motivos_rechazo.php').then(r => r.text()).then(t => {console.log('Raw:', t); return JSON.parse(t)}).then(d => console.log('JSON:', d)).catch(e => console.error('Error:', e))\">Test FETCH</button>";
echo "<p>Abre la consola (F12) para ver el resultado</p>";
?>
