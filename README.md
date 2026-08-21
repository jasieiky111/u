# CubaShop Kiosko

Repositorio oficial de desarrollo del sistema interno de ventas de CubaShop.

## Base técnica
Sistema de ventas PHP/MySQL adaptado desde `WorkTeam01/Sistema_de_Ventas_PHP`.

## Reglas funcionales
- Solo trabajadores usan el sistema para registrar operaciones.
- Trabajadores 1–6: acceso restringido a sus propias operaciones.
- Administrador Kiosko: visión global de ventas, inventario, caja y reportes.
- Administrador de servidor: control estructural y técnico.
- Moneda operativa: CUP.
- Métodos de pago: efectivo y Transfermóvil.
- Sin compras públicas, carrito público ni checkout para clientes.
- Auditoría de operaciones sensibles.

## Integración objetivo
Backend PHP/MySQL separado + integración con CubaShop0 (WordPress.com) mediante API segura.

## Estado
Fase de adaptación y desarrollo. No usar para operaciones reales hasta completar pruebas y hardening.
