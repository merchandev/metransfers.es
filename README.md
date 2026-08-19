# MeTransfers Platform

Plataforma WordPress integrada para la web, reservas de traslados, pricing, pagos Redsys, Hotel QR, administración y migraciones de MeTransfers. El repositorio conserva adaptadores legacy dentro del tema, pero su arranque, configuración y contratos principales se gestionan desde `app/`.

## Estado

- Preparada para validación completa en **staging**.
- Redsys usa **test/sandbox** por defecto.
- No debe activarse Redsys Live hasta completar el checklist operativo y rotar cualquier credencial expuesta en el historial anterior del repositorio.

## Arquitectura

```text
app/
├── Admin/                 Menú administrativo unificado
├── Booking/               Shortcodes, rutas, i18n y flujo de reserva
├── Core/                  Bootstrap, assets, settings y migraciones
├── Payments/Redsys/       Generación y verificación de pagos
├── Pricing/               Cálculo de tarifas
└── Legacy/
    ├── WPTB/              Adaptador del booking original
    └── Hotel/            Adaptador de Hotel QR
assets/                    Design system y tracking del booking
docs/integration/          Contratos, inventarios y guías de staging
tests/                     Pruebas smoke/unitarias sin WordPress completo
tools/                     Utilidades operativas mantenidas
```

`functions.php` carga `app/bootstrap.php` y ejecuta `MeTransfers\Core\Application::boot()`. La aplicación registra una sola vez CPT, shortcodes, assets, migraciones y administración.

## Requisitos

- WordPress 6.x y PHP 8.2 recomendado.
- MySQL/MariaDB compatible con `dbDelta()`.
- Node.js para validar sintaxis JavaScript.
- WooCommerce solo para el flujo legacy que crea pedidos/carrito.
- Google Maps JavaScript API para autocompletado/mapas.
- Google Distance Matrix API accesible desde el servidor para autorizar distancia y precio.

## Configuración

Los secretos no deben almacenarse en Git. La prioridad es: constantes de `wp-config.php`, opciones de WordPress y valores seguros por defecto.

```php
define( 'MT_GOOGLE_MAPS_API_KEY', '...' );
define( 'MT_GOOGLE_MAPS_SERVER_API_KEY', '...' );

define( 'MT_REDSYS_MERCHANT_CODE', '...' );
define( 'MT_REDSYS_SECRET', '...' );
define( 'MT_REDSYS_TERMINAL', '1' );
define( 'MT_REDSYS_CURRENCY', '978' );
define( 'MT_REDSYS_ENVIRONMENT', 'test' ); // test | live

define( 'MT_SMTP_HOST', '...' );
define( 'MT_SMTP_USER', '...' );
define( 'MT_SMTP_PASSWORD', '...' );
define( 'MT_SMTP_PORT', 587 );
define( 'MT_SMTP_ENCRYPTION', 'tls' );
define( 'MT_SMTP_FROM', '...' );
define( 'MT_SMTP_FROM_NAME', 'MeTransfers' );
```

La clave Maps pública debe restringirse por dominio. La clave de servidor debe restringirse por IP y por API. Las credenciales Redsys/SMTP deben rotarse antes de producción si estuvieron presentes en commits antiguos.

## Instalación y migraciones

1. Desplegar el contenido como el tema activo de staging.
2. Crear las constantes en `wp-config.php` o configurar sus opciones desde **MeTransfers → Ajustes generales**.
3. Cargar una página administrativa una vez para ejecutar la migración versionada.
4. Revisar `wp_options.mt_platform_db_version`; la versión esperada está definida por `MT_PLATFORM_DB_VERSION`.
5. Verificar el estado con `php tools/migration-status.php` dentro de un entorno WordPress cargado.

La migración actual también aprovisiona contenido inicial por compatibilidad. Debe probarse primero sobre una copia reciente de la base de datos.

## Pruebas locales

```bash
find . -path ./vendor -prune -o -type f -name "*.php" -print0 | xargs -0 -n1 php -l
php tests/test-legacy-load.php
php tests/test-pricing.php
php tests/test-route-distance.php
php tests/test-redsys-gateway.php
php tests/test-i18n.php
php tests/test-production-readiness.php
find . -path ./node_modules -prune -o -path ./vendor -prune -o -type f -name "*.js" -print0 | xargs -0 -n1 node --check
```

GitHub Actions repite estas verificaciones y añade controles de BOM/mojibake, patrones de credenciales y Gitleaks.

## Flujo de reservas y pagos

1. El navegador captura ruta, fecha y preferencias.
2. El servidor recalcula distancia, duración, capacidad y precio.
3. Redsys recibe un importe derivado exclusivamente del servidor.
4. La Return URL exige un token HMAC ligado a la orden y solo presenta el estado; nunca confirma el pago.
5. El IPN valida firma, comercio, terminal, moneda, orden e importe antes de marcar la reserva como pagada.
6. Las notificaciones repetidas son idempotentes.
7. El evento analítico `purchase` se emite únicamente cuando la plantilla confirma `paid` y `confirmed/completed` desde la base de datos.

## Assets, idiomas y analítica

Los assets se cargan por fase:

- búsqueda: Maps y booking;
- vehículos: booking;
- detalles: booking y Maps;
- pago: Redsys y Maps;
- confirmación: Redsys; jsPDF se descarga solo al solicitar el recibo;
- Hotel/Transfers Premium: solo en su contexto.

El booking incluye catálogo español e inglés sin dependencia externa. Los demás idiomas utilizan `mt_translate_batch()` cuando Google Cloud Translation está configurado. Las URLs internas conservan el prefijo de idioma.

Los eventos disponibles en `dataLayer` son `booking_start`, `route_search`, `vehicle_select`, `begin_checkout`, `add_payment_info`, `generate_lead`, `purchase`, `click_whatsapp`, `click_phone`, `booking_error` y `payment_error`. No se envía PII en estos eventos.

## Validación de staging

Seguir [docs/integration/staging-compatibility-report.md](docs/integration/staging-compatibility-report.md). En staging se pueden desactivar, sin borrar, los plugins originales **MeTransfers Booking** y **Hotel QR Plugin** para probar el reemplazo integrado.

Antes de Live deben verificarse al menos:

- Redsys Sandbox: pago autorizado, IPN, retorno, recarga e IPN duplicado;
- reserva one-way y round-trip;
- Hotel QR con token válido/inválido;
- emails y alertas;
- navegación `/en/` del booking;
- eventos de conversión sin PII;
- rotación de credenciales históricas.

## Despliegue y rollback

1. Hacer copia de archivos y base de datos.
2. Desplegar un commit con CI verde.
3. Mantener `MT_REDSYS_ENVIRONMENT=test` durante la aceptación.
4. Ejecutar migraciones y el checklist de staging.
5. Activar Live solo con credenciales rotadas y una prueba Sandbox completa.

Si falla el booking integrado: reactivar los plugins originales, restaurar la versión anterior del tema y revisar logs privados. No borrar tablas ni ejecutar scripts de reparación manuales.

## Seguridad

- No subir credenciales, volcados SQL ni scripts de diagnóstico públicos.
- No confirmar pagos desde query strings o datos de `sessionStorage`.
- Mantener logs de base de datos y pasarela fuera de respuestas AJAX públicas.
- Revisar alertas de Gitleaks y rotar el secreto afectado; borrar el valor del HEAD no invalida su exposición histórica.
- Proteger `main` y exigir el workflow de calidad antes de fusionar.

## Propiedad intelectual

Todos los derechos reservados © MERCHAN.DEV. El uso, copia, modificación o distribución requiere autorización expresa de sus titulares.
