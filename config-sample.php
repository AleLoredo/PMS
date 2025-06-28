<?php
// Configuración de la Base de Datos
define('DB_HOST', 'localhost');
define('DB_USER', 'tu_usuario_db');
define('DB_PASS', 'tu_contraseña_db');
define('DB_NAME', 'gestor_salud');

// Configuración de AWS SES (Simple Email Service)
define('AWS_SES_HOST', 'email-smtp.us-east-1.amazonaws.com'); // Cambia esto a tu región de SES
define('AWS_SES_PORT', '587'); // o 465 para SSL
define('AWS_SES_SMTP_USER', 'TU_USUARIO_SMTP_AWS'); // Tu usuario SMTP de AWS SES
define('AWS_SES_SMTP_PASSWORD', 'TU_CONTRASEÑA_SMTP_AWS'); // Tu contraseña SMTP de AWS SES
define('AWS_SES_FROM_EMAIL', 'noreply@tudominio.com'); // Email desde el que se enviarán los correos
define('AWS_SES_FROM_NAME', 'Gestor de Salud'); // Nombre del remitente

// Configuración General de la Aplicación
define('APP_URL', 'http://localhost/gestor_salud'); // URL base de tu aplicación
define('APP_NAME', 'Sistema de Gestión de Salud');

// Zonas Horarias y otros
date_default_timezone_set('America/Bogota'); // Ajusta a tu zona horaria

// Para tokens y seguridad
define('TOKEN_EXPIRATION_HOURS', 1); // Tiempo de expiración para tokens de recuperación en horas

// Configuración de Funcionalidad SMTP (para activar/desactivar envío de emails y flujos dependientes)
// Lee la variable de entorno 'SMTP_FUNCTION'. Si no está definida, usa 'ON' por defecto.
// Valores posibles: 'ON' (funcionalidad de email activada), 'OFF' (funcionalidad de email desactivada)
// Cuando está en 'OFF':
//  - Registro crea usuarios activados directamente.
//  - Recuperación de contraseña se deshabilita.
define('SMTP_FUNCTION_STATUS', getenv('SMTP_FUNCTION') ?: 'ON');

// Habilitar/Deshabilitar errores de PHP para desarrollo/producción
// En desarrollo:
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// En producción, considera desactivarlos o loggearlos a un archivo:
// error_reporting(0);
// ini_set('display_errors', 0);

// Iniciar sesiones si no están iniciadas (útil para poner en un archivo de cabecera común)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

?>
