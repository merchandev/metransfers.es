# Auditoría de producción: indexación y tráfico de MeTransfers

**Fecha:** 2 de octubre de 2026. **Sitio:** <https://metransfers.es/>. **Estado:** revisión de lectura; no se han aplicado cambios en producción durante esta auditoría.

## 1. Conclusión

La web española no tiene un bloqueo general de indexación. Las páginas comerciales, los 149 artículos publicados y las 97 rutas responden y permiten indexación en el inventario principal. Eso significa que pueden indexarse; no certifica que Google haya indexado cada una.

**La versión inglesa sí presenta un bloqueo general en las 278 URLs que respondieron directamente con HTTP 200: todas emiten `noindex, follow`.** Esto incluye portada, aeropuerto, puerto, grupos, blog y rutas. Google interpreta `noindex` como una instrucción de excluir la página cuando la rastrea. [Documentación de Google](https://developers.google.com/search/docs/crawling-indexing/block-indexing).

La bajada de visitas totales tiene una explicación adicional y distinta: Analytics deja de registrar tráfico en los canales Paid Search y Cross-Network durante septiembre. En una comparación de 28 días que excluye las fechas más recientes, **los clics orgánicos de Google aumentan un 17,4 %, aunque las impresiones caen un 17 %**. Por tanto, los datos no sustentan atribuir toda la pérdida de visitas a una desindexación de la web española. Falta Google Ads para distinguir entre campañas detenidas, presupuesto, incidencias de anuncios o cambios de atribución.

También hay problemas concretos de navegación e indexación: seis enlaces ingleses devuelven 404, dos URLs redirigidas siguen en el sitemap y una cadena sobre devolución del IVA termina en contenido de otro tema. Un enlace a Montserrat también termina en un artículo sobre aplicaciones de transfer.

## 2. Alcance, método y evidencias

- Inventario de los **350 objetos publicados** que expone WordPress: 149 artículos, 104 páginas y 97 rutas. Se revisaron sus URLs públicas y las variantes inglesas derivadas.
- **705 URLs del inventario principal**: HTML, estado HTTP final, redirecciones, robots, canonical, hreflang, título, descripción y contenido principal.
- **15 enlaces internos adicionales**, encontrados en ese HTML, más **3 peticiones de control**: una URL inglesa con parámetro para comprobar consistencia y dos peticiones con identificador Googlebot.
- `robots.txt`, índice de sitemaps y sus cinco documentos hijos; configuración de visibilidad en WordPress y estado de idiomas de Traducción MT.
- Lecturas MCP de WordPress y de metadatos SEO de la página de aeropuerto; revisión del código local del tema.
- Datos reales de Search Console y Analytics mostrados en **Site Kit de producción**. Cálculos sobre las tablas diarias visibles, sin estimar visitas a partir de búsquedas `site:`.
- Prueba móvil de rendimiento disponible en Site Kit; comprobación de disponibilidad de datos de campo.

Los archivos entregados con este informe son:

- [Inventario de respuestas HTTP y señales SEO](INDEXACION-URLS-2026-10-02.csv).
- [Comparación de tráfico y unidades de medida](TRAFICO-COMPARACION-2026-10-02.csv).
- [Datos resumidos de la auditoría](INDEXACION-RESUMEN-2026-10-02.json).

Las capturas DOM, respuestas públicas y cálculos intermedios se conservaron localmente en `C:/Users/merch/AppData/Local/Temp/metransfers-production-seo-2026-10-02`. No se exportaron credenciales ni se registraron reservas, pagos o solicitudes de prueba.

**Límite de acceso:** Site Kit permite leer rendimiento. El acceso directo al informe detallado de indexación de Search Console no quedó disponible en el navegador de esta sesión, pese al aviso de inicio de sesión. Los conectores de GSC disponibles devolvieron respectivamente necesidad de reautenticación y suscripción no activa. No se ha comprado una suscripción ni creado una propiedad nueva. No se pudieron certificar cifras de URLs indexadas, motivos individuales de exclusión, acciones manuales, seguridad, posiciones por país/dispositivo ni resultados de Inspección de URLs. Tampoco se accedió a Google Ads, registros de servidor o al panel de SiteGround.

## 3. Qué permiten las páginas en producción

### 3.1. Español: no hay una prohibición global

La opción de WordPress **«Pedir a los motores de búsqueda que no indexen este sitio» está desmarcada**. `robots.txt` responde HTTP 200 y contiene:

```text
# START YOAST BLOCK
User-agent: *
Disallow:
Sitemap: https://metransfers.es/sitemap_index.xml
# END YOAST BLOCK
```

El inventario principal contiene **273 URLs españolas que responden directamente con 200 y admiten indexación**: 23 páginas, 149 artículos, 97 rutas y 4 archivos. Sus canonicals apuntan a la URL final correspondiente. No se encontraron documentos indexables vacíos en ese conjunto.

Seis páginas operativas españolas están en `noindex`: `/gracias/`, `/reservaciones/`, `/reservas-hotel/`, `/pago/`, `/seleccionar-vehiculo/` y `/reservas-metransfers/`. Son pasos de reserva, pago o acceso operativo. Su exclusión es coherente con ese uso y debe conservarse al corregir las páginas comerciales.

Las 705 peticiones principales terminaron con HTTP 200, **incluyendo las que llegaron mediante redirecciones**. La comprobación posterior de enlaces descubrió los seis 404 descritos abajo; el resultado principal no equivale a ausencia de errores en toda la web.

### 3.2. Inglés: exclusión confirmada

En el inventario principal, las respuestas inglesas directas HTTP 200 se distribuyen así:

| Tipo | URLs con `noindex` |
|---|---:|
| Páginas, incluida portada y páginas operativas | 31 |
| Artículos | 149 |
| Rutas individuales | 97 |
| Archivo de rutas | 1 |
| **Total** | **278** |

No se encontró una URL inglesa directa HTTP 200 que permitiera indexación. Otros 73 alias ingleses redirigen; muchos terminan en español y no son páginas inglesas indexables.

Ejemplos comprobados:

- [Portada inglesa](https://metransfers.es/en/).
- [Aeropuerto en inglés](https://metransfers.es/en/transfer-aeropuerto-barcelona/).
- [Puerto en inglés](https://metransfers.es/en/traslados-puerto/).
- [Sobre nosotros en inglés](https://metransfers.es/en/sobre-nosotros/).
- [Grupos en inglés](https://metransfers.es/en/grupos/).
- [Blog en inglés](https://metransfers.es/en/blog/) y [rutas en inglés](https://metransfers.es/en/rutas/).

Estas respuestas contienen:

```html
<meta name="robots" content="noindex, follow">
```

No emiten canonical ni alternativos hreflang. Las páginas españolas revisadas tampoco anuncian variantes hreflang inglesas aprobadas. La comprobación del aeropuerto inglés con parámetro adicional y con identificador Googlebot devolvió el mismo `noindex`. Cambiar el identificador del cliente no reproduce una visita desde una IP real de Googlebot ni sustituye la Inspección de URLs.

**Contenido:** aeropuerto, puerto, grupos y «Sobre nosotros» ya presentan contenido principal en inglés. El archivo del blog sigue mostrando textos de artículos en español y hay páginas inglesas con texto principal idéntico al español, por ejemplo preguntas frecuentes y varios tours. No debe habilitarse la indexación inglesa de forma masiva sin revisión editorial. Google identifica el idioma mediante el contenido y recomienda alternativos coherentes entre traducciones. [Guía de sitios multilingües](https://developers.google.com/search/docs/specialty/international/localized-versions).

## 4. Causa técnica del `noindex` inglés

La pantalla de Traducción MT marca **ES y EN como «index»**. En la página española de aeropuerto, la lectura de metadatos SEO devuelve `robots_noindex: false` y `robots_nofollow: false`; no hay una anulación de canonical. Estos ajustes no bastan para superar la política del tema.

El código local exige una aprobación adicional por variante:

| Archivo | Comportamiento relevante |
|---|---|
| `app/SEO/Indexability.php` | Para una petición traducida, resuelve el contenido español y exige que sea indexable y que su variante esté aprobada. |
| `app/SEO/Variants.php` | Lee `_mt_seo_variant_en` y exige revisión editorial, HTTP 200, canonical exacto y huella del origen vigente. |
| `app/SEO/Policy.php` | Convierte el resultado negativo en `noindex` para WordPress y Yoast. |
| `app/I18n/Seo.php` | Omite alternativos cuando la petición no es indexable; además restringe portada, blog y rutas a ES para los alternativos. |
| `tools/seo-approve-variant.php` | Herramienta WP-CLI existente que genera la aprobación, con simulación por defecto. No se ha ejecutado en producción. |

La condición vigente de aprobación es:

```php
return is_array( $approval ) && ! empty( $approval['translated_reviewed'] )
    && 200 === ( $approval['http_status'] ?? 0 )
    && ( $approval['canonical'] ?? '' ) === $canonical
    && self::fingerprint( $post ) === ( $approval['source_hash'] ?? '' );
```

La huella incluye contenido, título, **`post_modified`**, hash de una plantilla y metadatos SEO. Guardar un artículo cambia `post_modified`; cambiar un slug también cambia el canonical esperado. Cualquiera de estas diferencias puede invalidar una aprobación previa. La indexación de archivos ingleses necesita además una política específica: un archivo como `/en/rutas/` no siempre corresponde a una página singular con metadatos de aprobación.

**Grado de certeza:** el bloqueo se observó en el HTML público. El código explica un mecanismo compatible con ese resultado. No se leyó el valor bruto de `_mt_seo_variant_en` de cada objeto ni se compararon todos los archivos remotos del tema, por lo que no puede distinguirse para cada URL entre aprobación ausente, caducada o cualquier otra condición del servidor. Tampoco puede atribuirse todo el bloqueo a la última modificación del blog: las páginas comerciales están afectadas igualmente.

### Solución propuesta

1. Revisar y aprobar primero las traducciones comerciales: portada, aeropuerto, puerto, grupos y «Sobre nosotros».
2. Leer sus metadatos de aprobación en el servidor y verificar el código realmente desplegado. Aplicar aprobaciones solo a traducciones revisadas y URLs con 200 directo.
3. Añadir un tratamiento explícito para los archivos de blog/rutas y sus alternativos; verificar que el mapa de sitio incluya las variantes inglesas que se decidan indexar.
4. Revisar la huella para que una mera fecha de guardado no destruya una aprobación editorial; mantener la invalidación por cambios relevantes de contenido, plantilla y URL. Esa revisión requiere pruebas y una migración controlada de las aprobaciones existentes.
5. Comprobar en la respuesta pública `index, follow`, un canonical propio y hreflang recíproco. Después solicitar rastreo de las páginas prioritarias y revisar su resultado en Search Console.

Resultado esperado para una página comercial inglesa aprobada:

```html
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://metransfers.es/en/transfer-aeropuerto-barcelona/">
<link rel="alternate" hreflang="es" href="https://metransfers.es/transfer-aeropuerto-barcelona/">
<link rel="alternate" hreflang="en" href="https://metransfers.es/en/transfer-aeropuerto-barcelona/">
```

El ejemplo expresa el objetivo; no es un parche desplegado ni una aprobación automática de artículos sin traducir.

### Código de diagnóstico de solo lectura

Este ejemplo puede ejecutarse mediante `wp eval-file` en el servidor autorizado para conocer qué condición falla. **No modifica metadatos ni cambia robots.** Requiere que el tema cargue sus clases y que se complete el listado de IDs según el inventario.

```php
<?php
// Diagnóstico; no es la corrección ni se ejecutó durante esta auditoría.
$post_ids = array( 31033 ); // Página de aeropuerto comprobada por MCP.
foreach ( $post_ids as $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post ) {
        continue;
    }
    $approval = get_post_meta( $post_id, '_mt_seo_variant_en', true );
    $path = trim( (string) wp_parse_url( get_permalink( $post ), PHP_URL_PATH ), '/' );
    $expected = \MeTransfers\I18n\Language::urlForLanguage( 'en', $path );
    $has_approval = is_array( $approval );
    echo wp_json_encode( array(
        'id' => $post_id,
        'expected_canonical' => $expected,
        'has_approval' => $has_approval,
        'reviewed' => $has_approval && ! empty( $approval['translated_reviewed'] ),
        'http_200' => $has_approval && 200 === ( $approval['http_status'] ?? 0 ),
        'canonical_matches' => $has_approval && ( $approval['canonical'] ?? '' ) === $expected,
        'fingerprint_matches' => $has_approval
            && ( $approval['source_hash'] ?? '' ) === \MeTransfers\SEO\Variants::fingerprint( $post ),
        'approved_now' => \MeTransfers\SEO\Variants::isApproved( $post, 'en' ),
    ), JSON_UNESCAPED_SLASHES ) . PHP_EOL;
}
```

## 5. Seis enlaces ingleses rotos: HTTP 404

Estas URLs aparecen en enlaces públicos y devuelven 404:

- <https://metransfers.es/en/category/taxi-en-barcelona/>
- <https://metransfers.es/en/category/tours-desde-barcelona/>
- <https://metransfers.es/en/category/traslados-desde-barcelona/>
- <https://metransfers.es/en/blog/page/2/>
- <https://metransfers.es/en/blog/page/3/>
- <https://metransfers.es/en/blog/page/15/>

La portada inglesa del blog enlaza a las páginas 2, 3 y 15. En español esas páginas funcionan, al igual que la paginación de categorías comprobada.

**Mecanismo observado en el repositorio:** `app/I18n/Router.php` reconoce exactamente `blog`, `noticias` y `rutas` como archivos. Su regla general captura `blog/page/2` o `category/...` como una ruta completa, pero `dispatch()` busca una página o artículo singular en esos casos y no tiene ramas específicas de paginación/categorías. Eso es compatible con los 404 encontrados en producción.

**Corrección propuesta:** reconocer paginación y categorías antes del resolvedor de singulares, conservar `mt_lang`, asignar correctamente `paged`/categoría y consultar solo contenido publicado. Probar páginas 1, 2, última y fuera de rango; categorías existentes y desconocidas; canonicals por página; ES/EN y ausencia de cambios en rutas de reservas. Una redirección general de todos los 404 a la portada ocultaría el problema y no es la solución.

No se afirma haber probado cada número intermedio de paginación. Los seis errores anteriores son verificaciones concretas.

## 6. Sitemaps y redirecciones con contenido equivocado

El índice y sus cinco documentos hijos responden HTTP 200. Contienen **275 URLs HTML únicas, todas españolas**, además del recurso geográfico KML. De esas URLs, 273 responden directamente con 200; dos redirigen.

El encabezado `X-Robots-Tag: noindex, follow` del propio sitemap XML es un comportamiento previsto por Yoast: evita indexar el documento XML como resultado de búsqueda. **No ordena excluir las páginas que enumera.** [Especificación oficial de Yoast](https://developer.yoast.com/features/xml-sitemaps/functional-specification/).

### 6.1. Devolución del IVA: cadena con tema incorrecto

La URL incluida en `page-sitemap.xml` sigue esta cadena:

```text
/recuperar-el-iva-en-el-aeropuerto-2/
  301 -> /recuperar-el-iva-en-el-aeropuerto/
  301 -> /beneficios-de-usar-un-servicio-de-traslado-privado-en-barcelona/
  200 -> artículo de beneficios del traslado privado
```

WordPress todavía expone una página de Tax Free, ID **29076**, con contenido sobre devolución del IVA. Su versión `/en/recuperar-el-iva-en-el-aeropuerto-2/` muestra ese contenido en español y está en `noindex`. La URL española publicada no lo alcanza: acaba en otro artículo, ID **29735**.

**Corrección propuesta:** elegir una URL canónica para la guía de IVA y servir su contenido directamente; revisar la primera redirección heredada y los conflictos con el antiguo slug del artículo 29735. Conservar las redirecciones válidas de artículos ya migrados y no cambiar a ciegas el destino de todas las URLs antiguas. Actualizar el sitemap a la URL final pertinente y comprobar enlaces internos, bucles, cadena y parámetros de campañas. Las cabeceras observadas no permiten identificar con certeza quién creó la primera regla ni si vive en servidor, plugin o una configuración anterior.

### 6.2. Cookies: redirección correcta, entrada de sitemap incorrecta

`/cookies/` redirige con 301 a `/politica-de-cookies/`, que responde 200 con el tema correcto. La redirección aparece expresamente en `includes/legal-pages.php`. El problema es mantener el origen redirigido en el sitemap. Conservar el 301 y listar la URL final, excluyendo el alias del sitemap.

El sitemap debe describir las URLs canónicas elegidas; enviarlo no garantiza su indexación. [Guía de construcción de sitemaps de Google](https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap).

### 6.3. Enlace editorial a Montserrat con destino incoherente

El artículo [Taxi privado Barcelona–Valencia](https://metransfers.es/taxi-privado-desde-barcelona-a-valencia-viaje-directo/) recomienda una excursión a Montserrat y enlaza a:

```text
/excursion-privada-desde-barcelona-a-montserrat/
  301 -> /las-mejores-aplicaciones-de-transfer-en-barcelona-compara-y-encuentra/
```

El destino es un artículo sobre aplicaciones de transfer. La variante inglesa del artículo conserva el mismo enlace. La antigua URL puede tener una redirección legítima para el contenido sustituido; lo que ya no tiene sentido es mantenerla como recomendación de Montserrat. Actualizar el enlace del cuerpo hacia una página vigente de ese tour —por ejemplo `/tour-a-montserrat/`, tras revisar su contenido y canal comercial— y revisar referencias similares antes de tocar la redirección histórica.

## 7. Qué dicen las cifras sobre la bajada de visitas

### 7.1. Comparación de periodos iguales sin los días más recientes

Se compararon **1–28 de septiembre** con **4–31 de agosto de 2026**, ambos de 28 días. Se excluyeron 29 de septiembre–2 de octubre para reducir el efecto de días parciales. Site Kit no aporta aquí el indicador API de datos definitivos; el corte es prudente, no una certificación de cierre de cada día.

| Métrica | 4–31 ago. | 1–28 sep. | Cambio |
|---|---:|---:|---:|
| Impresiones en Google Search Console | 17.979 | 14.920 | **−17,0 %** |
| Clics en Google Search Console | 109 | 128 | **+17,4 %** |
| CTR calculado: clics / impresiones | 0,61 % | 0,86 % | +0,25 puntos porcentuales |
| Analytics: suma de usuarios diarios, todos los canales | 524 | 219 | −58,2 % |
| Analytics: suma diaria del canal Organic Search | 31 | 66 | +112,9 % |
| Analytics: suma diaria del canal Paid Search | 135 | 0 | −100 % |
| Analytics: suma diaria del canal Cross-Network | 190 | 0 | −100 % |

**Unidades:** las filas de Analytics suman los recuentos de usuarios de cada día. Una misma persona puede aparecer varios días; estos valores **no son usuarios únicos del periodo completo, sesiones ni ventas**. Tampoco se deben sumar canales suponiendo personas distintas.

El último día con tráfico Paid Search registrado en esa serie es el **26 de agosto**; en Cross-Network es el **31 de agosto**. La desaparición de ambos canales coincide con gran parte de la caída total observada. Es una asociación sólida en los datos disponibles, pero no identifica por sí sola una pausa de campañas: hay que revisar Google Ads y la atribución.

Los 128 clics orgánicos equivalen a unos **4,6 clics por día**. Hay poca adquisición orgánica absoluta aunque mejora frente al periodo anterior. La pérdida de impresiones merece revisar consultas, páginas, país, dispositivo y posición media; no se puede atribuir a una penalización o a un cambio concreto de Google con estos datos agregados.

### 7.2. Qué mostraba el panel de 28 días por defecto

Para **5 de septiembre–2 de octubre**, Site Kit muestra 200 visitantes, −56 %; 120 clics de Google, +4,3 %; 13.628 impresiones; y 66 visitantes orgánicos, +100 %. El día 2 de octubre aparece parcial, por lo que esta comparación no se utilizó como cifra principal del diagnóstico.

Distribución mostrada: Direct 61,5 %, Organic Search 33 %, AI Assistant 3,5 %, Unassigned 1 % y Other 1 %. Google Search Console y Analytics miden cosas distintas y sus recuentos no deben igualarse automáticamente.

### 7.3. Consultas y páginas: oportunidades, no posiciones certificadas

Ejemplos de consultas mostradas en el panel de 28 días por defecto:

| Consulta | Clics | Impresiones | CTR calculado |
|---|---:|---:|---:|
| transfer barcelona | 10 | 356 | 2,81 % |
| transfers barcelona | 5 | 363 | 1,38 % |
| transfer barcelona aeropuerto | 3 | 123 | 2,44 % |
| transfer privado barcelona | 2 | 350 | 0,57 % |
| traslados privados barcelona | 2 | 137 | 1,46 % |

También aparecen búsquedas de marca como «me transfers barcelona» y «metransfers». No se extrapoló su proporción al conjunto de clics a partir de las diez primeras filas. Un CTR bajo puede depender de posición, intención y aspecto del resultado; requiere esos desgloses antes de prometer mejoras por cambiar títulos.

En contenido de Analytics de esos 28 días, la portada registra 102 vistas; `/reservas-hotel/`, 79; `/en/reservas-hotel/`, 42; y la portada inglesa, 30. Buena parte del uso visible corresponde al portal de hoteles, que no equivale a nuevos clientes adquiridos por SEO.

El panel muestra **381 eventos clave**. No se accedió a la lista de eventos que los compone, así que no se interpretan como 381 reservas o ventas. Hay que separar solicitudes, clics en WhatsApp, pasos de reserva y pagos confirmados, y comprobar duplicaciones en el embudo.

## 8. Rendimiento y calidad de señales SEO

### 8.1. Rendimiento móvil

Site Kit muestra una prueba **de laboratorio móvil** de la portada:

| Métrica | Resultado | Valoración del panel |
|---|---:|---|
| Largest Contentful Paint: aparición del contenido principal | **4,3 s** | Pobre |
| Cumulative Layout Shift: movimientos del diseño | 0,001 | Bueno |
| Tiempo total de bloqueo | 30 ms | Bueno |

En la pestaña de campo aparece **«Datos de campo no disponibles»**. No se afirma que todos los visitantes sufran ese LCP ni que la web suspenda los Core Web Vitals reales. La mediana de respuesta inicial en el rastreo local fue de unos 2,47 s; incluye condiciones de red y caché y no es una medición de campo.

**Lectura posterior, 2 de octubre a las 13:19 UTC:** al recargar Site Kit con la sesión de WordPress verificada, el panel de laboratorio móvil de la misma portada mostró LCP **13,5 s**, CLS **0** y tiempo total de bloqueo **160 ms**. Se conserva la lectura inicial de 4,3 s como evidencia anterior; ninguna se presenta como promedio de visitantes. La diferencia refuerza la necesidad de investigar el recurso LCP, caché y condiciones de prueba con mediciones comparables. No se pulsó «Ejecutar la prueba de nuevo» ni se cambiaron ajustes de rendimiento.

Revisar el recurso que determina el LCP, su tamaño/prioridad, fuentes, caché y tiempo del servidor. Verificar con pruebas repetibles de la misma página y dispositivo. Google considera la experiencia de página entre sus señales, pero una prueba lenta no demuestra por sí sola la causa de la caída de tráfico. [Documentación de experiencia de página](https://developers.google.com/search/docs/appearance/page-experience).

### 8.2. Ocho rutas sin meta description

Las siguientes páginas son indexables y no emiten descripción SEO:

- `/rutas/barcelona-taull/`
- `/rutas/barcelona-besalu/`
- `/rutas/barcelona-morella/`
- `/rutas/barcelona-altea/`
- `/rutas/barcelona-valderrobres/`
- `/rutas/barcelona-alquezar/`
- `/rutas/barcelona-collioure/`
- `/rutas/barcelona-carcassonne/`

Redactar descripciones pertinentes para cada ruta. Su ausencia no bloquea la indexación ni garantiza que Google use la descripción propuesta.

### 8.3. Dos páginas con el mismo título sobre Andorra

Estas dos URLs indexables emiten «Transfer privado Barcelona a Andorra | MeTransfers»:

- `/transfer-privado-barcelona-a-andorra/`
- `/transfer-privado-barcelona-a-andorra-esqui-compras-duty-free/`

Revisar la intención y diferenciar servicio general de guía de esquí/compras, o decidir consolidación si duplican la intención. Existe riesgo de solapamiento; no se certifica canibalización sin comprobar las consultas y páginas que Google muestra.

### 8.4. Medición y consentimiento

El código mantiene el consentimiento de Analytics y publicidad denegado hasta la elección correspondiente. No se debe retirar el consentimiento para aumentar artificialmente los recuentos. Revisar implementación, etiquetas y eventos con las opciones de consentimiento; contrastar solicitudes y pagos confirmados en el sistema comercial antes de calcular conversión.

## 9. Orden de corrección y criterios de aceptación

| Prioridad | Trabajo concreto | Comprobación de cierre |
|---|---|---|
| **Alta** | Aprobar las traducciones comerciales inglesas y resolver la política de archivos/alternativos. | 200 directo, `index`, canonical propio, hreflang recíproco y sitemap coherente; inspección de Google tras rastreo. |
| **Alta** | Corregir navegación inglesa de categorías y paginación. | Los seis enlaces comprobados funcionan; páginas fuera de rango y categorías inexistentes siguen devolviendo 404. |
| **Alta** | Resolver el conflicto de la guía de IVA y su cadena. | El destino corresponde a IVA, no a beneficios de transfer; sin bucles ni pérdida de parámetros. |
| **Alta, adquisición** | Revisar Google Ads y atribución desde finales de agosto. | Estado de campañas, gasto, clics, aprobaciones, etiquetas y canales explican la desaparición de Paid/Cross-Network. |
| **Media** | Actualizar enlaces editoriales heredados, empezando por Montserrat. | El texto y el destino coinciden; se conservan los canales comerciales válidos. |
| **Media** | Limpiar las dos entradas redirigidas del sitemap. | El sitemap enumera URLs canónicas pertinentes con 200 directo. |
| **Media** | Optimizar LCP móvil a partir del recurso y servidor medidos. | Mejora repetible, sin alterar funcionamiento de formularios y consentimiento. |
| **Media** | Completar descripciones de rutas y diferenciar las páginas de Andorra. | Señales editoriales coherentes; medir por consultas/páginas, sin prometer posiciones. |
| **Media** | Auditar eventos clave y embudo de solicitudes/reservas/pagos. | Eventos bien definidos y sin duplicaciones; ventas contrastadas con registros comerciales. |

Para cualquier cambio de SEO o router, el control de regresión debe cubrir calculador ES/EN, selección de origen/destino, búsqueda, selección de vehículo, hotel/QR, ida y vuelta, conservación de campañas y sesión, solicitud de presupuesto y llegada a la pasarela con entorno de prueba. Los cambios de indexación no deben convertir páginas operativas en páginas comerciales indexables.

El fallo anterior de verificación del origen es un problema funcional que puede reducir ventas aunque una página esté indexada. Esta auditoría no certifica su reparación ni ha creado reservas reales; debe comprobarse con su diagnóstico y pruebas de reserva independientes.

## 10. Qué falta para identificar todas las exclusiones de Google

Con acceso efectivo a la propiedad de Search Console:

1. Exportar «Indexación de páginas» por motivo: `noindex`, redirección, no encontrada, duplicada, rastreada/no indexada y descubierta/no indexada.
2. Inspeccionar portada, aeropuerto ES/EN, puerto EN, una ruta ES/EN, guía de IVA y una paginación inglesa; comparar canonical declarado y elegido por Google y último rastreo.
3. Comprobar sitemaps procesados, acciones manuales, seguridad y estadísticas de rastreo.
4. Analizar 28 días cerrados y 90 días por página, consulta, país y dispositivo; separar ES/EN y marca/no marca.
5. Contrastar Google Ads y Analytics con reservas efectivas para explicar adquisición y conversión por separado.

**Resultado de esta revisión:** hay un bloqueo inglés y errores de navegación verificables que conviene corregir. No se ha encontrado un bloqueo total español. Las cifras disponibles apuntan a pérdida del tráfico atribuido a publicidad como factor principal de la bajada total reciente, mientras los clics orgánicos aumentan en la comparación de septiembre. La causa exacta de cada exclusión de Google y el estado de campañas requieren los accesos detallados indicados.

## 11. Verificación posterior de la sesión

El 2 de octubre, tras el aviso del usuario de que la sesión estaba lista, se abrió el escritorio y el perfil de WordPress. La interfaz muestra **«Gabriel Molina»** y permite acceder a los apartados de administración; Site Kit carga y el conector MCP de WordPress responde a consultas de capacidades de lectura.

El enlace de Search Console que expone Site Kit selecciona `info@metransfers.es`. Al abrir ese enlace, Google sigue mostrando la pantalla de inicio de sesión. Por tanto, el acceso confirmado es a WordPress y a los datos de Google mostrados en Site Kit; no se ha confirmado una sesión directa de Google con acceso a los informes detallados de indexación. Esta comprobación no cambia las limitaciones del apartado 2 ni implica que las correcciones propuestas se hayan aplicado.

## 12. Cómo aplicar las correcciones pendientes

Se comprobó en la sesión actual el tema activo **Me Transfers Premium 5.0.6** y la disponibilidad de **Apariencia → Añadir temas → Subir tema**. La interfaz ofrece una posible vía de instalación de un ZIP corregido desde WordPress. No se subió ni instaló un paquete y todavía no se ha probado el reemplazo del tema. La visibilidad de ese control no confirma permisos de escritura del servidor ni acceso a SiteGround.

### Preparación y publicación del código

1. Guardar una copia recuperable de los archivos y la base de datos de producción; registrar tema activo, configuración y estado de las URLs antes de cambiar nada.
2. Comparar el código que se va a publicar con la versión realmente activa. Coincidir en número de versión no prueba igualdad de archivos. Preparar el paquete conservando el identificador del tema activo y sus ajustes.
3. Corregir router, aprobación de variantes, canonicals/alternativos y filtros de sitemap; comprobarlo con WordPress y Yoast en un entorno de prueba.
4. Generar y verificar el ZIP de producción, publicar el código en GitHub y aplicar el paquete por la vía de instalación comprobada. Un commit de GitHub no sustituye la instalación del tema en producción.
5. Aplicar los cambios editoriales y las aprobaciones revisadas, limpiar la caché afectada y comprobar las respuestas públicas reales. Restaurar el paquete o los datos anteriores si los controles de reserva fallan.

### Defecto adicional de la herramienta de aprobación

La revisión de `tools/seo-approve-variant.php` detectó que el bucle puede ejecutar `update_post_meta()` antes de comprobar todos los errores del manifiesto. Además, registrar un error de aprobación editorial ausente no detiene por sí solo la escritura de esa fila. No debe usarse así para una aprobación masiva.

La corrección necesita dos fases: validar **todo** el manifiesto sin escribir; y, solo si no hay errores, realizar escrituras con copia de los valores anteriores y comprobación del resultado. Debe rechazar filas sin revisión, idiomas no elegibles, destinos no publicados/no indexables, duplicados y respuestas distintas de 200. Las pruebas deben demostrar que un lote inválido no deja aprobaciones parciales y que una escritura fallida puede revertirse. Es una propuesta de reparación; no se ha modificado ni ejecutado esa herramienta durante esta auditoría.

### Cambios de contenido y comprobaciones externas

- Mediante WordPress/MCP: corregir enlaces editoriales, completar las ocho descripciones y diferenciar los dos contenidos de Andorra. Revisar cada regla de redirección antes de cambiar su destino; localizar en qué capa vive la primera redirección de IVA.
- En el tema: resolver categorías y paginación inglesa, conservar los 404 legítimos y preparar la indexación de variantes comerciales traducidas. Mantener protegidos los pasos de reserva, pago y portal.
- En rendimiento: identificar el elemento LCP y comprobar recursos, fuentes, imágenes y caché con mediciones comparables; cualquier cambio de carga de JavaScript requiere pruebas del calculador y Google Maps.
- En medición: verificar nombres y duplicación de eventos clave y relacionarlos con solicitudes y pagos reales, respetando el consentimiento.
- En Google: el informe detallado de Search Console requiere una sesión propia de Google; el diagnóstico de Paid Search/Cross-Network requiere acceso a Google Ads. No se propone reactivar campañas ni aumentar presupuesto sin conocer su estado y sus objetivos.

El trabajo se considerará cerrado tras comprobar indexabilidad comercial, navegación, destinos de enlaces y sitemaps en producción, junto con el recorrido de reservas ES/EN, hoteles/QR y pasarela en un entorno apropiado. La posterior inclusión y posición de cada página en Google depende de su rastreo y evaluación, y debe medirse desde Search Console.
