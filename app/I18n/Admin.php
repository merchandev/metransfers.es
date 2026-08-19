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
            $language = isset( $_POST['mt_prebuild_lang'] ) ? sanitize_key( wp_unslash( $_POST['mt_prebuild_lang'] ) ) : '';
            if ( isset( MT_LANGS[ $language ] ) && 'es' !== $language ) {
                $sources = array_values( \MeTransfers\Booking\I18n::sourceStrings() );
                $translated = Translation::remoteBatch( $sources, $language );
                $notice = array( 'success', sprintf( '%d de %d textos se guardaron para %s.', count( $translated ), count( $sources ), strtoupper( $language ) ) );
                AuditLog::record( 'i18n.catalog_prebuilt', 'language', 0, array( 'language' => $language, 'count' => count( $translated ) ) );
            }
        }

        if ( isset( $_POST['mt_test_api'] ) ) {
            check_admin_referer( 'mt_i18n_save' );
            $translated = Translation::remoteBatch( array( 'Hola mundo' ), 'en' );
            $ok = isset( $translated[0] ) && 'Hola mundo' !== $translated[0];
            $notice = $ok
                ? array( 'success', 'La API de traducción respondió correctamente.' )
                : array( 'error', 'No se pudo validar la API de traducción.' );
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
                    <select name="mt_prebuild_lang" aria-label="Idioma para pre-generar">
                        <?php foreach ( MT_LANGS as $code => $language ) : ?>
                            <?php if ( 'es' === $code ) { continue; } ?>
                            <option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $language['name'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php submit_button( 'Pre-generar catálogo booking', 'secondary', 'mt_prebuild_translations', false ); ?>
                </div>
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
