<?php
/**
 * Sistema de Traduccion Nativo - MeTransfers
 * @package Me_Transfers
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// =================================================================
// 1. CONFIGURACION DE IDIOMAS
// =================================================================

if ( ! defined('MT_LANGS') ) {
    define( 'MT_LANGS', [
        'es' => [ 'label' => 'ES', 'name' => 'Español',   'google_code' => 'es' ],
        'en' => [ 'label' => 'EN', 'name' => 'English',   'google_code' => 'en' ],
        'fr' => [ 'label' => 'FR', 'name' => 'Français',  'google_code' => 'fr' ],
        'de' => [ 'label' => 'DE', 'name' => 'Deutsch',   'google_code' => 'de' ],
        'it' => [ 'label' => 'IT', 'name' => 'Italiano',  'google_code' => 'it' ],
        'pt' => [ 'label' => 'PT', 'name' => 'Português', 'google_code' => 'pt' ],
        'ca' => [ 'label' => 'CA', 'name' => 'Català',    'google_code' => 'ca' ],
        'ru' => [ 'label' => 'RU', 'name' => 'Русский',   'google_code' => 'ru' ],
        'zh' => [ 'label' => 'ZH', 'name' => '中文',       'google_code' => 'zh-CN' ],
        'ja' => [ 'label' => 'JA', 'name' => '日本語',     'google_code' => 'ja' ],
    ] );
}

if ( ! defined('MT_ACTIVE_LANGS') ) {
    // All languages active. Translation via Google Cloud Translate (mt_google_api_key option).
    // Without an API key, content is shown in Spanish (safe fallback, no errors).
    define( 'MT_ACTIVE_LANGS', [ 'es', 'en', 'fr', 'de', 'it', 'pt', 'ca', 'ru', 'zh', 'ja' ] );
}

if ( ! defined( 'MT_SEO_LANGS' ) ) {
    define(
        'MT_SEO_LANGS',
        array(
            'es',
        )
    );
}

// =================================================================
// 2. DETECTAR IDIOMA ACTUAL DESDE LA URL
// =================================================================

function mt_get_current_lang(): string {
    $path = trim( parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH ), '/' );
    $first_segment = explode( '/', $path )[0] ?? '';
    if ( array_key_exists( $first_segment, MT_LANGS ) && $first_segment !== 'es' ) {
        return $first_segment;
    }
    return 'es';
}

$GLOBALS['mt_current_lang'] = mt_get_current_lang();

function mt_lang(): string {
    return $GLOBALS['mt_current_lang'];
}

function mt_is_translated(): bool {
    return mt_lang() !== 'es';
}


// =================================================================
// 3. REWRITE RULES
// =================================================================

add_action( 'init', function() {
    $lang_keys = array_filter( array_keys( MT_LANGS ), fn($l) => $l !== 'es' );
    $lang_pattern = implode( '|', $lang_keys );

    add_rewrite_rule(
        '^(' . $lang_pattern . ')/?$',
        'index.php?mt_lang=$matches[1]&mt_page=home',
        'top'
    );
    add_rewrite_rule(
        '^(' . $lang_pattern . ')/(.+?)/?$',
        'index.php?mt_lang=$matches[1]&mt_page=$matches[2]',
        'top'
    );
}, 5 );

add_filter( 'query_vars', function( $vars ) {
    $vars[] = 'mt_lang';
    $vars[] = 'mt_page';
    return $vars;
} );

add_action( 'after_switch_theme', function() { flush_rewrite_rules(); } );

add_action( 'init', function() {
    // Bump this string whenever you add/remove languages to force a rules flush
    $i18n_version = 'v2-ca-ru-zh-ja-nested';
    if ( get_option('mt_i18n_rules_flushed') !== $i18n_version ) {
        flush_rewrite_rules();
        update_option( 'mt_i18n_rules_flushed', $i18n_version );
    }
}, 99 );


// =================================================================
// 4. INTERCEPTAR PLANTILLA PARA URLS TRADUCIDAS
// =================================================================

add_action( 'template_redirect', function() {
    $lang = get_query_var( 'mt_lang' );
    if ( ! $lang || ! array_key_exists( $lang, MT_LANGS ) ) return;

    $GLOBALS['mt_current_lang'] = $lang;
    $page = get_query_var( 'mt_page', 'home' );

    $template_map = [
        'home'                       => 'front-page.php',
        'aeropuerto-barcelona'       => 'template-servicio.php',
        'puerto-barcelona'           => 'template-servicio.php',
        'conductor-privado'          => 'template-servicio.php',
        'traslados-corporativos'     => 'template-servicio.php',
        'tours-privados'             => 'template-tours.php',
        'bodas-eventos'              => 'template-servicio.php',
        'flota'                      => 'template-flota.php',
    ];

    $is_valid = false;
    $original_post = null;

    if ( isset( $template_map[ $page ] ) ) {
        $is_valid = true;
    } else {
        // Soporte para URLs anidadas: /rutas/barcelona-salou/ o /destinos/salou/
        $post_id = url_to_postid( home_url( '/' . $page ) );
        if ( $post_id ) {
            $is_valid = true;
            $original_post = get_post( $post_id );
            
            $tpl = get_page_template_slug( $post_id );
            if ( $tpl && file_exists( get_template_directory() . '/' . $tpl ) ) {
                $template_map[ $page ] = $tpl;
            } elseif ( $original_post->post_type === 'ruta' ) {
                $template_map[ $page ] = 'single-ruta.php';
            } elseif ( $original_post->post_type === 'post' ) {
                $template_map[ $page ] = 'single.php';
            } else {
                $template_map[ $page ] = 'page.php';
            }
        }
    }

    if ( ! $is_valid ) {
        global $wp_query;
        $wp_query->set_404();
        status_header( 404 );
        nocache_headers();
        return; // Devuelve 404 real, evita soft 404
    }

    $template_file = $template_map[ $page ] ?? 'index.php';
    $full_path     = get_template_directory() . '/' . $template_file;

    if ( file_exists( $full_path ) ) {
        if ( $original_post ) {
            global $post, $wp_query;
            $post = $original_post;
            $wp_query->queried_object    = $original_post;
            $wp_query->queried_object_id = $original_post->ID;
            $wp_query->is_page           = ( $original_post->post_type === 'page' );
            $wp_query->is_singular       = true;
            $wp_query->is_single         = ( $original_post->post_type !== 'page' );
            setup_postdata( $post );
        } elseif ( in_array( $template_file, ['template-servicio.php', 'template-tours.php', 'template-flota.php'] ) ) {
            $fallback = get_page_by_path( $page );
            if ( $fallback ) {
                global $post, $wp_query;
                $post = $fallback;
                $wp_query->queried_object    = $fallback;
                $wp_query->queried_object_id = $fallback->ID;
                $wp_query->is_page           = true;
                $wp_query->is_singular       = true;
                setup_postdata( $post );
            }
        }
        status_header( 200 );
        include $full_path;
        exit;
    }
}, 1 );


// =================================================================
// 5. FUNCION DE TRADUCCION CON GOOGLE CLOUD (CACHE EN DB)
// =================================================================

function mt_translate( string $text, string $lang = '' ): string {
    // If a fallback string (e.g. "Contact") was passed instead of a language code, ignore it
    if ( ! $lang || ! isset( MT_LANGS[ $lang ] ) ) {
        $lang = mt_lang();
    }
    
    if ( $lang === 'es' || trim( $text ) === '' ) return $text;

    $cache_key = 'mt_tr_' . $lang . '_' . md5( $text );
    $cached    = wp_cache_get( $cache_key, 'mt_i18n' );
    if ( $cached === false ) {
        $cached = get_option( $cache_key, null );
        if ( $cached !== null ) {
            wp_cache_set( $cache_key, $cached, 'mt_i18n', 3600 );
        }
    }
    if ( $cached !== null && $cached !== false ) return $cached;

    $api_key = get_option( 'mt_google_api_key', '' );
    if ( empty( $api_key ) ) return $text;

    $response = wp_remote_post(
        'https://translation.googleapis.com/language/translate/v2?key=' . $api_key,
        [
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( [
                'q'      => [ $text ],
                'source' => 'es',
                'target' => MT_LANGS[ $lang ]['google_code'],
                'format' => 'html',
            ] ),
            'timeout' => 10,
        ]
    );

    if ( is_wp_error( $response ) ) return $text;

    $body       = json_decode( wp_remote_retrieve_body( $response ), true );
    $translated = $body['data']['translations'][0]['translatedText'] ?? null;

    if ( $translated ) {
        $decoded = html_entity_decode( $translated, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        update_option( $cache_key, $decoded, false );
        wp_cache_set( $cache_key, $decoded, 'mt_i18n', 3600 );
        return $decoded;
    }
    return $text;
}

function mt_translate_batch( array $texts, string $lang = '' ): array {
    if ( ! $lang ) $lang = mt_lang();
    if ( $lang === 'es' || empty( $texts ) ) return $texts;

    $api_key      = get_option( 'mt_google_api_key', '' );
    $results      = [];
    $to_translate = [];
    $cache_keys   = [];

    foreach ( $texts as $i => $text ) {
        $cache_key = 'mt_tr_' . $lang . '_' . md5( $text );
        $cached    = wp_cache_get( $cache_key, 'mt_i18n' );
        if ( $cached === false ) {
            $cached = get_option( $cache_key, null );
            if ( $cached !== null ) {
                wp_cache_set( $cache_key, $cached, 'mt_i18n', 3600 );
            }
        }
        if ( $cached !== null && $cached !== false ) {
            $results[ $i ] = $cached;
        } elseif ( $api_key ) {
            $to_translate[ $i ] = $text;
            $cache_keys[ $i ]   = $cache_key;
        } else {
            $results[ $i ] = $text;
        }
    }

    if ( ! empty( $to_translate ) && $api_key ) {
        $chunks = array_chunk( $to_translate, 100, true );
        foreach ( $chunks as $chunk ) {
            $response = wp_remote_post(
                'https://translation.googleapis.com/language/translate/v2?key=' . $api_key,
                [
                    'headers' => [ 'Content-Type' => 'application/json' ],
                    'body'    => wp_json_encode( [
                        'q'      => array_values( $chunk ),
                        'source' => 'es',
                        'target' => MT_LANGS[ $lang ]['google_code'],
                        'format' => 'html',
                    ] ),
                    'timeout' => 15,
                ]
            );

            if ( ! is_wp_error( $response ) ) {
                $body         = json_decode( wp_remote_retrieve_body( $response ), true );
                $translations = $body['data']['translations'] ?? [];
                $keys         = array_keys( $chunk );
                foreach ( $translations as $j => $tr ) {
                    $idx     = $keys[ $j ];
                    $decoded = html_entity_decode( $tr['translatedText'], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                    $results[ $idx ] = $decoded;
                    update_option( $cache_keys[ $idx ], $decoded, false );
                    wp_cache_set( $cache_keys[ $idx ], $decoded, 'mt_i18n', 3600 );
                }
            } else {
                foreach ( $chunk as $i => $t ) { $results[ $i ] = $t; }
            }
        }
    }

    ksort( $results );
    return $results;
}


// =================================================================
// 6. SELECTOR DE IDIOMA (HTML)
// =================================================================

function gct_render_language_switcher(): void {
    $current_lang = mt_lang();
    $info         = MT_LANGS[ $current_lang ];
    $path         = trim( parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH ), '/' );
    $segments     = explode( '/', $path );
    $is_trans     = array_key_exists( $segments[0], MT_LANGS ) && $segments[0] !== 'es';
    $slug         = $is_trans ? implode( '/', array_slice( $segments, 1 ) ) : $path;
    ?>
    <div class="mt-lang-switcher" id="mt-lang-switcher">
        <button type="button" class="mt-lang-trigger" aria-label="Cambiar idioma" aria-expanded="false" aria-controls="mt-lang-menu">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
            <span><?php echo esc_html( $info['label'] ); ?></span>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <nav class="mt-lang-menu" id="mt-lang-menu" aria-label="Selector de idioma">
            <!-- Close button for mobile -->
            <button type="button" class="mt-lang-close" aria-label="Cerrar selector de idioma">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
            <ul>
        <?php foreach ( MT_LANGS as $code => $lang_info ) :
            if ( ! in_array( $code, MT_ACTIVE_LANGS, true ) ) continue;
            $url = ( $code === 'es' )
                ? home_url( '/' . ( $slug ? $slug . '/' : '' ) )
                : home_url( '/' . $code . '/' . ( $slug ? $slug . '/' : '' ) );
        ?>
            <li <?php echo ( $code === $current_lang ) ? 'class="active"' : ''; ?>>
                <a href="<?php echo esc_url( $url ); ?>">
                    <span class="mt-lang-code"><?php echo esc_html( $lang_info['label'] ); ?></span>
                    <span class="mt-lang-name"><?php echo esc_html( $lang_info['name'] ); ?></span>
                    <?php if ( $code === $current_lang ) : ?><span class="mt-lang-check" aria-label="Idioma activo">&#10003;</span><?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
            </ul>
        </nav>
    </div>
    <?php
}


// =================================================================
// 7. CSS + JS DEL SELECTOR
// =================================================================

add_action( 'wp_head', function() { ?>
<style id="mt-lang-css">
.mt-lang-switcher{position:relative;display:inline-flex;align-items:center}
.mt-lang-trigger{display:inline-flex;align-items:center;gap:.4rem;height:40px;padding:0 .9rem;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.15);border-radius:8px;color:#fff;font-size:.85rem;font-weight:600;cursor:pointer;transition:background .2s,border-color .2s;letter-spacing:.03em;white-space:nowrap}
.mt-lang-trigger:hover{background:rgba(255,255,255,.15);border-color:rgba(255,255,255,.3)}
.mt-lang-trigger[aria-expanded=true]{background:rgba(255,255,255,.15)}

/* DRAWER (DISEÑO MAIN MENU) */
.mt-lang-menu {
    position: fixed !important;
    top: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: min(320px, 85vw) !important;
    height: 100% !important;
    background: #004e9a !important; /* Azul del menú principal */
    border-left: 1px solid rgba(255, 255, 255, 0.12) !important;
    z-index: 999999 !important;
    transform: translateX(100%) !important;
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
    overflow-y: auto !important;
    display: flex !important;
    flex-direction: column !important;
    visibility: hidden !important;
    padding: 4.5rem 1.5rem 2rem !important;
    margin: 0 !important;
    list-style: none !important;
    box-shadow: -10px 0 30px rgba(0,0,0,0.5) !important;
}

