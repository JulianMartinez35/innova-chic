<?php
session_start();
include('includes/db.php');

// Procesar login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitizar entradas
    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $password = trim($_POST['password']);

    // Consulta segura
    $stmt = $conn->prepare("SELECT id, nombre, password, rol FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verificar contraseña
        if (password_verify($password, $user['password'])) {

            // Guardar datos del usuario en la sesión
            $_SESSION['usuario'] = [
                'id' => $user['id'],
                'nombre' => $user['nombre'],
                'rol' => $user['rol']
            ];

            // Redirigir según el rol (texto)
            if ($user['rol'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit;
        } else {
            $error = "⚠️ Contraseña incorrecta.";
        }
    } else {
        $error = "⚠️ Usuario no encontrado.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar sesión - Innova Chic</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h3 class="text-center mb-4 fw-bold text-dark">Iniciar sesión</h3>

          <?php if(isset($error)): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <form method="POST" novalidate>
            <div class="mb-3">
              <label class="form-label">Correo electrónico</label>
              <input type="email" name="email" class="form-control" placeholder="ejemplo@correo.com" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Contraseña</label>
              <input type="password" name="password" class="form-control" placeholder="********" required>
            </div>

            <button type="submit" class="btn btn-dark w-100">Entrar</button>
          </form>

          <div class="text-center mt-3">
            <a href="registro.php" class="text-decoration-none">¿No tenés cuenta? Registrate</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
