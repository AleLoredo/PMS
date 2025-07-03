<?php
// scripts/seed_rbac.php
// Script para sembrar datos iniciales de RBAC (Roles, Permisos, y asignaciones básicas).
// Este script se ejecuta manualmente desde la línea de comandos: php scripts/seed_rbac.php

// Cargar configuración y conexión a BD
// Asumimos que este script está en una carpeta 'scripts' en la raíz del proyecto.
$baseDir = dirname(__DIR__);
require_once $baseDir . '/config.php'; // Carga .env, define constantes, e inicia sesión (aunque no la usemos mucho aquí)
require_once $baseDir . '/includes/db_connection.php'; // Establece $conn
require_once $baseDir . '/includes/rbac_functions.php'; // Funciones RBAC por si son útiles, aunque haremos inserciones directas.

echo "Iniciando sembrado de datos RBAC...\n";

// --- 1. Definición de Permisos ---
// (Estos son ejemplos, deben ajustarse a las necesidades reales de la aplicación)
$permisos_a_crear = [
    // Gestión de Usuarios
    ['nombre_permiso' => 'user_list_view', 'descripcion_permiso' => 'Ver lista de usuarios del sistema.'],
    ['nombre_permiso' => 'user_view_details', 'descripcion_permiso' => 'Ver detalles de un usuario específico.'],
    ['nombre_permiso' => 'user_create', 'descripcion_permiso' => 'Crear nuevos usuarios en el sistema.'],
    ['nombre_permiso' => 'user_edit', 'descripcion_permiso' => 'Editar información de usuarios existentes.'],
    ['nombre_permiso' => 'user_delete', 'descripcion_permiso' => 'Eliminar usuarios del sistema.'],
    ['nombre_permiso' => 'user_manage_roles', 'descripcion_permiso' => 'Asignar o remover roles a los usuarios.'],

    // Gestión de Roles y Permisos (RBAC)
    ['nombre_permiso' => 'role_list_view', 'descripcion_permiso' => 'Ver lista de roles del sistema.'],
    ['nombre_permiso' => 'role_create', 'descripcion_permiso' => 'Crear nuevos roles.'],
    ['nombre_permiso' => 'role_edit', 'descripcion_permiso' => 'Editar roles existentes (nombre, descripción, permisos asignados).'],
    ['nombre_permiso' => 'role_delete', 'descripcion_permiso' => 'Eliminar roles del sistema.'],
    ['nombre_permiso' => 'permission_view_all', 'descripcion_permiso' => 'Ver todos los permisos definidos en el sistema.'], // Generalmente para desarrollo/superadmin

    // Módulo de Pacientes (Ejemplos)
    ['nombre_permiso' => 'patient_record_view_basic', 'descripcion_permiso' => 'Ver información básica de pacientes.'],
    ['nombre_permiso' => 'patient_record_view_full', 'descripcion_permiso' => 'Ver historial médico completo de pacientes.'],
    ['nombre_permiso' => 'patient_record_create', 'descripcion_permiso' => 'Crear nuevos registros de pacientes.'],
    ['nombre_permiso' => 'patient_record_edit', 'descripcion_permiso' => 'Editar registros de pacientes.'],

    // Módulo de Citas (Ejemplos)
    ['nombre_permiso' => 'appointment_schedule_own', 'descripcion_permiso' => 'Agendar citas para sí mismo (ej. paciente).'],
    ['nombre_permiso' => 'appointment_schedule_others', 'descripcion_permiso' => 'Agendar citas para otros (ej. secretaria para médicos/pacientes).'],
    ['nombre_permiso' => 'appointment_view_own', 'descripcion_permiso' => 'Ver sus propias citas.'],
    ['nombre_permiso' => 'appointment_view_all_doctor', 'descripcion_permiso' => 'Médico ve todas sus citas asignadas.'],
    ['nombre_permiso' => 'appointment_view_all_secretary', 'descripcion_permiso' => 'Secretaria ve todas las citas de la clínica/médicos.'],
    ['nombre_permiso' => 'appointment_cancel', 'descripcion_permiso' => 'Cancelar citas.'],

    // Permiso especial para Superadmin (si no se maneja por omisión de chequeo)
    // ['nombre_permiso' => 'SUPER_ADMIN_FULL_ACCESS', 'descripcion_permiso' => 'Acceso total a todas las funcionalidades (usado por el rol superadmin).'],
];

