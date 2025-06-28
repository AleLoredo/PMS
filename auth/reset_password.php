<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$baseDir = dirname(__DIR__);
require_once $baseDir . '/config.php';
require_once $baseDir . '/includes/db_connection.php';

$pageTitle = "Restablecer Contraseña";
$token = $_GET['token'] ?? null;
$showForm = false;
$message = '';
$message_type = 'info'; // 'success', 'danger', 'warning'

$smtp_enabled = (defined('SMTP_FUNCTION_STATUS') && SMTP_FUNCTION_STATUS === 'ON');

if (!$smtp_enabled) {
    $message = "El sistema de recuperación de contraseña ha sido deshabilitado por el administrador del sistema.";
    $message_type = 'warning';
    $showForm = false;
} elseif (!$token) {
    $message = "Token no proporcionado o inválido.";
    $message_type = 'danger';
} else {
    // Validar el token solo si SMTP está habilitado y hay un token
    $stmt = $conn->prepare("SELECT id_user, email, token_recupero_expira FROM systemusers WHERE token_recupero = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $token_expira_timestamp = strtotime($user['token_recupero_expira']);

        if (time() > $token_expira_timestamp) {
            $message = "El token de restablecimiento ha expirado. Por favor, solicita uno nuevo.";
            $message_type = 'warning';
        } else {
            $showForm = true;
            $_SESSION['reset_token'] = $token;
        }
    } else {
        $message = "Token inválido o ya utilizado. Por favor, asegúrate de usar el enlace correcto o solicita uno nuevo.";
        $message_type = 'danger';
    }
    $stmt->close();
}

// Mensajes de sesión de handle_reset_password.php
// Estos mensajes (éxito o error del intento de reseteo) tienen prioridad si el formulario se mostró.
$errors_reset = $_SESSION['reset_errors'] ?? [];
$success_message_reset = $_SESSION['reset_success_message'] ?? '';

if (!empty($success_message_reset)) {
    $showForm = false; // No mostrar formulario si la contraseña ya se cambió con éxito
    $message = $success_message_reset;
    $message_type = 'success';
}
if (!empty($errors_reset) && $showForm) { // Solo mostrar errores de reset si el formulario iba a ser mostrado
    // $message ya podría tener algo (ej. token expirado), así que podríamos concatenar o priorizar
    // Por ahora, los errores de 'reset_errors' tendrán prioridad si el token era inicialmente válido.
}


unset($_SESSION['reset_errors']);
unset($_SESSION['reset_success_message']);

$conn->close();
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
        <!-- No es necesario menú de usuario aquí ya que se asume que no está logueado -->
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center"><?php echo htmlspecialchars($pageTitle); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message) && !$showForm && $message_type !== 'success'): // Mensajes de error/advertencia cuando el formulario no se muestra ?>
                            <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
                            <a href="forgot_password.php" class="btn btn-link">Solicitar nuevo enlace</a>
                            <a href="login.php" class="btn btn-secondary float-right">Ir a Login</a>
                        <?php elseif ($message_type === 'success'): // Mensaje de éxito global ?>
                             <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                             <a href="login.php" class="btn btn-primary btn-block">Iniciar Sesión</a>
                        <?php endif; ?>

                        <?php if ($showForm): ?>
                            <?php if (!empty($errors_reset)): ?>
                                <div class="alert alert-danger">
                                    <ul>
                                        <?php foreach ($errors_reset as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            <form action="handle_reset_password.php" method="POST">
                                <!-- El token se envía a través de la sesión, pero también se puede incluir como campo oculto si se prefiere -->
                                <!-- <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>"> -->

                                <div class="form-group">
                                    <label for="new_password">Nueva Contraseña</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required autofocus>
                                    <small class="form-text text-muted">Debe tener al menos 8 caracteres.</small>
                                </div>
                                <div class="form-group">
                                    <label for="confirm_new_password">Confirmar Nueva Contraseña</label>
                                    <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Restablecer Contraseña</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php if (!$showForm && $message_type !== 'success'): ?>
                    <div class="card-footer text-center">
                        <small>¿Necesitas ayuda? <a href="login.php">Volver al Login</a></small>
                    </div>
                    <?php endif; ?>
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
