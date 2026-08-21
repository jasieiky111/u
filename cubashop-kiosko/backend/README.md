# CubaShop Kiosko Backend

Backend PHP 8.1+ / MySQL para el Kiosko interno de CubaShop.

## Reglas funcionales
- Moneda operativa: CUP.
- Pagos soportados: `efectivo` y `transfermovil`.
- `transfermovil` exige `transfer_reference`.
- No existe checkout público.
- Trabajadores operan sus propios recursos.
- `kiosk_admin` y `server_admin` tienen funciones administrativas según endpoint.
- Anulaciones restauran inventario y generan auditoría.

## Instalación
1. Crear una base MySQL vacía.
2. Ejecutar `database/schema.sql`.
3. Configurar variables de `.env.example` como variables de entorno del servidor.
4. Apuntar el document root a `public/`.
5. El endpoint `GET /health` no requiere autenticación y sirve únicamente para comprobar disponibilidad.
6. Todos los demás endpoints requieren el Bearer token correspondiente; `/login` además requiere el secreto de integración.

## Endpoints
- `GET /health`
- `POST /login`
- `GET /products`
- `POST /products` (admin)
- `POST /inventory/adjust` (admin)
- `POST /sales` (trabajador/admin)
- `POST /sales/void` (admin)
- `GET /cash`
- `POST /cash/open`
- `POST /cash/close`
- `GET /reports/sales` (admin)
- `GET /reports/audit` (admin)

## Seguridad
No almacenar secretos reales en Git. Usar variables de entorno. Producción debe usar HTTPS, una clave API aleatoria de alta entropía y una cuenta MySQL con privilegios mínimos.

## Integración WordPress.com
WordPress.com será la puerta de entrada/interfaz. El backend debe permanecer fuera de WordPress.com Free y no debe habilitar checkout público.
