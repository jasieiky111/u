# CubaShop Kiosko — despliegue PHP/MySQL

## Requisitos
- PHP 8.2+
- MySQL 8.0+ o MariaDB compatible con InnoDB
- Extensiones PHP: PDO, pdo_mysql, json, mbstring
- HTTPS obligatorio en producción

## 1. Base de datos
Crear una base de datos vacía y ejecutar:

`database/schema.sql`

No guardar contraseñas ni claves API en Git.

## 2. Variables de entorno
Copiar `.env.example` a la configuración del servidor y sustituir todos los valores de ejemplo. `DB_PASSWORD` y `API_SECRET` deben ser secretos reales y únicos.

## 3. Document root
Configurar el document root exclusivamente a:

`cubashop-kiosko/backend/public`

No publicar `database/`, `app/` ni archivos de configuración.

## 4. Comprobación
Abrir `GET /health`. Debe devolver `ok=true`. El endpoint no expone credenciales ni datos de negocio.

## 5. Seguridad
- Usar HTTPS.
- No activar errores PHP en pantalla en producción.
- Mantener secretos fuera de Git.
- Respaldar MySQL antes de actualizaciones.
- El endpoint de ventas requiere autenticación.
- El checkout público permanece deshabilitado.

## 6. WordPress.com
WordPress.com actúa como frontend/portal. El backend PHP debe vivir en un servidor PHP/MySQL independiente y exponerse mediante HTTPS. El botón de Kiosko puede apuntar al frontend del backend cuando el servidor esté desplegado.
