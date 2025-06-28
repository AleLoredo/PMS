<?php
// Este archivo se encarga de establecer la conexión a la base de datos.
// Se debe incluir config.php ANTES de incluir este archivo, ya que usa las constantes definidas allí.

// Asegurarse de que config.php se haya cargado.
// Normalmente, config.php se incluye al principio del script que necesita la conexión.
if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASS') || !defined('DB_NAME')) {
    $configFile = dirname(__DIR__) . '/config.php'; // Asume que config.php está en el directorio raíz
    if (file_exists($configFile)) {
        require_once $configFile;
    } else {
        // Si config.php aún no se encuentra, es un error fatal.
        die("Error crítico: Falta el archivo de configuración (config.php) o no se han definido las constantes de la base de datos.");
    }
}

// Crear conexión
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verificar conexión
if ($conn->connect_error) {
    // En un entorno de producción, no mostrarías $conn->connect_error directamente al usuario.
    // Lo registrarías y mostrarías un mensaje genérico.
    error_log("Error de conexión a la base de datos: " . $conn->connect_error);
    // No es seguro mostrar detalles del error de conexión al usuario final en producción.
    // Considera un mensaje más genérico para el usuario y loguea el error detallado.
    die("No se pudo conectar a la base de datos. Por favor, contacte al administrador del sistema o inténtelo más tarde.");
}

// Establecer el charset a UTF-8 para la conexión (muy importante para evitar problemas con caracteres especiales)
if (!$conn->set_charset("utf8mb4")) {
    error_log("Error al establecer el charset utf8mb4: %s\n" . $conn->error);
    // Aunque no es fatal para la conexión en sí, puede causar problemas de datos.
    // Dependiendo de la criticidad, podrías decidir terminar el script.
}

// Opcional: Configurar el modo de reporte de errores de MySQLi para lanzar excepciones.
// Esto puede ser útil para un manejo de errores más moderno y estructurado usando bloques try-catch.
// Descomentar si prefieres manejar errores de DB como excepciones:
// mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// La variable $conn estará ahora disponible para cualquier script PHP que incluya este archivo.
// Ejemplo de uso: include_once 'includes/db_connection.php';
// y luego usar $conn para las consultas.
?>
