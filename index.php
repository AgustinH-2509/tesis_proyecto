<?php
session_start();
if (!isset($_SESSION['usuario_logueado'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Laboratorio</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <button class="toggle-btn" id="toggleButton">
                <i class="bi bi-list"></i>
            </button>
            <span class="sidebar-title">Menú</span>
        </div>

        <div class="sidebar-content">
            <nav class="nav flex-column">
                <a class="nav-link" href="#" data-content-id="inicio.php">
                    <span class="icon"><i class="bi bi-house"></i></span>
                    <span class="description">Inicio</span>
                </a>
                <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#submenu-lab" aria-expanded="false" aria-control="submenu-lab">
                    <span class="icon"><i class="bi bi-flask"></i></span>
                    <span class="description">Laboratorio <i class="bi bi-caret-down-fill"></i></span>
                </a>
                <div class="sub-menu collapse" id="submenu-lab">
                    <a class="nav-link" href="#" data-content-id="control_devoluciones.php">
                        <span class="icon"><i class="bi bi-file-earmark-check"></i></span>
                        <span class="description">Control Devoluciones</span>
                    </a>
                </div>
                <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#submenu-dev" aria-expanded="false" aria-control="submenu-dev">
                    <span class="icon"><i class="bi bi-truck"></i></span>
                    <span class="description">Devoluciones <i class="bi bi-caret-down-fill"></i> </span> 
                </a>
                <div class="sub-menu collapse" id="submenu-dev">
                    <a class="nav-link" href="#" data-content-id="nueva_devolucion.php">
                        <span class="icon"><i class="bi bi-file-earmark-plus"></i></span>
                        <span class="description">Nueva Devolución</span>
                    </a>
                    <a class="nav-link" href="#" data-content-id="historial.php">
                        <span class="icon"><i class="bi bi-clipboard"></i></span>
                        <span class="description">Historial</span>
                    </a>
                    <a class="nav-link" href="#" data-content-id="informes.php">
                        <span class="icon"><i class="bi bi-clipboard2-data"></i></span>
                        <span class="description">Informes</span>
                    </a>
                </div>
                <a class="nav-link" href="#" data-content-id="distribuidores.php">
                    <span class="icon"><i class="bi bi-person"></i></span>
                    <span class="description">Distribuidores</span>
                </a>
            </nav>
        </div>

        <div class="user-profile">
            <a href="cerrar_sesion.php" style="text-decoration: none; color: inherit;">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="bi bi-person-circle"></i> 
                    </div>
                    <div class="user-details">
                        <span class="user-name">
                            <?php
                            // Muestra el nombre del usuario si existe en la sesión
                            if (isset($_SESSION['user_name'])) {
                                echo htmlspecialchars($_SESSION['user_name']);
                            } else {
                                echo "USUARIO";
                            }
                            ?>
                        </span>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <main class="main-content" id="main-content"> 
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="scripts/codes.js" type="module" ></script>
</body>
</html>