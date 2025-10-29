<?php
// Lógica de seguridad y conexión
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include '../includes/db.php'; 
include 'header.php'; 

// Inicializar variables
$productos = [];
$mensaje = $_GET['mensaje'] ?? '';

// 1. Lógica para obtener productos de la base de datos
// Se utiliza LEFT JOIN y SUM para obtener el stock total de todas las variantes
$sql = "
    SELECT 
        p.id, 
        p.nombre, 
        p.precio, 
        p.imagen,
        COALESCE(SUM(v.stock), 0) AS total_stock 
    FROM 
        productos p
    LEFT JOIN 
        producto_variantes v ON p.id = v.producto_id
    GROUP BY 
        p.id, p.nombre, p.precio, p.imagen
    ORDER BY 
        p.id DESC
";
$resultado = $conn->query($sql);

if ($resultado) {
    if ($resultado->num_rows > 0) {
        // Almacenar todos los productos en un array
        while($fila = $resultado->fetch_assoc()) {
            // Renombramos 'total_stock' por 'stock' para mantener la compatibilidad con el HTML
            $fila['stock'] = $fila['total_stock']; 
            $productos[] = $fila;
        }
    }
} else {
    // Manejo de error de la consulta
    $mensaje = "<div class='alert alert-danger'>Error al obtener productos: " . $conn->error . "</div>";
}
?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📋 Listado de Productos</h2>
        <a href="agregar_producto.php" class="btn btn-success">➕ Agregar Nuevo Producto</a>
    </div>

    <?php 
    // Muestra mensajes de éxito o error (ej. después de una carga o eliminación)
    if (!empty($mensaje)): 
        // Lógica mejorada para determinar la clase de alerta
        $alert_class = (strpos($mensaje, 'danger') !== false || strpos($mensaje, 'Error') !== false) ? 'alert-danger' : 'alert-success';
    ?>
        <div class="alert <?= $alert_class ?>" role="alert">
            <?= strip_tags($mensaje, '<div>') ?>
        </div>
    <?php endif; ?>

    <?php if (count($productos) > 0): ?>
        <div class="table-responsive shadow-sm">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>Stock Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $producto): ?>
                    <tr>
                        <td><?= htmlspecialchars($producto['id']) ?></td>
                        <td>
                            <img src="../assets/images/<?= htmlspecialchars($producto['imagen']) ?>" alt="<?= htmlspecialchars($producto['nombre']) ?>" style="width: 50px; height: 50px; object-fit: cover;">
                        </td>
                        <td><?= htmlspecialchars($producto['nombre']) ?></td>
                        <td>$<?= number_format($producto['precio'], 2) ?></td>
                        <td>
                            <span class="badge <?= $producto['stock'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                <?= htmlspecialchars($producto['stock']) ?>
                            </span>
                            <small class="d-block mt-1">
                                <a href="editar_producto.php?id=<?= $producto['id'] ?>#variantes">
                                    (Ver Variantes)
                                </a>
                            </small>
                        </td>
                        <td>
                            <a href="editar_producto.php?id=<?= $producto['id'] ?>" class="btn btn-sm btn-primary me-2">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                            <a href="eliminar_producto.php?id=<?= $producto['id'] ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('¿Estás seguro de que quieres eliminar este producto?');">
                                <i class="bi bi-trash"></i> Eliminar
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center mt-5">
            Aún no hay productos cargados en la tienda.
        </div>
    <?php endif; ?>
</div>

<?php 
include 'footer.php'; 
?>