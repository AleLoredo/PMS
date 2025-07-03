<?php
$baseDir = dirname(__DIR__);
require_once $baseDir . '/config.php'; // Carga config, db_connection, y rbac_functions (si config lo incluye)
require_once $baseDir . '/includes/db_connection.php'; // Asegurar $conn
require_once $baseDir . '/includes/rbac_functions.php'; // Funciones RBAC

// Proteger esta página: solo accesible para 'admin' o 'superadmin'
// Asegurarse de que APP_URL esté disponible para la URL de login.
// config.php define APP_URL y también inicia la sesión si es necesario.
$login_url = defined('APP_URL') ? rtrim(APP_URL, '/') . '/auth/login.php' : '../auth/login.php';
if (!isset($_SESSION['user_id'])) { // Si no hay sesión, redirigir a login antes de chequear roles
    header("Location: " . $login_url . "?redirect_to=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
require_role(['admin', 'superadmin'], $conn, $login_url . '?error=roles_unauthorized');

$pageTitle = "Gestión de Roles";

// --- Lógica para manejar POST (Crear/Actualizar Rol) ---
$feedback_message = '';
$feedback_type = ''; // 'success' o 'danger'

// Variables para el formulario de edición
$editing_role_id = null;
$editing_role_name = '';
$editing_role_description = '';
$editing_role_permissions = []; // IDs de permisos asignados al rol que se edita

// Cargar todos los permisos disponibles para los formularios
$all_permissions_stmt = $conn->query("SELECT id_permiso, nombre_permiso, descripcion_permiso FROM permisos ORDER BY nombre_permiso ASC");
$all_permissions = $all_permissions_stmt->fetch_all(MYSQLI_ASSOC);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();
    try {
        if (isset($_POST['save_role'])) { // Crear o Actualizar Rol
            $role_id = isset($_POST['role_id']) && !empty($_POST['role_id']) ? (int)$_POST['role_id'] : null;
            $role_name = trim($_POST['role_name']);
            $role_description = trim($_POST['role_description']);
            $assigned_permissions = isset($_POST['permissions']) ? $_POST['permissions'] : []; // Array de IDs de permisos

            if (empty($role_name)) {
                throw new Exception("El nombre del rol no puede estar vacío.");
            }

            if ($role_id) { // Actualizar Rol existente
                $stmt = $conn->prepare("UPDATE roles SET nombre_rol = ?, descripcion_rol = ? WHERE id_role = ?");
                $stmt->bind_param("ssi", $role_name, $role_description, $role_id);
                $stmt->execute();
                $stmt->close();
                $feedback_message = "Rol actualizado exitosamente.";

                // Actualizar permisos del rol: primero borrar los existentes, luego añadir los nuevos
                $stmt_delete_perms = $conn->prepare("DELETE FROM rol_permisos WHERE id_role = ?");
                $stmt_delete_perms->bind_param("i", $role_id);
                $stmt_delete_perms->execute();
                $stmt_delete_perms->close();

                if (!empty($assigned_permissions)) {
                    $stmt_add_perm = $conn->prepare("INSERT INTO rol_permisos (id_role, id_permiso) VALUES (?, ?)");
                    foreach ($assigned_permissions as $perm_id) {
                        $perm_id_int = (int)$perm_id;
                        $stmt_add_perm->bind_param("ii", $role_id, $perm_id_int);
                        $stmt_add_perm->execute();
                    }
                    $stmt_add_perm->close();
                }

            } else { // Crear Nuevo Rol
                $stmt = $conn->prepare("INSERT INTO roles (nombre_rol, descripcion_rol) VALUES (?, ?)");
                $stmt->bind_param("ss", $role_name, $role_description);
                $stmt->execute();
                $new_role_id = $conn->insert_id;
                $stmt->close();
                $feedback_message = "Rol creado exitosamente.";

                if ($new_role_id && !empty($assigned_permissions)) {
                    $stmt_add_perm = $conn->prepare("INSERT INTO rol_permisos (id_role, id_permiso) VALUES (?, ?)");
                    foreach ($assigned_permissions as $perm_id) {
                        $perm_id_int = (int)$perm_id;
                        $stmt_add_perm->bind_param("ii", $new_role_id, $perm_id_int);
                        $stmt_add_perm->execute();
                    }
                    $stmt_add_perm->close();
                }
            }
            $conn->commit();
            $feedback_type = 'success';

        } elseif (isset($_POST['delete_role'])) { // Eliminar Rol
            $role_id_to_delete = (int)$_POST['role_id_to_delete'];
            // Validaciones adicionales: no permitir eliminar roles con usuarios asignados, o roles 'superadmin'/'admin'
            $check_users_stmt = $conn->prepare("SELECT COUNT(*) as count FROM usuario_roles WHERE id_role = ?");
            $check_users_stmt->bind_param("i", $role_id_to_delete);
            $check_users_stmt->execute();
            $user_count = $check_users_stmt->get_result()->fetch_assoc()['count'];
            $check_users_stmt->close();

            $role_info_stmt = $conn->prepare("SELECT nombre_rol FROM roles WHERE id_role = ?");
            $role_info_stmt->bind_param("i", $role_id_to_delete);
            $role_info_stmt->execute();
            $role_to_delete_name = $role_info_stmt->get_result()->fetch_assoc()['nombre_rol'];
            $role_info_stmt->close();

            if (in_array($role_to_delete_name, ['superadmin', 'admin'])) {
                 throw new Exception("No se pueden eliminar los roles 'superadmin' o 'admin'.");
            }
            if ($user_count > 0) {
                throw new Exception("No se puede eliminar el rol porque tiene {$user_count} usuario(s) asignado(s).");
            }

            // Primero eliminar asignaciones de rol_permisos
            $stmt_delete_perms = $conn->prepare("DELETE FROM rol_permisos WHERE id_role = ?");
            $stmt_delete_perms->bind_param("i", $role_id_to_delete);
            $stmt_delete_perms->execute();
            $stmt_delete_perms->close();

            // Luego eliminar el rol
            $stmt_delete_role = $conn->prepare("DELETE FROM roles WHERE id_role = ?");
            $stmt_delete_role->bind_param("i", $role_id_to_delete);
            $stmt_delete_role->execute();
            $stmt_delete_role->close();

            $conn->commit();
            $feedback_message = "Rol eliminado exitosamente.";
            $feedback_type = 'success';
        }
    } catch (Exception $e) {
        $conn->rollback();
        $feedback_message = "Error: " . $e->getMessage();
        $feedback_type = 'danger';
    }
}

// --- Lógica para GET (Mostrar formulario de edición o listar roles) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit_id'])) {
    $editing_role_id = (int)$_GET['edit_id'];
    $stmt = $conn->prepare("SELECT id_role, nombre_rol, descripcion_rol FROM roles WHERE id_role = ?");
    $stmt->bind_param("i", $editing_role_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($role_data = $result->fetch_assoc()) {
        $editing_role_name = $role_data['nombre_rol'];
        $editing_role_description = $role_data['descripcion_rol'];

        // Cargar permisos asignados a este rol
        $perms_stmt = $conn->prepare("SELECT id_permiso FROM rol_permisos WHERE id_role = ?");
        $perms_stmt->bind_param("i", $editing_role_id);
        $perms_stmt->execute();
        $perms_result = $perms_stmt->get_result();
        while ($row = $perms_result->fetch_assoc()) {
            $editing_role_permissions[] = $row['id_permiso'];
        }
        $perms_stmt->close();

    } else {
        $feedback_message = "Rol no encontrado para editar.";
        $feedback_type = 'danger';
        $editing_role_id = null; // Resetear para no mostrar el form de edición vacío
    }
    $stmt->close();
}


// Obtener todos los roles para mostrarlos en la tabla
$roles_result = $conn->query("SELECT id_role, nombre_rol, descripcion_rol, fecha_creacion FROM roles ORDER BY nombre_rol ASC");

require_once $baseDir . '/templates/header.php'; // Incluir cabecera
?>

<div class="container mt-4">
    <h2><?php echo htmlspecialchars($pageTitle); ?></h2>

    <?php if ($feedback_message): ?>
        <div class="alert alert-<?php echo $feedback_type === 'success' ? 'success' : 'danger'; ?>" role="alert">
            <?php echo htmlspecialchars($feedback_message); ?>
        </div>
    <?php endif; ?>

    <!-- Formulario para Crear o Editar Rol -->
    <div class="card mb-4">
        <div class="card-header">
            <?php echo $editing_role_id ? 'Editar Rol: ' . htmlspecialchars($editing_role_name) : 'Crear Nuevo Rol'; ?>
        </div>
        <div class="card-body">
            <form action="roles_manage.php<?php echo $editing_role_id ? '?edit_id=' . $editing_role_id : ''; ?>" method="POST">
                <?php if ($editing_role_id): ?>
                    <input type="hidden" name="role_id" value="<?php echo $editing_role_id; ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label for="role_name">Nombre del Rol <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="role_name" name="role_name" value="<?php echo htmlspecialchars($editing_role_name); ?>" required>
                </div>
                <div class="form-group">
                    <label for="role_description">Descripción</label>
                    <textarea class="form-control" id="role_description" name="role_description" rows="2"><?php echo htmlspecialchars($editing_role_description); ?></textarea>
                </div>

                <div class="form-group">
                    <h5>Permisos</h5>
                    <?php if (empty($all_permissions)): ?>
                        <p class="text-muted">No hay permisos definidos en el sistema. Por favor, ejecute el script de sembrado o créelos manualmente.</p>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($all_permissions as $permission): ?>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo $permission['id_permiso']; ?>" id="perm_<?php echo $permission['id_permiso']; ?>"
                                            <?php echo in_array($permission['id_permiso'], $editing_role_permissions) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="perm_<?php echo $permission['id_permiso']; ?>" title="<?php echo htmlspecialchars($permission['descripcion_permiso']); ?>">
                                            <?php echo htmlspecialchars($permission['nombre_permiso']); ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" name="save_role" class="btn btn-primary">
                    <?php echo $editing_role_id ? 'Actualizar Rol' : 'Crear Rol'; ?>
                </button>
                <?php if ($editing_role_id): ?>
                    <a href="roles_manage.php" class="btn btn-secondary">Cancelar Edición</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Tabla de Roles Existentes -->
    <div class="card">
        <div class="card-header">
            Roles Existentes
        </div>
        <div class="card-body">
            <?php if ($roles_result && $roles_result->num_rows > 0): ?>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre del Rol</th>
                            <th>Descripción</th>
                            <th>Permisos Asignados</th>
                            <th>Fecha Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($role = $roles_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $role['id_role']; ?></td>
                                <td><?php echo htmlspecialchars($role['nombre_rol']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($role['descripcion_rol'])); ?></td>
                                <td>
                                    <?php
                                    $role_perms = get_role_permissions($role['id_role'], $conn);
                                    if (empty($role_perms)) {
                                        echo '<small class="text-muted">Ninguno</small>';
                                    } else {
                                        // Limitar la cantidad de permisos mostrados para no saturar la tabla
                                        $max_perms_to_show = 3;
                                        $shown_perms = array_slice($role_perms, 0, $max_perms_to_show);
                                        echo htmlspecialchars(implode(', ', $shown_perms));
                                        if (count($role_perms) > $max_perms_to_show) {
                                            echo '... (' . (count($role_perms) - $max_perms_to_show) . ' más)';
                                        }
                                    }
                                    ?>
                                </td>
                                <td><?php echo date("d/m/Y H:i", strtotime($role['fecha_creacion'])); ?></td>
                                <td>
                                    <a href="roles_manage.php?edit_id=<?php echo $role['id_role']; ?>" class="btn btn-sm btn-info">Editar</a>
                                    <?php if (!in_array($role['nombre_rol'], ['superadmin', 'admin', 'paciente'])): // No permitir borrar roles críticos o base ?>
                                    <form action="roles_manage.php" method="POST" style="display: inline-block;" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este rol? Esta acción no se puede deshacer.');">
                                        <input type="hidden" name="role_id_to_delete" value="<?php echo $role['id_role']; ?>">
                                        <button type="submit" name="delete_role" class="btn btn-sm btn-danger">Eliminar</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted">No hay roles definidos en el sistema. Puedes crear uno usando el formulario de arriba.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
if ($roles_result) $roles_result->free();
if ($all_permissions_stmt) $all_permissions_stmt->free();
$conn->close();
require_once $baseDir . '/templates/footer.php'; // Incluir pie de página
?>
