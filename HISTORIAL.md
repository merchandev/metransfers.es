# Historial completo de MeTransfers

Este documento registra la evolución funcional y técnica del proyecto final. El detalle exacto de cada cambio, autor, fecha y relación entre commits puede consultarse con `git log --all`.

## Repositorios consolidados

| Origen histórico | Contenido integrado |
|---|---|
| `tema-metransfers` | Tema WordPress, diseño, contenido, SEO e internacionalización |
| `plugin-de-reservas-metrasnfers` | Motor de reservas, vehículos, Hotel QR e importación/exportación |
| `Traductor-MT` | Traducción SEO, caché, selector y procesamiento de HTML |
| `metransfers.es` | Plataforma modular, seguridad, pagos, calidad y producción |

La consolidación conserva autores, fechas, mensajes y SHA. Los commits `483d5c1`, `f130a22` y `1c2a6bc` conectaron los historiales y ramas previamente independientes sin sustituir el árbol moderno. La etiqueta `v1.0.0` también se preservó.

## Cronología

### Julio de 2026 — Fundación del tema

- Creación del tema MeTransfers y primeros componentes visuales.
- Saneamiento de seguridad, estabilidad, BOM, mojibake y mixed content.
- Páginas de rutas, destinos, servicios, tours, contenido legal y navegación.
- Schema JSON-LD, breadcrumbs, mejoras SEO y diseño premium.

Referencias: `f960ba2`, `5715d03`, `f43b6f5`, `c4a59b2`.

### Julio de 2026 — Traducción e internacionalización

- Evolución de GCT Translator SEO Edition a Traductor MT v5.
- Traducciones persistidas, caché, rutas por idioma y selector integrado.
- Traducción de HTML estático, atributo `lang` y errores AJAX detallados.
- Internacionalización nativa del tema y compatibilidad SEO/Yoast.

Referencias: `2007305`, `31d6f0e`, `13031fe`, `7126e83`, `f1259db`, `655a74c`.

### Julio–agosto de 2026 — Reservas y Hotel QR

- Buscador, selección de vehículos, precios, checkout y pagos.
- Administración de flota, reservas, hoteles y códigos QR.
- Importación/exportación de hoteles conservando tokens.
- Refactorización de estilos, JavaScript, vistas y compatibilidad operativa.

Referencias: `319f77b`, `38447a8`, `f6f3f67`, `aed2842`.

### Agosto de 2026 — Plataforma modular

- Importación controlada de módulos legacy en `app/Legacy`.
- Bootstrap PSR-4 y centralización de CPT, administración, settings y assets.
- Pricing, Redsys, base de datos y caché desacoplados progresivamente.
- Integración de reservas, flota, hoteles, traducción y SEO.

Referencias: `ef04a30`, `da9b584`, `c8aeec5`, `ef480d9`, `572bbab`, `c555e33`.

### Agosto de 2026 — Diez fases de endurecimiento

1. Endurecimiento de entradas públicas, permisos, nonces y rate limiting.
2. Outbox durable para operaciones posteriores al pago.
3. Borradores de reserva idempotentes.
4. Cotización de vehículos en servidor.
5. Dinero almacenado en céntimos enteros.
6. Recibos derivados de estado autoritativo.
7. Administración de mínimo privilegio y auditoría.
8. Migraciones discretas y reanudables.
9. Router, caché, SEO e i18n modularizados.
10. Plataforma reproducible de pruebas y calidad.

Referencias: `03c2987`, `60cd038`, `199c2a7`, `f89b691`, `4cb8d71`, `5ec43d1`, `2941110`, `bc3df53`, `8d0d8ba`, `8556083`.

El detalle está en [docs/integration/RELEASE-CANDIDATE-10-10-CHANGES.md](docs/integration/RELEASE-CANDIDATE-10-10-CHANGES.md).

### Agosto de 2026 — Producción y regresiones

- Integración de las diez fases y reparación del flujo de reservas.
- Correcciones de Maps, codificación, estilos y contraste del booking.
- Auditoría SEO, sincronización V2 y correcciones de PortAventura.
- Cierre de PHPStan, PHPCS, Playwright y smoke tests de WordPress.

