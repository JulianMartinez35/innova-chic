<?php
session_start();

// Verificar si el usuario está logueado y es admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include '../includes/db.php';
include 'header.php';

// Inicializar array
$variantes_existentes = [];

// 2️⃣ Validar ID del producto
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger'>ID de producto no especificado o inválido.</div>";
    include 'footer.php';
    exit;
}
$id = (int)$_GET['id'];

// 3️⃣ Obtener datos del producto
$sql_select = "SELECT id, nombre, descripcion, precio, imagen FROM productos WHERE id = ?";
$stmt_select = $conn->prepare($sql_select);
$stmt_select->bind_param("i", $id);
$stmt_select->execute();
$result = $stmt_select->get_result();

if ($result->num_rows === 0) {
    echo "<div class='alert alert-danger'>Producto no encontrado.</div>";
    include 'footer.php';
    exit;
}
$producto = $result->fetch_assoc();
$stmt_select->close();

// 4️⃣ Obtener variantes existentes
$sql_variantes = "SELECT id, talle, color, stock FROM producto_variantes WHERE producto_id = ?";
$stmt_variantes = $conn->prepare($sql_variantes);
$stmt_variantes->bind_param("i", $id);
$stmt_variantes->execute();
$result_variantes = $stmt_variantes->get_result();
while ($variante = $result_variantes->fetch_assoc()) {
    $variantes_existentes[] = $variante;
}
$stmt_variantes->close();

// 5️⃣ Procesar formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = (float)($_POST['precio'] ?? 0);
    $variantes_post = $_POST['variantes'] ?? [];

    $imagen_actual = $producto['imagen'];
    $nueva_imagen = $imagen_actual;
    $target_dir = __DIR__ . '/../../assets/images/';

    // Crear carpeta si no existe
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // 🖼 Manejo de la imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        $imagen_file = $_FILES['imagen'];
        $error_code = $imagen_file['error'];

        if ($error_code === UPLOAD_ERR_OK) {
            $imageFileType = strtolower(pathinfo($imagen_file['name'], PATHINFO_EXTENSION));
            $formatos_permitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($imageFileType, $formatos_permitidos)) {
                $nueva_imagen = uniqid('prod_', true) . "." . $imageFileType;
                $target_file = $target_dir . $nueva_imagen;

                if (move_uploaded_file($imagen_file['tmp_name'], $target_file)) {
                    // Eliminar imagen anterior si existía
                    if (!empty($imagen_actual) && file_exists($target_dir . $imagen_actual)) {
                        unlink($target_dir . $imagen_actual);
                    }
                } else {
                    echo "<div class='alert alert-warning'>⚠️ No se pudo mover la nueva imagen. Se mantendrá la anterior.</div>";
                    $nueva_imagen = $imagen_actual;
                }
            } else {
                echo "<div class='alert alert-warning'>⚠️ Formato de imagen no permitido. Solo JPG, PNG, GIF o WEBP.</div>";
            }
        } else {
            echo "<div class='alert alert-warning'>⚠️ Error al subir la imagen (código: $error_code).</div>";
        }
    }

    // 🧩 Iniciar transacción
    $conn->begin_transaction();

    try {
        // Actualizar producto
        $update = "UPDATE productos SET nombre=?, descripcion=?, precio=?, imagen=? WHERE id=?";
        $stmt_update = $conn->prepare($update);
        $stmt_update->bind_param("ssdsi", $nombre, $descripcion, $precio, $nueva_imagen, $id);
        $stmt_update->execute();
        $stmt_update->close();

        // Eliminar variantes antiguas
        $stmt_delete = $conn->prepare("DELETE FROM producto_variantes WHERE producto_id = ?");
        $stmt_delete->bind_param("i", $id);
        $stmt_delete->execute();
        $stmt_delete->close();

        // Insertar variantes nuevas
        $stmt_insert = $conn->prepare("INSERT INTO producto_variantes (producto_id, talle, color, stock) VALUES (?, ?, ?, ?)");
        $variantes_insertadas = 0;

        foreach ($variantes_post as $variante) {
            $talle_var = trim($variante['talle'] ?? '');
            $color_var = trim($variante['color'] ?? '');
            $stock_var = (int)($variante['stock'] ?? 0);

            if ($talle_var !== '' && $color_var !== '') {
                $stmt_insert->bind_param("issi", $id, $talle_var, $color_var, $stock_var);
                $stmt_insert->execute();
                $variantes_insertadas++;
            }
        }
        $stmt_insert->close();

        // Confirmar cambios
        $conn->commit();

        echo "<div class='alert alert-success'>✅ Producto actualizado correctamente con $variantes_insertadas variantes.</div>";

        // Refrescar variantes
        $stmt_variantes_re = $conn->prepare($sql_variantes);
        $stmt_variantes_re->bind_param("i", $id);
        $stmt_variantes_re->execute();
        $result_variantes_re = $stmt_variantes_re->get_result();
        $variantes_existentes = $result_variantes_re->fetch_all(MYSQLI_ASSOC);
        $stmt_variantes_re->close();

    } catch (Exception $e) {
        $conn->rollback();
        echo "<div class='alert alert-danger'>❌ Error al actualizar: " . $e->getMessage() . "</div>";
    }
}
?>

