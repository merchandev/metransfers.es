# Corrección de contenido, inglés y enlaces — 1 de octubre de 2026

## Estado y alcance

Esta entrega corrige en el código del tema los defectos confirmados en la revisión de contenido: aeropuerto, versión inglesa de «Sobre nosotros», navegación, listado de rutas, llamadas a presupuesto y dos artículos con URL y extracto incongruentes. La aplicación en la base de datos está separada del despliegue del tema y limitada a los artículos 29745 y 29746.

**Producción pendiente de acceso:** durante esta intervención el conector de WordPress respondió `UNAUTHORIZED` y el navegador abrió el formulario de acceso de WordPress. No se ha actualizado el tema ni ejecutado la migración en la web publicada. Tener el commit en GitHub no demuestra que SiteGround lo haya desplegado.

Las correcciones de reservas, Google Maps y exportación de hoteles de los PR anteriores ya están en la base de esta entrega. No se vuelve a implementar la propuesta del informe de reservas del 28 de septiembre: varias de sus observaciones han sido resueltas desde entonces.

## Hallazgos y solución concreta

| Hallazgo confirmado | Causa | Corrección preparada |
| --- | --- | --- |
| El alias `/en/traslados-aeropuerto/` acaba en español | La redirección exigía aprobación SEO de la variante inglesa para conservar el idioma | Conserva `/en/` en las páginas editoriales revisadas. Mantiene parámetros. No modifica las reglas de aprobación de Google |
| Aeropuerto y «Sobre nosotros» mezclan ES/EN | `Translation::translate()` devuelve el original si no existe traducción en caché | Catálogo inglés revisado incluido en el tema, con coincidencias exactas y prioridad sobre traducciones antiguas; títulos ingleses en WordPress/Yoast y descripción inglesa de «Sobre nosotros» en Yoast |
| Cabecera, formularios y pie contienen textos españoles | Textos sin traducción persistente y algunas frases sin envolver | Traducciones de menús, etiquetas, placeholders, ayuda de WhatsApp y mensajes locales del formulario |
| Aeropuerto destaca principalmente hotel → aeropuerto | Títulos y pasos orientados a salidas | Titular, descripción y pasos explican aeropuerto → hotel y hotel → aeropuerto |
| El formulario de presupuesto anuncia «Reservar ahora» | Se reutiliza un CTA de reserva en el botón de envío | «Solicitar presupuesto» identifica el formulario. Se conserva otro enlace visible «Calcular y reservar online» al mismo panel de reservas |
| «Sobre nosotros» enlaza a un ancla incorrecta para reservar | El botón usa portada + `#solicitar` | Usa el helper del panel existente, que conserva el idioma |
| Rutas mezcla idiomas y traduce nombres geográficos incorrectamente | Introducción fija en español; nombres propios pasan por la caché de traducción | Introducción y contadores traducidos; Granada, La Pineda y demás destinos conservan su nombre |
| Dos artículos tienen URLs y extractos de temas distintos | Se cambió el cuerpo/título sin corregir slug y extracto | Migración limitada a dos IDs: conserva título/cuerpo y actualiza slug/extracto, con copia y 301 |
| Girona se anuncia sin aclarar ciudad/aeropuerto | Copy comercial ambiguo | Solicita indicar ciudad o aeropuerto para consultar servicio y precio. Conserva el enlace de ruta |

El resultado de «0 rutas» del informe previo **no se confirmó en la lectura directa**: el sitio mostraba 97 rutas y 61 destinos. Se conserva la consulta y el filtro de rutas existentes.

## Artículos: URLs antiguas conservadas mediante 301

| ID | URL antigua | URL final preparada |
| --- | --- | --- |
| 29745 | `/barcelona-seniors-comodidad-accesibilidad-vehiculos/` | `/diferencias-entre-servicios-de-traslado-taxi-vs-transfer-privado-en-barcelona/` |
| 29746 | `/lonjas-de-pescado-en-la-costa-de-cataluna/` | `/como-escoger-el-mejor-servicio-de-transfer-en-barcelona-guia-completa/` |

