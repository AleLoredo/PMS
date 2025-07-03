# Sistema de Gestión para Instituciones de Salud

## 1. Descripción General del Proyecto

Este proyecto tiene como objetivo desarrollar un sistema web de gestión integral para instituciones de salud. La aplicación se está construyendo utilizando exclusivamente PHP, MySQL y clases de Bootstrap, sin recurrir a frameworks de PHP adicionales para el backend.

El sistema busca proporcionar una solución robusta y fácil de usar para administrar diversos aspectos de una institución médica.

## 2. Módulos del Sistema

### Módulo 1: Autenticación de Usuarios

#### 2.1. Definición y Alcance del Módulo

El Módulo 1 se encarga de toda la gestión de acceso y seguridad de los usuarios al sistema. Sus características principales son:

*   **Registro de Usuarios:** Permite a nuevos usuarios crear una cuenta proporcionando datos personales (nombre, apellido, email, tipo y número de documento) y una contraseña.
*   **Activación de Cuenta por Email:** (Condicional) Si la funcionalidad SMTP está activada, los nuevos usuarios reciben un correo con un enlace único para activar su cuenta antes de poder iniciar sesión.
*   **Login Seguro:** Formulario para que los usuarios registrados accedan al sistema utilizando su email y contraseña. Valida la existencia del usuario, la correctitud de la contraseña (comparada con su hash) y el estado de activación de la cuenta.
*   **Recuperación de Contraseña por Email:** (Condicional) Si la funcionalidad SMTP está activada, los usuarios que olviden su contraseña pueden solicitar un enlace de recuperación a su correo electrónico. Este enlace permite establecer una nueva contraseña y tiene un tiempo de expiración.
*   **Logout:** Permite a los usuarios cerrar su sesión de forma segura.
*   **Interfaz Responsive:** Todos los formularios y vistas de este módulo están diseñados para ser responsivos utilizando clases de Bootstrap.
*   **Envío de Correos con AWS SES:** (Condicional) Cuando la funcionalidad SMTP está activa, todos los correos transaccionales (activación de cuenta, recuperación de contraseña) se envían utilizando AWS Simple Email Service (SES) como servidor SMTP, a través de una conexión segura (TLS/SSL).

#### 2.2. Estado Actual del Módulo 1

*   **Estado:** **COMPLETADO** (Implementación inicial finalizada).
*   **Tecnologías Utilizadas:**
    *   Backend: PHP (sin frameworks)
    *   Base de Datos: MySQL
    *   Frontend: HTML, Clases de Bootstrap 4, JavaScript (para validaciones básicas del lado del cliente)
    *   Envío de Emails: PHPMailer configurado para AWS SES.
*   **Características Implementadas:**
    *   Todas las funcionalidades descritas en la sección 2.1 (Registro, Activación, Login, Recuperación, Logout) han sido codificadas.
    *   Se ha implementado la estructura de base de datos necesaria (`contacts` y `systemusers`).
    *   El sistema utiliza `password_hash()` y `password_verify()` para la gestión segura de contraseñas.
    *   Se utilizan sentencias preparadas de MySQLi para la interacción con la base de datos, ayudando a prevenir inyecciones SQL.
    *   **Configuración Flexible de SMTP:**
        *   Se ha introducido una variable de entorno `SMTP_FUNCTION` (gestionada a través del archivo `.env`).
        *   Si `SMTP_FUNCTION` se establece en `"OFF"`:
            *   El registro de usuarios crea cuentas activadas directamente, sin enviar email de activación.
            *   La funcionalidad de recuperación de contraseña se deshabilita y se informa al usuario.
        *   Si `SMTP_FUNCTION` se establece en `"ON"` (valor por defecto):
            *   Se requiere activación por email para nuevas cuentas.
            *   La recuperación de contraseña por email está completamente funcional.
    *   **Configuración Centralizada vía `.env`:**
        *   El proyecto ahora utiliza un archivo `.env` (a partir de `.env.example`) para gestionar todas las configuraciones sensibles y específicas del entorno (credenciales de BD, AWS SES, URLs, etc.).
        *   Se ha integrado la librería `vlucas/phpdotenv` mediante Composer para cargar estas variables.

#### 2.3. Próximos Pasos para el Módulo 1