.mt-lang-menu.open {
    transform: translateX(0) !important;
    visibility: visible !important;
}

.mt-lang-menu li {
    margin-bottom: 0.15rem !important;
}

.mt-lang-menu li a {
    display: flex !important;
    align-items: center !important;
    padding: 0.8rem 0.9rem !important;
    font-size: 1rem !important;
    font-weight: 600 !important;
    color: rgba(255, 255, 255, 0.92) !important;
    border-radius: 10px !important;
    text-decoration: none !important;
    transition: background 0.2s, color 0.2s !important;
    border: none !important;
}

.mt-lang-menu li a:hover,
.mt-lang-menu li a:focus {
    background: rgba(255, 255, 255, 0.12) !important;
    color: #ffffff !important;
    outline: none !important;
}

.mt-lang-menu li.active a {
    background: rgba(255, 255, 255, 0.2) !important;
    color: #ffffff !important;
    font-weight: 700 !important;
}

.mt-lang-code {
    font-weight: 700 !important;
    font-size: 0.8rem !important;
    background: rgba(255, 255, 255, 0.15) !important;
    padding: 2px 6px !important;
    border-radius: 4px !important;
    min-width: 2rem !important;
    text-align: center !important;
    flex-shrink: 0 !important;
    margin-right: 0.75rem !important;
}

