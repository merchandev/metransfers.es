<?php
namespace MeTransfers\I18n;

final class Switcher {
	public function register() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueueAssets' ) );
	}

	public static function enqueueAssets() {
		if ( ! defined( 'MT_ACTIVE_LANGS' ) || count( MT_ACTIVE_LANGS ) <= 1 ) {
			return;
		}
		$version = defined( 'MT_PLATFORM_VERSION' ) ? MT_PLATFORM_VERSION : null;
		wp_enqueue_style( 'mt-i18n-switcher', get_template_directory_uri() . '/assets/css/i18n-switcher.css', array(), $version );
		wp_enqueue_script( 'mt-i18n-switcher', get_template_directory_uri() . '/assets/js/i18n-switcher.js', array(), $version, true );
		if ( function_exists( 'wp_script_add_data' ) ) {
			wp_script_add_data( 'mt-i18n-switcher', 'strategy', 'defer' );
		}
	}

	public static function render() {
		if ( ! defined( 'MT_ACTIVE_LANGS' ) || count( MT_ACTIVE_LANGS ) <= 1 ) {
			return;
		}
		$current     = Language::get();
		$info        = isset( MT_LANGS[ $current ] ) ? MT_LANGS[ $current ] : MT_LANGS['es'];
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		$slug        = Language::pathWithoutLanguage( $request_uri );
		?>
		<div class="mt-lang-switcher" id="mt-lang-switcher">
			<button type="button" class="mt-lang-trigger" aria-label="Cambiar idioma" aria-expanded="false" aria-controls="mt-lang-menu">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
				<span><?php echo esc_html( $info['label'] ); ?></span>
				<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
			</button>
			<nav class="mt-lang-menu" id="mt-lang-menu" aria-label="Selector de idioma">
				<button type="button" class="mt-lang-close" aria-label="Cerrar selector de idioma">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
				</button>
				<ul>
				<?php foreach ( MT_LANGS as $code => $language ) : ?>
					<?php
					if ( ! in_array( $code, MT_ACTIVE_LANGS, true ) ) {
						continue;
					}
					?>
					<li<?php echo $code === $current ? ' class="active"' : ''; ?>>
						<a href="<?php echo esc_url( Language::urlForLanguage( $code, $slug ) ); ?>"<?php echo $code === $current ? ' aria-current="page"' : ''; ?>>
							<span class="mt-lang-code"><?php echo esc_html( $language['label'] ); ?></span>
							<span class="mt-lang-name"><?php echo esc_html( $language['name'] ); ?></span>
							<?php
							if ( $code === $current ) :
								?>
								<span class="mt-lang-check" aria-hidden="true">&#10003;</span><?php endif; ?>
						</a>
					</li>
				<?php endforeach; ?>
				</ul>
			</nav>
		</div>
		<?php
	}
}
