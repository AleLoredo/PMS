<?php
// Este archivo asume que config.php ya ha sido incluido.
$appUrl = defined('APP_URL') ? APP_URL : '.';
$appName = defined('APP_NAME') ? APP_NAME : 'Mi Aplicación';
$currentYear = date('Y');
?>

    <!-- Aquí termina el contenido específico de la página que estaría entre header.php y footer.php -->
    </main> <!-- Cierre del <main role="main" class="container-fluid mt-3"> abierto en header.php -->

    <footer class="footer mt-auto py-3 bg-dark text-white">
        <div class="container text-center">
            <span>&copy; <?php echo $currentYear; ?> <?php echo htmlspecialchars($appName); ?>. Todos los derechos reservados.</span>
            <div>
                <!-- <a href="<?php echo $appUrl; ?>/privacy.php" class="text-white-50">Política de Privacidad</a> |
                <a href="<?php echo $appUrl; ?>/terms.php" class="text-white-50">Términos de Servicio</a> -->
            </div>
        </div>
    </footer>

    <!-- Scripts JS -->
    <!-- jQuery primero, luego Popper.js (Bootstrap 4 usa Popper.js v1, Bootstrap 5 usa Popper.js v2) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <!-- Para Bootstrap 4.5.2, Popper.js v1.16.1 es común -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>

    <!-- Tu script personalizado (asegúrate que la ruta sea correcta desde la raíz del sitio) -->
    <!-- La variable $appUrl debe estar definida y ser la URL base de tu aplicación -->
    <script src="<?php echo rtrim($appUrl, '/'); ?>/js/script.js"></script>

    <!-- Aquí podrías incluir otros scripts específicos de la página si es necesario, -->
    <!-- usando una variable o un array de scripts definidos en la página que incluye el footer. -->
    <?php if (isset($page_specific_scripts) && is_array($page_specific_scripts)): ?>
        <?php foreach ($page_specific_scripts as $script_path): ?>
            <script src="<?php echo rtrim($appUrl, '/') . '/' . ltrim(htmlspecialchars($script_path), '/'); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

</body>
</html>
