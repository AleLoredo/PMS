<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir config.php para acceso a APP_URL, APP_NAME, etc.
$baseDir = dirname(__DIR__);
if (file_exists($baseDir . '/config.php')) {
    require_once $baseDir . '/config.php';
} else {
    die('El archivo de configuración no se encuentra.');
}

// Si el usuario ya está logueado, redirigirlo a la página principal/dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ' . APP_URL . '/index.php'); // Asumiendo que index.php es el dashboard
    exit;
}

$pageTitle = "Iniciar Sesión";

// Lógica para manejar mensajes (éxito o error) que podrían venir de handle_login.php o de otras partes
$errors = $_SESSION['login_errors'] ?? [];
$success_message = $_SESSION['success_message'] ?? ''; // ej. después de resetear contraseña
$info_message = $_SESSION['info_message'] ?? ''; // ej. "Tu cuenta ha sido activada"

unset($_SESSION['login_errors']);
unset($_SESSION['success_message']);
unset($_SESSION['info_message']);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="<?php echo APP_URL; ?>"><?php echo htmlspecialchars(APP_NAME); ?></a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item active">
                    <a class="nav-link" href="login.php">Login <span class="sr-only">(current)</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="register.php">Registro</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center"><?php echo htmlspecialchars($pageTitle); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($info_message)): ?>
                            <div class="alert alert-info"><?php echo htmlspecialchars($info_message); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form action="handle_login.php" method="POST">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo htmlspecialchars($_SESSION['form_data_login']['email'] ?? ''); ?>" required autofocus>
                            </div>
                            <div class="form-group">
                                <label for="password">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                                    <label class="form-check-label" for="remember_me">Recordarme</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Ingresar</button>
                            <div class="text-center mt-3">
                                <a href="forgot_password.php">¿Olvidaste tu contraseña?</a>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <small>¿No tienes una cuenta? <a href="register.php">Regístrate aquí</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Limpiar datos de formulario de login si existían
    unset($_SESSION['form_data_login']);
    ?>

    <!-- Bootstrap JS, Popper.js, and jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../js/script.js"></script>
</body>
</html>
