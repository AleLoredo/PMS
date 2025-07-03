<?php
// config.php se encarga de iniciar la sesión después de configurar los parámetros.
// No iniciar sesión aquí explícitamente ANTES de incluir config.php,
// pero sí necesitamos asegurarnos de que la sesión esté activa para poder destruirla.
// config.php ya se encarga de iniciarla si no está activa.

// Cargar la configuración para obtener APP_URL para la redirección.
$baseDir = dirname(__DIR__);
if (file_exists($baseDir . '/config.php')) {
    require_once $baseDir . '/config.php'; // Esto asegura que la sesión está iniciada y configurada.
    $redirect_url = APP_URL . '/auth/login.php';
} else {
    // Fallback si config.php no está disponible.
    // Si config.php no está, es probable que APP_URL tampoco esté definido.
    // Intentar iniciar una sesión aquí sería problemático sin las configuraciones.
    // Esto no debería suceder en una aplicación bien configurada.
    // Redirigir a una ruta relativa si APP_URL no está disponible.
    $redirect_url = 'login.php';
    // Opcionalmente, loguear un error aquí porque config.php es esencial.
    error_log("logout.php: No se pudo cargar config.php. Usando redirección relativa.");
}

// 1. Destruir todas las variables de sesión.
$_SESSION = array();

// 2. Si se desea destruir la sesión completamente, también se borra la cookie de sesión.
// Nota: ¡Esto destruirá la sesión, y no solo los datos de la sesión!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Finalmente, destruir la sesión.
session_destroy();

// 4. Opcional: Añadir un mensaje a la página de login después del logout.
// session_start(); // Necesario para volver a usar $_SESSION después de destroy si quieres pasar un mensaje
// $_SESSION['info_message'] = "Has cerrado sesión exitosamente.";
// Sin embargo, es más simple no pasar mensajes aquí y solo redirigir.

// 5. Redirigir al usuario a la página de login.
header("Location: " . $redirect_url);
exit;
?>
