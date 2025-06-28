-- Tabla contacts
CREATE TABLE contacts (
    id_contact INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    apellido VARCHAR(255) NOT NULL,
    tipo_documento VARCHAR(50),
    numero_documento VARCHAR(50)
);

-- Tabla systemusers
CREATE TABLE systemusers (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    id_contact INT NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    activado BOOLEAN DEFAULT FALSE,
    token_activacion VARCHAR(255) DEFAULT NULL,
    token_recupero VARCHAR(255) DEFAULT NULL,
    token_recupero_expira TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (id_contact) REFERENCES contacts(id_contact)
);
