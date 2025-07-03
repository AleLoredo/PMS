-- Base de datos: gestor_salud

-- Tabla para almacenar información de contacto de los usuarios
CREATE TABLE contacts (
    id_contact INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    tipo_documento VARCHAR(50),
    numero_documento VARCHAR(50) UNIQUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para almacenar credenciales y estado de los usuarios del sistema
CREATE TABLE systemusers (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    id_contact INT NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    activado BOOLEAN DEFAULT FALSE,
    token_activacion VARCHAR(64) DEFAULT NULL UNIQUE,
    token_recupero VARCHAR(64) DEFAULT NULL UNIQUE,
    token_recupero_expira TIMESTAMP NULL DEFAULT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_login TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (id_contact) REFERENCES contacts(id_contact) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Índices para mejorar el rendimiento de las búsquedas
CREATE INDEX idx_email ON systemusers(email);
CREATE INDEX idx_token_activacion ON systemusers(token_activacion);
CREATE INDEX idx_token_recupero ON systemusers(token_recupero);

-- --- Tablas para RBAC (Role-Based Access Control) ---

-- Tabla de Roles
CREATE TABLE roles (
    id_role INT AUTO_INCREMENT PRIMARY KEY,
    nombre_rol VARCHAR(50) NOT NULL UNIQUE COMMENT 'Nombre clave del rol, ej: admin, medico, paciente',
    descripcion_rol TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Permisos
CREATE TABLE permisos (
    id_permiso INT AUTO_INCREMENT PRIMARY KEY,
    nombre_permiso VARCHAR(100) NOT NULL UNIQUE COMMENT 'Nombre clave del permiso, ej: crear_usuario, ver_citas',
    descripcion_permiso TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Unión: Roles y Permisos (Muchos a Muchos)
CREATE TABLE rol_permisos (
    id_rol_permiso INT AUTO_INCREMENT PRIMARY KEY,
    id_role INT NOT NULL,
    id_permiso INT NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_role) REFERENCES roles(id_role) ON DELETE CASCADE,
    FOREIGN KEY (id_permiso) REFERENCES permisos(id_permiso) ON DELETE CASCADE,
    UNIQUE KEY idx_rol_permiso_unico (id_role, id_permiso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Unión: Usuarios y Roles (Muchos a Muchos)
CREATE TABLE usuario_roles (
    id_usuario_rol INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_role INT NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES systemusers(id_user) ON DELETE CASCADE,
    FOREIGN KEY (id_role) REFERENCES roles(id_role) ON DELETE CASCADE,
    UNIQUE KEY idx_usuario_rol_unico (id_user, id_role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
