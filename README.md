# MeTransfers Platform

Repositorio principal y fuente única de MeTransfers: plataforma WordPress para reservas, cotización autoritativa, pagos Redsys, Portal de Hoteles, Hotel QR, flota, traducciones, SEO, analítica y operaciones.

Este árbol corresponde al proyecto final de producción y conserva el historial consolidado del tema, el motor de reservas y el traductor. Consulta [HISTORIAL.md](HISTORIAL.md) para conocer la evolución completa.

## Estado

- Proyecto consolidado en un único repositorio.
- Portal privado `/hoteles/` aislado por hotel, con usuarios, reservas, clientes, estadísticas e importación Excel.
- Administración separada en los menús **Reservas** y **Hoteles**, con control reversible de acceso para cada responsable.
- Cotización, capacidad, distancia y precio validados en servidor.
- Calidad automatizada con PHPUnit, PHPStan, WPCS, ESLint, Playwright y WordPress real.
- Redsys Live sólo debe habilitarse con credenciales configuradas fuera de Git y attestaciones operativas completas.

## Componentes principales

- Web pública multilingüe y páginas SEO de rutas, destinos, tours y servicios.
- Motor de reservas con rutas, vehículos, capacidad, tarifas, pagos y recibos autoritativos.
- Portal de Hoteles con dashboards independientes y responsables por hotel.
- Gestión de Hotel QR, usuarios, importación/exportación y atribución de reservas.
- **Hoteles → Reservas de Hoteles → «Exportar Excel por hotel»**: un único `.xlsx` con la pestaña **Resumen** (totales por hotel y todas las reservas de hoteles, con filtros) y una pestaña por cada hotel registrado, con sus datos, su actividad (reservas por estado, importes, pasajeros, kilómetros, primera y última reserva) y todas sus reservas. Requiere el permiso de exportar reservas y queda registrado en la auditoría.
- Pantalla **Hoteles → Usuarios / Accesos** para identificar responsables, hoteles asignados, supervisores y bloquear exclusivamente su entrada al portal.
- Importación XLSX/CSV idempotente por referencia y Token Hotel: crea filas nuevas, actualiza coincidencias y conserva precio, distancia, vehículo y atribución.
- **Newsletter** (menú propio en wp-admin): popup en la web a los 10 segundos con nombre, correo y consentimiento; doble confirmación por email; páginas visitadas en la visita de registro (solo con cookies analíticas aceptadas); suscriptores por estado; campañas a medida con envío en cola; importación CSV/XLSX; exportación a Excel; baja en un clic y herramientas RGPD de WordPress. Ver [Newsletter](#newsletter).
- Traducciones, caché, selector de idioma e integración SEO/Yoast.
- Outbox, notificaciones, analítica, auditoría y migraciones reanudables.

## Newsletter

Código en `app/Newsletter/` y `assets/{js,css}/newsletter.*`; tablas `{prefijo}mt_newsletter_subscribers`, `mt_newsletter_campaigns`, `mt_newsletter_deliveries` y `mt_newsletter_manual_queue` (esquema 6.12.0). Permiso `mt_manage_newsletter` (administradores).

- **Popup.** Aparece a los 10 segundos (configurable). Nunca durante la reserva o el pago, en el Portal de Hoteles ni encima del banner de cookies. **Como máximo una vez por sesión y una vez cada 24 horas** (configurable) por navegador y por IP: el navegador recuerda la sesión y el día y solo entonces hace una única consulta al servidor (`mt_newsletter_slot`), que dice que sí una vez por IP en ese plazo y guarda solo un hash con sal de la IP en un transient que caduca solo. Si la consulta falla, el popup no aparece. Si la persona lo cierra, no vuelve en 14 días, y si se suscribe, no vuelve nunca. Pide nombre, correo y consentimiento, que es obligatorio y enlaza la política de privacidad. Contra bots tiene un campo trampa, un tiempo mínimo y un límite de 5 intentos cada 10 minutos por IP.
- **Bloque en el pie.** «Recibe nuestras ofertas por email», arriba del footer de todas las páginas públicas (`PublicController::footerForm()`, llamado desde `footer.php`). Está también en las páginas de reserva, donde el popup no sale, porque no interrumpe. Usa el mismo envío, consentimiento, doble confirmación y protección contra bots que el popup, y guarda el origen «Formulario del pie». Quien se suscribe desde el pie ya no ve el popup. Se activa o desactiva en Ajustes, y su título y texto son editables en español e inglés. En escritorio va en una fila; en móvil se apila y la casilla de consentimiento queda antes del botón.
- **Páginas visitadas.** Si la visita aceptó las cookies analíticas, el navegador guarda en `sessionStorage` las páginas de esa sesión y se envían al suscribirse. Si no las aceptó, solo se guarda la página de registro. Solo se aceptan rutas de la propia web.
- **Estados.** Pendiente de confirmar, Suscrito, Dado de baja y Rebotado. Con la doble confirmación (activada por defecto), el alta queda pendiente hasta que se pulsa el enlace del correo. Si el servidor de correo falla, la confirmación se reintenta por el outbox. Las direcciones dadas de baja o rebotadas nunca reciben campañas, y una importación no las vuelve a suscribir.
- **Campañas.** Editor de WordPress con etiquetas `{{nombre}}`, `{{email}}`, `{{baja}}`, `{{confirmar}}` y `{{web}}`. Permite elegir audiencia por estado e idioma, enviar una prueba, ver una vista previa y usar plantillas («Oferta» y «Pedir confirmación a pendientes»). Al enviar, la lista de destinatarios se congela, con un registro único por campaña y suscriptor. El envío lo hace la tarea `mt_newsletter_dispatch` cada 5 minutos, con un solo proceso a la vez, en tandas que respetan el máximo por hora (300 por defecto) y con 3 intentos para los fallos temporales. Un envío interrumpido nunca se repite, y quien se da de baja a mitad de campaña ya no la recibe. Si WP-Cron está desactivado, hay un botón «Procesar ahora».
- **Correos.** Salen por el SMTP de Ajustes Generales, con versión en texto plano, pie legal configurable y enlace de baja. Llevan las cabeceras `List-Unsubscribe` y `List-Unsubscribe-Post` (baja en un clic, RFC 8058), que exigen Gmail y Yahoo. Los enlaces de confirmar y de baja van firmados con HMAC (sin secretos guardados) y pasan por `admin-post.php`, que la caché no guarda. La página de baja pide un clic, porque los antivirus de correo abren todos los enlaces.
- **Altas manuales y lotes.** En Newsletter → Ajustes se pueden pegar direcciones separadas por comas, punto y coma o saltos de línea. También se admiten CSV/XLSX de hasta 20.000 filas y 10 MB por carga. Todas las cargas administrativas van a una cola persistente sin máximo total acumulado: una tanda aleatoria de 12 a 98 cada seis horas, con primera tanda seis horas después de la primera carga. Se mantienen el orden de llegada y el consentimiento; los duplicados se omiten y no se reactivan bajas ni rebotados. Las altas del popup y del pie son inmediatas y no comparten este cupo. La pantalla permite ver y cancelar la espera; los pendientes reciben confirmación al incorporarse. WP-Cron depende del tráfico; un cron de servidor permite puntualidad. Ver [detalle del módulo](app/Newsletter/README.md).
- **Eliminar.** Botón por suscriptor y acción masiva, con permiso y nonce. Se borra también el historial y cualquier entrada manual coincidente; los errores de base de datos se muestran y revierten la operación.
- **Diseño de la web.** El popup, el bloque del pie, los correos y las páginas de confirmar y de baja usan los colores y la tipografía de la web en directo: azul marino `#0B1F35` de las secciones oscuras y del botón «Buscar vehículos», etiqueta en ámbar `#FFB547` con su raya, títulos en Outfit y texto en Inter, campos y botones del formulario de reserva, y el logo blanco de la cabecera (Apariencia → Personalizar → Logo). En los correos todos los estilos van en línea (`app/Newsletter/Brand.php`), porque la mayoría de los clientes de correo descartan las hojas de estilo.
- **RGPD.** Guarda el texto y la fecha del consentimiento y la fecha de confirmación, pero no la IP del suscriptor. Para el límite del popup solo se guarda 24 horas un hash con sal de la IP, que no se puede revertir y caduca solo. Exportar y borrar datos personales (Herramientas de WordPress) incluyen el newsletter, y borrar un suscriptor elimina también su historial de envíos.

## Arquitectura

```text
app/
├── Admin/                 Menú administrativo unificado
├── Analytics/             Adaptador GA4 y drenaje del outbox analítico legado
├── Booking/               Shortcodes, rutas, i18n y flujo de reserva
├── Core/                  Bootstrap, assets, settings, migraciones y outbox genérico
├── Payments/Redsys/       Generación y verificación de pagos
├── Pricing/               Cálculo de tarifas
├── HotelPortal/           Portal privado y operaciones por hotel
└── Legacy/
    ├── WPTB/              Adaptador del booking original
    └── Hotel/            Adaptador de Hotel QR
assets/                    Design system y tracking del booking
docs/integration/          Contratos, inventarios y guías de staging
tests/                     PHPUnit, regresión legacy, integración WordPress y Playwright
tools/                     Utilidades operativas mantenidas
```

`functions.php` carga `app/bootstrap.php` y ejecuta `MeTransfers\Core\Application::boot()`. La aplicación registra una sola vez CPT, shortcodes, assets, migraciones y administración.

## Requisitos

- WordPress 6.8.6 o 7.0.2 y PHP 8.2 (matriz verificada en CI).
- MySQL/MariaDB compatible con `dbDelta()`.
- Composer 2 para PHPUnit, PHPStan, WPCS y auditoría de dependencias.
- Node.js 24 y npm para ESLint, auditoría y Playwright.
- WooCommerce solo para el flujo legacy que crea pedidos/carrito.
- Una sola clave de Google Maps para mapa y autocompletado (Maps JavaScript API, Places API, Directions API) y para autorizar dirección, distancia y precio en el servidor (Geocoding API, Distance Matrix API).

## Configuración

Los secretos no deben almacenarse en Git. La prioridad es: constantes de `wp-config.php`, opciones de WordPress y valores seguros por defecto.

```php
define( 'MT_GOOGLE_MAPS_API_KEY', '...' ); // Una sola clave: navegador y servidor.

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

define( 'MT_GA4_MEASUREMENT_ID', 'G-...' );
define( 'MT_GA4_API_SECRET', '...' );

// Solo después de completar y documentar cada acción externa:
define( 'MT_REDSYS_CREDENTIALS_ROTATED_AT', '2026-08-19T12:00:00+02:00' );
define( 'MT_SMTP_CREDENTIALS_ROTATED_AT', '2026-08-19T12:00:00+02:00' );
define( 'MT_MAPS_CREDENTIALS_ROTATED_AT', '2026-08-19T12:00:00+02:00' );
define( 'MT_REDSYS_SANDBOX_VERIFIED_AT', '2026-08-19T12:00:00+02:00' );
```

`MT_GOOGLE_MAPS_API_KEY` (o el campo «Google Maps API Key» de MeTransfers → Integraciones) es la **única** clave de Google Maps desde el 29/09/2026: carga el mapa y el autocompletado en el navegador y también geocodifica y calcula distancias en el servidor para cotizar. Como se usa en el servidor, no puede restringirse por sitio web ni por IP (Restricciones de aplicación: «Ninguna»). Al ser pública, debe restringirse por API (Maps JavaScript, Places, Directions, Geocoding y Distance Matrix) y tener un límite diario de cuota. La antigua `MT_GOOGLE_MAPS_SERVER_API_KEY` ya no se lee; la migración `20260929_001_single_maps_key` pasa su valor al campo único si era la única configurada y borra la opción antigua. Las credenciales Redsys/SMTP deben rotarse antes de producción si estuvieron presentes en commits antiguos.

**Direcciones y rutas guardadas en la base de datos** (desde el 29/09/2026, migración `20260929_002_address_cache_schema`, esquema 6.10.0). Cada dirección y ruta que se cotiza queda en `{prefijo}mt_addresses` y `{prefijo}mt_routes`. Cuando se repite (aeropuerto, puerto, estaciones, hoteles), el servidor responde desde la base de datos sin llamar a Google. Los datos de Google se renuevan a los 7 días y, si Google falla, se siguen usando hasta los 30. La tarea diaria `mt_purge_address_cache` los borra a los 30 días: es el máximo que permiten las condiciones de Google Maps Platform para coordenadas, y para las distancias de Distance Matrix las condiciones no fijan plazo, así que se aplica el mismo. Se conservan 13 meses la dirección tal como la escribió el cliente, el place ID (Google permite guardarlo sin límite) y los contadores. MeTransfers → Integraciones muestra cuántas direcciones y rutas hay y cuántas consultas se respondieron sin llamar a Google. Consultas útiles (cambia `wp_` por el prefijo de la base de datos):

```sql
SELECT address, lookups, hits, last_seen_at FROM wp_mt_addresses ORDER BY lookups DESC LIMIT 50;
SELECT origin, destination, lookups, ROUND(distance_meters / 1000, 1) AS km FROM wp_mt_routes ORDER BY lookups DESC LIMIT 50;
```
El gateway bloquea el endpoint Live mientras falte cualquiera de las cuatro attestaciones anteriores; las fechas son evidencia operativa y no deben inventarse.

## Instalación y migraciones

1. Desplegar el contenido como el tema activo de staging.
2. Crear las constantes en `wp-config.php` o configurar sus opciones desde **MeTransfers → Ajustes generales**.
3. La migración versionada se ejecuta automáticamente en `init` y `admin_init`; comprobar el journal tras el primer request.
4. Revisar `wp_options.mt_platform_db_version`; la versión esperada está definida por `MT_PLATFORM_DB_VERSION`.
5. Verificar el estado con `php tools/migration-status.php` dentro de un entorno WordPress cargado.

Los cambios de schema, backfills y seeds están separados. Las migraciones deben probarse primero sobre una copia reciente de la base de datos.

## Pruebas locales

```bash
composer install
composer validate --strict
composer audit --locked
composer quality
npm ci
npm audit --audit-level=high
npm run lint:js
npx playwright install chromium
npm run test:e2e

# Regresión compatible mantenida durante la transición legacy:
php tests/test-legacy-load.php
php tests/test-pricing.php
php tests/test-route-distance.php
php tests/test-address-cache.php
php tests/test-hotel-bookings-export.php
php tests/test-newsletter.php
php tests/test-booking-policies.php
php tests/test-redsys-gateway.php
php tests/test-hardening-phase1.php
php tests/test-outbox.php
php tests/test-booking-drafts.php
php tests/test-server-vehicle-quotes.php
php tests/test-money.php
php tests/test-authoritative-receipt.php
php tests/test-admin-security.php
php tests/test-migrations.php
php tests/test-i18n.php
php tests/test-i18n-routing.php
php tests/test-production-readiness.php
```

GitHub Actions ejecuta gates independientes de PHP, JavaScript/Chromium, Gitleaks y arranque WordPress real sobre MariaDB para WordPress 6.8.6 y 7.0.2. El smoke de WordPress verifica migraciones, tablas, CPT, shortcodes, capacidades, cron y rewrite rules. Playwright cubre contratos de selector de idioma, tracking de compra confirmado/no confirmado, idempotencia, limpieza de datos de sesión y eventos de contacto; el funnel completo continúa siendo un gate de staging.

## Flujo de reservas y pagos

1. El navegador captura ruta, fecha y preferencias.
2. El servidor valida área operativa, fecha, antelación y orden cronológico de la vuelta.
3. `/quote` calcula distancia, duración y precio autoritativos; el pago vuelve a validarlos.
4. El servidor exige y persiste versión y fecha de aceptación de los términos.
5. Redsys recibe un importe derivado exclusivamente del servidor.
6. La Return URL exige un token HMAC ligado a la orden y solo presenta el estado; nunca confirma el pago.
7. El IPN valida firma, comercio, terminal, moneda, orden e importe antes de marcar la reserva como pagada.
8. Después del `UPDATE`, el IPN inserta de forma idempotente `booking.paid:{booking_id}` y responde HTTP 200 sin esperar a SMTP, WhatsApp ni GA4.
9. El worker expande el evento en claves únicas por canal: cliente, administración, hotel, WhatsApp y analytics. Cada canal tiene retry exponencial y dead-letter independiente.
10. Un único `NotificationService` genera los mensajes localizados; el reenvío manual de email nunca reenvía WhatsApp.

## Assets, idiomas y analítica

Los assets se cargan por fase:

- búsqueda: Maps y booking;
- vehículos: booking;
- detalles: booking y Maps;
- pago: Redsys y Maps;
- confirmación: estilos de estado, tracking seguro y script mínimo de limpieza; el recibo se renderiza en servidor;
- Hotel/Transfers Premium: solo en su contexto.

El booking incluye catálogo español e inglés sin dependencia externa. En los demás idiomas, el frontend lee exclusivamente traducciones pre-generadas desde caché/DB. La llamada remota solo se permite desde **Ajustes → Traducción MT → Pre-generar catálogo booking**; la API key es write-only y viaja en header. Router, caché, selector, administración y SEO/Yoast están separados, y el selector usa CSS/JS versionados sin bloques inline. Las URLs internas conservan el prefijo de idioma y los idiomas fuera de `MT_SEO_LANGS` usan `noindex,follow`.

El recibo del cliente es HTML imprimible y se reconstruye desde una reserva pagada mediante referencia + HMAC. No usa datos de sesión ni librerías PDF externas; el navegador puede imprimirlo o guardarlo como PDF.

El panel MeTransfers usa capacidades de mínimo privilegio y dispone del rol **MeTransfers Operaciones**, sin acceso a integraciones ni secretos. Los reenvíos de email y WhatsApp son acciones separadas y auditadas; las exportaciones exigen permiso propio y un rango máximo de 366 días.

Las migraciones de base de datos son discretas y reanudables: usan un lock por sitio, un journal por paso y solo avanzan la versión al completar el lote. Las definiciones de schema, los backfills y los seeds de contenido tienen responsables separados; el activador legacy se conserva únicamente como fachada compatible.

Los eventos disponibles en `dataLayer` son `booking_start`, `route_search`, `vehicle_select`, `begin_checkout`, `add_payment_info`, `generate_lead`, `purchase`, `click_whatsapp`, `click_phone`, `booking_error` y `payment_error`. Teléfono y WhatsApp se capturan globalmente mediante un script mínimo. No se envía PII a `dataLayer`. Con `MT_GA4_MEASUREMENT_ID` y `MT_GA4_API_SECRET`, el worker despacha `analytics.purchase:{booking_id}` desde el outbox genérico. La tabla analítica anterior se mantiene únicamente para drenar eventos creados antes de la versión 6.1.0.

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
- No conservar scripts de diagnóstico que escriban credenciales o endpoints privados en `wp_options`.

## Historial consolidado

La rama `main` conserva los commits únicos del tema WordPress, el plugin de reservas/Hotel QR, Traductor MT, las ramas de endurecimiento y esta versión final. Git contabiliza cada SHA una sola vez aunque haya aparecido en varios repositorios o ramas. Véase [HISTORIAL.md](HISTORIAL.md).

## Propiedad intelectual

Todos los derechos reservados © MERCHAN.DEV. El uso, copia, modificación o distribución requiere autorización expresa de sus titulares.
