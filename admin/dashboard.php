<?php
session_start();

// Verificar si el usuario es admin
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$nombre = $_SESSION['usuario_nombre'];

// Opcional: Manejar un mensaje de éxito/error de la redirección
$mensaje = $_GET['mensaje'] ?? ''; 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Innova Chic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    
    <div class="card shadow-sm mb-4">
        <div class="card-body text-center">
            <h1 class="fw-bold">👑 Panel de Administración</h1>
            <p class="lead">Bienvenido, <b><?= htmlspecialchars($nombre) ?></b></p>

            <?php if ($mensaje): ?>
                <div class="alert alert-success mt-3" role="alert">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <div class="d-grid gap-2 d-md-flex justify-content-center mt-3">
                <a href="../index.php" class="btn btn-outline-dark">Volver a la tienda</a>
                <a href="../logout.php" class="btn btn-danger">Cerrar sesión</a>
            </div>
        </div>
    </div>
    
    ---

    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">📦 Gestión de Productos</h3>
        </div>
        <div class="card-body">
            <div class="d-grid gap-3">
                
                <a href="productos.php" class="btn btn-success btn-lg">
                    <i class="bi bi-plus-circle-fill"></i> ➕ **Cargar Nuevo Producto**
                </a>
                
                <a href="ver_productos.php" class="btn btn-info">
                    <i class="bi bi-list-ul"></i> Ver y Editar Productos
                </a>
                <a href="ver_pedidos.php" class="btn btn-warning">
                    <i class="bi bi-cart"></i> Gestionar Pedidos
                </a>
            </div>
        </div>
    </div>
    
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</body>
</html>