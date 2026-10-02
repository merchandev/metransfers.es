# Corrección de las URLs antiguas del blog en inglés

Fecha de trabajo: 1 de octubre de 2026, hora del usuario. Estado: código probado localmente; instalación en producción pendiente.

## Problema confirmado

Una edición ordinaria del slug mediante el MCP de WordPress conserva la redirección nativa de la URL española, pero la URL antigua bajo `/en/` devuelve 404. La prueba de la entrada 29746 dio:

| Petición durante la prueba | Resultado |
|---|---|
| URL española antigua | 301 hacia el slug nuevo |
| URL española nueva | 200 y canonical nuevo |
| URL inglesa antigua | 404 |
| URL inglesa nueva | 200 |

La migración masiva de slugs se detuvo y se inició la restauración de las 39 URLs modificadas. Después de restaurar la entrada de prueba, sus URLs originales en español e inglés volvieron a responder 200. Las correcciones de contenido se conservan.

## Causa

El router del tema interpreta las URLs inglesas con las variables `mt_lang` y `mt_page`. La función nativa `wp_old_slug_redirect()` requiere la variable `name`; por eso no encuentra el historial del slug en esa ruta. La condición puede comprobarse en el [código oficial de WordPress](https://developer.wordpress.org/reference/functions/wp_old_slug_redirect/). El registro `_wp_old_slug` se crea al editar el permalink, pero el router de idiomas no lo consulta.

## Código preparado

La implementación está en `app/SEO/BlogSlugRedirects.php`. Amplía el manejador existente: cuando no hay una coincidencia en el mapa explícito, busca el antiguo slug en `_wp_old_slug` y conserva el prefijo `/en/` y la cadena de consulta.

Condiciones para redirigir:

- Petición GET o HEAD que WordPress ya ha determinado como 404.
- Ruta inglesa de un solo segmento; no coincide con rutas anidadas de reservas, administración o API.
- No existe una página o entrada con el slug solicitado.
- Existe exactamente una entrada publicada con ese slug antiguo.
- El destino es una entrada de blog publicada y sin contraseña.
- El slug de destino es distinto del origen.

El código no modifica aprobaciones de traducción, robots, canonical, reservas ni formularios. Visitar una URL inglesa sigue las reglas de indexación del tema. La redirección no equivale a aprobar o certificar la traducción del artículo.

## Instalación y verificación

1. Hacer una copia del archivo activo `app/SEO/BlogSlugRedirects.php` y comprobar que `app/Core/Application.php` registra esta clase. Si la versión instalada carece de ella, preparar el parche contra esa versión antes de subir archivos.
2. Reemplazar únicamente el manejador de redirecciones por el archivo preparado. No usar una actualización completa del tema para esta corrección aislada.
3. Cambiar una sola entrada de prueba, después de leer su estado actual y conservar una copia.
4. Verificar URLs antiguas y nuevas en español e inglés, con y sin parámetros de consulta. Exigir 301 correcto en las antiguas, 200 en las nuevas y ausencia de bucles.
5. Continuar con los 140 slugs del manifiesto solo cuando la prueba anterior pase.
6. Comprobar cada permalink y el sitemap final; conservar el diario de cambios y los archivos de restauración.

El MCP conectado permite editar entradas y campos SEO, pero no expone ninguna capacidad para modificar archivos del tema, instalar esta corrección o administrar redirecciones. Se abrió una pestaña del panel de WordPress para completar el acceso a los archivos mediante una sesión administrativa. El inicio de sesión está pendiente; no falta autorización del usuario.

## Pruebas locales

- PHPUnit: 107 pruebas, 947 aserciones; ninguna prueba fallida. El ejecutor informa una deprecación ya presente en su configuración.
- PHPStan: sin errores.
- PHPCS de los archivos PHP modificados: sin infracciones después del ajuste de formato.
- Casos añadidos: idioma y parámetros preservados; destinos inexistentes, ambiguos, privados o con contraseña rechazados; URLs existentes y rutas anidadas excluidas.

Estas pruebas locales no certifican que el archivo esté instalado en producción. La comprobación pública de las redirecciones debe repetirse después de instalarlo.

## Código completo preparado

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
