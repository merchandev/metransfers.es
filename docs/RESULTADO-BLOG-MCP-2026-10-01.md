# Resultado de la revisión y corrección del blog mediante MCP

**Fecha del usuario: 1 de octubre de 2026. Estado: correcciones de contenido aplicadas; migración definitiva de 140 slugs pendiente de instalar las redirecciones en inglés.**

## 1. Conexión comprobada

La conexión «MCP Server For WordPress» ya funciona contra **https://metransfers.es**. La sesión dispone de un usuario administrador y 202 capacidades. Se verificó que el entorno es producción, con WordPress 7.1.2 y PHP 8.2.34.

Este acceso permite leer y editar entradas, páginas y campos SEO. No expone herramientas para modificar archivos del tema, instalar el manejador de redirecciones o administrar el alojamiento de SiteGround. La cuenta conectada no equivale a acceso a los archivos del servidor.

Se revisaron las **149 entradas publicadas**. Se inventariaron además 104 páginas publicadas y 97 rutas publicadas, y se comprobaron los estados restantes y la papelera para buscar conflictos de URL. No se encontraron colisiones para los 140 slugs propuestos.

## 2. Hallazgos

| Hallazgo | Entradas |
|---|---:|
| Slug que describe un tema distinto del título y cuerpo | 138 |
| Desajuste parcial de enfoque | 2 |
| Slug coherente con el tema actual | 9 |
| Bloques defectuosos que repetían el artículo al renderizarlo | 35 |
| Extracto manual que conservaba el tema antiguo | 4 |

El listado individual de las 149 entradas, con su URL, título, contenido revisado y diagnóstico, está en [la auditoría completa](AUDITORIA-BLOG-2026-10-01.md). Sus secciones iniciales conservan el estado previo a esta intervención.

### Por qué ocurrió

WordPress guarda el título, el cuerpo y el slug como campos independientes. El estado observado es compatible con ediciones que sustituyeron el título y cuerpo y omitieron el campo de slug. La revisión no permite atribuir esas operaciones a una persona o herramienta concreta.

En los 35 artículos defectuosos también había comentarios de bloques mal emparejados. En lugar de cerrar un bloque, algunos comentarios lo abrían de nuevo. La entrada 29569 incluía además un bloque `wp:post-content` dentro de su propio contenido y copias repetidas del HTML.

La revisión anterior de la entrada 29569 contiene exactamente el mismo cuerpo defectuoso que la copia obtenida antes de esta intervención. Esto confirma que el defecto ya estaba guardado; no identifica por sí solo qué cliente o herramienta lo introdujo.

## 3. Cambios aplicados directamente en WordPress

| Corrección | Estado |
|---|---|
| Reconstrucción de bloques equilibrados en 35 artículos | Aplicada y comprobada en la versión pública |
| Retirada de las copias idénticas y del bloque de contenido recursivo de 29569 | Aplicada |
| Extractos de 29735, 29744, 29745 y 29746 alineados con su tema actual | Aplicada |
| Texto de 1038 corregido para aeropuerto El Prat → Barcelona | Aplicada |
| Título de 29566 cambiado a «Cómo Planificar un Viaje Perfecto en Barcelona: Guía Completa» | Aplicada |
| Migración definitiva de 140 slugs | Pendiente del despliegue y prueba de las redirecciones inglesas |

Son **41 entradas corregidas**: 36 cuerpos, 4 extractos manuales y 1 título. El resumen automático de 29569 se regeneró a partir del cuerpo limpio; las revisiones anteriores y posteriores confirman que su campo de extracto almacenado sigue vacío.

La reconstrucción conserva los textos y las referencias únicas a enlaces e imágenes. Los enlaces comerciales a `transfersinbarcelona.com/es` se mantienen. No se ha certificado que sean ajenos al negocio.

### Resultado del artículo más afectado

| Medida de 29569 | Antes | Después |
|---|---:|---:|
| Caracteres del HTML público del cuerpo | 3.949.474 | 2.916 |
| Bloques de texto mostrados | 11.301 | 9 |
| Textos de bloque distintos | 9 | 9 |

## 4. Incidencia durante la migración de URLs y restauración

Se cambió primero la entrada 29746 y se comprobó que la URL española antigua respondía 301, la nueva 200 y el canonical seguía la URL nueva. Después se inició el lote y se alcanzaron 39 cambios de slug.

