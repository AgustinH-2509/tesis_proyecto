<?php
function tienePermiso($rol_id, $modulo) {
    // Pase VIP: Rol 3 (Prueba / Administrador supremo) tiene acceso a todo
    if ($rol_id == 3) {
        return true;
    }

    $ruta_json = __DIR__ . '/permisos.json';
    if (!file_exists($ruta_json)) {
        return false;
    }

    $json_data = file_get_contents($ruta_json);
    $permisos = json_decode($json_data, true);

    if (!$permisos || !isset($permisos[$rol_id])) {
        return false;
    }

    return in_array($modulo, $permisos[$rol_id]);
}
?>
