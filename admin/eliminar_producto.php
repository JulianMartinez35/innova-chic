<?php
session_start();

// Verificar si el usuario está logueado y es admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include '../includes/db.php';
include 'header.php';

// Definir la ruta absoluta de la carpeta de imágenes
$target_dir = __DIR__ . '/../../assets/images/';

// 2. Obtener y validar ID (Usando Sentencias Preparadas)
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger'>ID de producto no especificado o inválido.</div>";
    include 'footer.php';
    exit;
}
$id = $_GET['id'];

// 3. Obtener datos del producto ANTES de eliminar (Preparado y seguro)
$sql_select = "SELECT imagen, nombre FROM productos WHERE id = ?";
$stmt_select = $conn->prepare($sql_select);
$stmt_select->bind_param("i", $id); // 'i' para integer
$stmt_select->execute();
$result = $stmt_select->get_result();

if ($result->num_rows == 0) {
    echo "<div class='alert alert-danger'>Producto no encontrado.</div>";
    include 'footer.php';
    exit;
}

$producto = $result->fetch_assoc();
$stmt_select->close();


// 4. Confirmar y ejecutar la eliminación
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Iniciar transacción de eliminación (CRUCIAL para la seguridad)
    $sql_delete = "DELETE FROM productos WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id); 
    
    if ($stmt_delete->execute()) {
        
        // 5. Eliminar la imagen física del servidor
        $imagen_a_eliminar = $producto['imagen'];
        if ($imagen_a_eliminar) {
            $ruta_imagen = $target_dir . $imagen_a_eliminar;
            
            // Verificamos que el archivo exista antes de intentar borrarlo
            if (file_exists($ruta_imagen)) {
                if (unlink($ruta_imagen)) {
                    // Mensaje de éxito si se borra la imagen y el registro
                    $mensaje_final = "Producto '" . htmlspecialchars($producto['nombre']) . "' y su imagen eliminados correctamente.";
                } else {
                    // Mensaje si falla solo la eliminación de la imagen
                    $mensaje_final = "Producto eliminado. Advertencia: No se pudo eliminar el archivo de imagen del servidor.";
                }
            } else {
                 // Mensaje si el archivo no existía
                 $mensaje_final = "Producto eliminado. Advertencia: El archivo de imagen no se encontró en el servidor.";
            }
        } else {
             $mensaje_final = "Producto eliminado correctamente.";
        }
        
        // Redirigir con mensaje de éxito (buena práctica PRG)
        header("Location: ver_productos.php?mensaje=" . urlencode($mensaje_final));
        exit;
        
    } else {
        echo "<div class='alert alert-danger'>Error al eliminar el registro: " . $stmt_delete->error . "</div>";
    }
    $stmt_delete->close();
}
?>

<div class="container mt-5 mb-5">
    <h2 class="mb-4 text-center text-danger">🗑️ Eliminar Producto</h2>
    <p class="text-center lead">**¡ADVERTENCIA!** Estás a punto de eliminar permanentemente el siguiente producto:</p>

    <div class="card mx-auto shadow" style="max-width: 400px;">
        <img src="../assets/images/<?php echo htmlspecialchars($producto['imagen']); ?>" class="card-img-top" alt="Imagen de <?php echo htmlspecialchars($producto['nombre']); ?>">
        
        <div class="card-body text-center">
            <h5 class="card-title fw-bold"><?php echo htmlspecialchars($producto['nombre']); ?></h5>
            <p class="card-text text-muted"><?php echo htmlspecialchars($producto['descripcion']); ?></p>
            <p class="fw-bold fs-4 text-danger">$<?php echo number_format($producto['precio'], 2, ',', '.'); ?></p>

            <form method="POST" class="mt-4">
                <button type="submit" class="btn btn-lg btn-danger w-100 mb-2">Sí, Eliminar Permanentemente</button>
                <a href="ver_productos.php" class="btn btn-secondary w-100">Cancelar y Volver</a>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>