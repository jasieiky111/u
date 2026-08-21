# CubaShop Kiosko — despliegue gratuito

## Opción recomendada para la primera versión

InfinityFree ofrece hosting gratuito con PHP 8.3, MySQL/MariaDB, SSL y subdominio gratuito. Se utilizará para la demo funcional y pruebas del Kiosko; para operación comercial real se recomienda migrar posteriormente a hosting de producción.

## 1. Crear hosting

1. Crear una cuenta en InfinityFree.
2. Crear un sitio/subdominio gratuito.
3. Crear una base de datos MySQL desde el panel.
4. Anotar host, nombre de BD, usuario y contraseña.

## 2. Preparar el backend

El document root debe apuntar a `cubashop-kiosko/backend/public`.

Si el proveedor no permite cambiar el document root, subir el contenido de `backend/public` al directorio público y conservar `backend/app`, `backend/database` y `backend/config.php` fuera del directorio público. Nunca publicar `config.php`.

## 3. Configuración

Copiar `backend/config.example.php` como `backend/config.php` en el servidor y completar DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD, API_SECRET y CORS_ORIGIN.

`API_SECRET` debe ser una cadena aleatoria larga. No se debe subir a GitHub.

## 4. Base de datos

Importar `backend/database/schema.sql` mediante phpMyAdmin.

Después crear las cuentas administrativas y de trabajadores usando hashes generados con `password_hash()`; nunca almacenar contraseñas en texto plano.

Usuarios previstos:

- server_admin
- kiosk_admin
- worker1
- worker2
- worker3
- worker4
- worker5
- worker6

Las contraseñas iniciales deben establecerse durante la instalación y cambiarse inmediatamente después del primer acceso.

## 5. Prueba

Abrir `https://TU-SUBDOMINIO/health`.

Debe devolver JSON con `ok: true`, `currency: CUP` y `public_checkout: false`.

Login: `POST /login` con JSON `{"username":"worker1","password":"TU_PASSWORD"}`.

El token devuelto se utiliza como `Authorization: Bearer TOKEN`.

## 6. Integración con CubaShop0

La interfaz WordPress debe llamar únicamente a la API HTTPS del Kiosko. El secreto de firma nunca debe aparecer en JavaScript ni en una página pública de WordPress.

CORS debe contener únicamente el origen exacto de CubaShop0.

## Seguridad

- HTTPS obligatorio.
- No publicar `config.php`.
- No subir contraseñas ni tokens a GitHub.
- Cambiar las contraseñas iniciales.
- Mantener `public_checkout=false`.
- Revisar logs y auditoría antes de usar dinero real.
- El hosting gratuito es para pruebas/MVP, no una garantía de disponibilidad empresarial.
