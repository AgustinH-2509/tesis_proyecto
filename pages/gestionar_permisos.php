<?php
session_start();
require_once '../administrador/verificar_permiso.php';

// Verificación estricta backend
$rol_id_actual = $_SESSION['rol_id'] ?? 0;
if (!isset($_SESSION['usuario_logueado']) || !tienePermiso($rol_id_actual, 'gestionar_permisos.php')) {
    echo "<div class='alert alert-danger'>Acceso denegado.</div>";
    exit();
}

$ruta_json = '../administrador/permisos.json';
$permisos = file_exists($ruta_json) ? json_decode(file_get_contents($ruta_json), true) : [];

require_once '../administrador/conexion_auto.php';

$stmt = $conn->query("SELECT id, nombre FROM roles ORDER BY id ASC");
$roles = [];
if ($stmt) {
    while ($r = $stmt->fetch_assoc()) {
        $roles[] = $r;
    }
}

// Catálogo de todos los módulos disponibles
$modulosSistema = [
    "inicio.php" => "Inicio",
    "control_devoluciones.php" => "Control de Devoluciones",
    "nueva_devolucion.php" => "Nueva Devolución",
    "historial.php" => "Historial",
    "informes.php" => "Informes",
    "distribuidores.php" => "Distribuidores",
    "gestionar_permisos.php" => "Gestionar Permisos"
];
?>
<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-shield-lock"></i> Gestión de Accesos (RBAC)</h5>
        </div>
        <div class="card-body">
            <form id="form-permisos">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Módulo</th>
                                <?php foreach ($roles as $rol): ?>
                                    <th><?= htmlspecialchars(ucfirst($rol['nombre'])) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($modulosSistema as $modulo => $descripcion): ?>
                            <tr>
                                <td class="text-start fw-bold"><?= $descripcion ?></td>
                                <?php foreach ($roles as $rol): 
                                    $rol_loop_id = $rol['id'];
                                    $is_vip = ($rol_loop_id == 3);
                                    $is_checked = $is_vip || (isset($permisos[$rol_loop_id]) && in_array($modulo, $permisos[$rol_loop_id]));
                                ?>
                                <td>
                                    <?php if ($is_vip): ?>
                                      <!-- Pase mágico VIP, mandamos un input escondido para que siempre llegue al backend o al JS -->
                                      <input type="hidden" name="permisos[<?= $rol_loop_id ?>][]" value="<?= $modulo ?>">
                                    <?php endif; ?>
                                    <input class="form-check-input check-permiso" type="checkbox" 
                                        name="permisos[<?= $rol_loop_id ?>][]" value="<?= $modulo ?>" 
                                        <?= $is_checked ? 'checked' : '' ?> 
                                        <?= $is_vip ? 'disabled' : '' ?>>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-success" id="btnGuardarPermisos"><i class="bi bi-save"></i> Guardar Cambios</button>
                </div>
            </form>
            <div id="permisosAlert" class="mt-3"></div>
        </div>
    </div>
</div>
