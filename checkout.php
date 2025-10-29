<?php
// ===============================
// 1. SEGURIDAD Y SESIÓN
// ===============================
session_start();

// Asume que la información de usuario se guarda en $_SESSION['usuario'] al loguearse
if (!isset($_SESSION['usuario'])) {
    // Si el usuario no está logueado, redirigir al login
    header("Location: login.php?redirect=checkout.php");
    exit;
}

include 'includes/db.php'; // Incluir la conexión a la base de datos
include 'includes/header.php'; // Incluir el encabezado de la tienda

// ===============================
// 2. VALIDACIÓN DEL CARRITO
// ===============================
if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    $mensaje = "Tu carrito está vacío. Agrega productos para finalizar la compra.";
    header("Location: carrito.php?mensaje=" . urlencode($mensaje));
    exit;
}

// ===============================
// 3. CÁLCULO DE TOTALES (Debe ser consistente con carrito.php)
// ===============================
$subtotal = 0;
$impuestos_porcentaje = 0.10; // Ejemplo: 10% de impuestos
$costo_envio = 5.00; // Costo fijo de envío
$productos_carrito = $_SESSION['carrito'];

foreach ($productos_carrito as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
}

$impuestos = $subtotal * $impuestos_porcentaje;
$total = $subtotal + $impuestos + $costo_envio;