echo "Creando permisos...\n";
$map_permisos_id = []; // Para mapear nombre_permiso a id_permiso para la siguiente fase
$stmt_permiso = $conn->prepare("INSERT INTO permisos (nombre_permiso, descripcion_permiso) VALUES (?, ?) ON DUPLICATE KEY UPDATE descripcion_permiso=VALUES(descripcion_permiso)");
if (!$stmt_permiso) die("Error preparando statement para permisos: " . $conn->error);

foreach ($permisos_a_crear as $permiso) {
    $stmt_permiso->bind_param("ss", $permiso['nombre_permiso'], $permiso['descripcion_permiso']);
    if ($stmt_permiso->execute()) {
        $id_permiso_insertado = $conn->insert_id;
        if ($id_permiso_insertado == 0) { // Si hubo ON DUPLICATE KEY, necesitamos obtener el ID existente
            $res_id = $conn->query("SELECT id_permiso FROM permisos WHERE nombre_permiso = '" . $conn->real_escape_string($permiso['nombre_permiso']) . "'");
            $id_permiso_insertado = $res_id->fetch_assoc()['id_permiso'];
        }
        $map_permisos_id[$permiso['nombre_permiso']] = $id_permiso_insertado;
        echo "Permiso '{$permiso['nombre_permiso']}' creado/actualizado con ID: {$map_permisos_id[$permiso['nombre_permiso']]}.\n";
    } else {
        echo "Error creando permiso '{$permiso['nombre_permiso']}': " . $stmt_permiso->error . "\n";
    }
}
$stmt_permiso->close();
echo "Permisos creados.\n\n";


// --- 2. Definición de Roles ---
$roles_a_crear = [
    ['nombre_rol' => 'paciente', 'descripcion_rol' => 'Usuario paciente del sistema.'],
    ['nombre_rol' => 'secretaria', 'descripcion_rol' => 'Personal administrativo, gestiona citas y pacientes.'],
    ['nombre_rol' => 'medicolvl1', 'descripcion_rol' => 'Médico nivel 1.'],
    ['nombre_rol' => 'medicolvl2', 'descripcion_rol' => 'Médico nivel 2.'],
    ['nombre_rol' => 'medicolvl3', 'descripcion_rol' => 'Médico nivel 3.'],
    ['nombre_rol' => 'medicolvl4', 'descripcion_rol' => 'Médico nivel 4.'],
    ['nombre_rol' => 'medicolvl5', 'descripcion_rol' => 'Médico nivel 5 (ej. especialista o jefe de área).'],
    ['nombre_rol' => 'admin', 'descripcion_rol' => 'Administrador del sistema con acceso a gestión de usuarios y roles.'],
    ['nombre_rol' => 'superadmin', 'descripcion_rol' => 'Super administrador con acceso total al sistema.'],
];

echo "Creando roles...\n";
$map_roles_id = []; // Para mapear nombre_rol a id_role
$stmt_rol = $conn->prepare("INSERT INTO roles (nombre_rol, descripcion_rol) VALUES (?, ?) ON DUPLICATE KEY UPDATE descripcion_rol=VALUES(descripcion_rol)");
if (!$stmt_rol) die("Error preparando statement para roles: " . $conn->error);

