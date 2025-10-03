<?php
session_start();

// Si no está autenticado, redirigir al login
if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    header("Location: login.php");
    exit;
}

include 'db/db.php';

// Inicializar arrays vacíos para evitar errores si no se devuelven resultados
$marcas = [];
$generos = [];
$categorias = [];

// Query para obtener las marcas
$query_marcas = "SELECT DISTINCT `marca` FROM `productos_variables`";
$result_marcas = $conn->query($query_marcas);
if ($result_marcas) {
    while ($row = $result_marcas->fetch_assoc()) {
        $marcas[] = $row['marca'];
    }
}

// Query para obtener los géneros
$query_generos = "SELECT DISTINCT `genero` FROM `productos_variables`";
$result_generos = $conn->query($query_generos);
if ($result_generos) {
    while ($row = $result_generos->fetch_assoc()) {
        $generos[] = $row['genero'];
    }
}

// Query para obtener las categorías
$query_categorias = "SELECT DISTINCT `categorias` FROM `productos_variables`";
$result_categorias = $conn->query($query_categorias);
if ($result_categorias) {
    while ($row = $result_categorias->fetch_assoc()) {
        $categorias[] = $row['categorias'];
    }
}

// Inicializar las condiciones de los filtros
$filtros = [];

// Filtrar por marcas seleccionadas
if (isset($_GET['marca']) && !empty($_GET['marca'])) {
    $marcas_seleccionadas = $_GET['marca'];
    $marcas_filtradas = implode(',', array_map(function($marca) use ($conn) {
        return "'" . $conn->real_escape_string($marca) . "'";
    }, $marcas_seleccionadas));
    $filtros[] = "pv.marca IN ($marcas_filtradas)";
}

// Filtrar por géneros seleccionados
if (isset($_GET['genero']) && !empty($_GET['genero'])) {
    $generos_seleccionados = $_GET['genero'];
    $generos_filtrados = implode(',', array_map(function($genero) use ($conn) {
        return "'" . $conn->real_escape_string($genero) . "'";
    }, $generos_seleccionados));
    $filtros[] = "pv.genero IN ($generos_filtrados)";
}

// Filtrar por categorías seleccionadas
if (isset($_GET['categoria']) && !empty($_GET['categoria'])) {
    $categorias_seleccionadas = $_GET['categoria'];
    $categorias_filtradas = implode(',', array_map(function($categoria) use ($conn) {
        return "'" . $conn->real_escape_string($categoria) . "'";
    }, $categorias_seleccionadas));
    $filtros[] = "pv.categorias IN ($categorias_filtradas)";
}

// Construir la consulta base para productos simples y variables
$query_todos_productos = "
    SELECT 
    pv.sku AS sku_producto, 
    pv.nombre AS nombre_producto, 
    pv.precio_normal, 
    pv.imagenes, 
    pv.categorias AS categoria_producto, 
    pv.marca AS marca, 
    pv.genero AS genero, 
    IF(pv.tipo = 'simple', 'Única', v.talla) AS talla_variacion, 
    IF(pv.tipo = 'simple', pv.inventario, v.inventario) AS inventario_variacion
FROM productos_variables pv
LEFT JOIN productos_variaciones v 
    ON pv.sku = v.superior_variable 
";

// Si hay filtros, agregarlos a la consulta
if (!empty($filtros)) {
    $query_todos_productos .= " WHERE " . implode(' AND ', $filtros);
}

// Ordenar por SKU y talla
$query_todos_productos .= " ORDER BY pv.sku, talla_variacion;";

// Ejecutar la consulta
$result_todos_productos = $conn->query($query_todos_productos);

