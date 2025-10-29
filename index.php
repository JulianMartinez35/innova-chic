<?php
// Incluir conexión a la base de datos
include 'includes/db.php';

// ==============================================================
// 1. OBTENER PRODUCTOS Y SUS VARIANTES COMPLETAS
// NOTA: Para este script JS, necesitamos el ID de la variante y el stock individual.
// ==============================================================

$query_productos = "SELECT * FROM productos";
$resultado_productos = $conn->query($query_productos);

$productos_para_js = []; // Array para el JSON de JavaScript
$productos_con_variantes = []; // Array para el bucle de la vista

if ($resultado_productos && $resultado_productos->num_rows > 0) {
    while ($producto = $resultado_productos->fetch_assoc()) {
        $producto_id = $producto['id'];
        
        // Obtener TODAS las variantes activas (stock > 0)
        $query_variantes = "SELECT id, talle, color, stock FROM producto_variantes WHERE producto_id = ? AND stock > 0";
        
        $stmt_variantes = $conn->prepare($query_variantes);
        
        if ($stmt_variantes) {
            $stmt_variantes->bind_param("i", $producto_id);
            $stmt_variantes->execute();
            $resultado_variantes = $stmt_variantes->get_result();
            
            $variantes = [];
            while ($variante = $resultado_variantes->fetch_assoc()) {
                // Asegurar que 'id' y 'stock' sean números para el JSON
                $variante['id'] = (int)$variante['id'];
                $variante['stock'] = (int)$variante['stock'];
                $variantes[] = $variante;
            }
            $stmt_variantes->close();
            
            $producto['variantes_list'] = $variantes; // Lista completa para el JS

            // Preparar arrays de talles y colores únicos para la vista HTML
            $producto['talles_unicos'] = array_unique(array_column($variantes, 'talle'));
            $producto['colores_unicos'] = array_unique(array_column($variantes, 'color'));

        } else {
            $producto['variantes_list'] = [];
            $producto['talles_unicos'] = [];
            $producto['colores_unicos'] = [];
        }
        
        // Agregar a la lista para el bucle de la vista
        $productos_con_variantes[] = $producto;

        // Crear el objeto para el JS (solo con los datos necesarios)
        $productos_para_js[] = [
            'id' => (int)$producto_id,
            'variantes' => $producto['variantes_list']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Innova Chic - Catálogo</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body { background-color: #f8f9fa; }
        .navbar { background-color: #e83e8c; }
        .navbar-brand { color: white !important; font-weight: bold; }
        .card { border: none; transition: transform 0.2s; }
        .card:hover { transform: scale(1.03); }
        .card-img-top { height: 250px; object-fit: cover; }
        .select-group { display: flex; gap: 5px; margin-bottom: 10px; }
        .select-group select { flex-grow: 1; }
        footer { background-color: #e83e8c; color: white; text-align: center; padding: 10px 0; margin-top: 40px; }
        .stock-feedback { font-size: 0.85rem; height: 1.2rem; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php">Innova Chic</a>
        <div>
            <a href="login.php" class="btn btn-light btn-sm me-2">Iniciar Sesión</a>
            <a href="registro.php" class="btn btn-light btn-sm">Registrarse</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <h2 class="text-center mb-4">Catálogo de Productos</h2>
    <div class="row g-4">
        <?php if (!empty($productos_con_variantes)): ?>
            <?php foreach ($productos_con_variantes as $producto): ?>
                <div class="col-md-3">
                    <div class="card h-100 shadow-sm">
                        <img src="assets/images/<?php echo htmlspecialchars($producto['imagen']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                        <div class="card-body text-center d-flex flex-column justify-content-between">
                            <div>
                                <h5 class="card-title"><?php echo htmlspecialchars($producto['nombre']); ?></h5>
                                <p class="card-text text-muted"><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                                <p class="fw-bold">$<?php echo number_format($producto['precio'], 2, ',', '.'); ?></p>
                            </div>
                            
                            <form action="carrito.php" method="GET" class="mt-2">
                                <input type="hidden" name="variante_id" class="variante-id" data-producto="<?php echo $producto['id']; ?>" value="">

                                <?php if (!empty($producto['variantes_list'])): ?>
                                    
                                    <div class="select-group mb-2">
                                        <select class="form-select form-select-sm talle-select" name="talle" required data-producto="<?php echo $producto['id']; ?>">
                                            <option value="" disabled selected>Talle</option>
                                            <?php foreach ($producto['talles_unicos'] as $talle): ?>
                                                <option value="<?php echo htmlspecialchars($talle); ?>">
                                                    <?php echo htmlspecialchars($talle); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <select class="form-select form-select-sm color-select" name="color" required data-producto="<?php echo $producto['id']; ?>">
                                            <option value="" disabled selected>Color</option>
                                            <?php foreach ($producto['colores_unicos'] as $color): ?>
                                                <option value="<?php echo htmlspecialchars($color); ?>">
                                                    <?php echo htmlspecialchars($color); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="stock-feedback text-start mb-2">
                                        <small id="stockText<?php echo $producto['id']; ?>" class="text-muted"></small>
                                    </div>

                                    <div class="mb-3">
                                        <input type="number" 
                                               class="form-control form-control-sm text-center" 
                                               name="cantidad" 
                                               value="1" 
                                               min="1" 
                                               max="1"
                                               disabled
                                               required>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success btn-sm w-100" disabled>🛒 Agregar al Carrito</button>

                                <?php else: ?>
                                    <p class="text-danger small">Agotado o sin variantes.</p>
                                    <button type="button" class="btn btn-outline-dark btn-sm w-100" disabled>Sin Stock</button>
                                <?php endif; ?>

                            </form>
                            </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center">
                <p>No hay productos disponibles.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer>
    <p>&copy; <?php echo date("Y"); ?> Innova Chic - Todos los derechos reservados</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // 1. Cargar los datos de PHP a JavaScript
    // Usamos json_encode con JSON_NUMERIC_CHECK para asegurar que los IDs y Stocks sean números.
    const productos = <?php echo json_encode($productos_para_js, JSON_NUMERIC_CHECK); ?>;

    /**
     * Actualiza el stock, habilita/deshabilita elementos y establece el ID de la variante.
     * @param {number} productoId - El ID del producto actual.
     */
    function actualizarVariante(productoId) {
        // Seleccionar elementos específicos del formulario (usando el data-producto para evitar colisiones)
        const color = document.querySelector(`.color-select[data-producto="${productoId}"]`).value;
        const talle = document.querySelector(`.talle-select[data-producto="${productoId}"]`).value;
        const stockText = document.getElementById(`stockText${productoId}`);
        const inputVariante = document.querySelector(`.variante-id[data-producto="${productoId}"]`);
        
        // Buscar el formulario para encontrar el botón y la cantidad
        const form = inputVariante.closest('form');
        const botonAgregar = form.querySelector('button[type="submit"]');
        const inputCantidad = form.querySelector('input[name="cantidad"]');

        // Resetear el estado
        stockText.textContent = '';
        inputVariante.value = "";
        botonAgregar.disabled = true;
        inputCantidad.disabled = true;
        inputCantidad.min = 1;
        inputCantidad.max = 1;
        inputCantidad.value = 1;

        if (color && talle) {
            // 2. Buscar el producto y la variante específica en los datos JS
            const producto = productos.find(p => p.id === productoId);
            // El operador ?. es útil para evitar errores si producto o variantes es undefined
            const variante = producto?.variantes?.find(v => v.color === color && v.talle === talle);

            if (variante) {
                // Si la variante existe
                inputVariante.value = variante.id; // ¡CRÍTICO! Enviamos el ID de la variante
                
                if (variante.stock > 0) {
                    // Hay stock
                    stockText.innerHTML = `<span class="text-success">Stock disponible: ${variante.stock}</span>`;
                    botonAgregar.disabled = false;
                    inputCantidad.disabled = false;
                    inputCantidad.max = variante.stock;
                    inputCantidad.value = 1; // Reseteamos a 1
                } else {
                    // Stock agotado
                    stockText.innerHTML = '<span class="text-danger">Agotado para esta combinación.</span>';
                    // El resto de elementos ya están deshabilitados por el reset inicial
                }
            } else {
                // Combinación no disponible
                stockText.textContent = "Combinación no disponible";
            }
        } else {
            // Faltan selecciones (el reset inicial ya maneja esto)
            stockText.textContent = "Selecciona talle y color";
        }
    }

    // 3. Asignar el evento 'change' a todos los selectores de talle y color
    document.querySelectorAll('.color-select, .talle-select').forEach(select => {
        select.addEventListener('change', function () {
            // El data-producto es un string, lo convertimos a número para la búsqueda
            const productoId = parseInt(this.dataset.producto);
            actualizarVariante(productoId);
        });
    });

    // 4. Inicializar estados al cargar página (desactivar botones hasta seleccionar)
    document.querySelectorAll('form').forEach(form => {
        const inputVariante = form.querySelector('.variante-id');
        if (inputVariante) {
            const productoId = parseInt(inputVariante.dataset.producto);
            actualizarVariante(productoId);
        }
    });

    // 5. Opcional: Validar cantidad en tiempo real (si el usuario ingresa un número mayor al stock)
    document.querySelectorAll('input[name="cantidad"]').forEach(input => {
        input.addEventListener('input', function() {
            const maxStock = parseInt(this.max);
            const cantidad = parseInt(this.value);

            if (cantidad > maxStock) {
                this.value = maxStock;
            }
            if (cantidad < 1) {
                this.value = 1;
            }
        });
    });
</script>
</body>
</html>