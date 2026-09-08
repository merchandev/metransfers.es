# Auditoria de estructura de paginas

Fecha: 2026-09-08. Revision del repositorio local y WordPress en solo lectura.

## Alcance y limites

- Inventario completo de las 98 paginas accesibles al conector, consultadas individualmente: permalink, estado, padre y longitud de contenido.
- Inventario de las 56 entradas del CPT ruta. No se ha descargado el contenido individual de las 56 rutas en esta revision.
- Revision de plantillas, router de idiomas, mapa legacy, menu, footer, politicas SEO y documento historico.
- Consultas publicas de los principales accesos de navegacion. El navegador de consulta devuelve algunas respuestas rastreadas y otras fallan; no equivale a un rastreo HTTP fresco de las 154 URLs. Las peticiones directas anteriores recibieron el desafio 202 de SiteGround.
- No se han enviado formularios, realizado pagos ni modificado WordPress. No se ha certificado el despliegue ni la configuracion de plugins del servidor.
- Los tres archivos PHP modificados antes de esta revision se conservan sin cambios adicionales. Esta revision agrega documentacion, no una reparacion en produccion.

## Hallazgos priorizados

### P1: enlaces de navegacion a paginas que no existen

`app/create-main-menu.php:56` y `:65` generan enlaces a taxis-privado-barcelona y taxis-barcelona-costa-brava. Los permalinks reales son /traslados-privados/ (30985) y /destinos/costa-brava/ (30687). No hay paginas con aquellos dos slugs en el inventario completo. Una plantilla page-SLUG.php por si sola no crea la pagina en WordPress.

