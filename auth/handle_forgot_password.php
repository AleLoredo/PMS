<?php
// config.php se encarga de iniciar la sesión después de configurar los parámetros.
// No iniciar sesión aquí prematuramente.
$baseDir = dirname(__DIR__);
require_once $baseDir . '/config.php'; // Esto ya debería iniciar la sesión si es necesario.
require_once $baseDir . '/includes/db_connection.php';
require_once $baseDir . '/helpers/email_sender.php'; // Para enviar el email de recuperación

$_SESSION['fp_errors'] = [];
$_SESSION['fp_form_data'] = ['email' => $_POST['email'] ?? ''];

// Leer el estado de SMTP_FUNCTION_STATUS de config.php
$smtp_enabled = (defined('SMTP_FUNCTION_STATUS') && SMTP_FUNCTION_STATUS === 'ON');

if (!$smtp_enabled) {
    // Si SMTP está deshabilitado, no se debería poder llegar aquí si el formulario está oculto.
    // Pero como defensa, establecemos un error y redirigimos.
    $_SESSION['fp_errors'][] = "El sistema de recuperación de contraseña está deshabilitado.";
    header('Location: forgot_password.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $_SESSION['fp_errors'][] = "El correo electrónico es obligatorio.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['fp_errors'][] = "El formato del correo electrónico no es válido.";
    }

    if (!empty($_SESSION['fp_errors'])) {
        header('Location: forgot_password.php');
        exit;
    }

    // Verificar si el email existe en la base de datos
    $stmt = $conn->prepare("SELECT id_user, nombre FROM systemusers s JOIN contacts c ON s.id_contact = c.id_contact WHERE s.email = ? AND s.activado = TRUE LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $user_id = $user['id_user'];
        $user_nombre = $user['nombre'];

        // Generar un token único de recuperación
        $token_recupero = bin2hex(random_bytes(32)); // Token de 64 caracteres hexadecimales

        // Establecer la fecha de expiración del token (ej. 1 hora desde ahora)
        // TOKEN_EXPIRATION_HOURS se define en config.php
        $expira_timestamp = time() + (TOKEN_EXPIRATION_HOURS * 60 * 60);
        $token_recupero_expira = date('Y-m-d H:i:s', $expira_timestamp);

        // Guardar el token y su expiración en la tabla systemusers
        $update_stmt = $conn->prepare("UPDATE systemusers SET token_recupero = ?, token_recupero_expira = ? WHERE id_user = ?");
        $update_stmt->bind_param("ssi", $token_recupero, $token_recupero_expira, $user_id);

        if ($update_stmt->execute()) {
            // Enviar correo con el enlace de recuperación
            $reset_link = APP_URL . "/auth/reset_password.php?token=" . $token_recupero;
            $email_subject = "Restablece tu contraseña en " . APP_NAME;

            $email_body = "<p>Hola " . htmlspecialchars($user_nombre) . ",</p>";
            $email_body .= "<p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en " . APP_NAME . ".</p>";
            $email_body .= "<p>Si no solicitaste esto, puedes ignorar este correo electrónico.</p>";
            $email_body .= "<p>Para restablecer tu contraseña, haz clic en el siguiente enlace. Este enlace es válido por " . TOKEN_EXPIRATION_HOURS . " hora(s):</p>";
            $email_body .= "<p><a href='" . $reset_link . "'>" . $reset_link . "</a></p>";
            $email_body .= "<p>Si tienes problemas con el enlace, cópialo y pégalo en tu navegador.</p>";
            $email_body .= "<p>Saludos,<br>El equipo de " . APP_NAME . "</p>";

            if (sendCustomEmail($email, $email_subject, $email_body)) {
                $_SESSION['fp_success_message'] = "Si hay una cuenta asociada con " . htmlspecialchars($email) . ", se ha enviado un enlace para restablecer la contraseña. Por favor, revisa tu bandeja de entrada (y spam).";
                unset($_SESSION['fp_form_data']); // Limpiar email del formulario en sesión
            } else {
                $_SESSION['fp_errors'][] = "No se pudo enviar el correo de recuperación en este momento. Por favor, inténtalo de nuevo más tarde.";
                error_log("Error al enviar email de recuperación para: $email. Token: $token_recupero");
                // Considerar no revertir el token de la BD aquí, para que el usuario no tenga que solicitarlo de nuevo inmediatamente
                // si el problema de envío de email es temporal.
            }
        } else {
            $_SESSION['fp_errors'][] = "Ocurrió un error al procesar tu solicitud. Inténtalo de nuevo.";
            error_log("Error al actualizar token_recupero en BD para usuario ID: $user_id. Email: $email");
        }
        $update_stmt->close();
    } else {
        // Email no encontrado o cuenta no activada.
        // Mostrar un mensaje genérico para no revelar si un email está registrado o no.
        $_SESSION['fp_success_message'] = "Si hay una cuenta asociada con " . htmlspecialchars($email) . ", se ha enviado un enlace para restablecer la contraseña. Por favor, revisa tu bandeja de entrada (y spam).";
        // Loguear el intento para monitoreo si se desea, pero no informar al usuario si el email no existe.
        error_log("Intento de recuperación de contraseña para email no registrado o inactivo: " . $email);
    }
    $stmt->close();
    $conn->close();

    header('Location: forgot_password.php');
    exit;

} else {
    $_SESSION['fp_errors'][] = "Acceso no permitido.";
    header('Location: forgot_password.php');
    exit;
}
?>
