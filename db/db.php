<?php
// Datos conexión
$servername = "localhost";
$username = "tangsrud"; // Cambia si tienes otro usuario
$password = "tangsrud"; // Coloca tu contraseña
$dbname = "productos_tangsrud"; // Tu base de datos

// Crear la conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Comprobar la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>
