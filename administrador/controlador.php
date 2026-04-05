<?php
session_start();
include __DIR__ . "/conexion_auto.php"; // Auto-detecta Docker o local

if (isset($_POST['btn_ingresar'])) {
    
    // Check database connection before proceeding
    if (isset($connection_failed) && $connection_failed) {
        $_SESSION['error_message'] = "No hay conexión con la base de datos. Intente más tarde.";
        header("Location: ../login.php");
        exit();
    }

    $user = $_POST['user'];
    $password = $_POST['password'];

    // Validar que no estén vacíos
    if (empty($user) || empty($password)) {
        $_SESSION['error_message'] = "Usuario y contraseña son requeridos.";
        header("Location: ../login.php");
        exit();
    }

    // Usar prepared statements para prevenir SQL injection
    $sql = "SELECT u.*, r.nombre AS rol_nombre FROM usuarios u JOIN roles r ON u.rol_id = r.id WHERE u.nombre = ? AND u.password = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        $_SESSION['error_message'] = "Error en el sistema. Intente nuevamente.";
        header("Location: ../login.php");
        exit();
    }
    
    $stmt->bind_param("ss", $user, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Credentials are correct, store user info in the session
        $_SESSION['usuario_logueado'] = true;
        $_SESSION['id'] = $row['ID']; // Guardar el ID del usuario
        $_SESSION['user_name'] = $row['nombre']; // Cambiado de 'user' a 'nombre'
        $_SESSION['rol'] = $row['rol_nombre']; // Store the user's role string from roles table
        $_SESSION['rol_id'] = $row['rol_id']; // ID numérico para permisos

        // Redirect to the index page
        header("Location: ../index.php");
        exit();
    } else {
        // Incorrect credentials
        $_SESSION['error_message'] = "Usuario o contraseña incorrectos.";
        header("Location: ../login.php");
        exit();
    }
    
    $stmt->close();
}
if (isset($conn) && $conn) {
    $conn->close();
}
?>