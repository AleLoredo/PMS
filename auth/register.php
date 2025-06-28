<?php
// Incluir config.php al inicio para asegurar que las sesiones y otras configuraciones estén disponibles.
// Es una buena práctica tener un archivo central de inicialización o configuración.
if (file_exists('../config.php')) {
    require_once '../config.php';
} else {
    die('El archivo de configuración no se encuentra. Por favor, copie config-sample.php a config.php y configurelo.');
}

// Incluir header temporalmente aquí, idealmente se gestionaría con un sistema de plantillas o includes más robusto.
if (file_exists('../templates/header.php')) {
    // Pasamos el título de la página al header
    $pageTitle = "Registro de Usuario";
    // Podríamos pasar otras variables si fueran necesarias para el header
    // include '../templates/header.php';
}

// Lógica para manejar mensajes (éxito o error) que podrían venir de handle_register.php
$errors = $_SESSION['errors'] ?? [];
$success_message = $_SESSION['success_message'] ?? '';
$form_data = $_SESSION['form_data'] ?? [];

unset($_SESSION['errors']);
unset($_SESSION['success_message']);
unset($_SESSION['form_data']);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Registro'; ?> - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css"> <!-- Tu CSS personalizado si lo tienes -->
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="<?php echo APP_URL; ?>"><?php echo htmlspecialchars(APP_NAME); ?></a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="login.php">Login</a>
                </li>
                <li class="nav-item active">
                    <a class="nav-link" href="register.php">Registro <span class="sr-only">(current)</span></a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Registro de Nuevo Usuario</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form action="handle_register.php" method="POST" id="registerForm">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="nombre">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($form_data['nombre'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="apellido">Apellido <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="apellido" name="apellido" value="<?php echo htmlspecialchars($form_data['apellido'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="email">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="tipo_documento">Tipo de Documento</label>
                                    <select id="tipo_documento" name="tipo_documento" class="form-control">
                                        <option value="" <?php echo !(isset($form_data['tipo_documento'])) ? 'selected' : ''; ?>>Seleccione...</option>
                                        <option value="DNI" <?php echo (isset($form_data['tipo_documento']) && $form_data['tipo_documento'] == 'DNI') ? 'selected' : ''; ?>>DNI</option>
                                        <option value="Pasaporte" <?php echo (isset($form_data['tipo_documento']) && $form_data['tipo_documento'] == 'Pasaporte') ? 'selected' : ''; ?>>Pasaporte</option>
                                        <option value="Cedula" <?php echo (isset($form_data['tipo_documento']) && $form_data['tipo_documento'] == 'Cedula') ? 'selected' : ''; ?>>Cédula de Identidad</option>
                                        <option value="Otro" <?php echo (isset($form_data['tipo_documento']) && $form_data['tipo_documento'] == 'Otro') ? 'selected' : ''; ?>>Otro</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="numero_documento">Número de Documento</label>
                                    <input type="text" class="form-control" id="numero_documento" name="numero_documento" value="<?php echo htmlspecialchars($form_data['numero_documento'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="password">Contraseña <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small id="passwordHelpBlock" class="form-text text-muted">
                                    La contraseña debe tener al menos 8 caracteres.
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirmar Contraseña <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">Registrarse</button>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <small>¿Ya tienes una cuenta? <a href="login.php">Inicia Sesión aquí</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Incluir footer temporalmente aquí
    // if (file_exists('../templates/footer.php')) {
    //     include '../templates/footer.php';
    // }
    ?>

    <!-- Bootstrap JS, Popper.js, and jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- Tu script personalizado si lo tienes -->
    <script src="../js/script.js"></script>
    <script>
        // Validación básica del lado del cliente
        document.getElementById('registerForm').addEventListener('submit', function(event) {
            var password = document.getElementById('password').value;
            var confirmPassword = document.getElementById('confirm_password').value;
            var email = document.getElementById('email').value;
            var nombre = document.getElementById('nombre').value;
            var apellido = document.getElementById('apellido').value;
            var errors = [];

            if (nombre.trim() === '') {
                errors.push('El nombre es obligatorio.');
            }
            if (apellido.trim() === '') {
                errors.push('El apellido es obligatorio.');
            }
            if (email.trim() === '') {
                errors.push('El email es obligatorio.');
            } else {
                var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(email)) {
                    errors.push('Por favor, introduce un email válido.');
                }
            }
            if (password.length < 8) {
                errors.push('La contraseña debe tener al menos 8 caracteres.');
                // Prevenir el envío del formulario si hay un error de contraseña simple
                // event.preventDefault();
                // document.getElementById('password').focus();
                // alert('La contraseña debe tener al menos 8 caracteres.');
                // return;
            }
            if (password !== confirmPassword) {
                errors.push('Las contraseñas no coinciden.');
                // Prevenir el envío del formulario
                // event.preventDefault();
                // document.getElementById('confirm_password').focus();
                // alert('Las contraseñas no coinciden.');
                // return;
            }

            if (errors.length > 0) {
                event.preventDefault(); // Detener el envío del formulario
                // Mostrar errores (podrías integrarlos mejor en el DOM)
                alert("Por favor, corrige los siguientes errores:\n" + errors.join("\n"));
            }
        });
    </script>
</body>
</html>
<?php
// Limpiar variables de sesión usadas para mensajes y datos de formulario
unset($_SESSION['errors']);
unset($_SESSION['success_message']);
unset($_SESSION['form_data']);
?>