foreach ($roles_a_crear as $rol) {
    $stmt_rol->bind_param("ss", $rol['nombre_rol'], $rol['descripcion_rol']);
    if ($stmt_rol->execute()) {
        $id_rol_insertado = $conn->insert_id;
         if ($id_rol_insertado == 0) { // Si hubo ON DUPLICATE KEY, necesitamos obtener el ID existente
            $res_id = $conn->query("SELECT id_role FROM roles WHERE nombre_rol = '" . $conn->real_escape_string($rol['nombre_rol']) . "'");
            $id_rol_insertado = $res_id->fetch_assoc()['id_role'];
        }
        $map_roles_id[$rol['nombre_rol']] = $id_rol_insertado;
        echo "Rol '{$rol['nombre_rol']}' creado/actualizado con ID: {$map_roles_id[$rol['nombre_rol']]}.\n";
    } else {
        echo "Error creando rol '{$rol['nombre_rol']}': " . $stmt_rol->error . "\n";
    }
}
$stmt_rol->close();
echo "Roles creados.\n\n";


// --- 3. Asignación de Permisos a Roles ---
// (Aquí defines qué permisos tiene cada rol. Ajusta según necesidad)
$asignaciones_rol_permiso = [
    'paciente' => ['appointment_schedule_own', 'appointment_view_own', 'patient_record_view_basic'], // Ver su propia info básica
    'secretaria' => [
        'user_list_view', 'user_view_details', // Ver usuarios para buscar pacientes/médicos
        'patient_record_view_basic', 'patient_record_create', 'patient_record_edit',
        'appointment_schedule_others', 'appointment_view_all_secretary', 'appointment_cancel'
    ],
    'medicolvl1' => [ // Asumimos que todos los médicos pueden ver su propia info y citas
        'appointment_view_own_doctor', 'patient_record_view_full', // Ver historial completo de sus pacientes
        'appointment_cancel', // Puede cancelar sus propias citas
    ],
    // medicolvl2 a 5 podrían heredar de lvl1 y añadir más permisos específicos.
    // Por simplicidad, les damos los mismos que lvl1, pero esto se refinaría.
    'medicolvl2' => ['appointment_view_own_doctor', 'patient_record_view_full', 'appointment_cancel'],
    'medicolvl3' => ['appointment_view_own_doctor', 'patient_record_view_full', 'appointment_cancel'],
    'medicolvl4' => ['appointment_view_own_doctor', 'patient_record_view_full', 'appointment_cancel'],
    'medicolvl5' => ['appointment_view_own_doctor', 'patient_record_view_full', 'appointment_cancel', 'user_list_view'], // Quizás un jefe de área puede ver otros usuarios

    'admin' => [
        'user_list_view', 'user_view_details', 'user_create', 'user_edit', 'user_delete', 'user_manage_roles',
        'role_list_view', 'role_create', 'role_edit', 'role_delete',
        'permission_view_all', // Puede ver qué permisos existen
        // Podría tener también permisos de secretaria si es necesario
        'patient_record_view_basic', 'patient_record_create', 'patient_record_edit',
        'appointment_schedule_others', 'appointment_view_all_secretary', 'appointment_cancel'
    ],
    'superadmin' => [], // Se podría llenar con todos los permisos o manejarlo en el código
];

echo "Asignando permisos a roles...\n";
$stmt_rol_permiso = $conn->prepare("INSERT IGNORE INTO rol_permisos (id_role, id_permiso) VALUES (?, ?)");
if (!$stmt_rol_permiso) die("Error preparando statement para rol_permisos: " . $conn->error);

