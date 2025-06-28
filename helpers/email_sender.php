<?php
// Incluir PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Asegurarse de que config.php se haya cargado, ya que contiene las credenciales de AWS SES.
// Esta es una salvaguarda; idealmente, config.php se incluye al principio del script que llama a esta función.
if (!defined('AWS_SES_HOST')) {
    $configFile = dirname(__DIR__) . '/config.php'; // Asume que config.php está en el directorio raíz del proyecto
    if (file_exists($configFile)) {
        require_once $configFile;
    } else {
        error_log("Error crítico en email_sender.php: Falta el archivo de configuración (config.php) o no se han definido las constantes de AWS SES.");
        // No se puede continuar si la configuración no está presente.
        // La función sendEmail retornará false.
    }
}

// Cargar las clases de PHPMailer. Ajusta la ruta según la ubicación de tu carpeta vendor.
// La estructura de PHPMailer a partir de v6 es vendor/phpmailer/phpmailer/src/
$phpMailerBaseDir = dirname(__DIR__) . '/vendor/phpmailer/phpmailer/';
require_once $phpMailerBaseDir . 'src/Exception.php';
require_once $phpMailerBaseDir . 'src/PHPMailer.php';
require_once $phpMailerBaseDir . 'src/SMTP.php';

/**
 * Envía un correo electrónico utilizando AWS SES a través de PHPMailer.
 *
 * @param string $to Destinatario del correo.
 * @param string $subject Asunto del correo.
 * @param string $body Cuerpo del correo (puede ser HTML).
 * @param string $altBody Cuerpo alternativo en texto plano para clientes de correo que no soportan HTML. Opcional.
 * @return bool True si el correo se envió correctamente, false en caso contrario.
 */
function sendCustomEmail($to, $subject, $body, $altBody = '') {
    // Verificar que las constantes de configuración de SES estén definidas
    if (!defined('AWS_SES_HOST') || !defined('AWS_SES_SMTP_USER') || !defined('AWS_SES_SMTP_PASSWORD') || !defined('AWS_SES_FROM_EMAIL') || !defined('AWS_SES_PORT') || !defined('APP_NAME')) {
        error_log('Error de configuración de AWS SES en email_sender.php: Faltan constantes necesarias (AWS_SES_HOST, AWS_SES_SMTP_USER, AWS_SES_SMTP_PASSWORD, AWS_SES_FROM_EMAIL, AWS_SES_PORT, APP_NAME).');
        return false;
    }

    $mail = new PHPMailer(true); // Habilitar excepciones

    try {
        // Configuración del servidor SMTP (AWS SES)
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Para depuración detallada. Comentar en producción.
        $mail->SMTPDebug = SMTP::DEBUG_OFF; // Desactivar depuración para producción.
        $mail->isSMTP();
        $mail->Host       = AWS_SES_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = AWS_SES_SMTP_USER;
        $mail->Password   = AWS_SES_SMTP_PASSWORD;

        // Determinar el tipo de seguridad (TLS o SSL) basado en el puerto
        if (AWS_SES_PORT == 587) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS
        } elseif (AWS_SES_PORT == 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
        } else {
            // Si el puerto no es 587 ni 465, podríamos no establecer SMTPSecure explícitamente
            // o manejarlo como un error de configuración si siempre esperamos uno de estos.
            // Por ahora, asumimos que si no es uno de esos, podría ser una conexión no encriptada (aunque SES usualmente requiere encriptación)
            // o que PHPMailer lo manejará. Para AWS SES, siempre será TLS o SSL.
            error_log('Puerto de AWS SES no estándar configurado (esperado 587 para TLS o 465 para SSL). Procediendo sin SMTPSecure explícito si no es uno de estos.');
        }
        $mail->Port = AWS_SES_PORT;

        // Remitente y Destinatarios
        // Usar AWS_SES_FROM_NAME si está definido, sino APP_NAME
        $fromName = defined('AWS_SES_FROM_NAME') && AWS_SES_FROM_NAME ? AWS_SES_FROM_NAME : APP_NAME;
        $mail->setFrom(AWS_SES_FROM_EMAIL, $fromName);
        $mail->addAddress($to); // El nombre del destinatario es opcional, PHPMailer lo extrae si es del formato "Nombre <email@example.com>"

        // Contenido del correo
        $mail->isHTML(true); // Establecer formato de email a HTML
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = ($altBody == '' && $body != '') ? strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $body)) : $altBody; // Generar AltBody simple si no se provee
        $mail->CharSet = 'UTF-8'; // Importante para caracteres especiales

        $mail->send();
        // Loggear éxito de forma discreta si es necesario, o no loggear nada en producción para no llenar logs.
        // error_log("Correo enviado exitosamente a: " . $to . " con asunto: " . $subject);
        return true;
    } catch (Exception $e) {
        // Loguear el error detallado para el administrador del sistema.
        // No mostrar $mail->ErrorInfo directamente al usuario final en producción.
        error_log("El mensaje no pudo ser enviado a {$to}. Error de PHPMailer: {$mail->ErrorInfo}");
        return false;
    }
}

// Ejemplo de uso (descomentar para probar, asegurándose de que config.php esté bien configurado):
/*
if (file_exists(dirname(__DIR__) . '/config.php')) { // Asegurar que config.php existe para la prueba
    require_once dirname(__DIR__) . '/config.php';

    $testTo = 'tu_email_de_prueba@example.com'; // CAMBIA ESTO
    $testSubject = 'Prueba de Correo AWS SES desde Gestor Salud';
    $testBody = '<h1>¡Hola!</h1><p>Este es un correo de prueba enviado usando <strong>AWS SES</strong> y <strong>PHPMailer</strong> desde el sistema Gestor Salud.</p><p>Si recibes esto, ¡la configuración funciona!</p>';
    $testAltBody = "¡Hola!\nEste es un correo de prueba enviado usando AWS SES y PHPMailer desde el sistema Gestor Salud.\nSi recibes esto, ¡la configuración funciona!";

    if (sendCustomEmail($testTo, $testSubject, $testBody, $testAltBody)) {
        echo "Correo de prueba enviado exitosamente a " . htmlspecialchars($testTo);
    } else {
        echo "Error al enviar correo de prueba. Revisa los logs del servidor para más detalles.";
    }
} else {
    echo "No se pudo encontrar config.php para ejecutar la prueba de envío de email.";
}
*/
?>
