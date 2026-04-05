<?php
// Debug directo del endpoint
header('Content-Type: text/plain; charset=utf-8');

echo "=== DEBUG: obtener_motivos_rechazo.php ===\n\n";

// Capturar el output del archivo
ob_start();
include 'ajax/control_devolucion/obtener_motivos_rechazo.php';
$output = ob_get_clean();

echo "Output raw (hex):\n";
echo bin2hex($output) . "\n\n";

echo "Output raw (string):\n";
echo $output . "\n\n";

echo "Output length: " . strlen($output) . " bytes\n\n";

echo "Primeros 100 caracteres:\n";
echo substr($output, 0, 100) . "\n\n";

// Intentar parsear
echo "Intentando parsear como JSON:\n";
try {
    $json = json_decode($output, true);
    if ($json === null) {
        echo "JSON Error: " . json_last_error_msg() . "\n";
        echo "JSON Error Code: " . json_last_error() . "\n";
    } else {
        echo "✅ JSON válido\n";
        echo "Tipo: " . gettype($json) . "\n";
        echo "Contenido:\n";
        var_dump($json);
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
?>