.mt-lang-check {
    margin-left: auto !important;
    font-size: 0.85rem !important;
    color: #fff !important;
}

.mt-lang-close {
    display: flex !important;
    position: absolute !important;
    top: 1.25rem !important;
    right: 1.25rem !important;
    align-items: center !important;
    justify-content: center !important;
    width: 40px !important;
    height: 40px !important;
    color: #fff !important;
    background: rgba(255,255,255,0.1) !important;
    border-radius: 50% !important;
    cursor: pointer !important;
    transition: background 0.2s !important;
    z-index: 10 !important;
}

.mt-lang-close:hover {
    background: rgba(255,255,255,0.2) !important;
}

.mt-lang-backdrop {
    position: fixed !important;
    inset: 0 !important;
    background: rgba(0, 0, 0, 0.65) !important;
    backdrop-filter: blur(5px) !important;
    -webkit-backdrop-filter: blur(5px) !important;
    z-index: 999998 !important;
    opacity: 0 !important;
    visibility: hidden !important;
    transition: opacity 0.32s, visibility 0.32s !important;
}

.mt-lang-backdrop.open {
    opacity: 1 !important;
    visibility: visible !important;
}

@media (max-width: 991px) {
    .mt-lang-trigger span { display: none !important; }
    .mt-lang-trigger { padding: 0 0.6rem !important; }
}
</style>
<?php }, 5 );