La verificación adicional de idioma detectó que la URL inglesa antigua devolvía **404**, aunque la nueva inglesa respondía 200. Se detuvo el lote y se restauraron los **39 slugs originales**, conservando las reparaciones de contenido.

La restauración terminó sin errores. Las **78 peticiones HEAD** a las URLs originales de esas 39 entradas, en español e inglés, respondieron **200**. También se verificaron con GET las URLs originales de la entrada de prueba y la última entrada restaurada.

**Los 149 slugs actuales coinciden con los que existían antes de esta sesión.** Los 140 cambios definitivos siguen pendientes; el desajuste de esas URLs aún no está resuelto en producción.

## 5. Comprobaciones finales

La nueva lectura pública de las 149 entradas confirma:

- Los mismos 149 IDs publicados.
- Slugs, URLs, autores, fechas de creación, categorías y etiquetas conservados.
- 149 canonical correspondientes a sus URLs actuales.
- Directivas noindex, nofollow, noimageindex, noarchive y nosnippet coherentes con los valores guardados antes de la intervención.
- Referencias únicas a enlaces e imágenes conservadas en las 149 entradas.
- Los 35 cuerpos reparados tienen bloques equilibrados y ya no repiten sus textos.
- Los cuatro extractos manuales coinciden con el contenido previsto.
- El título nuevo de 29566 se muestra correctamente.

Las marcas de modificación que devuelve WordPress incluyen el 2 de octubre, mientras el trabajo se realizó el 1 de octubre en la zona del usuario. No se verificó la configuración de zona horaria del sitio.

El trabajo se limitó a las entradas del blog. Las comprobaciones públicas y de código no certifican una compra real ni posiciones concretas en Google.

## 6. Copia de seguridad y diario

Copia externa al repositorio:

`C:/Users/merch/OneDrive/Escritorio/metransfers-backups/blog-2026-10-01-mcp/`

Archivos principales:

- `all-posts-before.json`: los cuerpos originales y el estado de las 149 entradas y sus campos SEO.
- `all-posts-after-content.json`: estado esperado después de las reparaciones.
- `applied-journal.json`: cambios iniciales antes de detener la migración de slugs.
- `slug-rollback-journal.json`: restauración de los 39 slugs.
- `content-applied-journal.json`: guardados de contenido y comprobaciones.
- `content-cleanup.json`: propuestas preparadas desde los cuerpos originales.

SHA-256 de `all-posts-before.json`:

`02A0D492629AE46553BA95DB31F4FDFE5DDB0BF3987836A2C1130ACA8BC4C530`

Las copias originales se conservan fuera de Git. Una restauración posterior debe leer primero la entrada actual para no sobrescribir nuevas ediciones.

## 7. Solución pendiente para las URLs inglesas

