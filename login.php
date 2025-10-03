<?php
session_start();
include 'db/db.php';

// Si ya está autenticado, redirige a catalogo.php
if (isset($_SESSION['autenticado']) && $_SESSION['autenticado'] === true) {
    header("Location: catalogo.php");
    exit;
}

// Comprobar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recoger los valores del formulario
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];
    $pregunta_seguridad = strtolower(str_replace(' ', '', $_POST['pregunta_seguridad'])); // Normalizar la respuesta

    // Consulta para buscar las credenciales en la tabla credenciales
    $query = "SELECT * FROM credenciales WHERE usuario = ? AND pregunta_seguridad = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $usuario, $pregunta_seguridad); // Vincula los parámetros

    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        // Obtener el registro encontrado
        $row = $resultado->fetch_assoc();

        // Verificar si la contraseña es correcta usando password_verify
        if (password_verify($password, $row['password'])) {
            $_SESSION['autenticado'] = true;
            header("Location: catalogo.php");
            exit;
        } else {
            $error = "Contraseña incorrecta.";
        }
    } else {
        $error = "Credenciales incorrectas. Verifica tus datos.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al catalogo</title>
    <link href="css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
</head>
<body>
<header>
    <nav class="navbar navbar-light bg-light d-flex p-3 mb-3">
        <div class="p-2">
            <a class="navbar-brand" href="#">
                <img src="imagenes/logo-tangsrud-green.png" width="100">
            </a>
        </div>
        <div class="d-flex justify-content-start">
            <h2>Acceso a Catalogo</h2>
        </div>
    </nav>
</header>
<div class="container d-flex justify-content-center align-items-center w-25 p-3 border border-grey rounded-2">
    <form class="needs-validation" style="margin-bottom: 0px" action="login.php" method="post" novalidate>
        <div class="row">
            <div class="col-md mb-3">
                <label for="usuario">Usuario</label>
                <input type="text" class="form-control" id="usuario" name="usuario" required>
                <div class="valid-feedback">¡Perfecto!</div>
                <div class="invalid-feedback">Introduce el usuario.</div>
            </div>
        </div>
        <div class="row">
            <div class="col-md mb-3">
                <label for="password">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <div class="valid-feedback">¡Perfecto!</div>
                <div class="invalid-feedback">Introduce la contraseña.</div>
            </div>
        </div>
        <div class="form-row">
            <div class="col-md mb-3">
                <label for="pregunta_seguridad">¿Quién es el mejor actor del mundo?</label>
                <input type="text" class="form-control" id="pregunta_seguridad" name="pregunta_seguridad" placeholder="En minúsculas y sin espacios" required>
                <div class="valid-feedback">¡Perfecto!</div>
                <div class="invalid-feedback">Introduce la respuesta.</div>
            </div>
        </div>
        <button class="btn btn-primary" type="submit">Enviar</button>

        <!-- Mostrar mensaje de error si ocurre un problema -->
        <?php if (isset($error)): ?>
            <div class="alert alert-danger mt-3"><?php echo $error; ?></div>
        <?php endif; ?>
    </form>
</div>

<script>
// Example starter JavaScript for disabling form submissions if there are invalid fields
(function() {
  'use strict';
  window.addEventListener('load', function() {
    var forms = document.getElementsByClassName('needs-validation');
    var validation = Array.prototype.filter.call(forms, function(form) {
      form.addEventListener('submit', function(event) {
        if (form.checkValidity() === false) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  }, false);
})();
</script>
</body>
</html>
