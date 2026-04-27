<?php
session_start();
if (!isset($_SESSION['usuario_logueado'])) {
    header("Location: login.php");
    exit();
}
require_once '../administrador/conexion_auto.php';

// Obtener roles para el form
$sql_roles = "SELECT id, nombre FROM roles WHERE estado = 1";
$res_roles = $conn->query($sql_roles);
$roles = [];
while ($row = $res_roles->fetch_assoc()) {
    $roles[] = $row;
}

// Obtener usuarios
$sql = "SELECT u.ID, u.nombre, r.nombre AS rol_nombre, r.id AS rol_id, u.distribuidor_codigo, d.razon_social AS distribuidor_nombre 
        FROM usuarios u 
        JOIN roles r ON u.rol_id = r.id 
        LEFT JOIN distribuidores d ON u.distribuidor_codigo = d.codigo";
$result = $conn->query($sql);
?>
<div class="row w-100 mt-2 mb-2 p-1 fade-in">
    <div class="col-12 mt-1">
        <div class="filter-header mb-1">
            <h2 class="mb-0 text-white">Gestionar Usuarios</h2>
            <button class="btn add-product-btn" data-bs-toggle="modal" data-bs-target="#usuarioModal"
                onclick="window.abrirModalUsuario()">
                <i class="bi bi-person-plus"></i> Nuevo Usuario
            </button>
        </div>

        <div class="table-container p-3 mb-2 shadow bg-white rounded">
            <div class="search-container mb-3 position-relative">
                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="text" id="search-usuario" class="form-control rounded-pill ps-5"
                    placeholder="Buscar por nombre o rol...">
            </div>

            <table class="table table-hover align-middle custom-table" id="usuarios-table">
                <thead class="table-light">
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Rol</th>
                        <th scope="col">Distribuidor Relacionado</th>
                        <th scope="col" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr data-id="<?= $row['ID'] ?>" data-nombre="<?= htmlspecialchars($row['nombre']) ?>"
                                data-rol="<?= $row['rol_id'] ?>" data-distribuidor="<?= $row['distribuidor_codigo'] ?>">
                                <td><span class="badge bg-secondary"><?= $row['ID'] ?></span></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($row['nombre']) ?></td>
                                <td><span class="badge bg-primary"><?= ucfirst(htmlspecialchars($row['rol_nombre'])) ?></span>
                                </td>
                                <td>
                                    <?php if ($row['distribuidor_codigo']): ?>
                                        <?= htmlspecialchars($row['distribuidor_nombre'] ?? '') ?> (<?= $row['distribuidor_codigo'] ?>)
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary btn-accion btn-edit" title="Editar">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger btn-accion btn-delete" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No hay usuarios registrados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Crear/Editar Usuario (Reutilizable globalmente si se monta) -->
<div class="modal fade" id="usuarioModal" tabindex="-1" aria-labelledby="usuarioModalLabel" aria-hidden="true"
    data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header text-white"
                style="background: linear-gradient(135deg, #FF6B6B 0%, #D32F2F 100%); border-top-left-radius: 12px; border-top-right-radius: 12px;">
                <h5 class="modal-title fw-bold" id="usuarioModalLabel"><i class="bi bi-person-badge me-2"></i><span
                        id="modal-title-text">Añadir Usuario</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div id="usuario-error-container" class="mb-3"></div>
                <form id="form-usuario">
                    <input type="hidden" id="usr_id" name="id">

                    <div class="mb-3">
                        <label for="usr_nombre" class="form-label fw-bold text-secondary">Nombre de Usuario</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control mb-0" id="usr_nombre" name="nombre"
                                placeholder="DISTRIBUIDOR123" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="usr_password" class="form-label fw-bold text-secondary">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-key"></i></span>
                            <input type="text" class="form-control mb-0" id="usr_password" name="password"
                                placeholder="Clave de acceso">
                        </div>
                        <div class="form-text" id="usr_password_help">Dejar en blanco al editar si no deseas cambiarla.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="usr_rol" class="form-label fw-bold text-secondary">Rol de Sistema</label>
                        <select class="form-select" id="usr_rol" name="rol_id" required>
                            <option value="">Seleccione un rol...</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= ucfirst(htmlspecialchars($r['nombre'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="usr_distribuidor" class="form-label fw-bold text-secondary">Código Distribuidor
                            Vinculado (Opcional)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-building"></i></span>
                            <input type="number" class="form-control mb-0" id="usr_distribuidor"
                                name="distribuidor_codigo" placeholder="Ej. 4063">
                        </div>
                        <div class="form-text">Dejar en blanco si es usuario administrativo/laboratorio.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-top-0"
                style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                <button type="button" class="btn btn-secondary px-4 rounded-pill shadow-sm"
                    data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger px-4 rounded-pill shadow-sm" id="btnGuardarUsuario">Guardar
                    Usuario</button>
            </div>
        </div>
    </div>
</div>