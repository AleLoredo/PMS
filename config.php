<?php

// --- Carga de Variables de Entorno ---
// Asegúrate de que vendor/autoload.php existe si estás usando Composer.
// Esto es crucial para cargar la librería phpdotenv.
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Fallback o error si no se encuentra autoload.php, lo que significa que phpdotenv no estará disponible.
    // Podrías morir aquí o intentar continuar con getenv() directamente,
    // pero la carga desde .env no funcionará.
    die("Error: vendor/autoload.php no encontrado. Ejecuta 'composer install' en la raíz del proyecto.");
}

// Cargar el archivo .env desde el directorio raíz del proyecto (donde está este config.php)
// createImmutable asegura que las variables de entorno del sistema no sean sobrescritas por .env
// y lanza una excepción si .env no existe o no es legible.
try {
    // __DIR__ se refiere al directorio donde reside config.php (la raíz del proyecto)
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load(); // Carga las variables en $_ENV y $_SERVER
    // Puedes añadir validaciones para variables requeridas si es necesario:
    // $dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'APP_URL'])->notEmpty();
} catch (Dotenv\Exception\InvalidPathException $e) {
    die("Error crítico: No se pudo encontrar el archivo .env. Asegúrate de copiar .env.example a .env y configurarlo correctamente en la raíz del proyecto. Detalle: " . $e->getMessage());
} catch (Dotenv\Exception\InvalidFileException $e) {
    die("Error crítico: El archivo .env no es válido o no se puede leer. Revisa su formato y permisos. Detalle: " . $e->getMessage());
}

// --- Configuración de la Base de Datos ---
// Usar los valores de $_ENV o getenv(), con valores por defecto opcionales si tiene sentido
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'gestor_salud');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// --- Configuración de AWS SES (Simple Email Service) ---
define('AWS_SES_HOST', $_ENV['AWS_SES_HOST'] ?? null);
define('AWS_SES_PORT', $_ENV['AWS_SES_PORT'] ?? '587');
define('AWS_SES_SMTP_USER', $_ENV['AWS_SES_SMTP_USER'] ?? null);
define('AWS_SES_SMTP_PASSWORD', $_ENV['AWS_SES_SMTP_PASSWORD'] ?? null);
define('AWS_SES_FROM_EMAIL', $_ENV['AWS_SES_FROM_EMAIL'] ?? 'noreply@example.com');
define('AWS_SES_FROM_NAME', $_ENV['AWS_SES_FROM_NAME'] ?? 'Gestor de Salud');

// --- Configuración General de la Aplicación ---
define('APP_URL', rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/')); // Eliminar barra al final si existe
define('APP_NAME', $_ENV['APP_NAME'] ?? 'Sistema de Gestión de Salud');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development'); // 'development', 'production', 'testing'
define('DEBUG_MODE', filter_var($_ENV['DEBUG_MODE'] ?? 'false', FILTER_VALIDATE_BOOLEAN));

// --- Configuración de Funcionalidad SMTP ---
// Valores posibles: 'ON', 'OFF'
define('SMTP_FUNCTION_STATUS', strtoupper($_ENV['SMTP_FUNCTION'] ?? 'ON'));

// --- Zonas Horarias y otros ---
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC'); // Ej: 'America/Bogota', 'Europe/Madrid'

// --- Para tokens y seguridad ---
define('TOKEN_EXPIRATION_HOURS', (int)($_ENV['TOKEN_EXPIRATION_HOURS'] ?? 1));

// --- Configuración de Sesiones ---
ini_set('session.gc_maxlifetime', ($_ENV['SESSION_LIFETIME'] ?? 1440) * 60); // en segundos

$sessionCookieParams = [
    'lifetime' => ($_ENV['SESSION_LIFETIME'] ?? 1440) * 60,
    'path' => '/',
    'domain' => '', // Dominio actual
    'secure' => filter_var($_ENV['SESSION_SECURE_COOKIE'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
    'httponly' => filter_var($_ENV['SESSION_HTTP_ONLY'] ?? 'true', FILTER_VALIDATE_BOOLEAN)
];

if (PHP_VERSION_ID >= 70300 && isset($_ENV['SESSION_SAME_SITE'])) {
    $sessionCookieParams['samesite'] = $_ENV['SESSION_SAME_SITE']; // Ej: 'Lax', 'Strict', 'None'
}

session_set_cookie_params($sessionCookieParams);


// --- Habilitar/Deshabilitar errores de PHP ---
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
    // Considera loguear errores a un archivo en producción:
    // ini_set('log_errors', 1);
    // ini_set('error_log', __DIR__ . '/logs/php_errors.log'); // Asegúrate que la carpeta logs exista y sea escribible
}

// --- Iniciar sesiones ---
// Es importante que session_start() se llame DESPUÉS de configurar los parámetros de la cookie de sesión.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

?>