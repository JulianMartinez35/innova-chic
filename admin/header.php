<!-- admin/header.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrador - Innova Chic</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #343a40;
        }
        .navbar-brand, .nav-link {
            color: white !important;
        }
        .nav-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="productos.php">Innova Chic - Admin</a>
        <div>
            <a href="productos.php" class="nav-link d-inline">Productos</a>
            <a href="agregar_producto.php" class="nav-link d-inline">Agregar Producto</a>
            <a href="../logout.php" class="nav-link d-inline text-danger">Cerrar Sesión</a>
        </div>
    </div>
</nav>
<div class="container mt-4">
