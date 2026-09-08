# Correcciones SEO: validacion y despliegue

Fecha: 2026-09-08. Rama: seo/indexation-cleanup-2026-09.

## Cambios de codigo

- Namespace MeTransfers\SEO coherente con app/SEO para sistemas Linux.
- Redirector antes del router y de las redirecciones antiguas; conserva idioma,
  parametros y barra final. Resuelve cadenas del mapa y rechaza ciclos.
- Una politica para listado, robots WordPress/Yoast y ambos sitemaps.
- Una ruta publicada requiere _mt_seo_ready=1. Un noindex manual, una canonical
  externa, una pagina protegida o un alias prevalecen sobre esa marca.
- Hreflang se omite en solicitudes no indexables y sin fuente publicada valida;
  los idiomas deben pertenecer a MT_ACTIVE_LANGS y MT_SEO_LANGS.
- Eliminadas puntuaciones, recuentos artificiales y SQL directo a Yoast de la
  migracion. El hook automatico sigue desactivado.
- No se han modificado reservas, Redsys ni los archivos del portal de hoteles.

## Datos que requieren revision antes del despliegue

No se ha cambiado ninguna publicacion ni metadato de produccion. Si ninguna
ruta tiene _mt_seo_ready=1, el listado seguira vacio. No activar esa marca en
masa: revisar contenido, canonical y traducciones de las rutas prioritarias.
Este cambio hace que las rutas no revisadas tambien queden fuera del sitemap
y reciban noindex, como exige el documento de auditoria.

Antes de desplegar, comprobar especialmente que los destinos de los 301 de
Salou, Cadaques y Andorra existen y tienen contenido aprobado y marca de
preparacion. Mantener las redirecciones historicas estables.

MT_SEO_LANGS conserva los idiomas previamente configurados salvo nl, que no
estaba activo. Su pertenencia a la lista no demuestra una traduccion humana
completa: revisar editorialmente cada idioma antes de considerarlo aprobado.
La whitelist de destinos sigue siendo salou y lloret-de-mar. Andorra requiere
decidir entre contenido diferenciado o consolidacion con su ruta.

## Verificacion en WordPress

1. Hacer copia de seguridad del tema y la base de datos.
2. Validar los metadatos de las rutas prioritarias en una copia de WordPress.
3. Desplegar el tema incluyendo app/SEO y purgar caches del sitio y de Yoast.
4. Comprobar Home, /rutas/, rutas prioritarias, servicios y versiones EN.
5. Probar /en/empresas/, /en/transfer-puerto-barcelona/ y todos los aliases:
   un 301 directo a una URL 200, indexable y self-canonical.
6. Comprobar sitemap sin aliases, noindex ni rutas no preparadas; revisar
   canonical, robots y reciprocidad de hreflang en las respuestas reales.
7. Solo despues, planificar la limpieza controlada de paginas WordPress.

Las peticiones HTTP automatizadas desde este entorno recibieron un CAPTCHA
de SiteGround (202); no constituyen una verificacion de los redirects reales.
No se ha desplegado ni solicitado indexacion a Google.

## Rollback

Restaurar el paquete de tema anterior completo y purgar caches. Estas
correcciones no escriben metadatos ni ejecutan migraciones de contenido.
Si se revisan metadatos durante el despliegue, registrar sus valores previos
para poder restaurarlos por separado. No borrar paginas durante esta fase.

## Resultados locales

- PHPUnit: 63 pruebas, 367 aserciones, todas correctas.
- PHPStan: sin errores; ejecutado sin paralelismo y con limite de 2 GB porque
  los workers superaban el limite original de 1 GB en este entorno Windows.
- ESLint, sintaxis PHP y git diff --check: correctos.
- PHPCS de las clases SEO, Seo.php y las nuevas pruebas: correcto. La pasada
  global encuentra finales CRLF en archivos existentes fuera de estos cambios.
- Playwright: 10 pruebas correctas y 1 fallo en runtime.spec.js:129; la barra
  lateral del portal intercepta el clic de cierre del menu. Los archivos de
  esa funcionalidad y sus fixtures no han cambiado en esta correccion SEO.
- Las pruebas de navegador existentes usan fixtures, no un WordPress completo.
  Sigue siendo necesaria la validacion HTTP post-deploy indicada arriba.
