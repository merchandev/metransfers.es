# Transicion SEO conservadora

Fecha: 2026-09-08. La rama contiene codigo y borradores; no ejecuta migraciones al activar el tema.

## Protecciones implementadas

- La ausencia historica de _mt_seo_ready no activa noindex. Una marca explicita
  distinta de 1 sigue excluyendo la ruta. mt_seo_enforce_route_readiness permite
  activar la politica estricta SOLO despues de revisar el inventario completo.
- Ninguna de las 56 rutas se marca automaticamente como preparada.
- Los 301 se ejecutan solo si el destino existe, esta publicado, no tiene noindex,
  usa self-canonical y, si es una ruta, tiene _mt_seo_ready=1. Si no cumple,
  se conserva el tratamiento normal de la URL origen, sin excluirla por alias.
- Los tres aliases de Costa Brava quedan suspendidos. No se inventa una ruta
  barcelona-costa-brava ni se redirige a un destino marcado noindex.
- Se retiran las listas y el wildcard 410 sin revision individual. Las paginas
  publicadas recuperan su respuesta normal; si no existen siguen siendo 404.
  Esto no restaura contenido borrado ni garantiza recuperar rankings.
- Robots, sitemap y listado usan la politica central. Staging conserva
  noindex/nofollow/noarchive. Los idiomas SEO iniciales son ES y EN.
- EN necesita aprobacion por publicacion. La ficha _mt_seo_variant_en contiene
  translated_reviewed=true, http_status=200, canonical exacta y source_hash
  calculado mediante Variants::fingerprint(). Revisar HTTP, canonical y texto
  completo en staging antes de registrar esa aprobacion. No se aprueba EN en masa.
- La huella invalida la aprobacion cuando cambia el contenido, titulo,
  fecha de modificacion, metadatos SEO o plantilla supervisada.
- Las variantes sin aprobar reciben noindex y no aparecen en hreflang. Los hubs
  no anuncian traducciones mientras no tengan un mecanismo propio de aprobacion.
- Yoast recibe titles y descriptions de ruta y de /rutas/, respetando overrides
  editoriales. Los enlaces de menu, contenido y mt_localized_url resuelven los
  aliases solo cuando el destino cumple la politica.
- Las migraciones automaticas y los hacks de puntuaciones Yoast siguen desactivados.

## Revision de datos reales

Lectura de WordPress realizada en esta sesion: Salou (29108), Andorra (29142)
y Cadaques (29138) estan publicados, pero no contienen texto ni marca SEO.
Se prepararon borradores individuales con texto, FAQ, H1, title y descripcion
en seo-priority-routes.json. editorial_approved permanece false: no se
presentan como contenido aprobado ni se ha escrito en produccion.

Revisar los borradores con el negocio y confirmar sus condiciones. Despues,
aprobar cada fila individualmente. La herramienta comprueba ID, slug,
publicacion, contenido y fecha de modificacion originales antes de escribir.

Dry run en una copia de WordPress:

    wp eval-file tools/seo-approve-routes.php docs/seo-priority-routes.json

Aplicacion explicita, solo despues de revisar y aprobar las filas:

    wp eval-file tools/seo-approve-routes.php docs/seo-priority-routes.json --apply

La herramienta crea primero una copia de los posts y metadatos en la opcion
mt_seo_backup_2026-09-08-priority-routes, rehusa sobrescribirla y modifica
unicamente las tres rutas seleccionadas. No activa la exigencia global.

No activar mt_seo_enforce_route_readiness hasta que cada ruta publicada tenga
una decision editorial explicita. No crear redirects para las URLs con autoridad
de Vielha, Peniscola o Delta del Ebro sin preparar primero equivalentes reales.

## Validacion

Pruebas unitarias: transicion de readiness, destinos inexistentes/noindex,
aliases suspendidos, aprobacion por variante, canonical, robots, sitemap,
enlaces y metadatos. Playwright comprueba el cierre desde la zona visible del
overlay: se corrigio el punto de clic del test, sin cambiar el portal.

GitHub CI incluye pruebas HTTP contra WordPress 6.8.6 y 7.0.2, primero con el
sitemap nativo y despues con Yoast 26.9: 301 directo a 200, canonical, robots,
hub, sitemap y reciprocidad de hreflang tras aprobacion. La aprobacion de idioma
de las fixtures solo prueba el mecanismo; no sustituye la revision linguistica.

Tras publicar codigo, purgar cache y repetir la validacion con las URLs reales.
El CAPTCHA de SiteGround puede impedir las peticiones automatizadas.
No se ha aplicado la migracion editorial ni solicitado indexacion en Google.

## Rollback

Restaurar el paquete de tema anterior y purgar cache. No se escriben datos
automaticamente al desplegar. Si se ejecuto la migracion explicita, recuperar
los posts y metadatos del backup antes de revertir su contenido. La herramienta
conserva el snapshot completo incluso si una escritura posterior falla.
