# Fase 10 — Plataforma de calidad

## Gates reproducibles

La calidad deja de depender de comandos instalados globalmente. `composer.lock` y `package-lock.json` fijan las herramientas y sus dependencias; CI instala exclusivamente desde esos locks.

El gate PHP incluye:

- PHPUnit 11 sobre contratos de dinero, outbox, routing/SEO y retorno Redsys;
- PHPStan nivel 5 sobre el dominio moderno de mayor riesgo, sin baseline ni errores ignorados;
- WordPress Coding Standards y PHPCompatibility sobre 15 archivos modernos/sensibles;
- las 16 pruebas de regresión compatibles existentes;
- auditoría Composer, sintaxis, UTF-8 y patrones de credenciales.

El alcance de PHPStan/WPCS es incremental y explícito. `app/Legacy/` no se presenta como saneado por esas herramientas; su descomposición corresponde a una fase posterior y sigue protegido por regresión, ESLint, Playwright e integración WordPress.

## JavaScript y navegador

ESLint analiza los 11 scripts públicos/legacy y el harness E2E. Sus primeros hallazgos permitieron corregir dos referencias inexistentes en el flujo hotel/vehículo.

Playwright ejecuta Chromium contra los assets reales del repositorio y valida:

- apertura/cierre accesible del selector y devolución de foco;
- bloqueo de scroll móvil;
- `purchase` solo con estado confirmado por servidor;
- deduplicación del evento de compra;
- ausencia de compra en estado pendiente;
- borrado del payload legacy de sesión tras confirmación;
- allowlist de tracking de teléfono y WhatsApp.

Estas pruebas de contrato no sustituyen las rutas completas one-way/round-trip ni Redsys Sandbox en staging.

## WordPress real

GitHub Actions levanta MariaDB 11.4 y dos instalaciones limpias: WordPress 6.8.6 y 7.0.2. Tras activar el tema, WP-CLI comprueba comportamiento real:

- arranque moderno y adaptadores legacy;
- CPT y shortcodes;
- rol operativo y capacidades;
- cinco migraciones discretas y once tablas;
- worker cron del outbox;
- query vars y rewrite rules i18n.

Las acciones de terceros están fijadas por SHA y los permisos del workflow son de solo lectura.

## Comandos

```bash
composer install
composer quality
npm ci
npm run lint:js
npm run test:e2e
```

Los gates externos de staging, proveedores, rotación de secretos y aceptación humana SEO siguen documentados y no se declaran cerrados desde CI.