add_action( 'wp_footer', function() { ?>
<script id="mt-lang-js">
(function(){
    var sw=document.getElementById('mt-lang-switcher');
    if(!sw)return;
    var btn=sw.querySelector('.mt-lang-trigger');
    var menu=sw.querySelector('.mt-lang-menu');
    if(!btn||!menu)return;
    
    // Mover el menú al body para evitar que sea recortado por el header
    document.body.appendChild(menu);
    
    // Crear el backdrop en el body
    var backdrop = document.createElement('div');
    backdrop.className = 'mt-lang-backdrop';
    document.body.appendChild(backdrop);
    
    var closeBtn=menu.querySelector('.mt-lang-close');
    
    function close(){
        sw.classList.remove('open');
        menu.classList.remove('open');
        backdrop.classList.remove('open');
        btn.setAttribute('aria-expanded','false');
        document.body.style.overflow = '';
    }
    btn.addEventListener('click',function(e){
        e.stopPropagation();
        var isOpen = sw.classList.contains('open');
        if(isOpen) {
            close();
        } else {
            sw.classList.add('open');
            menu.classList.add('open');
            backdrop.classList.add('open');
            btn.setAttribute('aria-expanded','true');
            if(window.innerWidth <= 991) { document.body.style.overflow = 'hidden'; }
        }
    });
    if(closeBtn) closeBtn.addEventListener('click', close);
    backdrop.addEventListener('click', close);
    document.addEventListener('click',close);
    document.addEventListener('keydown',function(e){if(e.key==='Escape')close();});
    menu.addEventListener('click', function(e){ e.stopPropagation(); });
})();
</script>
<?php } );


