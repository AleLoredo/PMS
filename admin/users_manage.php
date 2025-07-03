<?php
$baseDir = dirname(__DIR__);
require_once $baseDir . '/config.php';
require_once $baseDir . '/includes/db_connection.php';
require_once $baseDir . '/includes/rbac_functions.php';

$login_url = defined('APP_URL') ? rtrim(APP_URL, '/') . '/auth/login.php' : '../auth/login.php';
if (!isset($_SESSION['user_id'])) { // Si no hay sesión, redirigir a login antes de chequear roles
    header("Location: " . $login_url . "?redirect_to=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
require_role(['admin', 'superadmin'], $conn, $login_url . '?error=users_unauthorized');

$pageTitle = "Gestión de Usuarios y Roles";

$feedback_message = $_SESSION['feedback_message_usermgt'] ?? '';
$feedback_type = $_SESSION['feedback_type_usermgt'] ?? ''; // 'success' o 'danger'
unset($_SESSION['feedback_message_usermgt']);
unset($_SESSION['feedback_type_usermgt']);

// --- Lógica para manejar POST (Actualizar Roles de Usuario) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user_roles'])) {
    $user_id_to_update = (int)$_POST['user_id_to_update'];
    $assigned_roles_ids = isset($_POST['roles']) ? array_map('intval', $_POST['roles']) : [];

    $conn->begin_transaction();
    try {
        // Verificar que el usuario exista y no se esté intentando modificar un superadmin por un admin no superadmin
        $user_stmt = $conn->prepare("SELECT email FROM systemusers WHERE id_user = ?");
        $user_stmt->bind_param("i", $user_id_to_update);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        if ($user_result->num_rows === 0) {
            throw new Exception("Usuario no encontrado.");
        }
        // $user_data = $user_result->fetch_assoc(); // Podríamos usar el email para logs o validaciones
        $user_stmt->close();

        // No permitir que un 'admin' modifique los roles de un 'superadmin'
        // (Un superadmin sí puede modificar a otro superadmin o a sí mismo)
        if (user_has_role($user_id_to_update, 'superadmin', $conn) && !user_has_role($_SESSION['user_id'], 'superadmin', $conn)) {
            throw new Exception("Los administradores no pueden modificar los roles de un superadministrador.");
        }

        // No permitir quitar el último rol de superadmin si solo hay uno y es el que se está editando
        if (user_has_role($user_id_to_update, 'superadmin', $conn) && !in_array(get_role_id_by_name('superadmin', $conn), $assigned_roles_ids)) {
            $superadmin_role_id = get_role_id_by_name('superadmin', $conn);
            $count_superadmins_stmt = $conn->query("SELECT COUNT(DISTINCT id_user) as count FROM usuario_roles WHERE id_role = {$superadmin_role_id}");
            $superadmin_count = $count_superadmins_stmt->fetch_assoc()['count'];
            if ($superadmin_count <= 1) {
                 throw new Exception("No se puede remover el último rol de superadministrador del sistema.");
            }
        }


        // Borrar roles existentes del usuario
        $stmt_delete_roles = $conn->prepare("DELETE FROM usuario_roles WHERE id_user = ?");
        $stmt_delete_roles->bind_param("i", $user_id_to_update);
        $stmt_delete_roles->execute();
        $stmt_delete_roles->close();

        // Asignar nuevos roles
        if (!empty($assigned_roles_ids)) {
            $stmt_add_role = $conn->prepare("INSERT INTO usuario_roles (id_user, id_role) VALUES (?, ?)");
            foreach ($assigned_roles_ids as $role_id) {
                $stmt_add_role->bind_param("ii", $user_id_to_update, $role_id);
                $stmt_add_role->execute();
            }
            $stmt_add_role->close();
        }

        $conn->commit();
        $_SESSION['feedback_message_usermgt'] = "Roles del usuario actualizados exitosamente.";
        $_SESSION['feedback_type_usermgt'] = 'success';

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['feedback_message_usermgt'] = "Error: " . $e->getMessage();
        $_SESSION['feedback_type_usermgt'] = 'danger';
    }
    // Redirigir para evitar reenvío de formulario y mostrar el mensaje
    header("Location: users_manage.php" . (isset($_GET['edit_user_id']) ? "?edit_user_id=".$user_id_to_update : ""));
    exit;
}


// --- Lógica para GET (Mostrar formulario de edición de roles de usuario o listar usuarios) ---
$editing_user_id = null;
$editing_user_info = null;
$editing_user_assigned_roles_ids = []; // IDs de roles asignados al usuario que se edita

if (isset($_GET['edit_user_id'])) {
    $editing_user_id = (int)$_GET['edit_user_id'];
    $stmt_user = $conn->prepare(
        "SELECT su.id_user, su.email, su.activado, c.nombre, c.apellido
         FROM systemusers su
         JOIN contacts c ON su.id_contact = c.id_contact
         WHERE su.id_user = ?"
    );
    $stmt_user->bind_param("i", $editing_user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($editing_user_info = $result_user->fetch_assoc()) {
        // Cargar roles asignados a este usuario
        $user_roles_names = get_user_roles($editing_user_id, $conn); // Nombres de roles
        // Convertir nombres a IDs para el formulario
        if (!empty($user_roles_names)) {
            $in_clause = "'" . implode("','", array_map([$conn, 'real_escape_string'], $user_roles_names)) . "'";
            $roles_id_result = $conn->query("SELECT id_role FROM roles WHERE nombre_rol IN ({$in_clause})");
            while($row = $roles_id_result->fetch_assoc()){
                $editing_user_assigned_roles_ids[] = $row['id_role'];
            }
        }
    } else {
        $_SESSION['feedback_message_usermgt'] = "Usuario no encontrado para editar roles.";
        $_SESSION['feedback_type_usermgt'] = 'danger';
        $editing_user_id = null; // Resetear
    }
    $stmt_user->close();
}

// Obtener todos los usuarios para mostrarlos en la tabla
$users_query = "SELECT su.id_user, su.email, su.activado, c.nombre, c.apellido
                FROM systemusers su
                JOIN contacts c ON su.id_contact = c.id_contact
                ORDER BY c.apellido, c.nombre ASC";
$users_result = $conn->query($users_query);

// Obtener todos los roles disponibles para los formularios
$all_roles_stmt = $conn->query("SELECT id_role, nombre_rol FROM roles ORDER BY nombre_rol ASC");
$all_roles = $all_roles_stmt->fetch_all(MYSQLI_ASSOC);

// Helper function para obtener ID de rol por nombre (usado en validaciones)
function get_role_id_by_name(string $role_name, mysqli $conn): ?int {
    $stmt = $conn->prepare("SELECT id_role FROM roles WHERE nombre_rol = ?");
    $stmt->bind_param("s", $role_name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return (int)$row['id_role'];
    }
    $stmt->close();
    return null;
}


require_once $baseDir . '/templates/header.php';
?>

<div class="container mt-4">
    <h2><?php echo htmlspecialchars($pageTitle); ?></h2>

    <?php if ($feedback_message): ?>
        <div class="alert alert-<?php echo $feedback_type === 'success' ? 'success' : 'danger'; ?>" role="alert">
            <?php echo htmlspecialchars($feedback_message); ?>
        </div>
    <?php endif; ?>

    <!-- Formulario para Editar Roles de Usuario (si se está editando) -->
    <?php if ($editing_user_id && $editing_user_info): ?>
    <div class="card mb-4">
        <div class="card-header">
            Editando Roles para: <?php echo htmlspecialchars($editing_user_info['nombre'] . ' ' . $editing_user_info['apellido'] . ' (' . $editing_user_info['email'] . ')'); ?>
        </div>
        <div class="card-body">
            <form action="users_manage.php?edit_user_id=<?php echo $editing_user_id; ?>" method="POST">
                <input type="hidden" name="user_id_to_update" value="<?php echo $editing_user_id; ?>">

                <div class="form-group">
                    <h5>Roles Asignados</h5>
                    <?php if (empty($all_roles)): ?>
                        <p class="text-muted">No hay roles definidos en el sistema. Por favor, créelos en la sección de Gestión de Roles.</p>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($all_roles as $role): ?>
                                <?php
                                // No permitir que un admin no-superadmin asigne/desasigne el rol 'superadmin'
                                $disabled_superadmin_toggle = ($role['nombre_rol'] === 'superadmin' && !user_has_role($_SESSION['user_id'], 'superadmin', $conn));
                                ?>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="roles[]" value="<?php echo $role['id_role']; ?>" id="role_<?php echo $role['id_role']; ?>"
                                            <?php echo in_array($role['id_role'], $editing_user_assigned_roles_ids) ? 'checked' : ''; ?>
                                            <?php echo $disabled_superadmin_toggle ? 'disabled' : ''; ?>>
                                        <label class="form-check-label" for="role_<?php echo $role['id_role']; ?>">
                                            <?php echo htmlspecialchars($role['nombre_rol']); ?>
                                            <?php if ($disabled_superadmin_toggle): ?>
                                                <small class="text-muted">(Solo Superadmin)</small>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" name="update_user_roles" class="btn btn-primary">Actualizar Roles del Usuario</button>
                <a href="users_manage.php" class="btn btn-secondary">Cancelar y Volver al Listado</a>
            </form>
        </div>
    </div>
    <?php endif; ?>


    <!-- Tabla de Usuarios Existentes -->
    <div class="card">
        <div class="card-header">
            Listado de Usuarios del Sistema
        </div>
        <div class="card-body">
            <?php if ($users_result && $users_result->num_rows > 0): ?>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre Completo</th>
                            <th>Email</th>
                            <th>Estado</th>
                            <th>Roles Asignados</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $user['id_user']; ?></td>
                                <td><?php echo htmlspecialchars($user['apellido'] . ', ' . $user['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo $user['activado'] ? '<span class="badge badge-success">Activado</span>' : '<span class="badge badge-secondary">No Activado</span>'; ?></td>
                                <td>
                                    <?php
                                    $user_roles_display = get_user_roles($user['id_user'], $conn);
                                    echo !empty($user_roles_display) ? htmlspecialchars(implode(', ', $user_roles_display)) : '<small class="text-muted">Ninguno</small>';
                                    ?>
                                </td>
                                <td>
                                    <a href="users_manage.php?edit_user_id=<?php echo $user['id_user']; ?>" class="btn btn-sm btn-info">Gestionar Roles</a>
                                    <!-- Aquí podrían ir más acciones como Editar Usuario (datos de contacto), Desactivar/Activar, etc. -->
                                    <!-- Estas requerirían sus propios permisos y lógica -->
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted">No hay usuarios registrados en el sistema.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
if ($users_result) $users_result->free();
if ($all_roles_stmt) $all_roles_stmt->free();
// $conn->close(); // No cerrar la conexión aquí si header.php o footer.php la necesitan o la cierran ellos.
// Es mejor cerrar la conexión centralmente al final de la respuesta, o no cerrarla explícitamente y dejar que PHP lo haga.
// Por ahora, la cerramos en footer.php si es necesario, o al final del script si no hay footer.
// Para scripts como este que incluyen header/footer, el footer se encargará.
require_once $baseDir . '/templates/footer.php';
?>
