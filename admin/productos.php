<?php
// 1. Seguridad: Verificar sesión de administrador
session_start();
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include '../includes/db.php';
include 'header.php';

// Obtener mensajes de éxito/error de las redirecciones
$mensaje = $_GET['mensaje'] ?? '';

// 2. Obtener los productos (USANDO JOIN para CALCULAR el stock total de VARIANTEs)
$sql = "
    SELECT 
        p.id, 
        p.nombre, 
        p.precio, 
        p.imagen,
        -- COALESCE suma el stock de todas las variantes (v.stock) para cada producto (p.id)
        COALESCE(SUM(v.stock), 0) AS total_stock 
    FROM 
        productos p
    -- LEFT JOIN asegura que los productos sin variantes (stock 0) se muestren igual
    LEFT JOIN 
        producto_variantes v ON p.id = v.producto_id
    GROUP BY 
        p.id, p.nombre, p.precio, p.imagen -- Agrupamos por producto para que SUM funcione
    ORDER BY 
        p.id DESC
";
$resultado = $conn->query($sql);

// Verificar si la consulta fue exitosa
if (!$resultado) {
    // Si la consulta falló, muestra error
    $mensaje = "<div class='alert alert-danger'>Error al obtener productos: " . $conn->error . "</div>";
    $productos = []; // Inicializamos el array vacío para el HTML
} else {
     $productos = $resultado->fetch_all(MYSQLI_ASSOC); // Obtener todos los resultados de una vez
}
?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">📋 Listado de Productos</h2>
        <a href="agregar_producto.php" class="btn btn-success">+ Agregar Nuevo Producto</a>
    </div>

    <?php 
    // 3. Mostrar mensajes (éxito o error)
    if (!empty($mensaje)): 
        // Determinar la clase de alerta
        $alert_class = (strpos($mensaje, 'danger') !== false || strpos($mensaje, 'Error') !== false) ? 'alert-danger' : 'alert-success';
    ?>
        <div class="alert <?= $alert_class ?>" role="alert">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <?php if (count($productos) > 0): ?>
        <div class="table-responsive shadow-sm">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark text-center">
                    <tr>
                        <th>ID</th>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>Stock Total</th> <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $producto) { ?>
                    <tr class="text-center">
                        <td><?php echo $producto['id']; ?></td>
                        <td>
                            <img src="../assets/images/<?php echo htmlspecialchars($producto['imagen']); ?>" 
                                 width="70" height="70" style="object-fit: cover; border-radius: 4px;">
                        </td>
                        <td class="text-start"><?php echo htmlspecialchars($producto['nombre']); ?></td>
                        <td>$<?php echo number_format($producto['precio'], 2, ',', '.'); ?></td>
                        
                        <td>
                            <span class="badge <?= $producto['total_stock'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                <?php echo htmlspecialchars($producto['total_stock']); ?>
                            </span>
                             <small class="d-block mt-1">
                                <a href="editar_producto.php?id=<?= $producto['id'] ?>#variantes">
                                    (Ver Variantes)
                                </a>
                            </small>
                        </td>
                        
                        <td>
                            <a href="editar_producto.php?id=<?php echo $producto['id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                            <a href="eliminar_producto.php?id=<?php echo $producto['id']; ?>" 
                               class="btn btn-danger btn-sm" 
                               onclick="return confirm('¿Seguro que deseas eliminar este producto: <?php echo htmlspecialchars($producto['nombre']); ?>?');">
                                Eliminar
                            </a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center mt-5">
            No hay productos disponibles. <a href="agregar_producto.php">¡Carga el primero!</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>