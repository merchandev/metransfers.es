# Historial completo de MeTransfers

Este documento registra la evolución funcional y técnica del proyecto final, **de más reciente a más antiguo**. El detalle exacto de cada cambio, autor, fecha y relación entre commits puede consultarse con `git log --all` (ver [Consulta del historial](#consulta-del-historial) al final).

## Repositorios consolidados

| Origen histórico | Contenido integrado |
|---|---|
| `tema-metransfers` | Tema WordPress, diseño, contenido, SEO e internacionalización |
| `plugin-de-reservas-metrasnfers` | Motor de reservas, vehículos, Hotel QR e importación/exportación |
| `Traductor-MT` | Traducción SEO, caché, selector y procesamiento de HTML |
| `metransfers.es` | Plataforma modular, seguridad, pagos, calidad y producción |

La consolidación conserva autores, fechas, mensajes y SHA. Los commits `483d5c1`, `f130a22` y `1c2a6bc` conectaron los historiales y ramas previamente independientes sin sustituir el árbol moderno. La etiqueta `v1.0.0` también se preservó.

## Cronología

### 21 de septiembre de 2026 — Auditoría de indexación, seguridad y reservas (6 rondas)

Sesión larga a partir de un reporte de Google Search Console del usuario (muchas páginas "No index" y sospecha de que los hoteles no mostraban rutas). Incluye tres auditorías externas adicionales que el usuario compartió durante la sesión; cada hallazgo de esas auditorías se verificó contra el código y el sitio en vivo antes de actuar — los datos de Search Console que citaban no eran verificables desde esta sesión y no se usaron para decidir prioridades.

#### Ronda 1 — Causa raíz del noindex masivo

- Diagnóstico en vivo (sin credenciales de wp-admin) contra `metransfers.es`: robots meta, canonical, hreflang, `sitemap_index.xml`, `ruta-sitemap.xml`, `page-sitemap.xml` y la REST API pública (`/wp-json/wp/v2/ruta`, `/wp-json/wp/v2/pages`) para contrastar el conteo real de contenido publicado (97 rutas, 104 páginas) contra lo que aparece en el sitemap (26 páginas) y contra la captura de Search Console del usuario (227 "Excluida por etiqueta noindex").
- Causa raíz identificada: el commit `868322d` (16 sep 2026, "activate all languages and unblock indexation") amplió `MT_ACTIVE_LANGS`/`MT_SEO_LANGS` de `['es','en']` a los 11 idiomas del catálogo, incluidos los 9 idiomas retirados (`ar,ca,de,fr,it,ja,pt,ru,zh`) que `Redirects::RETIRED_LANGUAGES` sigue redirigiendo con 301 al canónico español. Verificado en producción: `/en/rutas/barcelona-madrid/` publicaba `<link rel="alternate" hreflang="ar|ca|de|fr|it|ja|pt|ru|zh" …>` hacia nueve URLs que devuelven 301 de inmediato, y el selector de idioma (`Switcher::render()`) ofrecía esos mismos 9 idiomas muertos a los visitantes. Ese cambio no resolvió el noindex original (las páginas afectadas eran mayormente en español, no variantes de idioma) y sí introdujo ruido de hreflang/canonical que Search Console reporta como duplicados. Revertido `MT_ACTIVE_LANGS`/`MT_SEO_LANGS` a `['es','en']` en `includes/i18n.php`.
- El mismo commit `868322d` creó, vía `Seeds::getSeoContent()`, tres páginas nuevas (`/transfer-privado-a-madrid/`, `/transfer-privado-a-tarragona/`, `/ebro-delta/`) que duplican contenido ya cubierto por rutas canónicas existentes (`/rutas/barcelona-madrid/`, `/rutas/barcelona-tarragona/`, `/rutas/barcelona-delta-del-ebro/`, confirmadas en 200 en vivo) sin declarar redirección ni canonical cruzado, y sin coincidir con ningún patrón de `LegacyUrlMap::getTarget()`. Añadidas las tres a `LegacyUrlMap::$redirects` para que consoliden con 301 hacia su ruta canónica, igual que el resto de páginas legacy.
- `tests/Unit/SeoPolicyTest.php` tenía dos pruebas en rojo desde `868322d` (nunca actualizadas tras añadir un "grandfathering" de variantes EN): corregidas inicialmente asumiendo que ese grandfathering era el diseño vigente — **rectificado en la ronda 3** (ver abajo).
- **Pendiente de contenido, no de código:** `/en/`, `/en/rutas/` y `/en/blog/` siguen `noindex,follow` a propósito (`I18n\Seo::renderHead()`) porque sirven el título y el contenido en español bajo `lang="en-US"`; indexarlos tal cual generaría contenido duplicado/mal etiquetado.
- **Pendiente de despliegue:** `tools/build-release.ps1` confirma que este tema se despliega generando un zip manual (`git archive`) y subiéndolo a WordPress; no hay CI/CD que sincronice `main` con el servidor. Verificar en Search Console 1-2 semanas después del despliegue real, no del commit.
- **Rutas de hoteles, primer intento sin resolver:** no se pudo reproducir "los hoteles no muestran los viajes/rutas" sin acceso a `/wp-admin/` (no se usan credenciales por política de seguridad) ni al Portal de Hoteles autenticado.

Archivos modificados: `includes/i18n.php`, `app/SEO/LegacyUrlMap.php`, `tests/Unit/SeoPolicyTest.php`.

#### Ronda 1 (continuación) — CRÍTICO: el formulario de reservas principal está roto en producción

- Reproducido en vivo en `metransfers.es`: al buscar un traslado desde la portada (origen "Barcelona, España", destino "Aeropuerto de Barcelona", ambos elegidos directamente del autocompletado de Google, sin escribir texto libre), la página de selección de vehículo devuelve siempre **"No se pudo verificar el origen del traslado."** y no llega a mostrar ningún vehículo. Probado también con "Aeropuerto de Barcelona-El Prat" → "Hotel Arts Barcelona": mismo resultado. Al ser una dirección trivial e inequívoca la que falla, se descarta un problema de formato de texto: es un fallo sistémico, no puntual.
- Causa técnica localizada: `app/Legacy/WPTB/includes/class-wptb-public.php:531` (`ajax_get_vehicles`) llama a `QuoteService::createVehicleList()`, que internamente valida la ruta con `ServiceAreaPolicy::validateRoute()` (`app/Booking/ServiceAreaPolicy.php:11`). Esa validación geocodifica origen y destino contra la Geocoding API de Google usando una clave de servidor separada de la clave del widget de autocompletado (`Settings::requireServerMapsKey()`, opción `wptb_google_maps_server_api_key` / constante `MT_GOOGLE_MAPS_SERVER_API_KEY`). El widget de autocompletado del navegador funciona correctamente (usa la clave de cliente), pero la verificación de servidor falla siempre.
- No se pudo confirmar la causa exacta sin acceso a wp-admin ni a Google Cloud Console (no se usan credenciales por política de seguridad), pero la latencia observada de la llamada AJAX (~656 ms, compatible con un viaje de ida y vuelta real a la API de Google, no con un fallo instantáneo por clave vacía) apunta a que la clave de servidor existe pero la llamada a Google falla: candidatos más probables, de mayor a menor probabilidad, son (a) la API de Geocoding no está habilitada para esa clave/proyecto, (b) el proyecto de Google Cloud no tiene facturación activa, (c) la clave tiene restricciones de aplicación (IP o referrer) que bloquean las peticiones servidor-a-servidor, o (d) se ha superado la cuota.
- `ServiceAreaPolicy::geocode()` silenciaba por completo cualquier fallo devolviendo `valid => false` sin dejar rastro. Se añadió `error_log()` en los tres puntos de fallo (clave de servidor ausente, error de red, y respuesta de Google sin resultados incluyendo su `status`/`error_message`) para que el próximo fallo quede diagnosticado en el log de PHP. No cambia ningún comportamiento, solo añade visibilidad.
- **Acción requerida del propietario (fuera de este repositorio):** en wp-admin, revisar la clave de Maps del servidor; en Google Cloud Console, confirmar que la API de Geocoding está habilitada, que la facturación está activa, y que las restricciones de la clave permiten peticiones desde el servidor de WordPress.

Archivo modificado: `app/Booking/ServiceAreaPolicy.php` (solo logging, sin cambio de comportamiento).

#### Ronda 1 (cierre) — Flujo de reservas de hoteles: causa confirmada y bug adicional corregido

- Revisión completa del flujo de reservas (formulario principal, QR de hotel y Portal de Hoteles). Conclusión: **es el mismo problema que el formulario principal.** `HotelBookingController::quote()` (`/hoteles/reservas/nueva/`, "Nueva reserva" en el Portal de Hoteles) pasa por el mismo `QuoteService::createVehicleList()` → `ServiceAreaPolicy`/`RouteDistance`, que depende de la misma clave de Maps de servidor. El widget del código QR en recepción (`HotelFixedPricing::availableVehicles()`) es independiente y no usa esa clave, así que ese flujo concreto no debería estar afectado por lo mismo.
- **Bug adicional confirmado y corregido**, independiente de la clave de Maps: `app/Legacy/Hotel/admin/class-hqp-admin.php`, la tabla "Clientes que usaron este Código QR" (wp-admin → Hoteles → gestión de un hotel), tenía varias etiquetas de apertura PHP y textos corrompidos por una conversión de encoding rota al importar el plugin legacy (rastreado a `ef04a30`, "setup platform skeleton and import legacy modules"). El caso grave: la celda de precio tenía `<?php` sustituido por una secuencia de guiones largos + espacio duro, por lo que en vez de ejecutar `<?php echo esc_html( $booking->price ); ?>` esa cadena se imprimía literalmente y el precio nunca se mostraba en esa tabla — probablemente parte de lo que el usuario veía como "no muestra los viajes". Se corrigieron también las mismas cabeceras/etiquetas de estado corrompidas cosméticamente. Verificado que el patrón de corrupción no aparece en ningún otro archivo del repositorio.

Archivo modificado: `app/Legacy/Hotel/admin/class-hqp-admin.php`.

#### Ronda 2 — Corrección: el "grandfathering" de variantes EN de 868322d también se revierte

- Rectificación de la ronda 1: al ejecutar la suite completa de CI (`tests/Integration/seo-http-smoke.php`, WordPress real vía WP-CLI) se detectó `Error: Unreviewed English must not be advertised.` — una aserción escrita en el PR #34 (`5b51a59`, 11 sep 2026, anterior a `868322d`) que exige que una variante EN sin revisión explícita **no** se anuncie por hreflang. Esto contradice directamente el "grandfathering" que `868322d` añadió a `Variants::isApproved()` (tratar una variante sin metadato como aprobada por defecto).
- Con tres pruebas deliberadamente escritas en el PR #34 apuntando en la misma dirección (2 unitarias + esta de integración), la conclusión correcta es la opuesta a la de la ronda 1: el grandfathering de `868322d` fue, como la expansión de idiomas de la misma commit, un intento fallido de resolver el mismo problema de noindex, que además nunca podía arreglar el problema real (afecta solo a la aprobación de variantes EN, no a la indexación de las páginas en español que son la mayoría de las 227 excluidas). Revertido `Variants::isApproved()` a exigir un registro de aprobación explícito y válido (`translated_reviewed`, `http_status=200`, `canonical` y `source_hash` vigentes); sin registro, la variante ya no se considera aprobada. Revertidas también las 2 pruebas unitarias a su forma original y añadida `testApprovedEnglishVariantKeepsEnglishPrefix` para cubrir el camino de aprobación explícita que antes no tenía prueba propia.
- Con esto, los tres cambios de `868322d` en `includes/i18n.php` y `app/SEO/Variants.php` quedan completamente revertidos; solo se conserva de esa commit la creación de las 3 páginas nuevas (`/transfer-privado-a-madrid/`, etc.), ya consolidadas por redirección en la ronda 1.

Archivo modificado: `app/SEO/Variants.php`, `tests/Unit/SeoPolicyTest.php`.

#### Ronda 3 — Revisión de la primera auditoría externa

El usuario compartió una auditoría independiente (otra sesión de IA) sobre el estado del repo/CI/producción.

- **Confirmado y corregido — scripts de diagnóstico sin usar en el paquete de producción.** `app/check_pages.php`, `app/regenerate.php` e `includes/debug-yoast.php` no están referenciados por ningún otro archivo (huérfanos), carecen de guarda `ABSPATH` y no los excluía ni `.gitattributes` ni `tools/build-release.ps1`, por lo que se colaban en el ZIP de release. `regenerate.php` en particular podía recrear/mutar contenido (`wp_insert_post`, borra flags de sincronización) sin ninguna autenticación si se solicitaba la URL directamente. Eliminados los tres.
- **Confirmado y corregido — `BookingImporter` marcaba reservas como pagadas sin ninguna señal de pago real.** `app/HotelPortal/Services/BookingImporter.php:157` fijaba `payment_status = 'paid'` únicamente porque la columna "estado" del Excel decía "confirmada" — sin ninguna columna de pago independiente en la plantilla. Ese campo alimenta `ReceiptService` (generaría un recibo de pago real) y `OutboxHandler`/`BookingEvents` (dispararía notificaciones de "pago recibido") para una reserva que puede estar confirmada pero no cobrada. Cambiado a `payment_status = 'pending'` siempre en la importación; no existe actualmente ninguna pantalla para cambiar `payment_status` manualmente después de importar.
- **Confirmado y corregido — el formulario de leads no tenía límite de envíos.** `mt_ajax_save_lead()` solo validaba el nonce (válido ~12h, no es de un solo uso) y longitudes de campo; sin límite por IP, un bot con un nonce válido podía enviar cientos de leads. Añadido `RequestRateLimiter::consume('save_lead', 5, 10 min)`.
- **Confirmado con evidencia en vivo, no corregible desde el repo — el blog tiene títulos y extractos desacoplados a nivel de contenido, no de código.** Verificado en `/blog/`. Se revisó `index.php`: el bucle usa correctamente `get_the_title($post_id)`/`get_the_excerpt($post_id)`/`get_permalink($post_id)` con el mismo `$post_id` en cada iteración — no hay bug de plantilla ni de caché. Los extractos desplazados coinciden textualmente con funciones de auto-creación ya desactivadas en `functions.php`, lo que indica que el desajuste ocurrió al editar manualmente estos posts en WordPress. **Corregido en la ronda 6.**
- **Revisado y matizado — exposición histórica de credenciales.** `README.md` ya documenta una rotación fechada (`2026-08-19T12:00:00+02:00`) para Redsys/SMTP/Maps. No se puede confirmar desde el repo si `wp-config.php` en el servidor real tiene esas constantes con una fecha de rotación auténtica.
- **Revisado, sin cambio de código — `ME_TRANSFERS_ENABLE_MIGRATIONS = true`.** Cada migración detrás de este flag ya es idempotente, así que el riesgo real en producción es bajo. **Matizado más en la ronda 5.**
- **Marcado como seguimiento, no implementado — webhook saliente fuera del Outbox.** `NotificationService::dispatch()` dispara el webhook de terceros con `wp_remote_post(..., ['blocking' => false])`: si falla, se pierde en silencio. Integrarlo en el Outbox con reintentos/backoff es un cambio de arquitectura más grande que queda pendiente.

Archivos modificados: eliminados `app/check_pages.php`, `app/regenerate.php`, `includes/debug-yoast.php`; modificados `app/HotelPortal/Services/BookingImporter.php`, `functions.php`.

#### Ronda 4 — Segunda auditoría externa (con datos de Search Console no verificables)

- El usuario compartió una segunda auditoría externa, citando datos de Google Search Console que esta sesión **no puede verificar** — no hay conexión a Search Console disponible aquí. Esos números no se han usado para decidir prioridades.
- **Confirmado y corregido — CSRF administrativo en `mt-seeds-run`.** `app/Core/Application.php` ejecutaba `Seeds::run()` en `admin_init` con solo una comprobación `current_user_can('manage_options')`, sin nonce. Impacto real limitado porque `Seeds::run()`/`ensurePage()` son idempotentes, pero se corrigió igualmente: `wp_nonce_url()` en el enlace del aviso admin y `check_admin_referer('mt_seeds_run')` antes de ejecutar.
- **Confirmado y corregido — segunda capa de scripts huérfanos sin autenticación.** `app/list_all.php` y `app/list_pages.php` (los más graves: hacen `require wp-load.php` directamente y listan todos los slugs de páginas publicadas a cualquiera que pida la URL), `app/bootstrap-pages.php`, `app/fix-blog-page.php`, `app/update-sobre-nosotros.php`, `app/create-main-menu.php`, `includes/yoast-admin-hack.php` (forzaba visualmente el icono de legibilidad de Yoast a verde, independientemente del contenido real). Todos eliminados.
- **Confirmado y corregido — anomalía de nombres en `/rutas/` ("Barcelona - Almería → Barcelona - Almería").** Causa raíz en `archive-ruta.php`: cuando falta el meta `_mt_ruta_origen`/`_mt_ruta_destino`, el código partía el título por un guion largo (`explode('–', ...)`), pero `RouteBootstrap.php` construye los títulos con guion normal `' - '` — `explode()` no encontraba el separador y devolvía el título completo para origen y destino. Reproducido y confirmado con un script PHP aislado. Corregido con `mt_split_ruta_title()`, que prueba primero `' - '` y luego `'–'`.
- **Confirmado y corregido — regresión de versión del tema (5.0.5 → 4.0.3).** `git log -S` localizó el commit `d755b4e` ("guarda cambios locales de hoteles y Redsys del 14-09-2026", #42) como el que reintrodujo `4.0.3`, probablemente por partir de una copia local desactualizada. Corregido a `5.0.6`.
- **Verificado en vivo — `/destinos/` sí redirige en producción.** Confirma que la consolidación `/destinos/` → `/rutas/` está desplegada y funcionando.
- **No modificado — contenido homogéneo de las 41 rutas bootstrap.** Decisión de contenido/producto, no un bug.

Archivos modificados: eliminados `app/list_all.php`, `app/list_pages.php`, `app/bootstrap-pages.php`, `app/fix-blog-page.php`, `app/update-sobre-nosotros.php`, `app/create-main-menu.php`, `includes/yoast-admin-hack.php`; modificados `app/Core/Application.php`, `app/Core/Seeds.php`, `archive-ruta.php`, `functions.php`, `style.css`.

#### Ronda 5 — Tercera auditoría externa: migraciones y marcador de versión

- **Confirmado — no existe workflow de despliegue automático.** `.github/workflows/` solo contiene `php-lint.yml`. Un commit en `main` no implica que esté desplegado en `metransfers.es`. No se ha creado un workflow de CD por ser una decisión de infraestructura/credenciales que corresponde al usuario.
- **Confirmado y corregido — el "interruptor de seguridad" de migraciones no gobierna lo que su comentario decía.** `ME_TRANSFERS_ENABLE_MIGRATIONS` solo controla 4 migraciones legacy puntuales; la clase real `app/Core/Migrations.php` (con lock y journal) se registra en `after_switch_theme`/`admin_init`/`init` sin consultar esta constante en ningún momento. Corregido el comentario para que describa con precisión su alcance real.
- **Confirmado y corregido — `includes/auto-migration-v5.php` se cargaba entero en cada petición de wp-admin sin necesidad.** Su único `add_action` estaba comentado desde antes de hoy, y el archivo solo define funciones sin efectos secundarios de nivel superior. Se quitó el `require_once` incondicional y se eliminó el archivo.
- **Confirmado, sin corrección posible dentro del repo — la API key de WhatsApp (CallMeBot) viaja en la query string.** La API pública y gratuita de CallMeBot solo admite autenticación por parámetro `apikey` en la URL; no hay modo de cabecera `Authorization`. Mitigarlo implicaría cambiar de proveedor (decisión de producto). Aceptado como riesgo P2 inherente al proveedor actual.
- **Confirmado con evidencia en vivo — `Translation::translate()` cae en silencio al español cuando no hay traducción en caché**, sin ningún criterio de "traducción completa". Al comprobar en vivo la URL de ejemplo citada por la auditoría (`/en/traslados-aeropuerto/`) el comportamiento actual no reproduce el síntoma descrito: consolida (301) directamente hacia el canónico español, el camino de seguridad restaurado en la ronda 2. Un criterio real de "cobertura de traducción" para `Variants::isApproved()` queda como recomendación para una sesión dedicada.
- **Añadido — marcador de versión no sensible en el `<head>`.** `<!-- MeTransfers theme 5.0.6 -->` vía `wp_head`, para que una futura auditoría pueda confirmar qué versión está realmente desplegada. Extendido después (mismo día) con el hash del commit vía `export-subst` de git (`app/Core/build-info.php`), sin tocar `build-release.ps1`.
- **No modificado — homogeneidad de las 41 rutas bootstrap y el hecho de que `RouteBootstrap` nunca actualiza una ruta ya creada** (`if ($existing) { continue; }`). Mejorar la plantilla no tocará las páginas ya publicadas; hace falta una migración editorial explícita.

Archivos modificados: eliminado `includes/auto-migration-v5.php`; modificado `functions.php`. Añadido después: `app/Core/build-info.php` (commit hash vía `export-subst`).

#### Ronda 6 — Corrección masiva de URLs del blog (139 de 149 posts)

- El usuario exportó el blog completo desde wp-admin (Herramientas → Exportar → Entradas) como XML/WXR y lo compartió para una revisión completa, en vez de la muestra parcial vía REST API usada hasta ahora.
- **Hallazgo**: 139 de 149 posts (93%) tienen una URL (slug) que no coincide con su tema real. Causa raíz: en algún momento se reutilizaron entradas antiguas de blog (con URLs de contenido estacional/puntual: Semana Santa, Mobile World Congress, Sant Jordi, mascotas, delegaciones médicas...) sustituyendo su título y contenido por artículos nuevos de "Transfer/Traslado Privado Barcelona a {destino}", sin actualizar nunca la URL. De esos 139: 135 tienen título y contenido ya coherentes entre sí (solo hacía falta corregir la URL); 4 tenían también el contenido genérico, sin relación con el tema original que la URL seguía indicando.
- **Corregido**: `app/SEO/BlogSlugRedirects.php`, una redirección 301 autocontenida que solo actúa sobre un 404 real comprobando la opción `mt_blog_slug_redirects` — segura sin importar el orden entre el despliegue de este código y la ejecución del script. `tools/fix-blog-slugs.php`, script WP-CLI con el mismo patrón que `tools/seo-approve-routes.php` (simulación por defecto, comprobación de que cada post no cambió desde que se generó el manifest, comprobación de colisión de la URL nueva contra todo el sitio, backup antes de aplicar). `docs/blog-slugs-fix-2026-09-21.json`, el manifest con las 139 correcciones.
- Para los 4 posts con contenido genérico se escribió contenido nuevo y específico restaurando el tema original que la URL siempre indicó: tour privado de la magia de Gaudí, tour privado a Girona y el Museo Dalí, ruta Juego de Tronos por Girona, y cómo recuperar el IVA (Tax Free) en el Aeropuerto de Barcelona.
- Nota de proceso: 3 posts marcados inicialmente como "contenido también roto" resultaron ser falsos positivos de un filtro de palabras clave que ignoraba términos de 4 caracteres o menos (p. ej. "Reus", "VIP"); al revisar el contenido completo se confirmó que título y cuerpo ya eran coherentes entre sí.
- **Pendiente de ejecución por el usuario** (fuera de este repositorio, requiere WP-CLI): `wp eval-file tools/fix-blog-slugs.php docs/blog-slugs-fix-2026-09-21.json` para simular, y con `--apply` para aplicar de verdad. No se ha ejecutado nada contra la base de datos real desde esta sesión.

Archivos añadidos: `app/SEO/BlogSlugRedirects.php`, `tools/fix-blog-slugs.php`, `docs/blog-slugs-fix-2026-09-21.json`. Modificado: `app/Core/Application.php`.

#### Ronda 7 — CSVs reales de Search Console: causa raíz confirmada fuera del repositorio (CAPTCHA de SiteGround bloqueando a Googlebot)

- El usuario exportó y compartió los CSV reales de Search Console (informes "Excluida por noindex" —227 URLs—, "No encontrado (404)" —200 URLs— y "Duplicada: sin versión canónica del usuario" —21 URLs—, cada uno con su tabla y su gráfico de evolución diaria) junto con capturas de la vista general de indexación (653 sin indexar / 502 indexadas) y del detalle de "Página con redirección" (88 URLs, validación en **estado de error**, iniciada el 16/9, con errores detectados el 19/9).
- **Causa raíz confirmada en vivo, fuera del repositorio.** Dentro del CSV de "noindex" aparecen directamente URLs como `https://metransfers.es/.well-known/sgcaptcha/?r=/monte-tibidabo/` y `https://metransfers.es/.well-known/captcha/?y=powf:66.249.79.8:...` — `66.249.79.x` es un rango oficial de Googlebot. Se reprodujo en vivo: una petición a `https://metransfers.es/ar/tours-privados/` (una de las 88 URLs con redirección en error) devuelve **HTTP 202** y redirige de inmediato a `/.well-known/sgcaptcha/?r=%2Far%2Ftours-privados%2F&y=ipr:...` — la protección de SiteGround (con un reto de prueba-de-trabajo en JavaScript) intercepta la petición **antes** de que WordPress y su lógica de redirección 301 (`Redirects::processRedirects()`) lleguen a ejecutarse. Un navegador con JavaScript resuelve el reto de forma automática y transparente (por eso el usuario y esta sesión ven el sitio con normalidad), pero Googlebot generalmente no resuelve retos de prueba-de-trabajo diseñados específicamente para filtrar bots, así que se queda atrapado en la pantalla de reto indefinidamente.
- Esto explica en un único mecanismo, ajeno al código: (a) las URLs de captcha que aparecen literalmente en el CSV de noindex; (b) muy probablemente las 88 páginas "con redirección" en estado de error de validación (Google intenta revalidar la corrección, choca con el reto en vez de recibir el 301 limpio, y la validación falla); (c) posible contribución a las 21 "duplicada sin canónica", si el reto se dispara de forma inconsistente según la reputación/frecuencia de la IP.
- **No corregible desde este repositorio.** Se revisó `functions.php` y el resto del tema en busca de cualquier configuración propia relacionada (`captcha`, `SiteGround`, `.htaccess`): la única mención existente es la purga de caché de SiteGround tras una actualización, sin relación con el bloqueo de bots. El reto vive en la capa de seguridad de SiteGround (Site Tools / plugin "Security Optimizer"), fuera de WordPress y de este repositorio. Pendiente de que el propietario revise y ajuste esa configuración (permitir explícitamente a Googlebot verificado o reducir la agresividad del bloqueador de bots/IA).
- **Sin corregir por ahora, y con evidencia probablemente contaminada por lo anterior:** el CSV de 404 (200 URLs) es en su mayoría contenido antiguo ya retirado legítimamente y rutas técnicas (`/wp-content/themes/<tema-antiguo>/*`, `/wp-admin/*`) de versiones previas del tema — no se detectó ningún patrón de bug de enrutado nuevo. Las 21 URLs "duplicada sin canónica" son todas de idiomas retirados con último rastreo entre el 20 y el 27 de julio, **antes** de la reversión de idiomas de la ronda 1 — dato probablemente obsoleto, pendiente de que Google las vuelva a rastrear. Revisar ambos informes de nuevo una vez resuelto el bloqueo de SiteGround, porque hasta entonces cualquier rastreo de Google puede seguir chocando con el mismo reto.

Archivos modificados: ninguno (diagnóstico; la causa está fuera del repositorio).

#### Ronda 8 — Auditoría completa de las 10 categorías de Search Console + apreciación externa de posicionamiento

El usuario pidió revisar una por una las 10 categorías del informe de indexación de Search Console (653 páginas sin indexar en total) y valorar una apreciación de un tercero sobre el posicionamiento actual: *MeTransfers* en 1.ª posición, *"traslado privado Barcelona"* ~5.ª, pero *"transfer privado Barcelona"*, *"private transfer Barcelona"* y *"Barcelona airport transfer"* fuera del Top 10, con la versión inglesa débil frente a SIXT/Transfeero/Viator/Tripadvisor.

- **Confirmado en vivo (3 comprobaciones) — la mayoría del bucket "noindex" (227) es dato desactualizado, no un fallo actual.** Se verificaron en producción `/rutas/barcelona-calella/` (indexable, `index,follow`, no aparece afectada realmente), `/destinos/andorra/` (301 correcto a `/rutas/barcelona-andorra/`, que es indexable) y `/zh/destinos/cadaques/` (301 correcto a `/rutas/barcelona-cadaques/`). Las fechas de "último rastreo" de las 227 URLs no superan el 15 de septiembre — todas son anteriores a la reversión de idiomas de la ronda 1 (21 sep) y, además de eso, quedan atascadas porque cualquier reintento de Google choca con el CAPTCHA de SiteGround (ronda 7). Desglose por prefijo: 148 de idiomas retirados (pt 20, ja 20, de 20, ar 20, it 17, ca 17, ru 14, zh 12, fr 8), 24 bajo `/en/`, 17 rutas ES, 5 URLs del propio captcha, 25 feeds RSS (exclusión correcta y esperada), y el resto páginas/entradas antiguas ya cubiertas por `LegacyUrlMap`.
- **Confirmado y NUEVO — hallazgo directamente relacionado con la apreciación de posicionamiento: todo el contenido en inglés está permanentemente noindex por diseño, sin ningún mecanismo para aprobarlo.** Verificado en vivo: `/en/` (portada) es una traducción completa y profesional (1.407 palabras, título "Barcelona Airport Transfers & Private Drivers", H1 "Private transfers to and from Barcelona", meta description propia, sin rastro de contenido en español) — exactamente la página que debería competir por *"Barcelona airport transfer"* y *"private transfer Barcelona"*. Aun así sirve `robots: noindex, follow`. Causa: `Variants::isApproved()` (`app/SEO/Variants.php`) exige un metadato `_mt_seo_variant_en` con `translated_reviewed`, `http_status`, `canonical` y `source_hash` vigentes, y **ningún flujo del sitio escribe nunca ese metadato** — no es una decisión de calidad de contenido, es una pieza de administración que nunca se construyó. Esto explica por qué el inglés no compite: no es que rankee bajo, es que ninguna página en inglés es elegible para aparecer en absoluto.
  - **Corregido — falta la herramienta, no el criterio.** Añadido `tools/seo-approve-variant.php` (mismo patrón que `tools/seo-approve-routes.php`): WP-CLI, simulación por defecto, verifica en vivo que la URL de la variante responde 200, calcula la huella de contenido (`Variants::fingerprint()`) y exige `editorial_approved: true` explícito por fila para `--apply` — la revisión de que la traducción está lista para publicarse sigue siendo una decisión humana, esta sesión no la toma por su cuenta.
  - **Pendiente de ejecución por el usuario:** construir el manifest (mínimo: ID de la página de portada en inglés) y ejecutar `wp eval-file tools/seo-approve-variant.php manifest.json` (simulación) y luego `--apply` tras marcar `editorial_approved: true`. Repetir por cada URL en inglés que se quiera indexar.
- **Confirmado en vivo — desajuste de frase exacta en la portada ES entre el copy real y la palabra clave que el cliente rastrea.** La portada usa consistentemente "traslados privados" (8 apariciones en el cuerpo, título "Transfer Aeropuerto Barcelona y Traslados Privados", H1 "Traslados privados desde y hacia Barcelona"), pero la frase exacta *"transfer privado"* no aparece ni una sola vez en el cuerpo ni en el H1. Es una decisión de copy/marca, no un bug — se deja como recomendación, no se ha modificado ningún texto sin autorización explícita.
- **404 (200) — revisado sin corrección de código.** 13 son rutas técnicas (`/wp-content/`, `/wp-admin/`, `/wp-json/`), 24 son contenido antiguo bajo idiomas retirados, y el resto es contenido/consultas legacy ya esperadas (URLs `entrada.php?slug=`, tags, temas de WordPress antiguos). No se detectó ningún patrón de enrutado roto nuevo.
- **Categorías sin datos suficientes para auditar — pendiente de exportación por el usuario.** De las 10 categorías del resumen de Search Console solo se pudieron auditar con datos completos 5 (noindex, 404, redirección con error —parcial, 10 de 88 ejemplos—, duplicada sin canónica, alternativa con canónica correcta). Quedan sin ninguna URL de ejemplo: **"Rastreada: actualmente sin indexar" (89, decisión algorítmica de Google sobre calidad/relevancia — la más relevante para la queja de posicionamiento)**, **"Duplicada: Google ha elegido otra canónica" (9, también decisión algorítmica)**, error de servidor 5xx (2) y bloqueada por un problema 4xx (1). No se ha especulado sobre estas sin datos verificables, siguiendo el mismo criterio aplicado a las auditorías externas anteriores.

Archivos añadidos: `tools/seo-approve-variant.php`.

#### Ronda 9 — Configuración de idiomas: solo ES/EN como fuente de verdad exacta

El usuario pidió eliminar del tema todo rastro de idiomas que no sean español e inglés, con una configuración exacta que evite futuros problemas de traducción e indexación (en línea directa con la causa raíz de toda esta sesión: el commit `868322d` que activó 11 idiomas el 16 de septiembre).

- **Mapeado primero, antes de tocar nada:** localizadas todas las referencias a los 9 códigos retirados (`ar,ca,de,fr,it,ja,pt,ru,zh`) en el repositorio. Dos falsos positivos descartados sin tocar: `PTS_ALLOWED_COUNTRIES` en `transfers-search.js` (países de cobertura del servicio, no idiomas) y `'pt'` en `fpdf.php` (unidad tipográfica "points" de una librería de PDF de terceros, vendorizada).
- **Corregido — `MT_LANGS` (`includes/i18n.php`) reducido a únicamente `es` y `en`.** Antes documentaba los 11 idiomas (incluidos los 9 retirados) aunque solo 2 estaban activos; ahora es literalmente la única fuente de verdad de qué idiomas soporta el tema, sin ambigüedad. No rompe nada: el selector (`Switcher`), el hreflang (`Seo::renderHead`) y el enrutado (`Router`) ya solo consultaban `MT_ACTIVE_LANGS` (ya en `['es','en']` desde la ronda 1), no `MT_LANGS`; y la página de administración de traducciones (`Admin::render()`, wp-admin → Ajustes → Traducción MT) itera `MT_LANGS`, así que ahora tampoco puede ofrecer accidentalmente pre-generar traducciones a un idioma retirado.
- **Mantenido a propósito, con la razón documentada en el propio código — `Redirects::RETIRED_LANGUAGES`.** Es la única lista que debe conservar los 9 códigos: reconoce URLs antiguas bajo esos prefijos (ya indexadas o enlazadas desde fuera) y las consolida con 301 hacia el canónico español. Quitarla convertiría esas URLs en 404 nuevos, exactamente el tipo de daño que esta sesión lleva corrigiendo. Añadido un comentario explícito indicando que es la única lista a tocar para añadir o quitar un idioma retirado, con referencia cruzada desde `includes/i18n.php`.
- **Revisado y descartado — el mapa `zh => zh-Hans` en `I18n\Seo::hreflang()`.** Se eliminó en un primer intento por parecer un resto de idioma retirado (zh nunca estuvo en `Redirects::RETIRED_LANGUAGES` como tal, era uno de los 9); la suite de pruebas (`tests/test-i18n-routing.php`) lo detectó de inmediato: ese test usa `zh` deliberadamente como idioma de referencia genérico para verificar que el mecanismo de hreflang funciona con un tercer idioma cualquiera, no como una afirmación de que el chino esté soportado en producción. Revertido con un comentario explicando la distinción.
- **Verificado — nada más depende de `MT_LANGS` para reconocer prefijos retirados.** `Language::pathWithoutLanguage()` sí consulta `MT_LANGS`, pero solo se invoca sobre URLs internas ya generadas (menú, contenido, `Redirects::verifiedTarget()`) o sobre la petición en curso después de que `Redirects::processRedirects()` ya haya interceptado y redirigido cualquier prefijo retirado en `template_redirect` (prioridad 0); no hay ninguna ruta de código donde depender de `MT_LANGS` para un idioma retirado cambie el resultado observable.
- **Validado:** 98 pruebas PHPUnit (668 aserciones), phpcs limpio en todo el alcance de `phpcs.xml.dist`, y las dos pruebas standalone de i18n (`tests/test-i18n.php`, `tests/test-i18n-routing.php`) en verde tras el cambio.
- **Pendiente, fuera del repositorio:** puede quedar caché de traducción antigua en `wp_options` (`mt_tr_{idioma}_*`) para los 9 idiomas retirados, generada mientras estuvieron activos. No afecta a nada (esos idiomas ya no se sirven ni se consultan), es solo limpieza cosmética de la base de datos; no se ha construido una herramienta para esto al no ser un problema funcional.

Archivos modificados: `includes/i18n.php`, `app/SEO/Redirects.php` (solo comentarios).

#### Ronda 10 — Auditoría técnica completa contra las directrices de Google: 4 hallazgos, 1 corregido

El usuario pidió una pasada final para que no queden errores según los parámetros de Google. Usada la API pública de WordPress (`/wp-json/wp/v2/pages`) para inspeccionar contenido e IDs sin necesitar credenciales -- el conector MCP de WordPress se reintentó y sigue rechazando las credenciales, pendiente de reconexión por el usuario.

- **Confirmado y corregido — 4 cadenas de texto visibles sin traducir en `template-servicio.php`.** Plantilla compartida por las páginas de aeropuerto, puerto, chófer por horas, corporativo y grupos en inglés. A diferencia de absolutamente todo el resto del archivo (que envuelve cada cadena en `mt_translate()`), dos encabezados `<h2>` con su etiqueta (`¿Por qué elegir MeTransfers?` / `Todo lo que necesitas, incluido` y `El proceso` / `Así de sencillo es reservar`), la opción de select `No lo sé aún`, y la pluralización de "persona/personas" en el desplegable de pasajeros se quedaron como texto español sin envolver -- un descuido, no un problema de caché. Corregido envolviéndolas con `mt_translate()`, igual que el resto del archivo (los nombres de terminal como "Adossat A" se dejan sin traducir a propósito, son nombres propios).
- **Confirmado, no corregible desde el repositorio — `page-traslados-privados.php` y las 5 plantillas `page-taxis-*.php` sí envuelven el 100% de su texto en `mt_translate()`, pero la caché de traducción no tiene esas frases guardadas.** `mt_translate()` nunca llama a la API en vivo durante el renderizado normal (`Translation::translate()` solo lee `get_option()`); la caché solo se rellena al pulsar "Pre-generar catálogo completo" en wp-admin → Ajustes → Traducción MT, con la clave de Google Cloud API configurada. Ninguna herramienta WP-CLI puede sustituir esto sin debilitar la comprobación de seguridad (`is_admin() && current_user_can(...)`) que protege esa llamada, así que no se ha construido ninguna -- requiere esa acción manual del usuario (o que se escriba a mano el contenido en inglés de esas 6 plantillas, ofrecido como alternativa si se prefiere).
- **Confirmado, no corregible desde el repositorio — el email de la organización en los datos estructurados (schema.org) está mal escrito en todo el sitio: `info@metrasnfers.es` en vez de `info@metransfers.es`.** Verificado idéntico en dos páginas distintas (`/traslados-privados/`, `/contacto/`), confirmando que es un ajuste global (Yoast SEO → datos de la empresa / Local SEO), no contenido de página. Afecta a la ficha de Google/Knowledge Panel en todo el sitio. El footer del tema sí muestra el email correcto -- la discrepancia está solo en los datos estructurados.
- **Confirmado, no corregible desde el repositorio — `/cookies/` aparece en `page-sitemap.xml` aunque redirige (301) a `/politica-de-cookies/`.** Google desaconseja explícitamente listar en el sitemap URLs que redirigen. `LegacyUrlMap` no tiene ninguna entrada `cookies → politica-de-cookies`, así que la redirección no la gestiona el tema -- es casi con toda seguridad una regla del gestor de redirecciones de Yoast SEO Premium, invisible desde el repositorio. Revisar en wp-admin → Yoast SEO → Redirecciones, o despublicar la página huérfana `cookies` si no tiene ya contenido propio.
- **Verificado sin cambios — `robots.txt` y `sitemap_index.xml`.** Estructura estándar generada por Yoast, sin reglas `Disallow` problemáticas, 5 sub-sitemaps correctamente enlazados. `page-sitemap.xml` excluye correctamente todas las URLs `/en/` (noindex), confirmando que Yoast ya filtra el sitemap por la etiqueta robots como debe.

Archivo modificado: `template-servicio.php`.

---

### 14 de septiembre de 2026 — Corrección del Quality Gate

- Corregida la regresión de Hotel QR que permitía guardar reservas con distancia cero: si el proveedor falla o devuelve una distancia no positiva, se responde con `route_distance_unavailable` antes de persistir la reserva. Las tarifas siguen siendo fijas; la disponibilidad del cálculo de ruta vuelve a ser necesaria para reservar.
- Actualizada la prueba de Redsys para verificar que una configuración válida permite pagos mientras `is_live_ready()` informa de las comprobaciones operativas pendientes, y que estas se reconocen al completarse.
- Ajustados `HotelFixedPricing` y `Gateway` a WordPress Coding Standards; consulta de flota mediante `$wpdb->prepare()` y placeholder de identificador `%i`.
- Validación local: 97 pruebas PHPUnit (655 aserciones), PHPStan, WPCS y 16 scripts legacy correctos. PHPUnit mantiene un aviso de deprecación preexistente.
- Aclaración: `php-lint` sí existe en el workflow y agrega los resultados del Quality Gate; su fallo anterior era consecuencia de la prueba PHP fallida.

#### Sincronización de cambios locales

Origen: instantánea local `1fb47798598ea9dc275282943d0983c3511371bd`, creada el 14/09/2026 a las 20:03:40 (America/Caracas). Comparación e integración sobre `317052e9b9f4298acec021ffb44d074e0e2ff08e` de `origin/main`. La fecha corresponde al guardado local; no existe un historial individual que permita fechar cada edición.

**Reservas y tarifas de hoteles**

- Nuevo servicio `HotelFixedPricing`: flota activa compartida por administración y reserva pública, disponibilidad por capacidad, tarifas positivas por vehículo, compatibilidad con precios antiguos de sedán/van y descuentos calculados en céntimos.
- Actualización del controlador público de Hotel QR, validación del token, consulta de tarifas y creación de reservas.
- Renovación de la plantilla, estilos CSS y JavaScript del formulario de hoteles.
- Acciones AJAX dedicadas `mt_hotel_get_fixed_pricing` y `mt_hotel_create_booking`, conservando los nombres antiguos para formularios en caché.
- Carga independiente de recursos del formulario QR; el motor genérico y Google Maps dejan de cargarse para la fase hotel.
- Administración de hoteles conectada al servicio compartido de flota y reparación de la página de reservas cuando falta el shortcode, conservando su contenido.

**Materiales QR**

- Nuevo controlador compartido `HQP_Materials` para descarga de QR PNG y hablador PDF, con autorización por hotel, nonce, validación de imágenes y registro de auditoría.
- Incorporación del fondo `HABLADOR - METRANSFERS.png`, fuentes Helvetica y Helvetica Bold y su licencia.
- Actualización de las instrucciones de materiales y delegación de las descargas desde la administración.

**Redsys**

- Preferencia por `wptb_redsys_secret_key`, con compatibilidad y conservación de la clave antigua.
- Validación de comercio, terminal, moneda y entorno; separación entre configuración del pago y comprobaciones operativas de despliegue.
- Nuevo diagnóstico de configuración y avisos administrativos sin mostrar el secreto; registro de campos ausentes al iniciar el pago.

**Versiones y diferencias SEO conservadas de la copia local**

- Plataforma: `6.9.3` → `6.9.4`. Hotel QR: `1.0.0` → `4.0.3`.
- El tema vuelve de `5.0.5` a `4.0.3`; el fallback de `functions.php` pasa de `4.3.12` a `4.0.3` y se elimina la definición anticipada `5.0.5` del bootstrap. *(Esta regresión de versión se corrigió el 21 de septiembre, ver ronda 4 arriba.)*
- El renderizado SEO vuelve a omitir canonical en solicitudes no indexables, incluidas las variantes traducidas antes contempladas.
- `RouteBootstrap` vuelve de `2026-09-11-v2` a `2026-09-11-v1`: se retiran el bloqueo de concurrencia y la reparación de metadatos de rutas existentes; se conservan los metadatos al crear nuevas rutas.
- Estas diferencias son parte de la instantánea solicitada, no mejoras verificadas; pueden revertir correcciones de SEO y versionado previamente publicadas.

**Integración Git**

- Los archivos locales nuevos y modificados se integran en `main` conservando el historial remoto.
- Se recuperan documentación, pruebas, configuración de calidad y herramientas presentes en GitHub y ausentes en la instantánea local.
- La rama local `master` conserva la instantánea original como respaldo.

**Archivos de la instantánea modificados o añadidos**

`app/Core/Application.php`, `app/Core/Settings.php`, `app/HotelPortal/Services/HotelFixedPricing.php`, `app/I18n/Seo.php`, `app/Legacy/Hotel/admin/class-hqp-admin.php`, `app/Legacy/Hotel/assets/HABLADOR - METRANSFERS.png`, `app/Legacy/Hotel/assets/README.txt`, `app/Legacy/Hotel/hotel-qr-plugin.php`, `app/Legacy/Hotel/includes/class-hqp-loader.php`, `app/Legacy/Hotel/includes/class-hqp-materials.php`, `app/Legacy/Hotel/includes/font/LICENSE.txt`, `app/Legacy/Hotel/includes/font/helvetica.php`, `app/Legacy/Hotel/includes/font/helveticab.php`, `app/Legacy/Hotel/public/class-hqp-public.php`, `app/Legacy/Hotel/public/css/hqp-booking.css`, `app/Legacy/Hotel/public/js/hqp-booking.js`, `app/Legacy/Hotel/public/partials/hqp-booking-form.php`, `app/Legacy/WPTB/includes/class-wptb-admin.php`, `app/Legacy/WPTB/includes/class-wptb-public.php`, `app/Payments/Redsys/Gateway.php`, `app/SEO/RouteBootstrap.php`, `app/bootstrap.php`, `functions.php`, `style.css`.

**Validación de esta sincronización**

- Sintaxis PHP: 224 archivos revisados sin errores.
- Sintaxis del JavaScript modificado: `node --check` correcto.
- `git diff --cached --check`: correcto.
- Suite legacy: 15 de 16 scripts correctos. `tests/test-redsys-gateway.php` falla con `live Redsys must be blocked without operational attestations`: la expectativa anterior contradice la separación entre configuración y preparación operativa introducida en esta instantánea. Se conserva el test y se documenta el fallo, sin ocultarlo.
- No se ejecutaron las suites dependientes de Composer, ESLint, Playwright ni la integración con WordPress en esta sesión. No se verificaron pagos reales ni el despliegue del sitio.

---

### 11 de septiembre de 2026 — Endurecimiento SEO técnico posterior al PR #34

- Auditoría live posterior a la instalación del PR #34 confirmó que las familias legacy prioritarias ya consolidan por `301` en un salto hacia `/rutas/`, y que los destinos finales responden `200`, son indexables y mantienen canonical propio. También se validaron los aliases de aeropuerto, puerto, chófer, corporativo y FAQ, los idiomas retirados hacia español y la conservación de `404` reales para URLs desconocidas.
- Se corrigieron directamente en WordPress los títulos SEO y meta descriptions de las rutas bootstrap prioritarias y del catálogo ampliado, sustituyendo títulos heredados de 70–99 caracteres y descripciones vacías por snippets específicos `Transfer Barcelona - {Destino} | MeTransfers` y descripciones compactas orientadas a traslado privado.
- Se detectó una duplicación real de `/rutas/barcelona-pineda-de-mar/` causada por dos ejecuciones concurrentes del bootstrap. El duplicado fue retirado y se mantuvo una única ruta publicada.
- `RouteBootstrap` se elevó a una revisión interna nueva para reparar metadata faltante en rutas ya existentes, no solamente crear posts nuevos. La reparación respeta un `_mt_seo_ready=0` explícito y no reactiva contenido bloqueado editorialmente.
- Se añadió un lock transitorio alrededor del bootstrap para evitar condiciones de carrera durante despliegues o primeras peticiones concurrentes, reduciendo el riesgo de generar dos CPT con el mismo slug.
- Las páginas EN-US que siguen pendientes de aprobación editorial conservan `noindex,follow`, pero ahora reciben canonical propio en inglés. No se anuncian hreflang hasta que la variante concreta esté aprobada.
- Search Console confirmó que varias familias históricas problemáticas empezaron a registrar impresiones durante 2026: `/destinos/` desde mayo y varias variantes `taxis-barcelona-*`, `*-taxis` y `*-traslados` entre julio y agosto. Esto documenta que parte de la deuda SEO se generó o reactivó en migraciones recientes, no únicamente en etapas antiguas del dominio.
- Se mantiene como incidencia separada la incompatibilidad observada en una rutina externa de invalidación de sitemap de Yoast (`WPSEO_Sitemaps_Router::invalidate_sitemap()`), que puede lanzar excepción después de ciertas operaciones administrativas aunque la mutación principal sí se haya ejecutado. No se considera cerrada hasta corregir o aislar esa llamada.

Referencias de esta fase: `635a7f4` (`RouteBootstrap` y protección anti-duplicados), `b7fe7d9` (canonical EN-US protegido).

#### Consolidación SEO post-deploy, rutas e idiomas (mismo día, fase anterior)

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
- Se normalizaron title y meta description de las páginas principales para evitar snippets de 90–129 caracteres generados por títulos históricos de WordPress/Yoast.
- El pre-generador de traducciones dejó de enviar bloques fijos de 100 textos. Ahora agrupa por cantidad de elementos y caracteres, usa lotes conservadores, aumenta el timeout y divide/reintenta automáticamente lotes fallidos o respuestas incompletas.
- Se añadieron pruebas unitarias específicas para el ledger legacy, rutas bootstrap, retirada de idiomas antiguos, enrutamiento ES/EN-US, hreflang regional y preservación de 404 para URLs realmente desconocidas.
- La versión de plataforma se incrementó a `6.9.3`. Las modificaciones se limitaron a SEO, rutas e internacionalización; no se alteraron pricing, Redsys, checkout, Portal de Hoteles ni la lógica de reservas.

**Validación requerida tras despliegue:** purgar cachés, comprobar que OLD devuelve 301 en un salto, que NEW devuelve 200/self-canonical, que los aliases desaparecen del sitemap y que una URL inventada continúa devolviendo 404. Solo después debe solicitarse recrawl/indexación de las URLs canónicas en Search Console.

---

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

---

### Septiembre de 2026 (primeros días) — Portal de Hoteles

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

---

### Agosto de 2026 — Producción y regresiones

- Integración de las diez fases y reparación del flujo de reservas.
- Correcciones de Maps, codificación, estilos y contraste del booking.
- Auditoría SEO, sincronización V2 y correcciones de PortAventura.
- Cierre de PHPStan, PHPCS, Playwright y smoke tests de WordPress.

Referencias: `6d1ad71`, `be10884`, `90f5b19`, `92cf414`, `d047faa`, `1d280c8`.

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

### Agosto de 2026 — Plataforma modular

- Importación controlada de módulos legacy en `app/Legacy`.
- Bootstrap PSR-4 y centralización de CPT, administración, settings y assets.
- Pricing, Redsys, base de datos y caché desacoplados progresivamente.
- Integración de reservas, flota, hoteles, traducción y SEO.

Referencias: `ef04a30`, `da9b584`, `c8aeec5`, `ef480d9`, `572bbab`, `c555e33`.

### Julio–agosto de 2026 — Reservas y Hotel QR

- Buscador, selección de vehículos, precios, checkout y pagos.
- Administración de flota, reservas, hoteles y códigos QR.
- Importación/exportación de hoteles conservando tokens.
- Refactorización de estilos, JavaScript, vistas y compatibilidad operativa.

Referencias: `319f77b`, `38447a8`, `f6f3f67`, `aed2842`.

### Julio de 2026 — Traducción e internacionalización

- Evolución de GCT Translator SEO Edition a Traductor MT v5.
- Traducciones persistidas, caché, rutas por idioma y selector integrado.
- Traducción de HTML estático, atributo `lang` y errores AJAX detallados.
- Internacionalización nativa del tema y compatibilidad SEO/Yoast.

Referencias: `2007305`, `31d6f0e`, `13031fe`, `7126e83`, `f1259db`, `655a74c`.

### Julio de 2026 — Fundación del tema

- Creación del tema MeTransfers y primeros componentes visuales.
- Saneamiento de seguridad, estabilidad, BOM, mojibake y mixed content.
- Páginas de rutas, destinos, servicios, tours, contenido legal y navegación.
- Schema JSON-LD, breadcrumbs, mejoras SEO y diseño premium.

Referencias: `f960ba2`, `5715d03`, `f43b6f5`, `c4a59b2`.

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
