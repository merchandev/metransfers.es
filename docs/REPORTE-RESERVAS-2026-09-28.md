# MeTransfers — Caída de la cotización online: diagnóstico y correcciones

**Fecha:** 28 de septiembre de 2026 (versión 2, revisada y aplicada).
**Repositorio:** https://github.com/merchandev/metransfers.es
**Base revisada:** `main`, commit `0cbfe14`. Correcciones en la rama `fix/reservas-cotizacion-2026-09-28` (12 commits, sección 6), [PR #58](https://github.com/merchandev/metransfers.es/pull/58). Los hashes de la sección 6 son los commits del PR.
**Estado:** correcciones de código aplicadas y probadas en local. **No desplegadas.** La causa raíz es configuración de Google Cloud y solo puede corregirla quien tenga acceso a esa cuenta (sección 4).

## 1. Resumen ejecutivo

- **Ningún cliente puede cotizar online.** Reproducido el 28/09 en producción con la ruta del cliente y también con «Barcelona» → «Girona»: no depende de la ruta, ni de la fecha, ni de la flota.
- **No es un fallo nuevo.** El mismo síntoma se detectó el 21/09 (`HISTORIAL.md`, ronda 1). Desde entonces solo se añadió un registro en el log de PHP, que el propietario no consulta. Llevamos al menos 7 días perdiendo reservas online.
- **Causa inmediata:** Google rechaza (o no recibe) la clave de Maps **del servidor** al geocodificar el origen. La clave del navegador sí funciona, y por eso el autocompletado parece estar bien.
- **Causa exacta todavía desconocida desde fuera.** Puede ser una clave ausente, restringida por *referrer*, con la IP del servidor sin autorizar, con la API sin habilitar, sin facturación o con la cuota agotada. Con estos cambios, wp-admin muestra el motivo exacto que da Google y el botón **«Probar conexión ahora»** lo comprueba al momento.
- **El cliente recibió un mensaje engañoso:** «No se pudo verificar el origen del traslado», en español aunque navegaba en inglés, que culpaba a su dirección. En analítica todo quedaba registrado como `no_vehicles`. Por eso concluyó que «no hay opciones».

## 2. Incidente y reproducción

Cliente (WhatsApp, 27/09 19:12): van desde BCN al H10 Casanova, «Sunday 10/04/2026 at 12:00 pm». Se interpreta como **domingo 4 de octubre de 2026, 12:00 (Europe/Madrid)** → `2026-10-04`. **Es dentro de 6 días:** conviene darle presupuesto manual ya, sin esperar al despliegue.

| Consulta al endpoint público `wptb_get_vehicles` (28/09) | Resultado |
|---|---|
| Aeropuerto BCN → H10 Casanova, 04/10/2026 12:00 (captura del cliente) | `No se pudo verificar el origen del traslado.` |
| «Barcelona» → «Girona», 04/10/2026 12:00 | Mismo error: fallo sistémico |
| «Barcelona, Spain» → «Girona, Spain» | Mismo error |

Nota para futuras pruebas con `curl` desde Windows: si la consola no envía UTF-8 (por ejemplo, la «ñ» de «España»), `sanitize_text_field()` vacía el campo y la respuesta es «Datos de reserva inválidos». Es un artefacto de la prueba, no del sitio: el navegador envía UTF-8.

## 3. Hallazgos (versión revisada)

| ID | Severidad | Hallazgo | Estado |
|---|---|---|---|
| H01 | **Crítica** | Geocodificación del servidor rechazada: bloquea todas las cotizaciones | Diagnóstico visible en wp-admin (commit 1). **La reparación es de configuración** (sección 4) |
| H01b | Alta (nuevo) | Alcance real: formulario principal, modal del carrusel, buscador premium, «Nueva reserva» del Portal de Hoteles, recotización en datos de reserva y en el inicio de pago. También **confirma reservas de Hotel QR** (`class-hqp-public.php:303` usa `RouteDistance` → Distance Matrix con la misma clave). Corrige lo anotado el 21/09 | Se resuelve con H01 |
| H02 | Media | Los avisos de consola «Google Maps autocomplete unavailable» en `/seleccionar-vehiculo/` eran ruido: la página no carga Maps y el script intentaba iniciar un formulario inexistente. **No eran la causa** | Corregido (commit 4) |
| H03 | Media | Maps se carga en asíncrono, pero el formulario lo esperaba solo 6 s y luego abandonaba para siempre | Corregido (commit 7) |
| H04 | Media | «Usar mi ubicación» invalidaba el origen recién validado y el envío quedaba bloqueado. Reproducido con el código anterior | Corregido (commit 5) |
| H05 | Media | El navegador solo admitía orígenes en Cataluña; el servidor admite regresos (París → Barcelona). Reproducido | Corregido (commit 6) |
| H05b | Media (nuevo) | El modal del carrusel rellena el origen por código y el envío exigía volver a elegirlo del desplegable. Reproducido | Corregido (commit 6) |
| H06 | Alta | Todo fallo técnico se mostraba y se medía como «no hay vehículos». Sin salida para el cliente | Corregido: código estable, mensaje honesto y botones de WhatsApp o llamada (commits 2 y 3) |
| H07 | **Baja** (antes Media) | `available` no se respetaba en las tarjetas. En la fase de vehículos no se envían pasajeros ni maletas, así que el servidor evalúa 1 pasajero y casi siempre devuelve `true` | Corregido de forma defensiva (commit 8) |
| H08 | Media | Idioma perdido en `ServiceAreaPolicy`, `BookingDatePolicy` y `RouteDistance` (este último con textos fijos en español). `origin_must_select` no existía en el catálogo | Corregido (commits 2 y 6) |
| H09 | Operación | Posible prevalencia de plugins antiguos (guardas `class_exists`) | Sin cambios; el aviso de conflicto ya existe |
| H10 | Mantenimiento | `RouteBootstrap` y versiones 5.0.6 / 6.9.4 | Sin cambios; no está relacionado |
| N1 | Alta (nuevo) | Si `wp-config.php` define `MT_GOOGLE_MAPS_SERVER_API_KEY`, **tiene prioridad sobre el campo del panel**: cambiar la clave en wp-admin no tendría efecto | Ahora se avisa en el panel y en el aviso rojo |
| N2 | Alta (nuevo) | Geocoding API y Distance Matrix API **no aceptan claves restringidas por sitio web (referrer)**, aunque el código envíe cabecera `Referer`. La clave del servidor debe restringirse por **IP** (la IP de salida del hosting, que Google muestra en su mensaje de rechazo) | Pista incluida en el aviso |
| N3 | A verificar (nuevo) | Según el aviso de Google de 2025, Distance Matrix API pasó a «Legacy» y no se puede activar en proyectos nuevos. Si la clave rotada el 19/08 (README) está en un proyecto nuevo, **la distancia podría fallar incluso con la geocodificación corregida**. En ese caso habría que usar una clave de un proyecto antiguo o migrar a Routes API | El botón de comprobación prueba ambas APIs y lo detecta |
| N4 | Alta (nuevo) | El buscador premium (`transfers-search.js`) no pedía vehículos al servidor hasta que cargaban Maps y un Distance Matrix **del navegador**. Si fallaba cualquiera de los dos, mostraba «El sistema no está disponible» o «No se pudo calcular la ruta». Ese cálculo era redundante: el servidor devuelve la ruta. Reproducido | Corregido (commit 9) |
| N5 | Baja (nuevo) | Mensaje de sesión caducada fijo en español en el buscador premium | Corregido (commit 10) |
| N6 | Media (nuevo) | En «Nueva reserva» del Portal de Hoteles, el autocompletado se intentaba activar una sola vez; con Maps en asíncrono no llegaba a activarse casi nunca. Es H03 en el portal, sin ningún reintento. Reproducido | Corregido (commit 11) |
| N7 | Media (nuevo) | Si fallaba la consulta de la flota a la base de datos, se devolvía una lista vacía y se mostraba «no hay vehículos» (parte de 9.2 de la v1) | Corregido (commit 12) |

Hipótesis a comprobar primero: la rotación de credenciales de Maps del 19/08 (documentada en el README) encaja con la cronología. Si la clave nueva se creó con la restricción por *referrer* de la del navegador, o en un proyecto nuevo sin las APIs o sin facturación, explicaría el fallo total. El aviso de wp-admin lo confirmará o lo descartará.

## 4. Qué tiene que hacer el propietario (esto es lo que acaba con la caída)

1. **Ahora, comercial:** responder al cliente del WhatsApp del 27/09 con un presupuesto manual para el 04/10 a las 12:00, BCN → H10 Casanova, van.
2. **Desplegar** la rama con `tools/build-release.ps1` (ZIP) tras revisar y fusionar el PR.
3. En wp-admin aparecerá un **aviso rojo** con el estado de Google (`REQUEST_DENIED`, `key_missing`, etc.), su mensaje literal y qué hacer. También en *MeTransfers → Integraciones → «Probar conexión ahora»*. Alternativa por SSH: `wp eval 'print_r( \MeTransfers\Booking\MapsProvider::runCheck() );'` (`tools/` no va en el ZIP).
4. En **Google Cloud Console**, para la clave del servidor:
   - APIs habilitadas en su proyecto: **Geocoding API** y **Distance Matrix API**.
   - **Facturación activa** en ese proyecto.
   - Restricción de aplicación: **direcciones IP** (la IP de salida de SiteGround; si Google la rechaza, aparece en el detalle del aviso). **Nunca por sitio web o referrer.**
   - Restricción de API: solo esas dos APIs.
5. Guardar la clave en *MeTransfers → Integraciones* o, **si el aviso indica que viene de `wp-config.php`, cambiarla allí**.
6. Pulsar «Probar conexión ahora» hasta que salga en verde (ambas APIs) y repetir BCN → H10 Casanova en la web. El aviso rojo desaparece solo en cuanto Google responde bien.

No sustituir la clave del servidor por la del navegador ni desactivar la validación: el código lo impide a propósito.

## 5. Estado de los demás módulos (sin cambios en esta revisión)

| Área | Controles observados | Verificación pendiente |
|---|---|---|
| Tarifas | Recálculo en servidor, mínimos, ida/vuelta y céntimos | Valores reales de flota y precios |
| Borradores | Tokens aleatorios, hash almacenado, caducidad y control de duplicados | Persistencia y concurrencia en producción |
| Redsys | Firma, comercio, terminal, moneda, importe y notificación del servidor | Pago de prueba y callbacks reales |
| Notificaciones | Cola persistente, reintentos y ejecución programada | Cron, SMTP y estado de eventos |
| Hoteles / Hotel QR | Acceso por hotel, permisos, tarifa fija | Recorrido completo con hotel de prueba (Hotel QR depende también de H01, ver H01b) |

## 6. Correcciones aplicadas (una por commit)

| # | Commit | Qué cambia | Hallazgos |
|---|---|---|---|
| 1 | `b5a0fc1` | `MapsProvider` centraliza las llamadas a Google, registra el último fallo (con la clave redactada) y muestra un aviso en wp-admin con el estado, la fecha, el mensaje de Google y la pista. Incluye el botón «Probar conexión ahora», `Settings::source()` (constante o panel) y `tools/maps-check.php` | H01, N1, N2, N3 |
| 2 | `f7f5b7a` | Códigos de error estables e idioma del visitante en todas las políticas. Una caída del proveedor ahora da `quote_service_unavailable` («no podemos calcular tu presupuesto online, escríbenos…») en lugar de culpar a la dirección. Flota con tarifas rotas → `invalid_server_price` | H06, H08 |
| 3 | `4d8ba16` | El navegador mide el código real y ofrece **WhatsApp** (mensaje con ruta, fecha y hora) y **llamada** en cualquier fallo de cotización (página, modal y buscador premium) | H06 |
| 4 | `a247471` | `initBookingForm` no se ejecuta sin formulario: desaparecen los avisos de consola engañosos | H02 |
| 5 | `02705a7` | La geolocalización mantiene válido el origen | H04 |
| 6 | `ae6f3ea` | El navegador aplica la regla del servidor (un extremo en Cataluña). Arregla los orígenes prellenados por código (carrusel) y localiza `origin_must_select` | H05, H05b, H08 |
| 7 | `d99abf0` | Maps avisa con `callback=mtMapsLoaded` → evento `mt:maps-ready`, sin límite de 6 s | H03 |
| 8 | `4071437` | Los vehículos con `available: false` no se pueden seleccionar desde ningún flujo | H07 |
| 9 | `8464c91` | El buscador premium cotiza con la ruta del servidor, sin depender de Maps en el navegador | N4 |
| 10 | `c24ca01` | Mensaje de sesión caducada del buscador premium en ES/EN (`session_expired`) | N5, H08 |
| 11 | `0806fa6` | Helper compartido `Assets::announceMapsReady()`. El Portal de Hoteles espera a Maps con `callback=mtMapsLoaded` | N6, H03 |
| 12 | `43f7867` | Un fallo de BD al leer la flota devuelve `vehicle_load_error`, no `no_vehicles` | N7 |

## 7. Verificación realizada

| Comprobación | Resultado |
|---|---|
| Suite legacy (17 scripts, incluido el nuevo `test-maps-provider.php`) | Pasa |
| PHPUnit | 98 pruebas y 668 aserciones: pasa (la deprecación previa no cambia) |
| PHPStan (se añade `MapsProvider.php` a las rutas analizadas) | Sin errores |
| PHPCS | Sin errores |
| ESLint | Sin errores |
| Navegador (páginas locales con el marcado, el CSS y los scripts reales; Maps y AJAX simulados) | Cada corrección de JS se contrastó con el script anterior. Antes fallaban y ahora pasan: geolocalización, París → Barcelona, prellenado del carrusel, Maps a los 8 s, buscador premium sin Maps y autocompletado del portal con Maps tardío. Siguen rechazados, como debe ser, Madrid → Lisboa y un origen en Marruecos. El estado de error se ve bien en escritorio y móvil, sin desbordamiento horizontal |
| Regresión final sobre la rama completa | 11 escenarios en navegador con el código definitivo (caída, error 429, disponibilidad, 7 casos del formulario y Maps tardío): todos correctos, sin avisos de consola en la página de vehículos |

Las pruebas de `MapsProvider` cubren: clave ausente, `REQUEST_DENIED` por *referrer* con redacción de la clave, pistas distintas para IP, API deshabilitada, API *legacy* y facturación, `ZERO_RESULTS` que no se trata como caída, `NOT_FOUND` de Distance Matrix, error de transporte y recuperación que limpia el aviso.

**No verificado:** producción (no hay acceso a wp-admin ni a Google Cloud, y no se desplegó nada). Playwright e2e no se ejecutó en local: lo ejecuta el CI y sus fixtures no cubren `booking-app.js`. No se creó ninguna reserva ni pago, ni se envió nada al cliente.

## 8. Pendiente

- Paso 4 completo (configuración de Google) y despliegue. **Sin eso la caída continúa**, aunque ahora el cliente verá una salida por WhatsApp o teléfono.
- Si el botón muestra el error *legacy* en Distance Matrix: migrar `RouteDistance` a Routes API (`computeRouteMatrix`). Es un cambio aparte.
- Opcional: alerta proactiva (email o WhatsApp al admin) cuando se registre una caída, para no depender de entrar en wp-admin.
