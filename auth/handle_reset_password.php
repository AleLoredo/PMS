<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$baseDir = dirname(__DIR__);
require_once $baseDir . '/config.php';
require_once $baseDir . '/includes/db_connection.php';

$_SESSION['reset_errors'] = [];

// Leer el estado de SMTP_FUNCTION_STATUS de config.php
$smtp_enabled = (defined('SMTP_FUNCTION_STATUS') && SMTP_FUNCTION_STATUS === 'ON');

if (!$smtp_enabled) {
    // Si SMTP está deshabilitado, no procesar el reseteo.
    // Esta situación no debería ocurrir si reset_password.php ya bloquea el formulario.
    $_SESSION['reset_errors'][] = "El sistema de recuperación de contraseña está deshabilitado.";
    // No hay un token GET para pasar aquí si el flujo es correcto,
    // pero si alguien llega aquí manipulando, redirigir a forgot_password que mostrará el mensaje.
    header('Location: forgot_password.php');
    exit;
}

// El token debe estar en la sesión, puesto allí por reset_password.php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['reset_token'])) {
    $token = $_SESSION['reset_token'];
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    // --- Validaciones del Servidor ---
    if (empty($new_password)) {
        $_SESSION['reset_errors'][] = "La nueva contraseña es obligatoria.";
    } elseif (strlen($new_password) < 8) {
        $_SESSION['reset_errors'][] = "La nueva contraseña debe tener al menos 8 caracteres.";
    }
    if ($new_password !== $confirm_new_password) {
        $_SESSION['reset_errors'][] = "Las contraseñas no coinciden.";
    }

    // Si hay errores de validación de contraseña, redirigir de vuelta
    if (!empty($_SESSION['reset_errors'])) {
        // Es importante mantener el token en la URL si redirigimos a reset_password.php
        // o asegurar que reset_password.php pueda manejar la ausencia de $_GET['token'] si solo usa la sesión.
        // Como reset_password.php ya guarda el token en sesión, solo necesitamos redirigir.
        header('Location: reset_password.php?token=' . urlencode($token)); // Re-añadir token a URL para que la página reset_password lo pueda validar de nuevo
        exit;
    }

    // --- Validar el token nuevamente antes de actualizar ---
    // (Defensa en profundidad, aunque reset_password.php ya lo hizo)
    $stmt_token = $conn->prepare("SELECT id_user, token_recupero_expira FROM systemusers WHERE token_recupero = ? LIMIT 1");
    $stmt_token->bind_param("s", $token);
    $stmt_token->execute();
    $result_token = $stmt_token->get_result();

    if ($result_token->num_rows === 1) {
        $user = $result_token->fetch_assoc();
        $user_id = $user['id_user'];
        $token_expira_timestamp = strtotime($user['token_recupero_expira']);

        if (time() > $token_expira_timestamp) {
            $_SESSION['reset_errors'][] = "El token de restablecimiento ha expirado. Por favor, solicita uno nuevo.";
            // Limpiar el token de la sesión ya que no es válido
            unset($_SESSION['reset_token']);
            header('Location: reset_password.php?token=' . urlencode($token)); // Token expirado, la página mostrará el error
            exit;
        }

        // --- Si el token es válido y no ha expirado, y las contraseñas son válidas ---
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        // Actualizar la contraseña e invalidar el token de recuperación
        $update_stmt = $conn->prepare("UPDATE systemusers SET password_hash = ?, token_recupero = NULL, token_recupero_expira = NULL WHERE id_user = ?");
        $update_stmt->bind_param("si", $password_hash, $user_id);

        if ($update_stmt->execute()) {
            $_SESSION['reset_success_message'] = "¡Tu contraseña ha sido actualizada exitosamente! Ahora puedes iniciar sesión con tu nueva contraseña.";
            unset($_SESSION['reset_token']); // Limpiar el token de la sesión
            // Redirigir a login.php con un mensaje de éxito, o a reset_password.php que mostrará el success y link a login
            // header('Location: login.php'); // Podría ser una opción, pero reset_password.php ya maneja el mensaje
            header('Location: reset_password.php?token_reset_success=1'); // Usar un param para indicar a reset_password que muestre el mensaje de éxito
            exit;
        } else {
            $_SESSION['reset_errors'][] = "Ocurrió un error al actualizar tu contraseña. Por favor, inténtalo de nuevo.";
            error_log("Error al actualizar contraseña para usuario ID: $user_id. Token: $token");
        }
        $update_stmt->close();
    } else {
        // Token no encontrado (esto no debería pasar si reset_password.php lo validó y lo puso en sesión)
        $_SESSION['reset_errors'][] = "Token inválido. Por favor, utiliza el enlace de tu correo o solicita uno nuevo.";
        unset($_SESSION['reset_token']);
    }
    $stmt_token->close();
    $conn->close();

    // Si llegamos aquí, hubo un error después de la validación de contraseña (ej. BD)
    header('Location: reset_password.php?token=' . urlencode($token));
    exit;

} else {
    // Si no es POST o no hay token en sesión
    $_SESSION['reset_errors'] = $_SESSION['reset_errors'] ?? []; // Asegurar que existe
    $_SESSION['reset_errors'][] = "Acceso no permitido o sesión inválida para restablecer contraseña.";
    // Redirigir a una página que tenga sentido, como forgot_password.php o login.php
    header('Location: forgot_password.php');
    exit;
}
?>
