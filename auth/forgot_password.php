<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$baseDir = dirname(__DIR__);
if (file_exists($baseDir . '/config.php')) {
    require_once $baseDir . '/config.php';
} else {
    die('El archivo de configuración no se encuentra.');
}

$pageTitle = "Recuperar Contraseña";
$smtp_enabled = (defined('SMTP_FUNCTION_STATUS') && SMTP_FUNCTION_STATUS === 'ON');

// Mensajes de sesión para feedback
$errors = $_SESSION['fp_errors'] ?? [];
$success_message = $_SESSION['fp_success_message'] ?? '';
$form_data = $_SESSION['fp_form_data'] ?? []; // Para repoblar el email si es necesario

unset($_SESSION['fp_errors']);
unset($_SESSION['fp_success_message']);
unset($_SESSION['fp_form_data']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - <?php echo htmlspecialchars(APP_NAME); ?></title>
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
                <li class="nav-item">
                    <a class="nav-link" href="login.php">Login</a>
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
                        <?php if (!$smtp_enabled): ?>
                            <div class="alert alert-warning" role="alert">
                                El sistema de recuperación de contraseña ha sido deshabilitado por el administrador del sistema.
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center">Ingresa tu dirección de correo electrónico y te enviaremos un enlace para restablecer tu contraseña.</p>

                            <?php if (!empty($success_message)): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
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

                            <?php if (empty($success_message)): // Solo mostrar el formulario si no hay mensaje de éxito y SMTP está habilitado ?>
                            <form action="handle_forgot_password.php" method="POST">
                                <div class="form-group">
                                    <label for="email">Correo Electrónico</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required autofocus>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Enviar Enlace de Recuperación</button>
                            </form>
                            <?php endif; ?>
                        <?php endif; // Cierre del if $smtp_enabled ?>
                    </div>
                    <div class="card-footer text-center">
                        <small><a href="login.php">Volver a Iniciar Sesión</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../js/script.js"></script>
</body>
</html>
