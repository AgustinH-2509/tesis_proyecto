<?php
session_start();
header('Content-Type: application/json');
require_once '../administrador/verificar_permiso.php';

$rol_id_actual = $_SESSION['rol_id'] ?? 0;
if (!isset($_SESSION['usuario_logueado']) || !tienePermiso($rol_id_actual, 'gestionar_permisos.php')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $permisos_enviados = $_POST['permisos'] ?? [];

    // Validar siempre forzar al rol 3 (VIP) a tener todo para mayor seguridad adicional en backend
    $modulosSistema = [
        "inicio.php", "control_devoluciones.php", "nueva_devolucion.php", 
        "historial.php", "informes.php", "distribuidores.php", "gestionar_permisos.php"
    ];
    $permisos_enviados[3] = $modulosSistema;

    $ruta_json = '../administrador/permisos.json';
    $json_data = json_encode($permisos_enviados, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    // Guardar permisos
    if (file_put_contents($ruta_json, $json_data) !== false) {
        echo json_encode(['success' => true, 'message' => 'Permisos guardados con éxito. Los efectos son inmediatos.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al escribir el archivo de configuración. (Revisa permisos del directorio)']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
