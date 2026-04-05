<?php
// Asegúrate de que la ruta a tu archivo de conexión sea correcta.
include 'conexion_auto.php'; 

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtén y sanitiza los datos del formulario
    $codigo = $conn->real_escape_string($_POST['codigo']);
    $razon_social = $conn->real_escape_string($_POST['razon_social']);

    // Prepara y ejecuta la consulta SQL para insertar los datos
    $sql = "INSERT INTO distribuidores (codigo, razon_social) VALUES ('$codigo', '$razon_social')";

    if ($conn->query($sql) === TRUE) {
        // Envía una respuesta de éxito al cliente
        echo json_encode(["success" => true, "message" => "Nuevo distribuidor agregado correctamente."]);
    } else {
        // Envía una respuesta de error
        echo json_encode(["success" => false, "message" => "Error: " . $sql . "<br>" . $conn->error]);
    }

    $conn->close();
} else {
    echo json_encode(["success" => false, "message" => "Método de solicitud no válido."]);
}
?>