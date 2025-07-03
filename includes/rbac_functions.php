<?php
// includes/rbac_functions.php
// Este archivo contendrá las funciones principales para el Role-Based Access Control (RBAC).
// Asume que config.php (y por lo tanto db_connection.php y la sesión) ya han sido incluidos
// por el script que llama a estas funciones.

/**
 * Obtiene todos los roles asignados a un usuario específico.
 *
 * @param int $id_user El ID del usuario.
 * @param mysqli $conn La conexión a la base de datos.
 * @return array Un array de strings con los nombres de los roles del usuario. Vacío si no tiene roles o hay error.
 */
function get_user_roles(int $id_user, mysqli $conn): array {
    $roles = [];
    $sql = "SELECT r.nombre_rol
            FROM usuario_roles ur
            JOIN roles r ON ur.id_role = r.id_role
            WHERE ur.id_user = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar la consulta para obtener roles de usuario: " . $conn->error);
        return $roles;
    }

    $stmt->bind_param("i", $id_user);
    if (!$stmt->execute()) {
        error_log("Error al ejecutar la consulta para obtener roles de usuario: " . $stmt->error);
        $stmt->close();
        return $roles;
    }

    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $roles[] = $row['nombre_rol'];
    }

    $stmt->close();
    return $roles;
}

/**
 * Verifica si un usuario tiene un rol específico.
 *
 * @param int $id_user El ID del usuario.
 * @param string $nombre_rol El nombre clave del rol a verificar.
 * @param mysqli $conn La conexión a la base de datos.
 * @return bool True si el usuario tiene el rol, false en caso contrario.
 */
function user_has_role(int $id_user, string $nombre_rol, mysqli $conn): bool {
    // Podríamos optimizar esto si los roles ya están en sesión.
    // Por ahora, consultaremos directamente para asegurar consistencia.
    $user_roles = get_user_roles($id_user, $conn);
    return in_array($nombre_rol, $user_roles);
}

/**
 * Obtiene todos los permisos asignados a un rol específico.
 *
 * @param int $id_role El ID del rol.
 * @param mysqli $conn La conexión a la base de datos.
 * @return array Un array de strings con los nombres de los permisos del rol. Vacío si no tiene permisos o hay error.
 */
function get_role_permissions(int $id_role, mysqli $conn): array {
    $permisos = [];
    $sql = "SELECT p.nombre_permiso
            FROM rol_permisos rp
            JOIN permisos p ON rp.id_permiso = p.id_permiso
            WHERE rp.id_role = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar la consulta para obtener permisos de rol: " . $conn->error);
        return $permisos;
    }

    $stmt->bind_param("i", $id_role);
    if (!$stmt->execute()) {
        error_log("Error al ejecutar la consulta para obtener permisos de rol: " . $stmt->error);
        $stmt->close();
        return $permisos;
    }

    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $permisos[] = $row['nombre_permiso'];
    }

    $stmt->close();
    return $permisos;
}

/**
 * Obtiene una lista plana (array único) de todos los permisos que un usuario tiene
 * a través de todos sus roles asignados.
 *
 * @param int $id_user El ID del usuario.
 * @param mysqli $conn La conexión a la base de datos.
 * @return array Un array de strings con los nombres de todos los permisos únicos del usuario.
 */
function get_user_permissions_flat(int $id_user, mysqli $conn): array {
    $all_permissions = [];
    $sql = "SELECT DISTINCT p.nombre_permiso
            FROM usuario_roles ur
            JOIN roles r ON ur.id_role = r.id_role
            JOIN rol_permisos rp ON r.id_role = rp.id_role
            JOIN permisos p ON rp.id_permiso = p.id_permiso
            WHERE ur.id_user = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar la consulta para obtener permisos aplanados de usuario: " . $conn->error);
        return $all_permissions;
    }

    $stmt->bind_param("i", $id_user);
    if (!$stmt->execute()) {
        error_log("Error al ejecutar la consulta para obtener permisos aplanados de usuario: " . $stmt->error);
        $stmt->close();
        return $all_permissions;
    }

    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $all_permissions[] = $row['nombre_permiso'];
    }

    $stmt->close();
    return array_unique($all_permissions); // Asegurar que sean únicos
}


/**
 * Verifica si un usuario tiene un permiso específico, ya sea directamente
 * o a través de los roles que tiene asignados.
 *
 * @param int $id_user El ID del usuario.
 * @param string $nombre_permiso El nombre clave del permiso a verificar.
 * @param mysqli $conn La conexión a la base de datos.
 * @return bool True si el usuario tiene el permiso, false en caso contrario.
 */