Se conserva el mismo ID, título, cuerpo, imágenes, categorías y enlaces comerciales. Los nuevos extractos describen el contenido actual. El manifiesto incorpora las fechas de modificación públicas observadas (`2026-08-05 09:00:00` y `2026-08-06 09:00:00`). Si título, slug o fecha ya no coinciden, el script se detiene antes de escribir. También valida colisiones.

El respaldo incluye posts completos, metadatos, permalinks y mapa anterior de redirecciones. Se conserva un canonical manual hacia otra dirección. Si había un canonical explícito hacia la propia URL antigua, se actualiza a la nueva URL. Cada post actualizado recibe su redirección antes de procesar el siguiente.

La reversión comprueba todo el lote antes de escribir y rechaza contenido, título, extracto, estado, slug o canonical que haya cambiado después. No borra el respaldo y conserva las redirecciones ajenas.

Google documenta las redirecciones permanentes de servidor como señal de traslado a una URL canónica nueva. Esto permite mantener el acceso desde los enlaces antiguos; no permite garantizar posiciones ni plazos de actualización de resultados. [Documentación de Google Search Central](https://developers.google.com/search/docs/crawling-indexing/301-redirects).

## Código de las correcciones principales

### Inglés sin depender de una traducción guardada

`app/I18n/Translation.php` consulta primero el catálogo revisado:

```php
$editorial = EditorialEnglish::lookup( (string) $text, $language );
if ( null !== $editorial ) {
    return $editorial;
}
```

`app/I18n/EditorialEnglish.php` contiene las equivalencias exactas. Ejemplo:

```php
'Nuestra historia y valores' => 'Our story and values',
'Solicitar presupuesto' => 'Request a quote',
'Calcular y reservar online' => 'Get a price and book online',
```

El español permanece igual. Un HTML arbitrario que no coincide exactamente con una entrada sigue usando la traducción almacenada o su contenido original. No hay sustituciones globales sobre HTML, URLs, shortcodes o nombres de campos. No se hacen llamadas a Google Translate durante la visita.

### Presupuesto y reserva distinguidos

`template-servicio.php` conserva los dos destinos:

```php
<a href="#solicitar" class="btn btn-primary">
    <?php echo esc_html( mt_translate( 'Solicitar presupuesto' ) ); ?>
</a>
<a href="<?php echo esc_url( me_transfers_get_section_url( 'panel' ) ); ?>">
    <?php echo esc_html( mt_translate( 'Calcular y reservar online' ) ); ?>
</a>
```

El segundo enlace respeta la condición existente de servicios con reserva online; empresas y grupos conservan su consulta por formulario. El formulario mantiene código técnico sin traducir:

```php
data-service="<?php echo esc_attr( $form_type ); ?>"
```

Se conservan `mt_save_lead`, nonce, `nombre`, `telefono`, `email`, `mensaje`, todos los campos `extra_*`, aceptación GDPR y su fecha. Un éxito muestra recepción de una solicitud, sin anunciar reserva confirmada. Un error conserva los datos y vuelve a habilitar el botón.

### Redirección inglesa sin alterar la indexación

`app/SEO/Redirects.php` conserva el idioma del alias cuando el destino español existe y es apto, para los dos paths editoriales revisados. Las reglas de `Variants`, robots, sitemap y hreflang siguen exigiendo aprobación por URL. No se aprueban automáticamente páginas, rutas ni artículos sin revisión.

`app/SEO/BlogSlugRedirects.php` utiliza el mapa guardado por la migración y comprueba que el artículo de destino sea existente, publicado y sin contraseña antes de redirigir. Un noindex o canonical manual no impide que los enlaces antiguos lleguen al artículo. Se ejecuta solamente en 404 y peticiones GET/HEAD; conserva la query y evita bucles. Las variantes EN de artículos sin aprobación consolidan en español según la política existente.

## Validación preparada y ejecutada

- PHPUnit: 104 pruebas, 924 aserciones; sin fallos. Existe una deprecación de configuración de PHPUnit ya presente en la base.
- PHPStan: sin errores.
- WPCS: sin errores.
- ESLint: sin errores.
- Auditoría npm: sin vulnerabilidades tras actualizar únicamente `brace-expansion` 5.0.9 → 5.0.12, dependencia de desarrollo. El archivo de producción excluye esas herramientas.
- Playwright: 15 pruebas aprobadas. El formulario probado se renderiza desde el PHP real, con dependencias de WordPress simuladas y AJAX interceptado localmente.
- Regresiones de precios, Google Maps, direcciones, vehículos, reservas, Redsys, recibos, hoteles, Excel, seguridad e internacionalización: aprobadas. Una prueba existente de rechazo de vehículos incompletos emite avisos de propiedades ausentes y termina correctamente.
- Migración: simulación sin escrituras, rechazo de ediciones posteriores, colisiones y protección de la reversión aprobados en el test aislado.
- Integración añadida a CI con WordPress 6.8.6, 7.0.2 y 7.1: aplicación real de la migración, conservación del enlace de venta y del cuerpo con apóstrofos, actualización del canonical y restauración del post. Pruebas HTTP con WordPress y Yoast comprueban el alias EN, contenido traducido y formularios.

El estado definitivo de esas integraciones se debe comprobar en el PR antes de fusionarlo. No son pruebas del servidor publicado ni una transacción bancaria real. No se ha enviado ninguna solicitud de cliente para probar producción.

## Aplicación en WordPress / SiteGround

1. Usar el paquete de tema construido desde el commit aprobado de `main`. Verificar el ZIP con `tools/build-release.ps1`. El archivo de tema excluye herramientas, pruebas y documentos.
2. Actualizar el tema existente conservando nombre, opciones, base de datos y configuración de integraciones. Para cambios de contenido, ejecutar el manifiesto independiente mediante WP-CLI desde la raíz de WordPress, con el repositorio o las herramientas auxiliares disponibles en una carpeta privada.
3. Ejecutar primero la simulación con rutas reales al código y al manifiesto:

```bash
wp eval-file /ruta/privada/metransfers/tools/fix-blog-slugs.php /ruta/privada/metransfers/docs/blog-repair-2026-10-01.json
```

Si ambos IDs y las comprobaciones coinciden, aplicar:

```bash
wp eval-file /ruta/privada/metransfers/tools/fix-blog-slugs.php /ruta/privada/metransfers/docs/blog-repair-2026-10-01.json --apply
```

4. Vaciar las cachés de las páginas afectadas del sitio/CDN. Verificar las páginas ES/EN, las dos URLs nuevas con 200, las dos antiguas con 301 al artículo correspondiente y los canonical. Comprobar enlaces al panel, formulario de presupuesto y WhatsApp. Una actualización de GitHub por sí sola no ejecuta esta migración.
5. Para comprobar la recuperación sin modificar nada:

```bash
wp eval-file /ruta/privada/metransfers/tools/restore-blog-slugs.php /ruta/privada/metransfers/docs/blog-repair-2026-10-01.json
```

Si procede la reversión:

```bash
wp eval-file /ruta/privada/metransfers/tools/restore-blog-slugs.php /ruta/privada/metransfers/docs/blog-repair-2026-10-01.json --apply
```

## Datos que no se deben inventar

- **Tarifa orientativa:** no se ha publicado un importe sin validar. El visitante puede obtener el precio de su trayecto en el calculador existente. Un precio fijo «desde X €» requiere confirmar vehículo, trayecto y condiciones vigentes.
- **Enlace externo:** ambos artículos contienen `https://transfersinbarcelona.com/es`. Se conserva por la instrucción de proteger puntos de venta, hasta confirmar su propiedad o finalidad. No se afirma que sea propio ni se cambia su destino.
- **Google:** las pruebas preservan los contratos SEO y comerciales; no certifican tráfico, posiciones ni aumentos de ventas. No se han cambiado Google Maps, Analytics, Ads, Search Console, pagos ni datos de reservas como parte de esta corrección.
- **Cierre en producción:** requiere acceso autenticado, despliegue y verificación del servidor publicado. El conector debe reconectarse o debe abrirse una sesión administrativa utilizable.
