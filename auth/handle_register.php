<?php
// config.php se encarga de iniciar la sesión después de configurar los parámetros.
// No iniciar sesión aquí prematuramente.

// Incluir archivos necesarios
// __DIR__ es el directorio actual (auth), dirname(__DIR__) es el directorio raíz del proyecto.
$baseDir = dirname(__DIR__);
require_once $baseDir . '/config.php'; // Esto ya debería iniciar la sesión si es necesario.
require_once $baseDir . '/includes/db_connection.php';
require_once $baseDir . '/helpers/email_sender.php'; // Para enviar el email de activación

// Inicializar arrays para errores y datos de formulario
// Las variables de sesión ya deberían estar disponibles gracias a config.php
$_SESSION['errors'] = [];
$_SESSION['form_data'] = $_POST; // Guardar los datos del POST para repoblar el formulario si hay error

// Verificar que la solicitud sea POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger y sanear (básicamente) los datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tipo_documento = trim($_POST['tipo_documento'] ?? '');
    $numero_documento = trim($_POST['numero_documento'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // --- Validaciones del Servidor ---
    if (empty($nombre)) {
        $_SESSION['errors'][] = "El nombre es obligatorio.";
    }
    if (empty($apellido)) {
        $_SESSION['errors'][] = "El apellido es obligatorio.";
    }
    if (empty($email)) {
        $_SESSION['errors'][] = "El email es obligatorio.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['errors'][] = "El formato del email no es válido.";
    }
    if (empty($password)) {
        $_SESSION['errors'][] = "La contraseña es obligatoria.";
    } elseif (strlen($password) < 8) {
        $_SESSION['errors'][] = "La contraseña debe tener al menos 8 caracteres.";
    }
    if ($password !== $confirm_password) {
        $_SESSION['errors'][] = "Las contraseñas no coinciden.";
    }
    if (!empty($numero_documento)) {
        // Validar si el número de documento ya existe (opcional, pero buena idea si debe ser único)
        $stmt_doc = $conn->prepare("SELECT id_contact FROM contacts WHERE numero_documento = ?");
        $stmt_doc->bind_param("s", $numero_documento);
        $stmt_doc->execute();
        $stmt_doc->store_result();
        if ($stmt_doc->num_rows > 0) {
            $_SESSION['errors'][] = "El número de documento ya está registrado.";
        }
        $stmt_doc->close();
    }

    // Validar si el email ya existe en systemusers
    $stmt_email = $conn->prepare("SELECT id_user FROM systemusers WHERE email = ?");
    $stmt_email->bind_param("s", $email);
    $stmt_email->execute();
    $stmt_email->store_result();
    if ($stmt_email->num_rows > 0) {
        $_SESSION['errors'][] = "El email ya está registrado. Intenta iniciar sesión o recuperar tu contraseña.";
    }
    $stmt_email->close();

    // Si hay errores, redirigir de vuelta al formulario de registro
    if (!empty($_SESSION['errors'])) {
        header('Location: register.php');
        exit;
    }

    // --- Si no hay errores, proceder con el registro ---
    $conn->begin_transaction(); // Iniciar transacción para asegurar atomicidad

    try {
        // 1. Insertar en la tabla 'contacts'
        $stmt_contacts = $conn->prepare("INSERT INTO contacts (nombre, apellido, tipo_documento, numero_documento) VALUES (?, ?, ?, ?)");
        $stmt_contacts->bind_param("ssss", $nombre, $apellido, $tipo_documento, $numero_documento);
        $stmt_contacts->execute();
        $id_contact = $conn->insert_id; // Obtener el ID del contacto recién insertado
        $stmt_contacts->close();

        if (!$id_contact) {
            throw new Exception("Error al crear el contacto.");
        }

        // 2. Hashear la contraseña
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Leer el estado de SMTP_FUNCTION_STATUS de config.php
        $smtp_enabled = (defined('SMTP_FUNCTION_STATUS') && SMTP_FUNCTION_STATUS === 'ON');

        if ($smtp_enabled) {
            // Flujo normal con activación por email
            $token_activacion = bin2hex(random_bytes(32));
            $activado_directamente = FALSE;

            // Insertar en la tabla 'systemusers' con token y activado = FALSE
            $stmt_systemusers = $conn->prepare("INSERT INTO systemusers (id_contact, email, password_hash, token_activacion, activado) VALUES (?, ?, ?, ?, ?)");
            $stmt_systemusers->bind_param("isssi", $id_contact, $email, $password_hash, $token_activacion, $activado_directamente);
            $stmt_systemusers->execute();
            $stmt_systemusers->close();

            // Enviar email de activación usando AWS SES
            $activation_link = APP_URL . "/auth/activate.php?token=" . $token_activacion;
            $email_subject = "Activa tu cuenta en " . APP_NAME;
            $email_body = "<p>Hola " . htmlspecialchars($nombre) . ",</p>";
            $email_body .= "<p>Gracias por registrarte en " . APP_NAME . ". Por favor, haz clic en el siguiente enlace para activar tu cuenta:</p>";
            $email_body .= "<p><a href='" . $activation_link . "'>" . $activation_link . "</a></p>";
            $email_body .= "<p>Si no te registraste, por favor ignora este correo.</p>";
            $email_body .= "<p>Saludos,<br>El equipo de " . APP_NAME . "</p>";

            if (sendCustomEmail($email, $email_subject, $email_body)) {
                $conn->commit();
                unset($_SESSION['form_data']);
                $_SESSION['success_message'] = "¡Registro exitoso! Se ha enviado un correo de activación a " . htmlspecialchars($email) . ". Por favor, revisa tu bandeja de entrada (y spam).";
            } else {
                $conn->rollback();
                $_SESSION['errors'][] = "Hubo un problema al enviar el correo de activación. Por favor, intenta registrarte más tarde o contacta con el soporte.";
                error_log("Error al enviar email de activación para: $email. Token: $token_activacion");
            }
        } else {
            // SMTP_FUNCTION_STATUS es 'OFF', registrar usuario como activado directamente
            $activado_directamente = TRUE;
            $token_activacion = null; // No se necesita token de activación

            // Insertar en la tabla 'systemusers' con activado = TRUE y sin token
            $stmt_systemusers = $conn->prepare("INSERT INTO systemusers (id_contact, email, password_hash, token_activacion, activado) VALUES (?, ?, ?, ?, ?)");
            $stmt_systemusers->bind_param("isssi", $id_contact, $email, $password_hash, $token_activacion, $activado_directamente);
            $stmt_systemusers->execute();

            if ($stmt_systemusers->affected_rows > 0) {
                $conn->commit();
                unset($_SESSION['form_data']);
                $_SESSION['success_message'] = "¡Registro exitoso! Tu cuenta ha sido creada y activada directamente. Ya puedes <a href='login.php'>iniciar sesión</a>.";
            } else {
                $conn->rollback();
                $_SESSION['errors'][] = "Hubo un problema al crear tu cuenta. Por favor, intenta registrarte más tarde.";
                error_log("Error al insertar usuario activado directamente para: $email");
            }
            $stmt_systemusers->close();
        }

    } catch (Exception $e) {
        $conn->rollback(); // Revertir la transacción en caso de cualquier error en la lógica try
        $_SESSION['errors'][] = "Ocurrió un error durante el proceso de registro: " . $e->getMessage();
        error_log("Error en handle_register: " . $e->getMessage());
    }

    // Redirigir de vuelta a register.php (que mostrará el mensaje de éxito o los errores)
    header('Location: register.php');
    exit;

} else {
    // Si no es POST, redirigir a alguna parte o mostrar error
    $_SESSION['errors'][] = "Acceso no permitido.";
    header('Location: register.php'); // O a una página de error general
    exit;
}

?>
