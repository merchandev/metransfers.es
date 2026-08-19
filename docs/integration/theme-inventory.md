# Theme Inventory — MeTransfers Platform Integration
**Fase:** 1 — Inventario | **Fecha:** 2026-08-19

> Este documento contiene el inventario exhaustivo del tema WordPress actual (`mt120826_merchandev`), enfocado en identificar los puntos de integracion, dependencias y conflictos potenciales con el plugin de reservas.

---

## 1. INFORMACION GENERAL

- **Directorio:** `mt120826_merchandev`
- **Version:** `4.3.11` (constante `ME_TRANSFERS_VERSION`)
- **Text Domain:** `me-transfers`
- **Archivo principal:** `functions.php` (206 KB, 2618 lineas)

---

## 2. ARQUITECTURA DE ARCHIVOS (functions.php includes)

El archivo `functions.php` delega funcionalidad en los siguientes archivos dentro de `includes/`:

1. `i18n.php` — Sistema multilenguaje propio
2. `destinations.php` — Catalogo estatico y formulario de destinos
3. `faq.php` — Preguntas frecuentes
4. `legal-pages.php` — Paginas legales
5. `seo-page-titles.php` — Gestion de titulos SEO
6. `tours.php` — Catalogo estatico de tours
7. `services.php` — Catalogo de servicios
8. `request-cpt.php` — Custom Post Type para peticiones
9. `tour-bookings.php` — CPT para reservas de tours
10. `rutas-cpt.php` — Custom Post Type para landing SEO de rutas
11. `leads-cpt.php` — Custom Post Type para contactos
12. `admin-content-repopulate.php` — Scripts de migracion (deshabilitados)
13. `auto-migration-v5.php` — Scripts de migracion (deshabilitados)
14. `admin-route-builder.php` — Constructor admin de rutas
15. `mt-seo-importer.php` (en raiz) — Importador SEO

---

## 3. HOOKS PRINCIPALES REGISTRADOS (functions.php)

### Acciones (add_action)
| Hook | Callback | Proposito |
|---|---|---|
| `template_redirect` | anonimo | Redirige 404 de destinos al anchor #destinos |
| `after_setup_theme` | `me_transfers_setup` | Soporte de tema, menus, html5 |
| `widgets_init` | `me_transfers_unregister_sidebars` | Elimina sidebars por defecto |
| `admin_menu` | `me_transfers_hide_menus_checkhoteles` | Oculta menus para rol 'check_hoteles' |
| `admin_init` | `me_transfers_restrict_checkhoteles_access` | Bloquea acceso admin a 'check_hoteles' |
| `wp_enqueue_scripts` | `me_transfers_scripts` | Carga de CSS y JS principal |
| `template_redirect` | `me_transfers_custom_redirects` | Motor 301/410 para URLs SEO/WooCommerce legacy |

### Filtros (add_filter)
| Hook | Callback | Proposito |
|---|---|---|
| `the_content` / `get_the_excerpt` | `me_transfers_strip_deprecated_shortcodes` | Elimina shortcode legacy `[mt_hero_card]` |
| `script_loader_tag` | anonimo | Agrega defer/async a scripts |
| `image_editor_output_format` | anonimo | Fuerza formato WebP |
| `wp_robots` | anonimo | noindex para staging, tags, search, author, date |
| `wpseo_*` (multiples) | multiples anonimos | Control granular de sitemaps y titles de Yoast SEO |

---

## 4. SISTEMA DE RUTAS (includes/rutas-cpt.php)

### Custom Post Type
- **Slug:** `ruta`
- **Label:** Rutas (SEO)
- **Archive:** `rutas`
- **REST API:** `show_in_rest` = true

### Post Meta (Detalles de la ruta)
- `_mt_ruta_origen`
- `_mt_ruta_destino`
- `_mt_ruta_duracion`
- `_mt_ruta_pax`
- `_mt_ruta_maletas`
- `_mt_ruta_precio`
- `_mt_ruta_h1`
- `_mt_seo_ready`

### Shortcode
- `[ruta_enlace id="" texto=""]` — Renderiza un enlace SEO optimizado a una ruta.

---

## 5. SISTEMA I18N (includes/i18n.php)

El tema no usa WPML/Polylang, sino un sistema propio basado en subdirectorios URL (`/en/`, `/fr/`) y traduccion on-the-fly via Google Cloud Translation API.

