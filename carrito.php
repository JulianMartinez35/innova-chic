<?php
session_start();
include('includes/db.php');

// Verificamos si el usuario está logueado
if (!isset($_SESSION['usuario']['id'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['usuario']['id'];

// ---- AGREGAR PRODUCTO AL CARRITO ----
if (isset($_GET['agregar'])) {
    $id_producto = intval($_GET['agregar']);

    // Verificamos si el producto ya existe en el carrito
    $existe = $conn->query("SELECT * FROM carrito WHERE id_usuario=$id_usuario AND id_producto=$id_producto");

    if ($existe->num_rows > 0) {
        // Si ya está en el carrito, aumentamos la cantidad
        $conn->query("UPDATE carrito SET cantidad = cantidad + 1 WHERE id_usuario=$id_usuario AND id_producto=$id_producto");
    } else {
        // Si no está, lo agregamos
        $conn->query("INSERT INTO carrito (id_usuario, id_producto, cantidad) VALUES ($id_usuario, $id_producto, 1)");
    }

    header("Location: carrito.php");
    exit();
}

// ---- ELIMINAR PRODUCTO ----
if (isset($_GET['eliminar'])) {
    $id_carrito = intval($_GET['eliminar']);
    $conn->query("DELETE FROM carrito WHERE id=$id_carrito AND id_usuario=$id_usuario");
    header("Location: carrito.php");
    exit();
}

// ---- ACTUALIZAR CANTIDADES ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar'])) {
    foreach ($_POST['cantidades'] as $id_carrito => $cantidad) {
        $cantidad = max(1, intval($cantidad)); // Evita cantidades 0 o negativas
        $conn->query("UPDATE carrito SET cantidad=$cantidad WHERE id=$id_carrito AND id_usuario=$id_usuario");
    }
    header("Location: carrito.php");
    exit();
}

// ---- OBTENER PRODUCTOS DEL CARRITO ----
$result = $conn->query("
    SELECT c.id, p.nombre, p.precio, c.cantidad, p.imagen 
    FROM carrito c
    JOIN productos p ON c.id_producto = p.id
    WHERE c.id_usuario=$id_usuario
");

$total = 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carrito de Compras - Innova Chic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">🛍️ Innova Chic</a>
        <div class="d-flex">
            <a href="index.php" class="btn btn-outline-light me-2">Seguir Comprando</a>
            <a href="logout.php" class="btn btn-danger">Cerrar Sesión</a>
        </div>
    </div>
</nav>

<div class="container my-5">
    <h2 class="mb-4 text-center">🛒 Tu Carrito</h2>

    <?php if ($result->num_rows > 0): ?>
        <form method="POST">
            <table class="table table-bordered table-striped text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Imagen</th>
                        <th>Producto</th>
                        <th>Precio</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $result->fetch_assoc()): 
                        $subtotal = $item['precio'] * $item['cantidad'];
                        $total += $subtotal;
                    ?>
                    <tr>
                        <td><img src="uploads/<?php echo $item['imagen']; ?>" width="70"></td>
                        <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                        <td>$<?php echo number_format($item['precio'], 2); ?></td>
                        <td>
                            <input type="number" name="cantidades[<?php echo $item['id']; ?>]" 
                                   value="<?php echo $item['cantidad']; ?>" 
                                   min="1" class="form-control text-center" style="width:80px; margin:auto;">
                        </td>
                        <td>$<?php echo number_format($subtotal, 2); ?></td>
                        <td>
                            <a href="?eliminar=<?php echo $item['id']; ?>" class="btn btn-danger btn-sm">
                                🗑️ Eliminar
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div class="text-end">
                <h4>Total: <strong>$<?php echo number_format($total, 2); ?></strong></h4>
                <button type="submit" name="actualizar" class="btn btn-warning">Actualizar Cantidades</button>
                <a href="checkout.php" class="btn btn-success">Proceder al Pago</a>
            </div>
        </form>
    <?php else: ?>
        <div class="alert alert-info text-center">Tu carrito está vacío 😢</div>
    <?php endif; ?>
</div>

</body>
</html>
