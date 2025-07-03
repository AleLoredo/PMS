-- Base de datos: gestor_salud
-- Este script crea la base de datos y las tablas necesarias para el sistema de gestión de salud

-- Opcional: elimina la base de datos si ya existe (solo para desarrollo)
-- DROP DATABASE IF EXISTS gestor_salud;

CREATE DATABASE IF NOT EXISTS gestor_salud CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE gestor_salud;
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
