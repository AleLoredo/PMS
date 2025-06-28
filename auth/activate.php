<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$baseDir = dirname(__DIR__);
require_once $baseDir . '/config.php';
require_once $baseDir . '/includes/db_connection.php'; // Para interactuar con la BD

$pageTitle = "Activación de Cuenta";
$message = "";
$message_type = "info"; // Puede ser 'success' o 'danger'

// Verificar si se proporcionó un token en la URL
if (isset($_GET['token']) && !empty(trim($_GET['token']))) {
    $token = trim($_GET['token']);

    // Buscar el token en la base de datos
    $stmt = $conn->prepare("SELECT id_user, email, activado FROM systemusers WHERE token_activacion = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($user['activado'] == 1) {
            // La cuenta ya estaba activada
            $message = "Esta cuenta ya ha sido activada previamente. Puedes <a href='login.php'>iniciar sesión</a>.";
            $message_type = "info";
        } else {
            // Activar la cuenta y limpiar el token de activación
            $update_stmt = $conn->prepare("UPDATE systemusers SET activado = TRUE, token_activacion = NULL WHERE id_user = ?");
            $update_stmt->bind_param("i", $user['id_user']);

            if ($update_stmt->execute()) {
                $message = "¡Tu cuenta ha sido activada exitosamente! Ahora puedes <a href='login.php'>iniciar sesión</a>.";
                $message_type = "success";
                // Opcional: podrías loguear la activación
                error_log("Cuenta activada para el usuario ID: " . $user['id_user'] . ", Email: " . $user['email']);
            } else {
                $message = "Error al activar la cuenta. Por favor, intenta más tarde o contacta al soporte.";
                $message_type = "danger";
                error_log("Error al actualizar la base de datos para activar la cuenta del usuario ID: " . $user['id_user'] . ". Token: " . $token);
            }
            $update_stmt->close();
        }
    } else {
        // Token no encontrado o inválido
        $message = "El enlace de activación no es válido o ha expirado. Por favor, verifica el enlace o intenta registrarte de nuevo.";
        $message_type = "danger";
        error_log("Intento de activación con token inválido o no encontrado: " . $token);
    }
    $stmt->close();
} else {
    // No se proporcionó token
    $message = "No se proporcionó un token de activación. Por favor, utiliza el enlace enviado a tu correo electrónico.";
    $message_type = "warning";
}

$conn->close();

// Incluir el header y el footer (o una plantilla completa)
// Para simplificar, mostraremos el mensaje directamente aquí.
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
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4><?php echo htmlspecialchars($pageTitle); ?></h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                                <?php echo $message; // El mensaje ya puede contener HTML (el enlace), así que no se escapa aquí. ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($message_type === 'success' || ($message_type === 'info' && strpos($message, 'iniciar sesión') !== false) ): ?>
                            <a href="login.php" class="btn btn-primary">Ir a Login</a>
                        <?php elseif ($message_type === 'danger' || $message_type === 'warning'): ?>
                             <a href="register.php" class="btn btn-secondary">Ir a Registro</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts de Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