// =================================================================
// 8. SEO: hreflang + canonical
// =================================================================

// 1. Sobrescribir el Canonical de Yoast SEO para las rutas traducidas virtuales
add_filter( 'wpseo_canonical', function( $canonical ) {
    if ( ! mt_is_translated() ) {
        return $canonical;
    }
    $path = wp_parse_url(
        wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ),
        PHP_URL_PATH
    );
    return home_url( $path );
} );

// 2. Inyectar etiquetas Hreflang y Canonical (si no hay Yoast)
add_action( 'wp_head', function() {
    $path     = trim( parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH ), '/' );
    $segments = explode( '/', $path );
    $is_trans = array_key_exists( $segments[0] ?? '', MT_LANGS ) && $segments[0] !== 'es';
    $slug     = $is_trans ? implode( '/', array_slice( $segments, 1 ) ) : $path;
    
    // Si Yoast no está activo, aseguramos que la página traducida tenga su canonical
    if ( ! defined( 'WPSEO_VERSION' ) && mt_is_translated() ) {
        $path = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ), PHP_URL_PATH );
        echo '<link rel="canonical" href="' . esc_url( home_url( $path ) ) . '" />' . "\n";
    }

    // Inyectar hreflang para decirle a Google que son variantes de idioma y no duplicados
    $seo_langs = defined( 'MT_SEO_LANGS' ) ? MT_SEO_LANGS : array( 'es' );
    
    if ( count( $seo_langs ) > 1 ) {
        foreach ( $seo_langs as $code ) {
            if ( ! isset( MT_LANGS[ $code ] ) ) {
                continue;
            }

            $url = ( $code === 'es' )
                ? home_url( '/' . ( $slug ? $slug . '/' : '' ) )
                : home_url( '/' . $code . '/' . ( $slug ? $slug . '/' : '' ) );
                
            // Ajuste para el código de idioma chino (zh)
            $hreflang = ( $code === 'zh' ) ? 'zh-Hans' : $code; 
            echo '<link rel="alternate" hreflang="' . esc_attr( $hreflang ) . '" href="' . esc_url( $url ) . '" />' . "\n";
        }
        
        // x-default siempre apunta a la versión en español
        echo '<link rel="alternate" hreflang="x-default" href="' . esc_url( home_url( '/' . ( $slug ? $slug . '/' : '' ) ) ) . '" />' . "\n";
    }
}, 2 );


