<?php
session_start();
session_destroy(); // Eliminar todas las sesiones
header("Location: login.php"); // Redirigir al usuario al formulario de inicio de sesión
exit(); // Asegurarse de que el script termine aquí
?>