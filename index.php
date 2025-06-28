<?php
// index.php (Pantalla Principal / Dashboard)

// Iniciar sesión y cargar configuración primero
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cargar configuración y funciones comunes
require_once __DIR__ . '/config.php';
// require_once __DIR__ . '/includes/functions.php'; // Si tienes funciones helper adicionales

// Verificar si el usuario está logueado. Si no, redirigir a login.php.
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . APP_URL . '/auth/login.php');
    exit;
}

// El usuario está logueado. Podemos obtener su información de la sesión.
$userId = $_SESSION['user_id'];
$userEmail = $_SESSION['user_email'];
$userNombre = $_SESSION['user_nombre'] ?? 'Usuario';
$userApellido = $_SESSION['user_apellido'] ?? '';

$pageTitle = "Panel Principal"; // Título para el header

// Incluir el header
require_once __DIR__ . '/templates/header.php';
?>

<!-- Contenido específico de la página principal -->
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="jumbotron">
                <h1 class="display-4">¡Bienvenido, <?php echo htmlspecialchars(trim($userNombre . ' ' . $userApellido)); ?>!</h1>
                <p class="lead">Este es tu panel principal en el <?php echo htmlspecialchars(APP_NAME); ?>.</p>
                <hr class="my-4">
                <p>Desde aquí podrás acceder a las diferentes funcionalidades del sistema una vez que estén implementadas.</p>
                <!-- <a class="btn btn-primary btn-lg" href="#" role="button">Aprender más</a> -->
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">Acciones Rápidas</div>
                <div class="card-body">
                    <h5 class="card-title">Gestión de Usuarios</h5>
                    <p class="card-text">Administra perfiles, permisos y configuraciones de usuarios.</p>
                    <a href="#" class="btn btn-info disabled">Ir a Usuarios (Próximamente)</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">Información del Sistema</div>
                <div class="card-body">
                    <h5 class="card-title">Estado y Notificaciones</h5>
                    <p class="card-text">Revisa el estado actual del sistema y las últimas notificaciones.</p>
                    <a href="#" class="btn btn-warning disabled">Ver Notificaciones (Próximamente)</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Ejemplo de cómo podrías mostrar más información del usuario -->
    <!--
    <div class="row mt-4">
        <div class="col-12">
            <h4>Información de tu sesión:</h4>
            <ul>
                <li>ID de Usuario: <?php echo htmlspecialchars($userId); ?></li>
                <li>Email: <?php echo htmlspecialchars($userEmail); ?></li>
            </ul>
        </div>
    </div>
    -->

</div>
<!-- Fin del contenido específico de la página principal -->

<?php
// Incluir el footer
require_once __DIR__ . '/templates/footer.php';
?>
