<?php
include 'db/db.php';

// Seleccionar todas las contraseñas sin encriptar
$query = "SELECT usuario, password FROM credenciales";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    $usuario = $row['usuario'];
    $password = $row['password'];

    // Encriptar la contraseña usando password_hash
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Actualizar la contraseña en la base de datos
    $update_query = "UPDATE credenciales SET password = ? WHERE usuario = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('ss', $password_hash, $usuario);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
echo "Contraseñas encriptadas correctamente.";
?>