<?php
namespace MeTransfers\I18n;

use MeTransfers\Admin\AuditLog;
use MeTransfers\Admin\Capabilities;
use MeTransfers\Core\Settings;

final class Admin {
	public function register() {
		add_action( 'admin_menu', array( __CLASS__, 'addMenu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueueAssets' ) );
	}

	public static function addMenu() {
		add_options_page(
			'Traducción MeTransfers',
			'Traducción MT',
			Capabilities::MANAGE_INTEGRATIONS,
			'mt-i18n-settings',
			array( __CLASS__, 'render' )
		);
	}

	public static function enqueueAssets( $hook ) {
		if ( 'settings_page_mt-i18n-settings' !== $hook ) {
			return;
		}
		$version = defined( 'MT_PLATFORM_VERSION' ) ? MT_PLATFORM_VERSION : null;
		wp_enqueue_style( 'mt-i18n-admin', get_template_directory_uri() . '/assets/css/i18n-admin.css', array(), $version );
	}

	public static function render() {
		if ( ! current_user_can( Capabilities::MANAGE_INTEGRATIONS ) ) {
			wp_die( esc_html__( 'No tienes permisos para gestionar traducciones.', 'me-transfers' ) );
		}

		$notice = null;
		if ( isset( $_POST['mt_save_settings'] ) ) {
			check_admin_referer( 'mt_i18n_save' );
			$key = isset( $_POST['mt_google_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['mt_google_api_key'] ) ) : '';
			if ( '' !== $key ) {
				update_option( 'mt_google_api_key', $key, false );
				$notice = array( 'success', 'Configuración guardada.' );
			}
		}

		if ( isset( $_POST['mt_prebuild_translations'] ) ) {
			check_admin_referer( 'mt_i18n_save' );
			// The site is written in Spanish; English is the only translation.
			$sources          = Translation::sourceCatalog();
			$translated       = Translation::remoteBatch( $sources, 'en', 'es' );
			$translated_count = count( $translated );
			$notice_type      = count( $sources ) === $translated_count ? 'success' : ( 0 < $translated_count ? 'warning' : 'error' );
			$notice           = array( $notice_type, sprintf( '%d de %d textos del sitio se tradujeron al inglés.', $translated_count, count( $sources ) ) );
			AuditLog::record(
				'i18n.catalog_prebuilt',
				'language',
				0,
				array(
					'language' => 'en',
					'count'    => $translated_count,
					'total'    => count( $sources ),
				)
			);
		}

		if ( isset( $_POST['mt_test_api'] ) ) {
			check_admin_referer( 'mt_i18n_save' );
			$to_english = Translation::remoteBatch( array( 'Hola mundo' ), 'en', 'es' );
			$to_spanish = Translation::remoteBatch( array( 'Hello world' ), 'es', 'en' );
			$ok         = isset( $to_english[0], $to_spanish[0] )
				&& 'Hola mundo' !== $to_english[0]
				&& 'Hello world' !== $to_spanish[0];
			$notice     = $ok
				? array( 'success', sprintf( 'La API de traducción respondió en ambas direcciones: «Hola mundo» → «%s» y «Hello world» → «%s».', $to_english[0], $to_spanish[0] ) )
				: array( 'error', 'No se pudo validar la API de traducción (español → inglés e inglés → español).' );
			AuditLog::record( 'i18n.provider_tested', 'integration', 0, array( 'result' => $ok ? 'success' : 'failed' ) );
		}

		self::renderPage( $notice );
	}

	private static function renderPage( $notice ) {
		$configured = '' !== trim( (string) Settings::get( 'translation_api_key', '' ) );
		?>
		<div class="wrap mt-i18n-admin">
			<h1>Traducción MeTransfers — Sistema nativo</h1>
			<?php if ( $notice ) : ?>
				<div class="notice notice-<?php echo esc_attr( $notice[0] ); ?>"><p><?php echo esc_html( $notice[1] ); ?></p></div>
			<?php endif; ?>
			<form method="post">
				<?php wp_nonce_field( 'mt_i18n_save' ); ?>
				<table class="form-table">
					<tr>
						<th><label for="mt_google_api_key">Google Cloud API Key</label></th>
						<td>
							<input type="password" id="mt_google_api_key" name="mt_google_api_key" value="" class="regular-text" autocomplete="new-password" placeholder="Dejar vacío para conservar la actual" />
							<p class="description"><?php echo $configured ? 'Hay una clave configurada.' : 'No hay una clave configurada.'; ?> Se envía en header y nunca se muestra en el HTML.</p>
						</td>
					</tr>
				</table>
				<div class="mt-i18n-actions">
					<?php submit_button( 'Guardar API Key', 'primary', 'mt_save_settings', false ); ?>
					<?php submit_button( 'Probar API ahora', 'secondary', 'mt_test_api', false ); ?>
					<?php submit_button( 'Pre-generar catálogo en inglés', 'secondary', 'mt_prebuild_translations', false ); ?>
				</div>
				<p class="description">El sitio se escribe en español de España. El traductor solo trabaja español → inglés e inglés → español; no hay ningún otro idioma.</p>
			</form>
			<hr>
			<h2>Estado de idiomas</h2>
			<table class="widefat mt-i18n-language-table">
				<thead><tr><th>Idioma</th><th>URL</th><th>SEO</th></tr></thead>
				<tbody>
				<?php foreach ( MT_LANGS as $code => $language ) : ?>
					<?php $url = Language::urlForLanguage( $code ); ?>
					<tr>
						<td><?php echo esc_html( $language['label'] . ' ' . $language['name'] ); ?></td>
						<td><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $url ); ?></a></td>
						<td><?php echo in_array( $code, MT_SEO_LANGS, true ) ? 'index' : 'noindex,follow'; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