La consulta de [taxis privado](https://metransfers.es/taxis-privado-barcelona/) devuelve 404; las capturas del usuario confirman ambos fallos. [Traslados privados](https://metransfers.es/traslados-privados/) y [Costa Brava](https://metransfers.es/destinos/costa-brava/) si devuelven contenido en la consulta publica. El parche local previo invierte el alias de traslados privados y adapta navegacion, pero no esta desplegado ni resuelve por si solo el acceso directo a Costa Brava. Su 301 sigue deliberadamente suspendido hasta aprobar un destino SEO equivalente.

### P1: el router de idiomas no comprueba publicacion antes de hidratar paginas

En `app/I18n/Router.php:173` se busca el post por slug y en `:179` se acepta sin verificar post_status ni permisos de lectura. `hydrateVirtualPage()` en `:309` hace lo mismo con su fallback. Una pagina privada o borrador encontrada puede entrar en el contexto de renderizado publico de una URL traducida. Es un riesgo identificado en codigo; no se ha intentado extraer contenido privado en produccion.

Requiere pruebas en WordPress con fixtures de borrador, privado, papelera y protegido antes de corregir y desplegar. Las excepciones de previsualizacion deben exigir autorizacion.

### P2: URLs traducidas virtuales sin una pagina base existente

`app/I18n/Router.php:86` acepta prefijos taxis-barcelona-* y traslados-barcelona-*; `:200` hidrata un post virtual y `:203` fuerza 200 si existe la plantilla. Esto permite diferencias entre ES (404) y otros idiomas (plantilla generica), incluso con slugs no inventariados. Noindex no convierte esa respuesta en 404. La consulta publica de un slug de prueba fue bloqueada; hallazgo de codigo, no reproduccion HTTP certificada.

### P2: el parche local de navegacion necesita ampliar su validacion

`app/SEO/Links.php:29` compara paths sin separar prefijos de idioma: el fallback solo reconoce los slugs ES. Ademas, `:31` usa get_page_by_path para rutas con prefijo /rutas/, aunque el CPT no es jerarquico y ese segmento es la base rewrite, no un padre de pagina. Debe resolver tambien por permalink (url_to_postid) y probarse con WordPress real. El doble de pruebas actual simplifica esa resolucion y no demuestra el comportamiento real del CPT. No debe presentarse el parche como reparacion completa del menu multilingue.

### P2: el blog hereda los metadatos de la portada

`functions.php:1254` y `:1495` aplican titulo y descripcion de portada tambien cuando is_home() es verdadero, que es el indice de entradas de WordPress. La consulta de [/blog/](https://metransfers.es/blog/) muestra el titulo de transfer al aeropuerto. Separar portada de blog y respetar metadatos editoriales.

### P2: documento de estructura no representa el inventario actual

estructura_paginas.md anuncia creacion automatica y landings que no figuran como paginas. `functions.php:1103` mantiene desactivado mt_ensure_seo_pages; tambien estan desactivadas sincronizaciones de servicios y destinos. La descripcion no debe usarse como prueba de publicacion ni como instruccion para regenerar paginas masivamente.

El inventario contiene 30 landings raiz con sufijos taxis/traslados y 38 paginas hijas, ademas de las rutas. Hay solapamientos geograficos, pero no se ha demostrado canibalizacion mediante datos GSC en esta revision. No borrar ni redirigir en bloque.

## Cambio observado durante la revision

[/rutas/](https://metransfers.es/rutas/) ahora muestra 56 rutas y 21 destinos, frente a las cero rutas observadas anteriormente. El titulo publico tambien coincide ahora con el nuevo fallback SEO. Es evidencia de un cambio en la respuesta publica, no prueba del SHA desplegado ni de su causa. No reproducimos ahora el archivo vacio.

Las consultas de aliases no fueron uniformes: Salou devolvio 404 en la consulta mas reciente; Girona devolvio una redireccion a su ruta. No se consideran todos los aliases sanos por tener un destino real: los 301 del codigo exigen readiness SEO explicito.

## Estructura verificada y propuesta de navegacion

| Area | URL real o familia | Observacion |
|---|---|---|
| Portada | / | WordPress devuelve / como permalink de la pagina 30676, antes identificada como hub Destinos; no asumir que /destinos/ es su permalink actual |
| Aeropuerto | /transfer-aeropuerto-barcelona/ | Tambien existe /traslados-aeropuerto/; consolidacion legacy condicionada |
| Puerto | /traslados-puerto/ | Contenido recuperado en consulta publica |
| Por horas | /chofer-por-horas/ | Contenido recuperado en consulta publica |
| Empresas | /corporativo-y-eventos/ | Contenido recuperado; respuesta rastreada de hace cuatro dias |
| Grupos | /grupos/ | Contenido recuperado en consulta publica |
| Traslados privados | /traslados-privados/ | Pagina 30985; sustituir enlaces al slug inexistente |
| Rutas | /rutas/ y /rutas/{slug}/ | 56 entradas publicadas, 21 destinos en el archivo publico |
| Costa Brava | /destinos/costa-brava/ | Pagina 30687; enlazar no implica aprobar su indexacion |
| Salou / Girona | /rutas/barcelona-salou/ y /rutas/barcelona-girona/ | Destinos reales de navegacion; no aprobar readiness en masa |
| PortAventura | /taxis-barcelona-port-aventura/ | Pagina 31139 existente y contenido de plantilla; no inventar ruta barcelona-portaventura |
| Tours | /tours-privados/ | Cuatro paginas: tour-en-barcelona, tour-a-montserrat, tour-costa-brava y tour-a-girona |
| Corporativo | /sobre-nosotros/, /blog/, /contacto/ | Blog requiere metadatos propios |
| Ayuda | /preguntas-frecuentes/ | Pagina real 30656; no asumir que /faq/ existe |
| Legales | /politica-de-privacidad/, /politica-de-cookies/, /aviso-legal/, /terminos-y-condiciones/ | Existe tambien /cookies/; comprobar reglas y plantilla sin evaluar aqui suficiencia juridica |
| Reserva | /reservaciones/, /seleccionar-vehiculo/, /reservas-metransfers/, /pago/, /gracias/ | Son los slugs de WordPress; finalizar-reserva/finalizar-pago son titulos historicos, no los permalinks reales |
| Hoteles | /reservas-hotel/ | Flujo autenticado; no probarlo mediante envios reales |

Hay cinco paginas con post_content vacio: PortAventura, blog, gracias, contacto y FAQ. No significa que rendericen vacias: dependen de plantillas, consultas o componentes. No rellenarlas automaticamente para corregir un supuesto 404.

## Orden de reparacion

1. Reparar y probar el router de idiomas con estados de publicacion y URLs inexistentes.
2. Completar el resolver de enlaces con permalinks reales, idiomas y pruebas de menu contra WordPress; aplicar despues los enlaces de menu y footer.
3. Separar los metadatos del blog y cerrar la cobertura de URLs transaccionales reales.
4. Validar el paquete desplegado, navegacion ES/EN y respuestas reales de aliases, sin forzar aprobaciones SEO.
5. Revisar duplicidades con datos de trafico y aprobar cualquier consolidacion por URL.

Inventario completo: [154 registros](page-structure-inventory-2026-09-08.md). Esta auditoria no autoriza ni ejecuta cambios editoriales o eliminaciones.