Referencias: `6d1ad71`, `be10884`, `90f5b19`, `92cf414`, `d047faa`, `1d280c8`.

### Septiembre de 2026 — Portal de Hoteles

- Acceso privado mediante la cuenta y contraseña reales de WordPress.
- Aislamiento de datos y dashboard independiente para cada hotel.
- Administración de responsables y asignación a uno o varios hoteles.
- Reservas, clientes, estadísticas, perfil e importación desde Excel.
- Nueva reserva con Google Places, ida/vuelta, capacidad y vehículos disponibles.
- Endpoint autenticado de cotización y recotización obligatoria antes de guardar.
- Precio, distancia, duración y vehículo persistidos desde la respuesta del servidor.
- Logo, navegación responsive y secciones operativas sin placeholders.
- Separación del escritorio administrativo en Reservas y Hoteles, conservando los slugs históricos.
- Nueva administración central de usuarios y accesos por hotel.
- Bloqueo reversible del Portal de Hoteles mediante `mt_hotel_access_blocked`, con nonce y auditoría.
- Cierre de la contradicción heredada que volvía a conceder permisos administrativos al rol `check_hoteles`.
- Reparación automática de relaciones históricas entre reservas y hoteles mediante `hotel_token`.
- Importación idempotente: las referencias coincidentes se actualizan y los archivos globales se distribuyen por Token Hotel.
- Recuperación de precio, distancia y vehículo en listados administrativos, más vista agregada para el supervisor global.
- Cierre definitivo de seguridad y aislamiento del portal (Roles `mt_hotel_access_all`, `mt_hotel_import_bookings`, Supervisor).
- Correcciones de Auditoría SEO: Redirección 301 de `/destinos/` a `/rutas/` y soporte multi-idioma nativo para metadatos Yoast SEO en portada.
- Enrutamiento forzado de formularios web (leads) hacia `info@metransfers.es` ignorando el correo de administración de WordPress.

### 8 de septiembre de 2026 — Actualización destacada: SEO, integraciones y herramientas

- Política central de indexabilidad para robots, sitemaps, archivo de rutas e idiomas. La ausencia histórica de `_mt_seo_ready` no provoca una desindexación masiva; las exclusiones explícitas siguen respetándose.
- Redirecciones legacy 301 condicionadas a destinos publicados, indexables y canónicos. Las rutas de destino requieren aprobación SEO explícita; se conservan idioma y parámetros y se evitan cadenas y bucles.
- Suspensión de los aliases de Costa Brava sin equivalentes válidos y retirada de las respuestas 410 no revisadas. Una URL inexistente conserva su 404; no se restaura contenido eliminado automáticamente.
- Integración de WordPress y Yoast para canonical, robots, sitemaps, títulos y descripciones, respetando los metadatos editoriales. Normalización de enlaces internos únicamente hacia destinos válidos.
- Idiomas SEO ES y EN con aprobación individual de variantes antes de anunciarlas en hreflang. La aprobación queda invalidada cuando cambian el contenido, los metadatos o las plantillas supervisadas.
- Desactivación de migraciones automáticas y eliminación de modificaciones artificiales de puntuaciones Yoast.
- Herramienta WP-CLI `tools/seo-approve-routes.php` con simulación por defecto, aprobación editorial individual, comprobación del estado original y copia de seguridad antes de aplicar cambios.
- Borradores de Salou, Andorra y Cadaqués en `docs/seo-priority-routes.json`, más checklist de despliegue y rollback en `docs/seo-deployment-checklist.md`.
- Ampliación del Quality Gate con pruebas HTTP reales sobre WordPress 6.8.6 y 7.0.2, tanto con sitemap nativo como con Yoast 26.9. Se verifican redirecciones, canonical, robots, sitemaps y reciprocidad de hreflang.
- Validación del código: 69 pruebas unitarias y 372 aserciones, 11 pruebas Playwright y regresiones legacy. CI incluye ESLint, PHPCS, PHPStan y escaneo de secretos; PHPStan utiliza 2 GB y un único proceso para evitar agotar memoria.
- Corrección del punto de clic de la prueba del overlay del portal, sin modificar su comportamiento de producción.