// ===============================
// 4. PROCESAMIENTO DEL PAGO
// ===============================
$mensaje_pago = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // Recolección y saneamiento de datos del formulario
    $direccion = trim($_POST['direccion'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $codigo_postal = trim($_POST['codigo_postal'] ?? '');
    $metodo_pago = $_POST['metodo_pago'] ?? '';
    
    // Validación simple
    if (empty($direccion) || empty($ciudad) || empty($codigo_postal) || empty($metodo_pago)) {
        $mensaje_pago = "<div class='alert alert-danger'>Por favor, completa todos los campos de envío y selecciona un método de pago.</div>";
    } else {
        
        // 🚨 4.1. Lógica de Pasarela de Pago (Requiere integración real) 🚨
        // ******************************************************************
        
        $pago_exitoso = true; // Simulación: cambiar a la respuesta real de la pasarela
        
        if ($pago_exitoso) {
            
            // 4.2. INICIO DE TRANSACCIÓN CRÍTICA
            $conn->begin_transaction();
            try {
                // 4.3. 🗄️ Insertar el Pedido Principal 
                $usuario_id = $_SESSION['usuario']['id']; // Asume que el ID está en la sesión
                $estado = 'Pendiente'; 

                $sql_pedido = "INSERT INTO pedidos (usuario_id, fecha_pedido, total, estado, direccion_envio, metodo_pago) 
                               VALUES (?, NOW(), ?, ?, ?, ?)";
                $stmt_pedido = $conn->prepare($sql_pedido);
                $stmt_pedido->bind_param("idsss", $usuario_id, $total, $estado, $direccion, $metodo_pago);
                
                if (!$stmt_pedido->execute()) {
                    throw new Exception("Error al guardar el pedido principal.");
                }
                
                $pedido_id = $conn->insert_id;
                $stmt_pedido->close();

                // 4.4. 📝 Insertar el Detalle del Pedido y Actualizar Stock
                $sql_detalle = "INSERT INTO detalle_pedido (pedido_id, producto_id, variante_id, cantidad, precio_unitario) 
                                VALUES (?, ?, ?, ?, ?)";
                $stmt_detalle = $conn->prepare($sql_detalle);

                $sql_stock = "UPDATE producto_variantes SET stock = stock - ? WHERE id = ?";
                $stmt_stock = $conn->prepare($sql_stock);

                foreach ($productos_carrito as $item) {
                    // Insertar detalle
                    $stmt_detalle->bind_param("iiiid", $pedido_id, $item['producto_id'], $item['variante_id'], $item['cantidad'], $item['precio']);
                    if (!$stmt_detalle->execute()) {
                         throw new Exception("Error al guardar detalle del producto: " . htmlspecialchars($item['nombre']));
                    }

                    // Actualizar stock
                    $stmt_stock->bind_param("ii", $item['cantidad'], $item['variante_id']);
                    if (!$stmt_stock->execute()) {
                         throw new Exception("Error al actualizar stock para el producto: " . htmlspecialchars($item['nombre']));
                    }
                    
                    // Opcional: Verificar que el stock se haya reducido correctamente (rowCount > 0)
                }
                
                $stmt_detalle->close();
                $stmt_stock->close();
                
                // 4.5. 🟢 COMMIT: Confirmar todo
                $conn->commit();
                
                // Limpiar carrito y redirigir
                unset($_SESSION['carrito']);
                $mensaje_final = urlencode("¡Tu pedido #$pedido_id ha sido realizado con éxito!");
                header("Location: confirmacion.php?pedido_id=$pedido_id&mensaje=" . $mensaje_final);
                exit;

            } catch (Exception $e) {
                // 4.6. 🔴 ROLLBACK: Deshacer todo si algo falló
                $conn->rollback();
                $mensaje_pago = "<div class='alert alert-danger'>Error al procesar el pedido: " . htmlspecialchars($e->getMessage()) . ". Reintenta.</div>";
            }
            
        } else {
            // Error de la pasarela
            $mensaje_pago = "<div class='alert alert-danger'>El pago fue rechazado. Verifica tu información o intenta con otro método.</div>";
        }
    }
}
?>

<div class="container mt-5 mb-5">
    <h2 class="mb-4">💳 Finalizar Compra (Checkout)</h2>

    <?= $mensaje_pago ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5>1. Dirección de Envío</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="direccion" class="form-label">Dirección Completa</label>
                            <input type="text" class="form-control" id="direccion" name="direccion" required 
                                   value="<?= htmlspecialchars($_POST['direccion'] ?? $_SESSION['usuario']['direccion'] ?? '') ?>">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="ciudad" class="form-label">Ciudad</label>
                                <input type="text" class="form-control" id="ciudad" name="ciudad" required 
                                   value="<?= htmlspecialchars($_POST['ciudad'] ?? $_SESSION['usuario']['ciudad'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="codigo_postal" class="form-label">Código Postal</label>
                                <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" required 
                                   value="<?= htmlspecialchars($_POST['codigo_postal'] ?? $_SESSION['usuario']['codigo_postal'] ?? '') ?>">
                            </div>
                        </div>

                        <h5 class="mt-4 mb-3">2. Método de Pago</h5>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="metodo_pago" id="mp_tarjeta" value="Tarjeta de Crédito/Débito" required>
                            <label class="form-check-label" for="mp_tarjeta">
                                💳 Tarjeta de Crédito/Débito (Pasarela Externa)
                            </label>
                        </div>
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="radio" name="metodo_pago" id="mp_transferencia" value="Transferencia Bancaria" required>
                            <label class="form-check-label" for="mp_transferencia">
                                🏦 Transferencia Bancaria (Manual)
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-success btn-lg w-100">Finalizar y Pagar $<?php echo number_format($total, 2, ',', '.'); ?></button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5>Resumen del Pedido</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush mb-3">
                        <?php foreach ($productos_carrito as $item): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center p-2">
                                <span><?= htmlspecialchars($item['nombre']) ?> (x<?= $item['cantidad'] ?>)</span>
                                <span>$<?= number_format($item['precio'] * $item['cantidad'], 2, ',', '.') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <ul class="list-group list-group-flush border-top pt-2">
                        <li class="list-group-item d-flex justify-content-between">
                            Subtotal:
                            <span>$<?= number_format($subtotal, 2, ',', '.') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            Envío:
                            <span>$<?= number_format($costo_envio, 2, ',', '.') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            Impuestos (<?= $impuestos_porcentaje * 100 ?>%):
                            <span>$<?= number_format($impuestos, 2, ',', '.') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between fw-bold bg-light">
                            Total a Pagar:
                            <span class="fs-5 text-success">$<?= number_format($total, 2, ',', '.') ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; // Incluir el pie de página ?>