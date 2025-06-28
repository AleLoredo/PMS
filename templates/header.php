<?php
// Este archivo asume que config.php ya ha sido incluido y session_start() ya ha sido llamado
// en el script que lo incluye (ej. index.php, o un controlador principal).

// Es buena práctica verificar si las constantes y variables de sesión existen antes de usarlas.
$appName = defined('APP_NAME') ? htmlspecialchars(APP_NAME) : 'Mi Aplicación';
$appUrl = defined('APP_URL') ? APP_URL : '#'; // Asegúrate que APP_URL no tenga una barra al final en config.php

$isUserLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$userDisplayName = '';

if ($isUserLoggedIn) {
    $nombre = isset($_SESSION['user_nombre']) ? htmlspecialchars($_SESSION['user_nombre']) : '';
    $apellido = isset($_SESSION['user_apellido']) ? htmlspecialchars($_SESSION['user_apellido']) : '';
    $userDisplayName = trim($apellido . ", " . $nombre);
    if ($userDisplayName === ',') $userDisplayName = 'Usuario'; // Fallback si no hay nombre/apellido
}

// $pageTitle es una variable que debería ser definida en la página que incluye este header.
$currentPageTitle = isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Bienvenido';

// Path para el logo placeholder. Crear la carpeta assets/images si no existe.
$logoPath = $appUrl . '/assets/images/logo_placeholder.png';
// Deberías crear una imagen placeholder en 'assets/images/logo_placeholder.png'
// o reemplazar esta ruta con la correcta si ya tienes un logo.

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo $currentPageTitle; ?> - <?php echo $appName; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tu CSS personalizado -->
    <!-- Ajusta la ruta de style.css para que sea relativa a la raíz del sitio -->
    <link rel="stylesheet" href="<?php echo $appUrl; ?>/css/style.css">
    <!-- Favicon (ejemplo, deberías tener uno y ajustar la ruta) -->
    <!-- <link rel="icon" href="<?php echo $appUrl; ?>/favicon.ico" type="image/x-icon"> -->

    <style>
        body {
            padding-top: 56px; /* Altura estándar del navbar de Bootstrap */
        }
        .navbar-brand img {
            margin-right: 5px;
        }
        .dropdown-menu-right { /* Para asegurar que el dropdown se alinee a la derecha */
            right: 0;
            left: auto;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
        <a class="navbar-brand" href="<?php echo $appUrl; ?>/index.php">
            <img src="<?php echo $logoPath; ?>" width="30" height="30" class="d-inline-block align-top" alt="Logo">
            <?php echo $appName; ?>
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav mr-auto">
                <?php if ($isUserLoggedIn): ?>
                    <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'index.php') !== false) ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?php echo $appUrl; ?>/index.php">Inicio <span class="sr-only">(current)</span></a>
                    </li>
                    <!-- Aquí irían más enlaces para usuarios logueados, ej: -->
                    <!-- <li class="nav-item"><a class="nav-link" href="<?php echo $appUrl; ?>/patients.php">Pacientes</a></li> -->
                    <!-- <li class="nav-item"><a class="nav-link" href="<?php echo $appUrl; ?>/appointments.php">Citas</a></li> -->
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav"> <!-- ml-auto fue quitado para que Bootstrap 4 lo maneje mejor con mr-auto en el ul anterior -->
                <?php if ($isUserLoggedIn): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarUserDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <?php echo $userDisplayName; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarUserDropdown">
                            <a class="dropdown-item" href="<?php echo $appUrl; ?>/user/profile.php">Mi Perfil</a>
                            <a class="dropdown-item" href="<?php echo $appUrl; ?>/user/settings.php">Configuración</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="<?php echo $appUrl; ?>/auth/logout.php">Cerrar Sesión</a>
                        </div>
                    </li>
                <?php else: ?>
                    <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'login.php') !== false) ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?php echo $appUrl; ?>/auth/login.php">Login</a>
                    </li>
                    <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'register.php') !== false) ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?php echo $appUrl; ?>/auth/register.php">Registro</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- El contenido principal de la página se insertará después de este header -->
    <!-- La etiqueta de cierre <body> y <html> estará en footer.php -->
    <!-- Se añade un div contenedor principal que será cerrado en footer.php -->
    <main role="main" class="container-fluid mt-3"> <!-- mt-3 para un pequeño margen superior bajo el navbar -->
        <!-- El contenido específico de la página va aquí -->
    </main>

    <!-- NOTA: No cerrar <main>, <body> o <html> aquí. Eso se hace en footer.php -->
    <!-- El div 'container-fluid' de la versión anterior fue reemplazado por <main> y su padding/margin ajustado -->
    <!-- Los scripts JS se moverán a footer.php para mejor rendimiento de carga -->
</body>
</html>