// Array para agrupar productos simples y variables con variaciones
$productos = [];
while ($row = $result_todos_productos->fetch_assoc()) {
    // Agrupar las variaciones (o productos simples) por sku
    $sku_producto = $row['sku_producto'];
    
    if (!isset($productos[$sku_producto])) {
        // Inicializa el array para un nuevo producto simple o variable
        $productos[$sku_producto] = [
            'nombre' => $row['nombre_producto'],
            'precio' => $row['precio_normal'],
            'imagenes' => $row['imagenes'],
            'categoria' => $row['categoria_producto'],
            'marca' => $row['marca'],
            'genero' => $row['genero'],
            'variaciones' => []
        ];
    }
    
    // Añadir la variación (o talla única para productos simples)
    $productos[$sku_producto]['variaciones'][] = [
        'talla' => $row['talla_variacion'],
        'stock' => $row['inventario_variacion']
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Catálogo de Productos</title>
        <link href="css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    </head>
    <body>
    <header>
    <nav class="navbar navbar-expand-lg navbar-light bg-body-tertiary">
        <!-- Logo -->
        <a class="navbar-brand" href="#">
            <img class="d-block" src="imagenes/logo-tangsrud-green.png" height="50" alt="Tangsrud" loading="lazy"/>
        </a>

        <!-- Botón para colapsar el navbar en pantallas pequeñas -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Contenedor del navbar -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Links de la izquierda -->
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="#">Pedla</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Elevenate</a></li>
                <li class="nav-item"><a class="nav-link" href="#">7mesh</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Filson</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Bergans</a></li>
            </ul>

            <!-- Botón Logout a la derecha -->
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="btn btn-danger" href="logout.php" type="button">
                        <img src="imagenes/switch.png" height="20"> Salir
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</header>
    <!--Menu Offcanvas para filtrar -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasFiltros" aria-labelledby="offcanvasExampleLabel">
        <div class="offcanvas-header">
            <!-- Botón cerrar -->
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <!-- checkboxes para filtrar y enviar el form -->
            <form method="GET" action="catalogo.php">
                <!-- Filtro de Marcas -->
                <div class="form-check">
                    <h3>Marcas</h3>
                    <?php foreach ($marcas as $marca): ?>
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="marca[]" value="<?php echo htmlspecialchars($marca); ?>"
                                <?php if (isset($_GET['marca']) && in_array($marca, $_GET['marca'])) echo 'checked'; ?>>
                            <?php echo htmlspecialchars($marca); ?>
                        </label><br>
                    <?php endforeach; ?>
                </div>

                <!-- Filtro de Género -->
                <div class="form-check">
                    <h3>Género</h3>
                    <?php foreach ($generos as $genero): ?>
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="genero[]" value="<?php echo htmlspecialchars($genero); ?>"
                                <?php if (isset($_GET['genero']) && in_array($genero, $_GET['genero'])) echo 'checked'; ?>>
                            <?php echo htmlspecialchars($genero); ?>
                        </label><br>
                    <?php endforeach; ?>
                </div>

                <!-- Filtro de Categoría -->
                <div class="form-check">
                    <h3>Categoría</h3>
                    <?php foreach ($categorias as $categoria): ?>
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="categoria[]" value="<?php echo htmlspecialchars($categoria); ?>"
                                <?php if (isset($_GET['categoria']) && in_array($categoria, $_GET['categoria'])) echo 'checked'; ?>>
                            <?php echo htmlspecialchars($categoria); ?>
                        </label><br>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="btn btn-primary">Filtrar</button>
            </form>
        </div>
    </div>

    <div class="container">
        <!-- Botón para llamar al offcanvas -->
        <div class="p-3">
            <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasFiltros" aria-controls="offcanvas">
                Filtrar Productos
            </button>
            <a href="catalogo.php" class="btn btn-danger">Borrar filtros</a>
        </div>

        <div class="row">
            <!-- Mostrar productos simples y variables -->
            <?php foreach ($productos as $sku_producto => $producto): ?>
                <div class="col-md-3 mb-4">
                    <div class="card h-100 shadow-sm p-1 bg-white rounded">
                        <img src="<?php echo $producto['imagenes']; ?>" class="card-img-top img-clickable" alt="<?php echo $producto['nombre']; ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $producto['nombre']; ?></h5>
                            <p class="card-text">SKU: <?php echo $sku_producto; ?></p> <!-- Aquí usamos el SKU dinámico -->
                            <p class="card-text">Marca: <?php echo $producto['marca']; ?></p>
                            <p class="card-text">Género: <?php echo $producto['genero']; ?></p>
                            <p class="card-text">Precio: €<?php echo number_format($producto['precio'], 2); ?></p>

                            <!-- Mostrar tabla de variaciones (tallas y stock) -->
                            <div class="container text-center">
                                <table class="table table-info">
                                    <thead>
                                        <tr>
                                            <?php foreach ($producto['variaciones'] as $variacion): ?>
                                                <th scope="col"><?php echo $variacion['talla']; ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <?php foreach ($producto['variaciones'] as $variacion): ?>
                                                <td><?php echo $variacion['stock']; ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div> 

    <!-- Modal Bootstrap para mostrar la imagen al 100% -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
        <div class="modal-body">
            <img src="" id="modalImage" class="img-fluid" alt="Imagen del producto">
        </div>
        </div>
    </div>
    </div>
    <script>
    // JavaScript para abrir el modal con la imagen seleccionada
    document.querySelectorAll('.img-clickable').forEach(img => {
        img.addEventListener('click', function() {
            // Obtener la URL de la imagen clicada
            let imgSrc = this.src;
            // Cambiar el src de la imagen del modal
            document.getElementById('modalImage').src = imgSrc;
            // Mostrar el modal
            var myModal = new bootstrap.Modal(document.getElementById('imageModal'));
            myModal.show();
        });
    });
    </script>

    <!-- Incluir Bootstrap y jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