function user_has_permission(int $id_user, string $nombre_permiso, mysqli $conn): bool {
    // Si el usuario es superadmin, siempre tiene permiso (convención)
    // Esta lógica de superadmin podría ser más robusta, ej. un permiso '*' o una tabla de permisos directos de usuario.
    // Por ahora, si el rol 'superadmin' existe y el usuario lo tiene, se asume permiso total.
    // Esto requiere que el rol 'superadmin' sea consistente.
    if (user_has_role($id_user, 'superadmin', $conn)) {
        // Antes de retornar true, verificar si el permiso 'superadmin_override_all' existe
        // o si simplemente el rol 'superadmin' implica todos los permisos.
        // Por simplicidad inicial, si es superadmin, tiene todos los permisos.
        // En una implementación más compleja, 'superadmin' podría tener un permiso especial como '*'
        // y esta función buscaría ese permiso. O, no tener esta lógica aquí y que 'superadmin'
        // tenga explícitamente todos los permisos asignados en la tabla rol_permisos.
        // Para este caso, si el rol es 'superadmin', asumimos que sí tiene el permiso.
        // Esta es una simplificación. Una mejor forma sería que 'superadmin' tenga todos los permisos
        // asignados en la BD o un permiso especial como 'access_all_areas'.
        // Para no complicar demasiado ahora, lo dejamos así, pero es un punto a mejorar.

        // Una forma simple de manejar 'superadmin' es que tenga un permiso especial 'SUPER_ADMIN_ACCESS'
        // y aquí se verifique si $nombre_permiso es ese o si el usuario tiene ese permiso.
        // O, como está ahora, simplemente darle acceso.
        // Considerar que el rol "superadmin" debe existir en la tabla `roles`.
        // return true; // Esta línea daría acceso total a 'superadmin' a cualquier permiso consultado.
                     // Es potente pero puede no ser lo deseado si se quiere granularidad incluso para superadmin.
                     // Por ahora, vamos a la comprobación explícita de permisos.
    }

    $user_permissions = get_user_permissions_flat($id_user, $conn);
    return in_array($nombre_permiso, $user_permissions);
}

/**
 * Función de ayuda para ser usada en la parte superior de las páginas/scripts
 * para restringir el acceso basado en roles.
 * Redirige si el usuario no tiene ninguno de los roles requeridos.
 *
 * @param array $required_roles Array de nombres de roles requeridos.
 * @param mysqli $conn Conexión a la BD.
 * @param string $redirect_url URL a la que redirigir si no tiene acceso (default: login.php).
 */
function require_role(array $required_roles, mysqli $conn, string $redirect_url = ''): void {
    if (!isset($_SESSION['user_id'])) {
        $final_redirect_url = !empty($redirect_url) ? $redirect_url : APP_URL . '/auth/login.php?auth_required=1';
        header("Location: " . $final_redirect_url);
        exit;
    }

    $has_required_role = false;
    foreach ($required_roles as $role_name) {
        if (user_has_role($_SESSION['user_id'], $role_name, $conn)) {
            $has_required_role = true;
            break;
        }
    }

    if (!$has_required_role) {
        // Podríamos tener una página de "Acceso Denegado" más amigable
        // $_SESSION['error_message'] = "No tienes permiso para acceder a esta página.";
        // header("Location: " . APP_URL . "/error_access_denied.php");
        http_response_code(403); // Forbidden
        die("Acceso Denegado. No tienes el rol requerido para acceder a esta página.");
    }
}

/**
 * Función de ayuda para ser usada para verificar permisos antes de realizar acciones.
 * Termina la ejecución si el usuario no tiene el permiso.
 *
 * @param string $required_permission Nombre del permiso requerido.
 * @param mysqli $conn Conexión a la BD.
 */
function require_permission(string $required_permission, mysqli $conn): void {
    if (!isset($_SESSION['user_id'])) {
        // Si no hay sesión, es un problema de autenticación, no de autorización necesariamente.
        // Podría redirigir a login o simplemente morir.
        http_response_code(401); // Unauthorized
        die("Autenticación requerida para realizar esta acción.");
    }

    if (!user_has_permission($_SESSION['user_id'], $required_permission, $conn)) {
        http_response_code(403); // Forbidden
        die("Acceso Denegado. No tienes el permiso necesario ('" . htmlspecialchars($required_permission) . "') para realizar esta acción.");
    }
}

?>