foreach ($asignaciones_rol_permiso as $nombre_rol => $permisos_del_rol) {
    if (!isset($map_roles_id[$nombre_rol])) {
        echo "ADVERTENCIA: Rol '$nombre_rol' no encontrado en el mapeo, saltando asignación de permisos.\n";
        continue;
    }
    $id_role = $map_roles_id[$nombre_rol];
    foreach ($permisos_del_rol as $nombre_permiso) {
        if (!isset($map_permisos_id[$nombre_permiso])) {
            echo "ADVERTENCIA: Permiso '$nombre_permiso' no encontrado en el mapeo para el rol '$nombre_rol', saltando.\n";
            continue;
        }
        $id_permiso = $map_permisos_id[$nombre_permiso];
        $stmt_rol_permiso->bind_param("ii", $id_role, $id_permiso);
        if ($stmt_rol_permiso->execute()) {
            if ($stmt_rol_permiso->affected_rows > 0) {
                 echo "Permiso '{$nombre_permiso}' asignado al rol '{$nombre_rol}'.\n";
            } else {
                 echo "Permiso '{$nombre_permiso}' YA ESTABA asignado al rol '{$nombre_rol}' o error menor.\n";
            }
        } else {
            echo "Error asignando permiso '{$nombre_permiso}' al rol '{$nombre_rol}': " . $stmt_rol_permiso->error . "\n";
        }
    }
}

// Asignar todos los permisos existentes al rol 'superadmin'
if (isset($map_roles_id['superadmin'])) {
    $id_superadmin_role = $map_roles_id['superadmin'];
    echo "Asignando todos los permisos al rol 'superadmin' (ID: $id_superadmin_role)...\n";
    foreach ($map_permisos_id as $nombre_permiso => $id_permiso) {
        $stmt_rol_permiso->bind_param("ii", $id_superadmin_role, $id_permiso);
        if ($stmt_rol_permiso->execute()) {
             if ($stmt_rol_permiso->affected_rows > 0) {
                echo "Permiso '{$nombre_permiso}' asignado a 'superadmin'.\n";
            }
        } else {
            echo "Error asignando permiso '{$nombre_permiso}' a 'superadmin': " . $stmt_rol_permiso->error . "\n";
        }
    }
}
$stmt_rol_permiso->close();
echo "Asignación de permisos a roles completada.\n\n";


// --- 4. (Opcional) Asignar un rol por defecto a un usuario existente (ej. el primer usuario como superadmin) ---
// Esto es un ejemplo. Necesitarías el ID de un usuario existente.
/*
$primer_usuario_email = "admin@example.com"; // Cambia esto al email de tu usuario admin
$id_user_admin = null;
$stmt_find_user = $conn->prepare("SELECT id_user FROM systemusers WHERE email = ?");
$stmt_find_user->bind_param("s", $primer_usuario_email);
$stmt_find_user->execute();
$result_user = $stmt_find_user->get_result();
if($result_user->num_rows > 0) {
    $id_user_admin = $result_user->fetch_assoc()['id_user'];
}
$stmt_find_user->close();

if ($id_user_admin && isset($map_roles_id['superadmin'])) {
    echo "Asignando rol 'superadmin' al usuario con email '{$primer_usuario_email}' (ID: {$id_user_admin})...\n";
    $id_superadmin_role = $map_roles_id['superadmin'];
    $stmt_user_role = $conn->prepare("INSERT IGNORE INTO usuario_roles (id_user, id_role) VALUES (?, ?)");
    if (!$stmt_user_role) die("Error preparando statement para usuario_roles: " . $conn->error);
    $stmt_user_role->bind_param("ii", $id_user_admin, $id_superadmin_role);
    if ($stmt_user_role->execute()) {
        if ($stmt_user_role->affected_rows > 0) {
            echo "Rol 'superadmin' asignado al usuario ID {$id_user_admin}.\n";
        } else {
            echo "Rol 'superadmin' YA ESTABA asignado al usuario ID {$id_user_admin} o error menor.\n";
        }
    } else {
        echo "Error asignando rol 'superadmin' al usuario ID {$id_user_admin}: " . $stmt_user_role->error . "\n";
    }
    $stmt_user_role->close();
} else {
    echo "No se pudo asignar el rol 'superadmin' al usuario '{$primer_usuario_email}'. Verifica que el usuario y el rol 'superadmin' existan.\n";
}
*/

echo "\nSembrado RBAC completado.\n";
$conn->close();
?>