El router del tema utiliza `mt_lang` y `mt_page`. La función nativa de WordPress para slugs antiguos requiere `name`, por lo que no encuentra esa URL inglesa. Esta condición está en el [código oficial de wp_old_slug_redirect](https://developer.wordpress.org/reference/functions/wp_old_slug_redirect/).

La corrección preparada consulta el historial `_wp_old_slug` en un verdadero 404 inglés, exige un único artículo publicado sin contraseña, conserva el idioma y los parámetros y excluye reservas, pagos, administración y rutas anidadas. No modifica la aprobación SEO de las traducciones.

Antes de reanudar los 140 slugs:

1. Instalar el parche en la versión activa del tema y comprobar que la clase se registra.
2. Repetir una sola entrada de prueba en español e inglés.
3. Exigir 301 correcto en las URLs antiguas, 200 en las nuevas y canonical correcto.
4. Volver a leer cada entrada antes de cambiar su slug y conservar el diario.
5. Comprobar los 140 pares de URLs y el sitemap final.

Está abierta una pestaña del panel de WordPress para completar el acceso a los archivos. El inicio de sesión sigue pendiente. La autorización del usuario para corregir el blog ya está concedida.

La instalación está detallada en [la guía de redirecciones](CORRECCION-REDIRECCIONES-BLOG-EN-2026-10-01.md).

### Validación del código preparado

- PHPUnit: **107 pruebas, 947 aserciones**, sin fallos; una deprecación existente del ejecutor.
- PHPStan: sin errores.
- PHPCS de los PHP modernos modificados: sin infracciones.
- Regresiones locales de migración/reversión e idioma: aprobadas.
- Preparación reproducible desde la copia original: **35 propuestas, 0 rechazos**.
- Se añadieron comprobaciones de integración con WordPress real para el historial nativo inglés y la restauración. Su ejecución corresponde a CI.

## 8. Artículos con bloques reparados

| ID | Bloques públicos antes | Bloques públicos después |
|---|---:|---:|
| 1000 | 43 | 10 |
| 1012 | 143 | 12 |
| 1013 | 77 | 9 |
| 1014 | 76 | 11 |
| 1017 | 55 | 11 |
| 1019 | 76 | 12 |
| 1020 | 69 | 11 |
| 1021 | 50 | 10 |
| 1037 | 65 | 12 |
| 1039 | 144 | 13 |
| 1041 | 70 | 13 |
| 1044 | 59 | 11 |
| 1051 | 55 | 11 |
| 18057 | 58 | 11 |
| 18188 | 52 | 10 |
| 24131 | 55 | 11 |
| 27253 | 120 | 11 |
| 27276 | 52 | 10 |
| 27345 | 62 | 12 |
| 27360 | 62 | 12 |
| 27442 | 62 | 12 |
| 27465 | 72 | 12 |
| 27586 | 117 | 12 |
| 27622 | 31 | 10 |
| 27643 | 31 | 9 |
| 27656 | 55 | 10 |
| 27807 | 17 | 12 |
| 27815 | 92 | 17 |
| 27826 | 43 | 10 |
| 27836 | 255 | 16 |
| 27864 | 43 | 10 |
| 27870 | 107 | 15 |
| 27885 | 77 | 13 |
| 29561 | 143 | 12 |
| 29569 | 11301 | 9 |

## 9. Código completo de las redirecciones

Archivo: `app/SEO/BlogSlugRedirects.php`. **Preparado y probado localmente; pendiente de instalación en producción.**

```php
<?php
namespace MeTransfers\SEO;

/**
 * 301s a corrected blog post slug from its old, unrelated one (see
 * tools/fix-blog-slugs.php). Deliberately independent of LegacyUrlMap:
 * the mapping is generated data, not a fixed pattern, and only fires on an
 * actual 404 so it is safe regardless of whether this code deploys before
 * or after the WP-CLI script renames the posts — a URL that still resolves
 * to a real post is never intercepted.
 */
final class BlogSlugRedirects {
	const OPTION = 'mt_blog_slug_redirects';

	public function register() {
		add_action( 'template_redirect', array( __CLASS__, 'maybeRedirect' ), 5 );
	}

	public static function maybeRedirect() {
		if ( ! is_404() || is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST )
			|| ! in_array( $_SERVER['REQUEST_METHOD'] ?? 'GET', array( 'GET', 'HEAD' ), true ) ) {
			return;
		}
		$map     = get_option( self::OPTION, array() );
		$request = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		$target  = self::targetForRequest( $request, is_array( $map ) ? $map : array() );
		if ( null === $target ) {
			$target = self::nativeEnglishTargetForRequest( $request );
		}
		if ( null === $target ) {
			return;
		}
		wp_safe_redirect( home_url( $target ), 301 );
		exit;
	}

	/**
	 * The language router uses mt_page, so WordPress's name-based old-slug
	 * redirect cannot resolve /en/ links after an ordinary post update.
	 * Read core's recorded slug history without changing SEO approvals.
	 */
	public static function nativeEnglishTargetForRequest( string $request ): ?string {
		if ( 'en' !== \MeTransfers\I18n\Language::detectFromUri( $request ) ) {
			return null;
		}
		$slug = \MeTransfers\I18n\Language::pathWithoutLanguage( $request );
		// Blog permalinks on this site have exactly one segment. Never match
		// nested route, checkout, admin, feed or API paths.
		if ( '' === $slug || false !== strpos( $slug, '/' ) || ! preg_match( '/^[a-z0-9%_-]+$/i', $slug ) ) {
			return null;
		}
		if ( in_array( $slug, array( 'pago', 'reservaciones', 'seleccionar-vehiculo', 'reservas-metransfers', 'reservas-hotel', 'gracias', 'contacto', 'wp-admin', 'wp-json', 'feed' ), true ) ) {
			return null;
		}
		if ( UrlPolicy::postForPath( $slug ) ) {
			return null;
		}
		$matches = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 2,
				'fields'         => 'ids',
				'meta_key'       => '_wp_old_slug',
				'meta_value'     => $slug,
				'no_found_rows'  => true,
			)
		);
		// An ambiguous history must not send visitors to an arbitrary post.
		if ( 1 !== count( $matches ) ) {
			return null;
		}
		$post = get_post( (int) $matches[0] );
		if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status
			|| '' !== $post->post_password || $slug === $post->post_name ) {
			return null;
		}
		$target = (string) $post->post_name;
		if ( '' === $target || ! preg_match( '/^[a-z0-9%_-]+$/i', $target ) ) {
			return null;
		}
		$query = parse_url( $request, PHP_URL_QUERY );
		return '/en/' . $target . '/' . ( is_string( $query ) && '' !== $query ? '?' . $query : '' );
	}

	public static function targetForRequest( string $request, array $map ): ?string {
		$path = \MeTransfers\I18n\Language::pathWithoutLanguage( $request );
		if ( '' === $path || ! isset( $map[ $path ] ) || ! is_string( $map[ $path ] ) ) {
			return null;
		}
		$target = trim( $map[ $path ], '/' );
		$post   = UrlPolicy::postForPath( $target );
		// A public article remains a valid redirect destination even when its
		// owner set noindex or a manual canonical. Those SEO choices must not
		// break inbound links after a slug correction.
		if ( $target === $path || ! $post || 'post' !== $post->post_type
			|| 'publish' !== $post->post_status || '' !== (string) ( $post->post_password ?? '' ) ) {
			return null;
		}
		$language = \MeTransfers\I18n\Language::detectFromUri( $request );
		$prefix   = 'en' === $language && UrlPolicy::eligibleTarget( $target, 'en' ) ? '/en/' : '/';
		$query    = parse_url( $request, PHP_URL_QUERY );
		return $prefix . $target . '/' . ( is_string( $query ) && '' !== $query ? '?' . $query : '' );
	}
}
```

## 10. Código utilizado para preparar los cuerpos limpios

Archivo: `tools/prepare-blog-block-repair.php`. Genera un JSON de propuestas desde una copia autenticada; **no escribe en WordPress**. Las propuestas se aplicaron posteriormente con el MCP, leyendo y verificando cada entrada.

```php
<?php
declare(strict_types=1);
/**
 * Proposes block repairs from an authenticated raw post_content backup.
 * Never writes to WordPress. Usage:
 * php tools/prepare-blog-block-repair.php backup.json proposal.json 1000,1012,...
 */
if (PHP_SAPI !== 'cli') { exit; }
if ($argc < 4 || !is_file($argv[1])) {
    fwrite(STDERR, "Usage: php prepare-blog-block-repair.php backup.json proposal.json comma-separated-ids\n");
    exit(1);
}
function htmlRoot(string $html): array {
    $doc = new DOMDocument();
    $doc->loadHTML('<?xml encoding="utf-8"?><html><body><div id="audit-root">' . $html . '</div></body></html>', LIBXML_NONET);
    return [$doc, $doc->getElementById('audit-root')];
}
function references(DOMNode $root): array {
    $result = [];
    foreach (['a' => 'href', 'img' => 'src'] as $tag => $attribute) {
        foreach ($root->getElementsByTagName($tag) as $node) { $result[$tag . ':' . $node->getAttribute($attribute)] = true; }
    }
    ksort($result);
    return array_keys($result);
}
function wrapNode(DOMDocument $doc, DOMNode $node): string {
    $html = trim($doc->saveHTML($node));
    $name = $node->nodeName;
    if ($name === 'p') { $block = 'paragraph'; $attrs = ''; }
    elseif (preg_match('/^h([1-6])$/', $name, $matches)) {
        $block = 'heading'; $attrs = $matches[1] === '2' ? '' : ' ' . json_encode(['level' => (int) $matches[1]]);
    } elseif ($name === 'ul' || $name === 'ol') {
        $block = 'list'; $attrs = $name === 'ol' ? ' {"ordered":true}' : '';
        $html = preg_replace('/(<li(?:\s[^>]*)?>)/', '<!-- wp:list-item -->$1', $html);
        $html = str_replace('</li>', '</li><!-- /wp:list-item -->', $html);
    } elseif ($name === 'figure' && $node instanceof DOMElement && str_contains($node->getAttribute('class'), 'wp-block-image')) {
        $block = 'image'; $imageAttrs = [];
        if (preg_match('/(?:^|\s)size-([\w-]+)/', $node->getAttribute('class'), $size)) { $imageAttrs['sizeSlug'] = $size[1]; }
        if (preg_match('/(?:^|\s)align(left|right|center|wide|full)(?:\s|$)/', $node->getAttribute('class'), $align)) { $imageAttrs['align'] = $align[1]; }
        $image = $node->getElementsByTagName('img')->item(0);
        if ($image && preg_match('/\bwp-image-(\d+)\b/', $image->getAttribute('class'), $imageId)) { $imageAttrs['id'] = (int) $imageId[1]; }
        $attrs = $imageAttrs ? ' ' . json_encode($imageAttrs) : '';
    } elseif ($name === 'figure' && $node instanceof DOMElement && str_contains($node->getAttribute('class'), 'wp-block-table')) {
        $block = 'table'; $attrs = '';
    } else { throw new RuntimeException('Unsupported top-level node: ' . $name); }
    return '<!-- wp:' . $block . $attrs . " -->\n" . $html . "\n<!-- /wp:" . $block . ' -->';
}
libxml_use_internal_errors(true);
$snapshot = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
$eligible = isset($argv[3]) ? array_map('intval', explode(',', $argv[3])) : [];
$repairs = []; $refused = [];
foreach ($snapshot['posts'] as $post) {
    $raw = $post['content'];
    if (!str_contains($raw, 'wp:post-content') && !in_array($post['id'], $eligible, true)) { continue; }
    try {
        preg_match_all('/<!--\s*\/?wp:([\w-]+(?:\/[\w-]+)?)/', $raw, $blocks);
        $unexpected = array_diff(array_unique($blocks[1]), ['paragraph', 'heading', 'list', 'list-item', 'image', 'table', 'post-content']);
        if ($unexpected) { throw new RuntimeException('Other blocks present: ' . implode(', ', $unexpected)); }
        if (preg_match('/<(?:form|input|button|iframe|script)\b|\[[a-zA-Z][\w-]*(?:\s|\])/', $raw)) { throw new RuntimeException('Interactive HTML or shortcode present'); }
        $html = preg_replace('/<!--\s*\/?wp:[\s\S]*?-->/', '', $raw);
        [$doc, $root] = htmlRoot($html);
        $unique = []; $sourceCount = 0;
        foreach ($root->childNodes as $index => $node) {
            if ($node instanceof DOMText && trim($node->textContent) === '') { continue; }
            $sourceCount++;
            $outer = trim($doc->saveHTML($node));
            $signature = hash('sha256', preg_replace('/>\s+</', '><', $outer));
            // Last occurrences retain the order of the complete final article,
            // after the malformed nested copies prepended fragments to it.
            $unique[$signature] = ['index' => $index, 'node' => $node];
        }
        uasort($unique, fn($a, $b) => $a['index'] <=> $b['index']);
        $content = implode("\n\n", array_map(fn($item) => wrapNode($doc, $item['node']), array_values($unique)));
        [$cleanDoc, $cleanRoot] = htmlRoot(preg_replace('/<!--\s*\/?wp:[\s\S]*?-->/', '', $content));
        if (references($root) !== references($cleanRoot)) { throw new RuntimeException('Link or image reference set changed'); }
        $beforeText = [];
        foreach ($unique as $item) { $beforeText[] = trim(preg_replace('/\s+/u', ' ', $item['node']->textContent)); }
        $afterText = [];
        foreach ($cleanRoot->childNodes as $node) {
            if ($node instanceof DOMText && trim($node->textContent) === '') { continue; }
            $afterText[] = trim(preg_replace('/\s+/u', ' ', $node->textContent));
        }
        if ($beforeText !== $afterText) { throw new RuntimeException('Unique text changed'); }
        $repairs[] = ['id' => $post['id'], 'title' => $post['title'], 'expected_modified' => $post['modified'], 'expected_raw_sha256' => hash('sha256', $raw), 'old_characters' => mb_strlen($raw), 'new_characters' => mb_strlen($content), 'original_html_nodes' => $sourceCount, 'unique_html_nodes' => count($unique), 'references' => references($root), 'new_content' => $content];
    } catch (Throwable $error) { $refused[] = ['id' => $post['id'], 'error' => $error->getMessage()]; }
}
$result = ['source' => 'authenticated_raw_post_content', 'status' => 'prepared_not_applied', 'repairs' => $repairs, 'refused' => $refused];
file_put_contents($argv[2], json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo json_encode(['repairs' => count($repairs), 'refused' => $refused, 'metrics' => array_map(fn($r) => array_diff_key($r, ['new_content' => 1, 'references' => 1]), $repairs)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), "\n";
```

