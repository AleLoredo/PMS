<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$baseDir = dirname(__DIR__);
require_once $baseDir . '/config.php';
require_once $baseDir . '/includes/db_connection.php'; // Para interactuar con la BD

$_SESSION['login_errors'] = [];
$_SESSION['form_data_login'] = ['email' => $_POST['email'] ?? '']; // Guardar email para repoblar

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    // $remember_me = isset($_POST['remember_me']); // Funcionalidad para "Recordarme" (pendiente)

    if (empty($email)) {
        $_SESSION['login_errors'][] = "El email es obligatorio.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['login_errors'][] = "El formato del email no es válido.";
    }
    if (empty($password)) {
        $_SESSION['login_errors'][] = "La contraseña es obligatoria.";
    }

    if (!empty($_SESSION['login_errors'])) {
        header('Location: login.php');
        exit;
    }

    // Proceder a la validación contra la base de datos
    // Seleccionamos también nombre y apellido para la cabecera
    $stmt = $conn->prepare(
        "SELECT s.id_user, s.id_contact, s.email, s.password_hash, s.activado, c.nombre, c.apellido
         FROM systemusers s
         JOIN contacts c ON s.id_contact = c.id_contact
         WHERE s.email = ? LIMIT 1"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // 1. Verificar contraseña
        if (password_verify($password, $user['password_hash'])) {
            // 2. Verificar si el usuario está activado
            if ($user['activado'] == 1) {
                // Login exitoso
                session_regenerate_id(true); // Regenerar ID de sesión por seguridad

                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['contact_id'] = $user['id_contact'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_nombre'] = $user['nombre'];
                $_SESSION['user_apellido'] = $user['apellido'];

                // Limpiar datos de formulario de login de la sesión
                unset($_SESSION['form_data_login']);
                unset($_SESSION['login_errors']);

                // Actualizar último login (opcional)
                $update_login_stmt = $conn->prepare("UPDATE systemusers SET ultimo_login = CURRENT_TIMESTAMP WHERE id_user = ?");
                $update_login_stmt->bind_param("i", $user['id_user']);
                $update_login_stmt->execute();
                $update_login_stmt->close();

                // Redirigir a la pantalla principal/dashboard
                // Usar APP_URL de config.php para la redirección
                header('Location: ' . APP_URL . '/index.php'); // Asumiendo que index.php es el dashboard en la raíz
                exit;

            } else {
                $_SESSION['login_errors'][] = "Tu cuenta aún no ha sido activada. Por favor, revisa tu correo electrónico para el enlace de activación.";
            }
        } else {
            // Contraseña incorrecta
            $_SESSION['login_errors'][] = "Email o contraseña incorrectos.";
        }
    } else {
        // Usuario no encontrado
        $_SESSION['login_errors'][] = "Email o contraseña incorrectos.";
    }
    $stmt->close();
    $conn->close();

    header('Location: login.php');
    exit;

} else {
    $_SESSION['login_errors'][] = "Acceso no permitido.";
    header('Location: login.php');
    exit;
}
?>