// =================================================================
// 9. ADMIN - AJUSTES
// =================================================================

add_action( 'admin_menu', function() {
    add_options_page(
        'Traduccion MeTransfers',
        'Traduccion MT',
        'manage_options',
        'mt-i18n-settings',
        'mt_i18n_settings_page'
    );
} );

function mt_i18n_settings_page(): void {
    if ( ! current_user_can( 'manage_options' ) ) return;

    if ( isset( $_POST['mt_save_settings'] ) && check_admin_referer( 'mt_i18n_save' ) ) {
        update_option( 'mt_google_api_key', sanitize_text_field( $_POST['mt_google_api_key'] ?? '' ) );
        echo '<div class="notice notice-success"><p>Configuracion guardada.</p></div>';
    }

    // Handle API test
    $test_result = null;
    if ( isset( $_POST['mt_test_api'] ) && check_admin_referer( 'mt_i18n_save' ) ) {
        $api_key = get_option( 'mt_google_api_key', '' );
        if ( $api_key ) {
            $response = wp_remote_post(
                'https://translation.googleapis.com/language/translate/v2?key=' . $api_key,
                [
                    'headers' => [ 'Content-Type' => 'application/json' ],
                    'body'    => wp_json_encode( [ 'q' => ['Hola mundo'], 'source' => 'es', 'target' => 'en', 'format' => 'text' ] ),
                    'timeout' => 10,
                ]
            );
            if ( is_wp_error( $response ) ) {
                $test_result = [ 'ok' => false, 'msg' => 'Error de conexion: ' . $response->get_error_message() ];
            } else {
                $body = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( isset( $body['data']['translations'][0]['translatedText'] ) ) {
                    $test_result = [ 'ok' => true, 'msg' => '"Hola mundo" → "' . $body['data']['translations'][0]['translatedText'] . '"' ];
                } else {
                    $error_msg = $body['error']['message'] ?? wp_remote_retrieve_body( $response );
                    $test_result = [ 'ok' => false, 'msg' => 'Error de API: ' . $error_msg ];
                }
            }
        } else {
            $test_result = [ 'ok' => false, 'msg' => 'No hay API Key configurada.' ];
        }
    }

    $api_key = get_option( 'mt_google_api_key', '' );
    ?>
    <div class="wrap">
        <h1>Traduccion MeTransfers - Sistema Nativo</h1>
        <form method="post">
            <?php wp_nonce_field( 'mt_i18n_save' ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="mt_google_api_key">Google Cloud API Key</label></th>
                    <td>
                        <input type="text" id="mt_google_api_key" name="mt_google_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" placeholder="AIzaSy..." />
                        <p class="description">Obtela en <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a> habilitando Cloud Translation API.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button( 'Guardar API Key', 'primary', 'mt_save_settings', false ); ?>
            &nbsp;&nbsp;
            <?php submit_button( 'Probar API ahora', 'secondary', 'mt_test_api', false ); ?>
        </form>

        <?php if ( $test_result !== null ) : ?>
        <div class="notice notice-<?php echo $test_result['ok'] ? 'success' : 'error'; ?>" style="margin-top:12px">
            <p><?php echo $test_result['ok'] ? '✅ API funciona: ' : '❌ Fallo: '; echo esc_html( $test_result['msg'] ); ?></p>
        </div>
        <?php endif; ?>

        <hr>
        <h2>Estado de idiomas</h2>
        <table class="widefat" style="max-width:500px">
            <thead><tr><th>Idioma</th><th>URL</th></tr></thead>
            <tbody>
            <?php foreach ( MT_LANGS as $code => $lang_info ) :
                $url = ( $code === 'es' ) ? home_url('/') : home_url("/{$code}/"); ?>
                <tr>
                    <td><?php echo esc_html( $lang_info['label'] . ' ' . $lang_info['name'] ); ?></td>
                    <td><a href="<?php echo esc_url( $url ); ?>" target="_blank"><?php echo esc_html( $url ); ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}