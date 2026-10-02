# Redirecciones antiguas del blog en español e inglés

**Estado final: aplicadas en producción el 1–2 de octubre de 2026 y verificadas sobre 866 URLs.**

## Solución aplicada

Se utilizó **Yoast SEO Premium**, ya instalado, con el método **PHP**. El método anterior de servidor y archivo separado guardaba las reglas, pero no las ejecutaba públicamente. No fue necesario editar el tema activo ni la configuración de SiteGround.

Los 140 slugs se cambiaron mediante el MCP de WordPress. Las reglas del gestor se contrastaron y se comprobaron las URLs antiguas y nuevas en ambos idiomas, con y sin parámetros de campaña.

- 560 URLs antiguas: 301 al destino correcto.
- 280 URLs nuevas: 200.
- 18 URLs de artículos que conservaron su slug: 200.
- 8 páginas de control de ventas: 200.
- 149 canonical correctos, robots conservados y 149 entradas en el sitemap.
- Ninguna regla temporal 302 restante.
- 12 reglas históricas ajenas a esta migración conservadas.

El inventario está en [BLOG-URLS-FINAL-2026-10-02.csv](BLOG-URLS-FINAL-2026-10-02.csv). El mapa de reglas aplicado es [REDIRECCIONES-BLOG-2026-10-02.csv](REDIRECCIONES-BLOG-2026-10-02.csv). El reporte completo y el código están en [RESULTADO-BLOG-MCP-2026-10-01.md](RESULTADO-BLOG-MCP-2026-10-01.md).

## Patrones de las reglas

Para slugs que empiezan por letras, una regla exacta conserva el prefijo inglés y la cadena de consulta:

```text
Origen: ^/?(en/)?slug-antiguo/?(\?.*)?$
Destino: /$1slug-nuevo/$2
Tipo: 301
```

La entrada de prueba 29746 utiliza la regla de parámetros con dos capturas y sendas reglas simples para las URLs sin parámetros:

```text
Origen: ^/?(en/)?lonjas-de-pescado-en-la-costa-de-cataluna/?\?(.*)$
Destino: /$1como-escoger-el-mejor-servicio-de-transfer-en-barcelona-guia-completa/?$2
Tipo: 301
```

Para el artículo cuyo slug empieza por **10-**, se usan reglas independientes. Así los dígitos no se concatenan con una referencia de captura:

```text
Origen ES: ^/?tour-privado-por-los-pueblos-medievales-de-cataluna-desde-barcelona/?(\?.*)?$
Destino ES: /10-consejos-para-elegir-el-mejor-servicio-de-traslado-en-barcelona/$1
Origen EN: ^/?en/tour-privado-por-los-pueblos-medievales-de-cataluna-desde-barcelona/?(\?.*)?$
Destino EN: /en/10-consejos-para-elegir-el-mejor-servicio-de-traslado-en-barcelona/$1
Tipo: 301
```

La primera verificación encontró una concatenación ambigua en ese caso. Las seis URLs afectadas se repitieron después de corregirlo y pasaron. Se preserva el resultado inicial y el resultado final en la copia externa de seguridad.

En 138 artículos se conservaron también las reglas inglesas explícitas, convertidas de protección temporal a 301. Sus destinos coinciden con los de las reglas de ambos idiomas. El total actual es **153 reglas simples y 278 regex**.

La importación CSV de Yoast añade reglas nuevas y omite los orígenes ya existentes. Para corregir una regla guardada se utilizó su editor; repetir el CSV no sustituye esa edición. Véase la [documentación oficial de importación de Yoast](https://yoast.com/help/import-redirects/).

## Alternativa de código para futuros cambios

El router del tema usa `mt_lang` y `mt_page`; la redirección nativa de slugs antiguos exige `name`. Por eso los enlaces ingleses antiguos no funcionaban con la redirección nativa. La condición está en el [código oficial de WordPress](https://developer.wordpress.org/reference/functions/wp_old_slug_redirect/).

El fallback de `app/SEO/BlogSlugRedirects.php` consulta `_wp_old_slug` ante un 404 inglés real y preserva idioma y parámetros. Rechaza destinos ambiguos, privados o con contraseña y excluye reservas, pagos, administración y rutas anidadas.

**Este fallback está integrado y probado en el repositorio; no se instaló en el tema de producción.** El editor de archivos del tema denegó el acceso. Mientras no se despliegue, los futuros cambios de permalink requieren mantener su redirección inglesa en Yoast.

La validación de los PR [64](https://github.com/merchandev/metransfers.es/pull/64) y [65](https://github.com/merchandev/metransfers.es/pull/65) aprobó los ocho controles de CI, incluidas tres versiones reales de WordPress. La redirección no aprueba traducciones ni cambia las directivas robots.

## Código del fallback

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