Referencias de implementación: `0d22e95`, `c80ebad`, `b0280ff`, `868d254`. Integración: [PR #29](https://github.com/merchandev/metransfers.es/pull/29). El [Quality Gate del código](https://github.com/merchandev/metransfers.es/actions/runs/34221690225) terminó correctamente.

**Alcance y pendientes:** actualizar GitHub no acredita el despliegue del tema. La inspección de WordPress fue de solo lectura; los borradores mantienen `editorial_approved: false`. No se ha aplicado la migración editorial, aprobado idiomas en masa ni solicitado indexación a Google. Tras desplegar, deben purgarse las cachés y verificarse las URLs reales.

### 11 de septiembre de 2026 — Consolidación SEO post-deploy, rutas e idiomas

- Auditoría completa post-deploy mediante Google Search Console/GSC Wizard y WordPress MCP. Se confirmó que varias URLs históricas seguían devolviendo `200 + self-canonical`, mientras otras de alto valor (`/taxis-barcelona-cadaques/`, `/taxis-barcelona-salou/`, `/taxis-barcelona-vielha/`, entre otras) habían quedado en 404 pese a conservar señales e indexación histórica en Google.
- Diagnóstico del fallo principal: `UrlPolicy` aplicaba una segunda exigencia `_mt_seo_ready=1` distinta a la política de `Indexability`, por lo que una ruta podía ser válida e indexable y, al mismo tiempo, ser rechazada como destino de una redirección 301. Se eliminó esa contradicción y `Indexability` quedó como fuente única de verdad.
- `Indexability` ahora considera heredadas las rutas publicadas sin `_mt_seo_ready`; únicamente el valor explícito `0` bloquea una ruta. Los aliases legacy y toda la antigua familia `/destinos/` quedan fuera del sitemap y de la indexación canónica.
- `LegacyUrlMap` se convirtió en un ledger de consolidación por patrones: absorbe `destinos/*`, `taxis-barcelona-*`, `traslados-barcelona-*`, `*-taxis` y `*-traslados` para destinos conocidos. Se añadieron aliases fijos de aeropuerto, puerto, chófer, corporativo, FAQ, privacidad, bodas/eventos y noticias para impedir que WordPress aplique redirecciones de old-slug equivocadas.
- Se añadió `RouteBootstrap`, migración idempotente y no destructiva que crea únicamente rutas canónicas faltantes para destinos históricos con demanda o páginas legacy publicadas. Nunca sobrescribe rutas existentes. Entre ellas: PortAventura, Costa Brava, Perpignan, Granollers, Mataró, Badalona, L'Hospitalet, Madrid, Vielha, Peñíscola, Delta del Ebro, Besalú, Begur, Morella, Altea, Valderrobres, Alquézar, Collioure y Carcassonne.
- Las nuevas rutas bootstrap se publican bajo `/rutas/barcelona-{destino}/`, reciben metadatos de origen/destino, H1, title, description y `_mt_seo_ready=1`. La migración ejecuta un único `flush_rewrite_rules(false)` cuando crea rutas y queda bloqueada por versión después de completarse.
- Los aliases traducidos no aprobados ya no terminan en 404: si la variante EN-US no está aprobada, el alias se consolida al canónico español equivalente. Los idiomas retirados (`FR`, `DE`, `IT`, `PT`, `CA`, `RU`, `ZH`, `JA`, `AR`) se reconocen exclusivamente para hacer 301 a su equivalente español.
- La oferta pública de idiomas queda reducida a `ES` y `EN`. Español permanece como idioma principal sin prefijo; English usa locale `en_US` y atributo `lang="en-US"`. Se eliminó la redirección automática por cookie que podía provocar bucles entre `/` y `/en/`.
- Se añadió protección frente a `redirect_canonical()` para URLs activas `/en/.../`, evitando que WordPress elimine el prefijo o provoque `ERR_TOO_MANY_REDIRECTS`. Las variantes inglesas continúan con `noindex` hasta que su contenido concreto sea revisado/aprobado; no se publican hreflang hacia traducciones parciales.
- Los hreflang aprobados pasan a usar `es-ES` y `en-US`, con `x-default` apuntando al español. Los hubs Home, Blog y Rutas permanecen ES-only hasta completar revisión editorial EN-US.
- Se normalizaron title y meta description de las páginas principales para evitar snippets de 90–129 caracteres generados por títulos históricos de WordPress/Yoast. Se añadieron copies específicos ES y EN-US para Home, Traslados Privados, Aeropuerto, Puerto, Chófer por horas, Corporativo, Grupos, FAQ, Sants, Hoteles y Flota.
- El pre-generador de traducciones dejó de enviar bloques fijos de 100 textos. Ahora agrupa por cantidad de elementos y caracteres, usa lotes conservadores, aumenta el timeout y divide/reintenta automáticamente lotes fallidos o respuestas incompletas. Esto corrige el estancamiento observado cuando el catálogo quedaba parcialmente traducido.
- Se añadieron pruebas unitarias específicas para el ledger legacy, rutas bootstrap, retirada de idiomas antiguos, enrutamiento ES/EN-US, hreflang regional y preservación de 404 para URLs realmente desconocidas.
- La versión de plataforma se incrementó a `6.9.3`. Las modificaciones se limitaron a SEO, rutas e internacionalización; no se alteraron pricing, Redsys, checkout, Portal de Hoteles ni la lógica de reservas.

**Validación requerida tras despliegue:** purgar cachés, comprobar que OLD devuelve 301 en un salto, que NEW devuelve 200/self-canonical, que los aliases desaparecen del sitemap y que una URL inventada continúa devolviendo 404. Solo después debe solicitarse recrawl/indexación de las URLs canónicas en Search Console.

### 11 de septiembre de 2026 — Endurecimiento SEO técnico posterior al PR #34

- Auditoría live posterior a la instalación del PR #34 confirmó que las familias legacy prioritarias ya consolidan por `301` en un salto hacia `/rutas/`, y que los destinos finales responden `200`, son indexables y mantienen canonical propio. También se validaron los aliases de aeropuerto, puerto, chófer, corporativo y FAQ, los idiomas retirados hacia español y la conservación de `404` reales para URLs desconocidas.
- Se corrigieron directamente en WordPress los títulos SEO y meta descriptions de las rutas bootstrap prioritarias y del catálogo ampliado, sustituyendo títulos heredados de 70–99 caracteres y descripciones vacías por snippets específicos `Transfer Barcelona - {Destino} | MeTransfers` y descripciones compactas orientadas a traslado privado.
- Se detectó una duplicación real de `/rutas/barcelona-pineda-de-mar/` causada por dos ejecuciones concurrentes del bootstrap. El duplicado fue retirado y se mantuvo una única ruta publicada.
- `RouteBootstrap` se elevó a una revisión interna nueva para reparar metadata faltante en rutas ya existentes, no solamente crear posts nuevos. La reparación respeta un `_mt_seo_ready=0` explícito y no reactiva contenido bloqueado editorialmente.
- Se añadió un lock transitorio alrededor del bootstrap para evitar condiciones de carrera durante despliegues o primeras peticiones concurrentes, reduciendo el riesgo de generar dos CPT con el mismo slug.
- Las páginas EN-US que siguen pendientes de aprobación editorial conservan `noindex,follow`, pero ahora reciben canonical propio en inglés. No se anuncian hreflang hasta que la variante concreta esté aprobada, manteniendo separadas la accesibilidad pública y la elegibilidad para indexación.
- Search Console confirmó que varias familias históricas problemáticas empezaron a registrar impresiones durante 2026: `/destinos/` desde mayo y varias variantes `taxis-barcelona-*`, `*-taxis` y `*-traslados` entre julio y agosto. Esto documenta que parte de la deuda SEO se generó o reactivó en migraciones recientes, no únicamente en etapas antiguas del dominio.
- Se mantiene como incidencia separada la incompatibilidad observada en una rutina externa de invalidación de sitemap de Yoast (`WPSEO_Sitemaps_Router::invalidate_sitemap()`), que puede lanzar excepción después de ciertas operaciones administrativas aunque la mutación principal sí se haya ejecutado. No se considera cerrada hasta corregir o aislar esa llamada.

Referencias de esta fase: `635a7f4` (`RouteBootstrap` y protección anti-duplicados), `b7fe7d9` (canonical EN-US protegido) y el commit de documentación que añade esta sección.

## Seguridad operativa

- Las claves Redsys, Maps, SMTP y webhooks no se almacenan en Git.
- Todo secreto que haya aparecido en un archivo local debe rotarse antes del despliegue.
- El precio del navegador nunca se acepta como precio final.
- El usuario del hotel utiliza exclusivamente `wp_users.user_pass`.
- Las importaciones se atribuyen al hotel seleccionado por el servidor.

## Consulta del historial

```bash
git log --graph --decorate --date=short --pretty=format:'%ad %h %an %s' main
git log --all --graph --decorate --oneline
git show <sha>
git shortlog -sne main
git rev-list --count main
```

## Política de conservación

- No reescribir ni forzar `main`.
- No duplicar commits para aumentar el contador de GitHub.
- Conservar autores, fechas, mensajes, tags y relaciones entre commits.
- Incorporar cambios mediante revisión y controles automáticos.

## 14 de septiembre de 2026 — Sincronización de cambios locales

Origen: instantánea local `1fb47798598ea9dc275282943d0983c3511371bd`, creada el 14/09/2026 a las 20:03:40 (America/Caracas). Comparación e integración sobre `317052e9b9f4298acec021ffb44d074e0e2ff08e` de `origin/main`. La fecha corresponde al guardado local; no existe un historial individual que permita fechar cada edición.

### Reservas y tarifas de hoteles

- Nuevo servicio `HotelFixedPricing`: flota activa compartida por administración y reserva pública, disponibilidad por capacidad, tarifas positivas por vehículo, compatibilidad con precios antiguos de sedán/van y descuentos calculados en céntimos.
- Actualización del controlador público de Hotel QR, validación del token, consulta de tarifas y creación de reservas.
- Renovación de la plantilla, estilos CSS y JavaScript del formulario de hoteles.
- Acciones AJAX dedicadas `mt_hotel_get_fixed_pricing` y `mt_hotel_create_booking`, conservando los nombres antiguos para formularios en caché.
- Carga independiente de recursos del formulario QR; el motor genérico y Google Maps dejan de cargarse para la fase hotel.
- Administración de hoteles conectada al servicio compartido de flota y reparación de la página de reservas cuando falta el shortcode, conservando su contenido.

### Materiales QR

- Nuevo controlador compartido `HQP_Materials` para descarga de QR PNG y hablador PDF, con autorización por hotel, nonce, validación de imágenes y registro de auditoría.
- Incorporación del fondo `HABLADOR - METRANSFERS.png`, fuentes Helvetica y Helvetica Bold y su licencia.
- Actualización de las instrucciones de materiales y delegación de las descargas desde la administración.

### Redsys

- Preferencia por `wptb_redsys_secret_key`, con compatibilidad y conservación de la clave antigua.
- Validación de comercio, terminal, moneda y entorno; separación entre configuración del pago y comprobaciones operativas de despliegue.
- Nuevo diagnóstico de configuración y avisos administrativos sin mostrar el secreto; registro de campos ausentes al iniciar el pago.

### Versiones y diferencias SEO conservadas de la copia local

- Plataforma: `6.9.3` → `6.9.4`. Hotel QR: `1.0.0` → `4.0.3`.
- El tema vuelve de `5.0.5` a `4.0.3`; el fallback de `functions.php` pasa de `4.3.12` a `4.0.3` y se elimina la definición anticipada `5.0.5` del bootstrap.
- El renderizado SEO vuelve a omitir canonical en solicitudes no indexables, incluidas las variantes traducidas antes contempladas.
- `RouteBootstrap` vuelve de `2026-09-11-v2` a `2026-09-11-v1`: se retiran el bloqueo de concurrencia y la reparación de metadatos de rutas existentes; se conservan los metadatos al crear nuevas rutas.
- Estas diferencias son parte de la instantánea solicitada, no mejoras verificadas; pueden revertir correcciones de SEO y versionado previamente publicadas.

### Integración Git

- Los archivos locales nuevos y modificados se integran en `main` conservando el historial remoto.
- Se recuperan documentación, pruebas, configuración de calidad y herramientas presentes en GitHub y ausentes en la instantánea local.
- La rama local `master` conserva la instantánea original como respaldo.

### Archivos de la instantánea modificados o añadidos
- `app/Core/Application.php`
- `app/Core/Settings.php`
- `app/HotelPortal/Services/HotelFixedPricing.php`
- `app/I18n/Seo.php`
- `app/Legacy/Hotel/admin/class-hqp-admin.php`
- `app/Legacy/Hotel/assets/HABLADOR - METRANSFERS.png`
- `app/Legacy/Hotel/assets/README.txt`
- `app/Legacy/Hotel/hotel-qr-plugin.php`
- `app/Legacy/Hotel/includes/class-hqp-loader.php`
- `app/Legacy/Hotel/includes/class-hqp-materials.php`
- `app/Legacy/Hotel/includes/font/LICENSE.txt`
- `app/Legacy/Hotel/includes/font/helvetica.php`
- `app/Legacy/Hotel/includes/font/helveticab.php`
- `app/Legacy/Hotel/public/class-hqp-public.php`
- `app/Legacy/Hotel/public/css/hqp-booking.css`
- `app/Legacy/Hotel/public/js/hqp-booking.js`
- `app/Legacy/Hotel/public/partials/hqp-booking-form.php`
- `app/Legacy/WPTB/includes/class-wptb-admin.php`
- `app/Legacy/WPTB/includes/class-wptb-public.php`
- `app/Payments/Redsys/Gateway.php`
- `app/SEO/RouteBootstrap.php`
- `app/bootstrap.php`
- `functions.php`
- `style.css`

### Validación de esta sincronización

- Sintaxis PHP: 224 archivos revisados sin errores.
- Sintaxis del JavaScript modificado: `node --check` correcto.
- `git diff --cached --check`: correcto.
- Suite legacy: 15 de 16 scripts correctos. `tests/test-redsys-gateway.php` falla con `live Redsys must be blocked without operational attestations`: la expectativa anterior contradice la separación entre configuración y preparación operativa introducida en esta instantánea. Se conserva el test y se documenta el fallo, sin ocultarlo.
- No se ejecutaron las suites dependientes de Composer, ESLint, Playwright ni la integración con WordPress en esta sesión. No se verificaron pagos reales ni el despliegue del sitio.

## 14 de septiembre de 2026 — Corrección del Quality Gate

- Corregida la regresión de Hotel QR que permitía guardar reservas con distancia cero: si el proveedor falla o devuelve una distancia no positiva, se responde con `route_distance_unavailable` antes de persistir la reserva. Las tarifas siguen siendo fijas; la disponibilidad del cálculo de ruta vuelve a ser necesaria para reservar.
- Actualizada la prueba de Redsys para verificar que una configuración válida permite pagos mientras `is_live_ready()` informa de las comprobaciones operativas pendientes, y que estas se reconocen al completarse.
- Ajustados `HotelFixedPricing` y `Gateway` a WordPress Coding Standards; consulta de flota mediante `$wpdb->prepare()` y placeholder de identificador `%i`.
- Validación local: 97 pruebas PHPUnit (655 aserciones), PHPStan, WPCS y 16 scripts legacy correctos. PHPUnit mantiene un aviso de deprecación preexistente.
- Aclaración: `php-lint` sí existe en el workflow y agrega los resultados del Quality Gate; su fallo anterior era consecuencia de la prueba PHP fallida.