*   **Pruebas Exhaustivas:** Es fundamental realizar pruebas completas de todos los flujos en un entorno de desarrollo configurado, incluyendo pruebas con `SMTP_FUNCTION` en "ON" y "OFF".
*   **Revisión de Seguridad Detallada:** Aunque se han seguido buenas prácticas, una auditoría de seguridad más profunda es recomendable.
*   **Refinamiento de la Interfaz de Usuario (UI/UX):** Mejoras visuales y de usabilidad según sea necesario.
*   **Documentación Interna del Código:** Añadir o completar comentarios en el código.

## 3. Configuración del Entorno de Desarrollo

Para ejecutar este proyecto localmente, necesitarás:

*   **PHP:** Versión 7.3 o superior (recomendado 7.4+).
*   **MySQL:** Servidor de base de datos MySQL o MariaDB.
*   **Composer:** Para gestionar las dependencias de PHP (actualmente `vlucas/phpdotenv`).
*   **Servidor Web:** Apache, Nginx, o el servidor web integrado de PHP para desarrollo.
*   **Credenciales de AWS SES (Opcional):** Si deseas probar la funcionalidad de envío de emails (`SMTP_FUNCTION="ON"`), necesitarás una cuenta de AWS con SES configurado y las credenciales SMTP correspondientes.

### 3.1. Pasos para la Instalación:

1.  **Clonar el Repositorio:**
    ```bash
    git clone <URL_DEL_REPOSITORIO>
    cd <NOMBRE_DEL_DIRECTORIO_DEL_PROYECTO>
    ```

2.  **Instalar Dependencias de Composer:**
    ```bash
    composer install
    ```
    Esto instalará `vlucas/phpdotenv` y cualquier otra dependencia definida en `composer.json`.

3.  **Configurar el Archivo de Entorno:**
    *   Copia el archivo `.env.example` a un nuevo archivo llamado `.env`:
        ```bash
        cp .env.example .env
        ```
    *   Edita el archivo `.env` con tu configuración local específica:
        *   Credenciales de la base de datos (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).
        *   Configuración de AWS SES (`AWS_SES_HOST`, `AWS_SES_SMTP_USER`, etc.) si vas a usar el envío de emails.
        *   La URL base de tu aplicación (`APP_URL`).
        *   El valor de `SMTP_FUNCTION` (`"ON"` o `"OFF"`).
        *   Otras configuraciones según sea necesario.

4.  **Crear la Base de Datos e Importar Estructura:**
    *   Asegúrate de que tu servidor MySQL esté en ejecución.
    *   Crea una base de datos con el nombre que especificaste en `DB_NAME` en tu archivo `.env`.
    *   Importa la estructura de las tablas utilizando el archivo `database.sql`:
        ```bash
        mysql -u TU_USUARIO_MYSQL -p TU_BASE_DE_DATOS < database.sql
        ```
        (Reemplaza `TU_USUARIO_MYSQL` y `TU_BASE_DE_DATOS` con tus valores).

5.  **Configurar tu Servidor Web:**
    *   Apunta la raíz de tu servidor web (o virtual host) al directorio raíz del proyecto.
    *   Asegúrate de que el módulo `rewrite` (para URLs amigables, si se implementan más adelante) esté habilitado si usas Apache.

6.  **Permisos (si es necesario):**
    *   Asegúrate de que el servidor web tenga permisos de escritura para directorios de logs o uploads si se implementan en el futuro.

7.  **Acceder a la Aplicación:**
    *   Abre tu navegador y ve a la `APP_URL` que configuraste en tu archivo `.env`.

## 4. Variables de Entorno Clave (`.env`)

Consulta el archivo `.env.example` para una lista completa. Algunas de las más importantes son:

*   `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`: Para la conexión a la base de datos.
*   `APP_URL`: La URL base de tu aplicación. Es crucial para generar enlaces correctos (ej. en emails).
*   `SMTP_FUNCTION`: Controla si las funcionalidades de email (activación, recuperación de contraseña) están activas (`"ON"`) o si se usan flujos alternativos (`"OFF"`).
*   `AWS_SES_*`: Credenciales y configuración para el envío de emails a través de AWS SES (solo relevantes si `SMTP_FUNCTION="ON"`).

## 5. Próximos Pasos del Proyecto

Con el Módulo 1 de Autenticación completado, el desarrollo continuará con los siguientes módulos y funcionalidades planificadas para el sistema de gestión de instituciones de salud.

---

Este `README.md` proporciona una visión general del proyecto, el estado detallado del Módulo 1, y cómo configurar el entorno. Debería ser actualizado a medida que el proyecto evoluciona.