### Caracteristicas
- **Idiomas soportados:** es, en, fr, de, it, pt, ca, ru, zh, ja, ar
- **SEO Activo:** Solo `es` esta indexado (`MT_SEO_LANGS = ['es']`)
- **Traduccion Automatica:** `mt_translate()` usa API key de `get_option('mt_google_api_key')`
- **Cache:** Utiliza object cache y options DB (`mt_tr_{lang}_{md5}`)
- **Hooks:** Intercepta `the_content`, `the_title`, `the_excerpt` con prioridad 99.

---

## 6. CATALOGOS ESTATICOS (Destinos y Tours)

### Destinos (includes/destinations.php)
- Catalogo hardcodeado de destinos (Salou, Lloret de Mar, y 36 genericos).
- Formulario AJAX de solicitud de destino (`me_transfers_handle_destination_request`).

### Tours (includes/tours.php)
- Catalogo hardcodeado: Montserrat, Costa Brava, Girona, Barcelona.
- Generacion de URLs y paginas automatizada (actualmente deshabilitada).

---

## 7. ANALITICA DE BOTONES (Event Tracking)

- Crea tabla custom: `{prefix}mt_event_tracking`
- Trackea clicks en botones via AJAX (`mt_ajax_track_button_click`)
- Menu admin "Metricas Botones" (`mt-button-metrics`)

---

## 8. AJAX ENDPOINTS DEL TEMA

| Accion | Handler | Priv/Nopriv |
|---|---|---|
| `mt_save_lead` | `mt_ajax_save_lead` | Ambos |
| `mt_track_button_click` | `mt_ajax_track_button_click` | Ambos |
| `me_transfers_destination_request` | `me_transfers_handle_destination_request` | Ambos (via admin-post.php) |

---

## 9. CONFIGURACIONES (Options)

| Option Key | Proposito |
|---|---|
| `mt_theme_version` | Version para cache busting |
| `mt_google_api_key` | API Key de Google Translate |
| `admin_email` | Receptor de leads de contacto |
| `mt_*_migrated_v*` | Multiples flags de estado de migraciones pasadas |

---

## 10. RECURSOS ENCOLADOS (Assets)

- **Fuentes:** Google Fonts (Inter + Outfit)
- **Estilos:** `style.css`
- **Scripts:** `assets/js/main.js`
- **Terceros:** GSAP y ScrollTrigger (condicionales en front_page, template-tours.php o single ruta/tour)

---

## 11. ROLES Y PERMISOS

- Crea rol custom `check_hoteles`.
- Oculta dashboard widgets, menus de admin y contadores de posts para este rol (`me_transfers_hide_menus_checkhoteles`, `me_transfers_restrict_checkhoteles_access`).
- Esto se interseca con la funcionalidad del modulo "Hotel" del plugin.

---

## 12. LEGACY / DEUDA TECNICA IDENTIFICADA

1. **Migraciones Comentadas:** Gran cantidad de funciones de migracion (`me_transfers_migrate_*`) que estan comentadas/deshabilitadas. Deben ser movidas a un directorio `tools/` o eliminadas si ya se ejecutaron en produccion.
2. **WooCommerce Dead Code:** Existen multiples redirecciones 301/410 de URLs `/tienda-barcelona-tours-transfers/`, lo que indica que WooCommerce ya no se usa para la tienda principal (aunque el plugin de reservas lo usa como motor de caja oculto).
3. **Hardcoded Contact Data:** `info@metransfers.es`, telefono y NIF estan hardcodeados en el codigo o Schema.org JSON-LD en lugar de venir del Customizer/Options.

---

## 13. PUNTOS DE CONFLICTO CON PLUGIN

1. **Google Maps API:** El plugin lee `wptb_google_maps_api_key`, el tema lee `mt_google_api_key`. Debera unificarse.
2. **Roles (check_hoteles):** El tema define el rol y restringe vistas, el plugin de hoteles tambien depende de esta logica. Requiere coordinacion.
3. **Traducciones:** El plugin de reservas tiene textos en Espanol/Ingles (y traducciones en JS), mientras que el tema usa Google Cloud API en tiempo de ejecucion/renderizado. Las cadenas del formulario de reservas deberan integrarse en el sistema del tema sin causar cuellos de botella de API.
