# MeTransfers — Informe consolidado de cambios, fases 1–10

**Repositorio:** `merchandev/metransfers.es`

**Baseline de `main`:** `c4083efd2bbac772dfbb7d5f785dec8a46cfd71d`

**Rama acumulada final:** `codex/phase-10-quality-platform`

**Fecha de cierre técnico:** 2026-08-19

**Documento de origen:** `MeTransfers — Auditoría técnica contrastada 10/10 v2`

## 1. Resultado ejecutivo

Se implementaron las diez fases solicitadas sin reescribir el proyecto y conservando los contratos públicos: slugs, shortcodes, IDs, tablas existentes, Redsys, WooCommerce legacy, Hotel QR y prefijos de idioma. La versión de aplicación resultante es `6.9.0` y la versión de esquema es `6.5.0`.

El resultado queda dividido en dos estados que no deben confundirse:

- **Código y automatización:** las diez fases están implementadas, probadas localmente y publicadas como una cadena de diez pull requests borrador. Los gates automatizados están verdes.
- **Operación en producción:** sigue bloqueada hasta completar pruebas y evidencias externas de credenciales, proveedores, staging, Redsys Sandbox, migración sobre una copia real, backup y rollback. `ReleaseGate` mantiene Redsys Live bloqueado mientras falten esas attestations.

Este informe reemplaza el informe RC anterior allí donde aquel documento hacía afirmaciones absolutas sobre privacidad, despliegue o producción. No afirma que los PR estén integrados en `main`: permanecen abiertos y apilados para revisión.

## 2. Publicación en GitHub

Los PR deben revisarse e integrarse en este orden. Cada uno tiene como base la fase anterior para mantener diffs pequeños y auditables.