<div class="container mt-5 mb-5">
    <h2 class="mb-4 text-center">✏️ Editar Producto: <?= htmlspecialchars($producto['nombre']) ?></h2>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" name="nombre" id="nombre" class="form-control"
                   value="<?= htmlspecialchars($producto['nombre']) ?>" required>
        </div>

        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea name="descripcion" id="descripcion" class="form-control" rows="4" required><?= htmlspecialchars($producto['descripcion']) ?></textarea>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="precio" class="form-label">Precio ($)</label>
                <input type="number" step="0.01" name="precio" id="precio" class="form-control"
                       value="<?= htmlspecialchars($producto['precio']) ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="imagen" class="form-label">Nueva Imagen (opcional)</label>
                <input type="file" name="imagen" id="imagen" class="form-control" accept="image/*">
                <div class="form-text">Si no seleccionas una nueva, se mantiene la actual.</div>
            </div>
        </div>

        <hr class="my-4" id="variantes">
        <h4 class="mb-3">📦 Variantes (Talle / Color / Stock)</h4>
        <div id="variantes-container">
            <?php
            $counter = 0;
            if (!empty($variantes_existentes)) {
                foreach ($variantes_existentes as $variante) {
                    echo '<div class="row mb-2 align-items-center variante-row">
                        <div class="col-4">
                            <input type="text" class="form-control form-control-sm" name="variantes['.$counter.'][talle]" placeholder="Talle" value="'.htmlspecialchars($variante['talle']).'" required>
                        </div>
                        <div class="col-4">
                            <input type="text" class="form-control form-control-sm" name="variantes['.$counter.'][color]" placeholder="Color" value="'.htmlspecialchars($variante['color']).'" required>
                        </div>
                        <div class="col-3">
                            <input type="number" class="form-control form-control-sm" name="variantes['.$counter.'][stock]" placeholder="Stock" value="'.htmlspecialchars($variante['stock']).'" min="0" required>
                        </div>
                        <div class="col-1">
                            <button type="button" class="btn btn-danger btn-sm remover-variante">X</button>
                        </div>
                    </div>';
                    $counter++;
                }
            }
            ?>
        </div>

        <button type="button" class="btn btn-info btn-sm" id="agregar-variante">➕ Añadir Variante</button>

        <hr class="my-4">
        <div class="mb-3">
            <label>Imagen actual:</label><br>
            <img src="../assets/images/<?= htmlspecialchars($producto['imagen']) ?>" width="120" class="img-thumbnail mt-2">
        </div>

        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
            <a href="ver_productos.php" class="btn btn-secondary">Volver</a>
            <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('variantes-container');
    const addButton = document.getElementById('agregar-variante');
    let counter = container.children.length;

    function addVariantRow() {
        const row = document.createElement('div');
        row.classList.add('row', 'mb-2', 'align-items-center', 'variante-row');
        row.innerHTML = `
            <div class="col-4">
                <input type="text" class="form-control form-control-sm" name="variantes[${counter}][talle]" placeholder="Talle" required>
            </div>
            <div class="col-4">
                <input type="text" class="form-control form-control-sm" name="variantes[${counter}][color]" placeholder="Color" required>
            </div>
            <div class="col-3">
                <input type="number" class="form-control form-control-sm" name="variantes[${counter}][stock]" placeholder="Stock" min="0" required>
            </div>
            <div class="col-1">
                <button type="button" class="btn btn-danger btn-sm remover-variante">X</button>
            </div>`;
        container.appendChild(row);
        counter++;
    }

    addButton.addEventListener('click', addVariantRow);

    container.addEventListener('click', e => {
        if (e.target.classList.contains('remover-variante')) {
            e.target.closest('.variante-row').remove();
        }
    });
});
</script>

<?php include 'footer.php'; ?>
