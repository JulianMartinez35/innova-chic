<?php
// ===============================
// SEGURIDAD Y SESIÓN
// ===============================
session_start();
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include '../includes/db.php'; 

$mensaje = ''; 
$error_redireccion = false;
$producto_id = null; 

// ===============================
// PROCESAMIENTO DE FORMULARIO
// ===============================
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Recolección de Datos Principales
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $precio = (float)($_POST['precio'] ?? 0);

    // 2. Recolección de Variantes
    $variantes = $_POST['variantes'] ?? [];

    if (empty($variantes) || count($variantes) === 0) {
        $mensaje = "<div class='alert alert-danger'>Debe agregar al menos una variante (Talle/Color/Stock).</div>";
        goto end_post;
    }

    // 3. Manejo de Imagen
    $imagen = '';
    $imagen_subida_ok = false;

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $imagen_file = $_FILES['imagen'];

        // Carpeta de destino
        $target_dir = $_SERVER['DOCUMENT_ROOT'] . '/innova_chic/assets/images/';

        // Crear carpeta si no existe
        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0755, true)) {
                $mensaje = "<div class='alert alert-danger'>Error: No se pudo crear el directorio de subida de imágenes. Verifica permisos.</div>";
                goto end_post;
            }
        }

        // Validación de la imagen
        $imageFileType = strtolower(pathinfo($imagen_file['name'], PATHINFO_EXTENSION));
        $check = getimagesize($imagen_file["tmp_name"]);

        if ($check === false) {
            $mensaje = "<div class='alert alert-danger'>El archivo no es una imagen válida.</div>";
        } elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $mensaje = "<div class='alert alert-danger'>Solo se permiten archivos JPG, JPEG, PNG, GIF o WEBP.</div>";
        } else {
            $imagen = uniqid('prod_', true) . "." . $imageFileType;
            $target_file = $target_dir . $imagen;

            if (move_uploaded_file($imagen_file['tmp_name'], $target_file)) {
                $imagen_subida_ok = true;
            } else {
                $mensaje = "<div class='alert alert-danger'>Hubo un error al mover la imagen al directorio destino. Verifica permisos.</div>";
            }
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>Error de subida de imagen. Código: " . ($_FILES['imagen']['error'] ?? 'No definido') . "</div>";
    }

    // 4. Inserción en la Base de Datos (solo si la imagen se subió correctamente)
    if ($imagen_subida_ok) {
        $sql_prod = "INSERT INTO productos (nombre, descripcion, precio, imagen) VALUES (?, ?, ?, ?)";
        $stmt_prod = $conn->prepare($sql_prod);

        if ($stmt_prod) {
            $stmt_prod->bind_param("ssds", $nombre, $descripcion, $precio, $imagen);

            if ($stmt_prod->execute()) {
                $producto_id = $conn->insert_id;
                $stmt_prod->close();

                // Insertar variantes
                $sql_variante = "INSERT INTO producto_variantes (producto_id, talle, color, stock) VALUES (?, ?, ?, ?)";
                $stmt_var = $conn->prepare($sql_variante);

                if ($stmt_var) {
                    $variantes_insertadas = 0;
                    foreach ($variantes as $variante) {
                        $talle_var = htmlspecialchars(trim($variante['talle'] ?? ''));
                        $color_var = htmlspecialchars(trim($variante['color'] ?? ''));
                        $stock_var = (int)($variante['stock'] ?? 0);

                        if (!empty($talle_var) && !empty($color_var)) {
                            $stmt_var->bind_param("issi", $producto_id, $talle_var, $color_var, $stock_var);
                            if ($stmt_var->execute()) $variantes_insertadas++;
                        }
                    }
                    $stmt_var->close();

                    if ($variantes_insertadas > 0) {
                        $mensaje = "Producto y $variantes_insertadas variantes agregados correctamente.";
                        header("Location: ver_productos.php?mensaje=" . urlencode($mensaje));
                        exit;
                    } else {
                        // Revertir si no se insertaron variantes
                        $conn->query("DELETE FROM productos WHERE id = $producto_id");
                        if (file_exists($target_file)) unlink($target_file);
                        $mensaje = "<div class='alert alert-danger'>Producto NO agregado: No se pudo guardar ninguna variante válida.</div>";
                    }
                } else {
                    $mensaje = "<div class='alert alert-danger'>Error al preparar SQL de variantes: " . $conn->error . "</div>";
                }
            } else {
                $mensaje = "<div class='alert alert-danger'>Error al insertar producto: " . $stmt_prod->error . "</div>";
            }
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al preparar SQL del producto: " . $conn->error . "</div>";
        }
    }
}
end_post:

// ===============================
// FRONTEND - FORMULARIO
// ===============================
include 'header.php'; 
?>

<div class="card shadow-lg">
    <div class="card-header bg-primary text-white">
        <h2 class="mb-0">➕ Cargar Nuevo Producto</h2>
    </div>
    <div class="card-body">
        
        <?php if (!empty($mensaje) && !$error_redireccion): ?>
            <div class="alert alert-danger" role="alert">
                <?= strip_tags($mensaje, '<div>') ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre del Producto</label>
                <input type="text" class="form-control" id="nombre" name="nombre" required>
            </div>
            
            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="4" required></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="precio" class="form-label">Precio ($)</label>
                    <input type="number" class="form-control" id="precio" name="precio" step="0.01" required min="0">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="imagen" class="form-label">Imagen del Producto</label>
                    <input class="form-control" type="file" id="imagen" name="imagen" accept="image/jpeg, image/png, image/gif, image/webp" required>
                    <div class="form-text">Sube una imagen JPG, PNG, GIF o WEBP.</div>
                </div>
            </div>
            
            <hr class="my-4">
            
            <h4 class="mb-3">📦 Stock por Talle y Color</h4>
            <p class="text-muted"><small>Ingrese cada combinación única de Talle y Color con su respectivo stock.</small></p>
            <div id="variantes-container"></div>
            
            <button type="button" class="btn btn-info btn-sm" id="agregar-variante">
                ➕ Añadir Talle/Color
            </button>
            
            <hr class="my-4">
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="ver_productos.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-success">💾 Guardar Producto</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('variantes-container');
    const addButton = document.getElementById('agregar-variante');
    let counter = 0;

    function addVariantRow(talle = '', color = '', stock = 0) {
        const row = document.createElement('div');
        row.classList.add('row', 'mb-2', 'align-items-center', 'variante-row');
        row.innerHTML = `
            <div class="col-4">
                <input type="text" class="form-control form-control-sm" name="variantes[${counter}][talle]" placeholder="Talle (Ej: S)" value="${talle}" required>
            </div>
            <div class="col-4">
                <input type="text" class="form-control form-control-sm" name="variantes[${counter}][color]" placeholder="Color (Ej: Rojo)" value="${color}" required>
            </div>
            <div class="col-3">
                <input type="number" class="form-control form-control-sm" name="variantes[${counter}][stock]" placeholder="Stock" value="${stock}" min="0" required>
            </div>
            <div class="col-1">
                <button type="button" class="btn btn-danger btn-sm remover-variante">X</button>
            </div>
        `;
        container.appendChild(row);
        counter++;
    }

    addButton.addEventListener('click', () => addVariantRow());
    addVariantRow(); // Inicializar con una fila

    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remover-variante')) {
            if (container.children.length > 1) {
                e.target.closest('.variante-row').remove();
            } else {
                alert('Debe haber al menos una variante de stock.');
            }
        }
    });
});
</script>

<?php include 'footer.php'; ?>
