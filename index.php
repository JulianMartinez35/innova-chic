<?php
// Incluir conexión a la base de datos
include 'includes/db.php';

// Consulta de productos
$query = "SELECT * FROM productos";
$resultado = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Innova Chic - Catálogo</title>
    

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Estilos personalizados -->
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #e83e8c;
        }
        .navbar-brand {
            color: white !important;
            font-weight: bold;
        }
        .card {
            border: none;
            transition: transform 0.2s;
        }
        .card:hover {
            transform: scale(1.03);
        }
        .card-img-top {
            height: 250px;
            object-fit: cover;
        }
        footer {
            background-color: #e83e8c;
            color: white;
            text-align: center;
            padding: 10px 0;
            margin-top: 40px;
        }
    </style>
</head>
<body>

<!-- Encabezado -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php">Innova Chic</a>
        <div>
            <a href="login.php" class="btn btn-light btn-sm me-2">Iniciar Sesión</a>
            <a href="registro.php" class="btn btn-light btn-sm">Registrarse</a>
        </div>
    </div>
</nav>

<!-- Catálogo -->
<div class="container mt-5">
    <h2 class="text-center mb-4">Catálogo de Productos</h2>
    <div class="row g-4">
        <?php if ($resultado && $resultado->num_rows > 0): ?>
            <?php while ($producto = $resultado->fetch_assoc()): ?>
                <div class="col-md-3">
                    <div class="card h-100 shadow-sm">
                        <img src="assets/images/<?php echo htmlspecialchars($producto['imagen']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                        <div class="card-body text-center">
                            <h5 class="card-title"><?php echo htmlspecialchars($producto['nombre']); ?></h5>
                            <p class="card-text text-muted"><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                            <p class="fw-bold">$<?php echo number_format($producto['precio'], 2, ',', '.'); ?></p>
                            <a href="carrito.php?agregar=<?php echo $producto['id']; ?>" class="btn btn-outline-dark btn-sm">Agregar al carrito</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center">
                <p>No hay productos disponibles.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Pie de página -->
<footer>
    <p>&copy; <?php echo date("Y"); ?> Innova Chic - Todos los derechos reservados</p>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