| Fase | Cambio | Commit(s) | PR borrador | Ejecución validada |
|---:|---|---|---|---|
| 1 | Hardening de entradas públicas | `03c2987` | [#3](https://github.com/merchandev/metransfers.es/pull/3) | [32290758892](https://github.com/merchandev/metransfers.es/actions/runs/32290758892) |
| 2 | Outbox genérico y notificaciones asíncronas | `60cd038`, `00a2cbb` | [#4](https://github.com/merchandev/metransfers.es/pull/4) | [32292230278](https://github.com/merchandev/metransfers.es/actions/runs/32292230278) |
| 3 | Booking drafts e idempotencia | `199c2a7` | [#5](https://github.com/merchandev/metransfers.es/pull/5) | [32293728791](https://github.com/merchandev/metransfers.es/actions/runs/32293728791) |
| 4 | Vehículos cotizados en servidor | `f89b691` | [#6](https://github.com/merchandev/metransfers.es/pull/6) | [32294804651](https://github.com/merchandev/metransfers.es/actions/runs/32294804651) |
| 5 | Dinero entero en céntimos | `4cb8d71` | [#7](https://github.com/merchandev/metransfers.es/pull/7) | [32295773572](https://github.com/merchandev/metransfers.es/actions/runs/32295773572) |
| 6 | Recibo autoritativo | `5ec43d1` | [#8](https://github.com/merchandev/metransfers.es/pull/8) | [32296647403](https://github.com/merchandev/metransfers.es/actions/runs/32296647403) |
| 7 | Seguridad administrativa | `2941110` | [#9](https://github.com/merchandev/metransfers.es/pull/9) | [32298376589](https://github.com/merchandev/metransfers.es/actions/runs/32298376589) |
| 8 | Migraciones discretas y reanudables | `bc3df53` | [#10](https://github.com/merchandev/metransfers.es/pull/10) | [32299466177](https://github.com/merchandev/metransfers.es/actions/runs/32299466177) |
| 9 | Modularización i18n/SEO | `8d0d8ba` | [#11](https://github.com/merchandev/metransfers.es/pull/11) | [32300938864](https://github.com/merchandev/metransfers.es/actions/runs/32300938864) |
| 10 | Plataforma de calidad reproducible | `8556083`, `b6e9e14`, `a4f89de`, `b18c8c8` | [#12](https://github.com/merchandev/metransfers.es/pull/12) | [32304427056](https://github.com/merchandev/metransfers.es/actions/runs/32304427056) |

Estado de la rama principal al redactar este informe:

```text
main: c4083efd2bbac772dfbb7d5f785dec8a46cfd71d
PR #3 -> main
PR #4 -> phase 1
...
PR #12 -> phase 9
```

Por tanto, el código acumulado no debe describirse como desplegado ni integrado en `main` hasta que GitHub muestre esos merges.

## 3. Arquitectura resultante del flujo crítico

```text
Browser
  -> cotización autoritativa de todos los vehículos
  -> BookingDraft con token opaco (PII server-side)
  -> inicio idempotente de Redsys
  -> Redsys
       -> IPN firmado
            -> transición atómica en DB
            -> eventos idempotentes en outbox
            -> ACK sin esperar SMTP/WhatsApp/GA4
                 -> worker con retry, backoff y dead-letter
       -> retorno firmado
            -> estado pagado leído de DB
            -> recibo server-side con HMAC
```

Principios que ahora se aplican en código:

- el servidor es la autoridad de ruta, vehículo, capacidad, precio y estado de pago;
- el navegador no puede confirmar una compra ni construir un recibo financiero;
- Redsys usa el importe entero persistido, no un `float` recalculado;
- el IPN no espera proveedores remotos;
- la PII del booking no viaja a pago dentro de `sessionStorage`;
- las operaciones administrativas se autorizan por capacidad, no solo por nonce;
- las migraciones son aditivas, bloqueadas, journalizadas y reanudables.

## 4. Fase 1 — Hardening inmediato

Se cerraron las entradas inseguras detectadas en la auditoría:

- se eliminó el fallback de la clave de Google Maps de navegador en `RouteDistance` y `ServiceAreaPolicy`; el backend requiere una clave server-side separada;
- se eliminó la distancia inventada de 50 km: un fallo de Maps bloquea la cotización y nunca fabrica un precio;
- se retiraron los hooks públicos `wptb_calculate_price`; el contrato público queda en la cotización autoritativa;
- el retorno Redsys `ko` exige el mismo token HMAC ligado al pedido que `ok`;
- `RequestRateLimiter` limita el endpoint de quote antes de llamadas externas;
- `PathGuard` impide borrar/restaurar backups fuera de la carpeta permitida;
- se añadieron casos de regresión de clave Maps, distancia fallida, rate limit, path traversal y token manipulado.

Archivos centrales: `app/Core/Settings.php`, `app/Security/RequestRateLimiter.php`, `app/Security/PathGuard.php`, `app/Booking/RouteDistance.php`, `app/Booking/ServiceAreaPolicy.php`, `app/Payments/Redsys/Gateway.php` y `tests/test-hardening-phase1.php`.

## 5. Fase 2 — Outbox genérico

La entrega remota quedó desacoplada del callback bancario:

- nueva tabla `mt_outbox` con `event_key` único, payload, estado, intentos, disponibilidad, lock y errores;
- worker cron con claim atómico, recuperación de locks antiguos, backoff exponencial, máximo de intentos y estado terminal `failed`;
- eventos por canal para correo cliente, correo admin, correo hotel, WhatsApp y analítica;
- claves idempotentes por reserva, evento y canal para que un IPN duplicado no duplique envíos;
- `BookingEvents` persiste la intención después de la transición DB;
- el IPN responde después de DB + outbox, antes de cualquier red o SMTP;
- el reenvío manual de email no vuelve a disparar WhatsApp;
- `PurchaseOutbox` se adapta al transporte genérico manteniendo compatibilidad.

Archivos centrales: `app/Core/Outbox.php`, `app/Core/OutboxHandler.php`, `app/Booking/BookingEvents.php`, `app/Notifications/NotificationService.php` y `tests/test-outbox.php`.

## 6. Fase 3 — Booking drafts e idempotencia

La PII deja de persistirse en almacenamiento web durante el salto a pago:

- nueva tabla `mt_booking_drafts` con TTL de dos horas;
- token aleatorio opaco de 256 bits; en DB se guarda solo SHA-256;
- limpieza cron limitada de drafts expirados;
- el browser conserva únicamente `draft_token` y recupera un resumen permitido en memoria;
- `wptb_initiate_redsys` recalcula y valida nuevamente el draft;
- `payment_idempotency_key` único garantiza una sola reserva ante doble clic, retry o concurrencia;
- una repetición válida devuelve la misma reserva y el mismo formulario Redsys.

Archivos centrales: `app/Booking/BookingDraftService.php`, `app/Legacy/WPTB/assets/js/booking-app.js`, `app/Legacy/WPTB/assets/js/redsys-payment.js` y `tests/test-booking-drafts.php`.

## 7. Fase 4 — Cotización de vehículos en servidor

La lista de vehículos dejó de exponer fórmulas y coeficientes:

- `RouteContext` valida y resuelve la ruta una vez por solicitud;
- `QuoteService` cotiza todos los vehículos activos con el mismo contexto;
- el payload público entrega precio final y moneda, no mínimos ni tarifas por km/hora;
- `booking-app.js` y `transfers-search.js` renderizan el precio del servidor sin recalcularlo;
- `VehicleCapacityPolicy` unifica pasajeros, maletas grandes y equipaje de mano para web, Redsys, draft y WooCommerce;
- el retorno exacto de round-trip se vuelve a cotizar antes de crear el draft;
- se reducen llamadas duplicadas a Maps al no resolver una ruta por vehículo.

Archivos centrales: `app/Booking/RouteContext.php`, `app/Booking/QuoteService.php`, `app/Booking/VehicleCapacityPolicy.php` y `tests/test-server-vehicle-quotes.php`.

## 8. Fase 5 — Dinero entero en céntimos

Los bordes financieros ya no dependen de aritmética binaria de `float`:

- nuevo value object `MeTransfers\Pricing\Money`;
- columna aditiva `price_cents BIGINT UNSIGNED`;
- backfill idempotente con aritmética `DECIMAL` en MySQL/MariaDB;
- dual-write temporal de `price_cents` y `price` para lectores legacy;
- `Money::fromBooking()` prefiere céntimos y admite filas antiguas;
- Redsys genera y valida directamente el entero guardado;
- Hotel QR y WooCommerce legacy escriben ambas representaciones;
- GA4, correo y WhatsApp convierten/formatean solo en el borde de salida.

Archivos centrales: `app/Pricing/Money.php`, `app/Pricing/Calculator.php`, `app/Core/Schema.php`, adaptadores legacy y `tests/test-money.php`.

## 9. Fase 6 — Recibo autoritativo

La confirmación y el PDF ya no confían en datos del browser:

- `ReceiptService` valida referencia, HMAC, existencia, estado financiero y estado operativo;
- el DTO se reconstruye desde `wptb_bookings`, vehículo y `price_cents`;
- `ReceiptController` sirve HTML imprimible propio y 404 para solicitudes inválidas;
- se retiraron jsPDF/CDN, el logo hardcodeado y `window.lastBookingData` como autoridad;
- cabeceras `no-store`/no-cache, `noindex`, `no-referrer`, `nosniff` y CSP propia;
- se mantienen las URLs de confirmación y sus prefijos de idioma;
- `payment_result` es solo una pista visual: nunca cambia el estado pagado de DB.

Archivos centrales: `app/Booking/ReceiptService.php`, `app/Booking/ReceiptController.php`, `app/Legacy/WPTB/templates/receipt.php`, `assets/js/receipt.js`, `assets/css/receipt.css` y `tests/test-authoritative-receipt.php`.

## 10. Fase 7 — Seguridad administrativa

Se aplicó mínimo privilegio y trazabilidad:

- capacidades separadas para reservas, flota, hoteles, estadísticas, exportaciones, integraciones y notificaciones;
- rol `metransfers_operator` sin `manage_options` ni acceso a secretos/integraciones;
- `check_hoteles` queda limitado a sus propios hoteles;
- menús, CPT, AJAX y `admin-post` comprueban capacidad y nonce;
- reenvíos de email y WhatsApp son acciones independientes;
- WhatsApp usa `Settings`/constantes; la API key es write-only y `autoload=no`;
- tokens de hotel aleatorios, inmutables y enmascarados; no aparecen en exports;
- exportaciones con capacidad, HMAC, rango máximo de 366 días, `no-store` y auditoría;
- nueva tabla `mt_admin_audit` con contexto operacional saneado y sin PII/secretos.

Archivos centrales: `app/Admin/Capabilities.php`, `app/Admin/AuditLog.php`, `app/Core/Settings.php`, administradores legacy y `tests/test-admin-security.php`.

## 11. Fase 8 — Migraciones discretas y reanudables

El activador monolítico fue reemplazado por una orquestación comprobable:

- advisory lock MySQL/MariaDB con `GET_LOCK` y liberación en `finally`;
- nueva tabla `mt_schema_migrations` con estados `running`, `succeeded` y `failed`;
- cinco pasos estables para core, eventos, flota, backfill de céntimos y floor de IDs;
- un fallo impide avanzar la versión global y el siguiente request reanuda desde el paso fallido;
- `Schema`, `DataMigrations` y `Seeds` tienen responsabilidades separadas;
- `WPTB_Activator` queda como fachada compatible sin SQL propio;
- no se eliminan columnas, tablas, reservas, IDs, slugs ni shortcodes.

Archivos centrales: `app/Core/Migrations.php`, `app/Core/Schema.php`, `app/Core/DataMigrations.php`, `app/Core/Seeds.php` y `tests/test-migrations.php`.

## 12. Fase 9 — i18n y SEO modular

La fachada i18n de 842 líneas se dividió conservando sus funciones públicas:

- `Language`: detección, locale, atributo `lang` y URLs;
- `Router`: rewrite rules, rutas virtuales y `template_include` sin `exit` prematuro;
- `Translation`: lectura pública cache-only y pre-generación administrativa;
- `Switcher`: markup accesible y assets externos;
- `Seo`: canonical, Yoast, hreflang aprobado y `x-default` español;
- `Admin`: clave de traducción write-only, prueba y precarga auditadas;
- chino usa `zh-Hans`; idiomas no aprobados continúan en `noindex,follow`;
- la API de traducción recibe la clave en `X-Goog-Api-Key`, no en query string;
- se retiraron CSS/JS inline de la fachada y se corrigió el manejo de foco móvil/teclado.

Archivos centrales: `app/I18n/*.php`, `includes/i18n.php`, `assets/css/i18n-*.css`, `assets/js/i18n-switcher.js` y `tests/test-i18n-routing.php`.

## 13. Fase 10 — Plataforma de calidad reproducible

Las verificaciones ya no dependen de herramientas globales ni de búsquedas de strings aisladas:

- `composer.lock` fija PHPUnit 11, PHPStan 2, WPCS/PHPCS, PHPCompatibilityWP y stubs WordPress;
- `package-lock.json` fija ESLint 10, Playwright y sus dependencias;
- PHPUnit prueba dinero, política de outbox, routing/SEO y retornos Redsys;
- PHPStan nivel 5 analiza el dominio moderno de mayor riesgo, sin baseline ni errores ignorados;
- WPCS/PHPCompatibility analiza 15 archivos modernos/sensibles con alcance incremental explícito;
- ESLint cubre los 11 scripts públicos/legacy, los tests y la configuración;
- Playwright prueba seis contratos reales de accesibilidad, scroll, compra confirmada, no-compra pendiente, idempotencia, limpieza de sesión y tracking allowlisted;
- el runner usa su canal Chrome preinstalado; local usa Chromium fijado por Playwright;
- WordPress real se instala con WP-CLI sobre MariaDB 11.4 para WordPress 6.8.6 y 7.0.2;
- el smoke real comprueba tema activo, bootstrap, CPT, shortcodes, capacidades, 11 tablas, cinco migraciones, cron/outbox y rewrite rules;
- Composer/npm audit, UTF-8, patrones de credenciales y Gitleaks forman parte del gate;
- actions de terceros están fijadas por SHA y el token del workflow es read-only.

La primera ejecución del smoke real descubrió y permitió corregir un `require_once` muerto a `mt-seo-importer.php`, que hacía fatal un WordPress limpio. La siguiente ejecución descubrió que el test consultaba `WP::$public_query_vars` antes de aplicar el filtro; se corrigió para verificar el contrato público real. ESLint encontró además dos llamadas inexistentes en los adaptadores Hotel/vehículos, reemplazadas por sus APIs correctas.

Archivos centrales: `composer.json`, `phpunit.xml.dist`, `phpstan.neon`, `phpcs.xml.dist`, `package.json`, `eslint.config.mjs`, `playwright.config.mjs`, `tests/Unit/`, `tests/Integration/`, `tests/e2e/` y `.github/workflows/php-lint.yml`.

## 14. Evidencia final automatizada

### Local

| Gate | Resultado |
|---|---:|
| Composer validate | Verde |
| Composer audit | 0 advisories |
| PHPUnit | 23/23 tests, 31 assertions |
| PHPStan nivel 5 | 0 errores |
| WPCS/PHPCompatibility | 15/15 archivos |
| Regresión legacy | 16/16 scripts |
| PHP syntax | 142 archivos |
| npm audit | 0 vulnerabilidades |
| ESLint | Verde |
| Playwright | 6/6 contratos |
| Node syntax | 15 JS/MJS |
| UTF-8/BOM/mojibake | 176 archivos limpios |
| Patrones de credenciales | Sin coincidencias |
| Gitleaks sobre cada diff | Sin leaks |
| `git diff --check` | Verde |

### GitHub Actions

El workflow `Quality Gate` separa los fallos por dominio:

1. PHP, dependencias, PHPUnit, PHPStan, WPCS, sintaxis y regresión legacy.
2. JavaScript, npm audit, ESLint y Playwright en Chrome.
3. WordPress 6.8.6 + MariaDB 11.4.
4. WordPress 7.0.2 + MariaDB 11.4.
5. Gitleaks del árbol actual o del rango del PR.

La ejecución de referencia de la fase 10 es [32304427056](https://github.com/merchandev/metransfers.es/actions/runs/32304427056).

## 15. Compatibilidad preservada

- Slugs españoles, prefijos de idioma y rutas públicas existentes.
- Shortcodes de booking, selección, detalles, checkout y compatibilidad Stripe.
- WooCommerce legacy como adaptador de transición.
- Hotel QR, roles de hotel y asignaciones existentes.
- Tabla `wptb_bookings`, IDs y columna decimal `price` mediante cambios aditivos.
- Redsys Test/Sandbox; ningún cambio fuerza Live.
- Fachadas legacy para evitar romper llamadas existentes mientras la lógica crítica vive en `app/`.

### Estado de Stripe

No existe un gateway Stripe activo propio en el runtime integrado: el shim/endpoints incompletos y la plantilla checkout legacy fueron retirados. Quedan nombres de shortcode/opciones y comentarios de compatibilidad para instalaciones que deleguen Stripe en WooCommerce. No se encontró una clave Live en el árbol actual. Los hallazgos históricos documentados deben revisarse/rotarse si Stripe vuelve a activarse; no se reproducen valores en este informe.

## 16. Qué significa “verde” y qué no

El verde automatizado demuestra que el código arranca, respeta los contratos cubiertos y supera controles reproducibles. No demuestra por sí solo que los proveedores reales o el contenido editorial estén aprobados.

### Cerrado por código/CI

- cotización y capacidad server-side;
- ausencia de distancia inventada y clave Maps pública en backend;
- idempotencia del inicio de pago y de eventos por canal;
- IPN desacoplado de proveedores remotos;
- money en céntimos en el borde financiero;
- recibo desde DB pagada con HMAC;
- PII del draft fuera de `sessionStorage`;
- mínimo privilegio, masking, export y audit log;
- lock, journal y reanudación de migraciones;
- router/SEO/i18n modular;
- gates PHP, JS, navegador, WordPress real, dependencias y secretos.

### Gates externos u operativos pendientes

- [ ] Rotar y restringir Redsys, SMTP y Google Maps; revisar Stripe histórico si se reactiva.
- [ ] Configurar una clave Maps server-side restringida por IP/API y otra browser-side por referrer/API.
- [ ] Ejecutar Redsys Sandbox E2E: OK, KO, firma inválida, importe incorrecto, IPN duplicado/concurrente y ambos órdenes IPN/return.
- [ ] Verificar SMTP cliente/admin/hotel y WhatsApp real, una vez por canal y sin retrasar el ACK.
- [ ] Acordar con privacidad/CMP una señal explícita de consentimiento analítico vigente; la mera cookie `_ga` no debe tratarse como evidencia legal suficiente.
- [ ] Validar GA4 DebugView con consentimiento concedido y rechazado.
- [ ] Probar migraciones sobre una copia reciente y anonimizada de la DB de producción; medir backfill y journal.
- [ ] Desactivar ambos plugins originales en staging y probar one-way, round-trip, WooCommerce legacy y Hotel QR.
- [ ] Ejecutar revisión visual responsive, accesibilidad completa, Lighthouse y performance con Maps/proveedores reales.
- [ ] Revisar por humanos contenido SEO, 404, canonical, hreflang recíproco y sitemap antes de ampliar `MT_SEO_LANGS`.
- [ ] Probar backup restore y ensayo de rollback.
- [ ] Revisar política/logs del proveedor WhatsApp actual, que usa una URL GET; migrar a `Authorization` + POST JSON si el proveedor lo permite.

Las cuatro attestations del `ReleaseGate` solo deben definirse después de completar su evidencia real:

```text
MT_REDSYS_CREDENTIALS_ROTATED_AT
MT_SMTP_CREDENTIALS_ROTATED_AT
MT_MAPS_CREDENTIALS_ROTATED_AT
MT_REDSYS_SANDBOX_VERIFIED_AT
```

## 17. Procedimiento de merge y staging

1. Revisar y aprobar PR #3 contra `main`.
2. Integrar sucesivamente #4, #5, #6, #7, #8, #9, #10, #11 y #12, preservando el orden.
3. Confirmar que `Quality Gate` queda requerido en la protección de `main`.
4. Crear backup verificable y desplegar primero en staging.
5. Ejecutar migraciones y comprobar `mt_platform_db_version = 6.5.0`, 11 tablas y cinco pasos `succeeded`.
6. Mantener los plugins originales disponibles para rollback, pero desactivados durante la aceptación del tema integrado.
7. Completar la checklist externa anterior.
8. Habilitar Redsys Live únicamente cuando `ReleaseGate` esté satisfecho con evidencias reales.

## 18. Conclusión

Las diez fases del prompt maestro quedaron implementadas y publicadas como una cadena auditable de PR. El repositorio ahora tiene autoridad financiera server-side, idempotencia, outbox durable, privacidad mejorada del draft, money en céntimos, recibo verificable, administración de mínimo privilegio, migraciones reanudables, i18n modular y una plataforma de calidad reproducible con WordPress y navegador reales.

La formulación correcta del estado es:

> **Código de las fases 1–10 con gates automatizados verdes y listo para aceptación en staging. Producción operativa y Redsys Live continúan bloqueados hasta cerrar y documentar los gates externos.**
