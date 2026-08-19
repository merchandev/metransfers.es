# Fase 9 — Modularización i18n

## Componentes

El antiguo `includes/i18n.php` de 842 líneas queda como una fachada pequeña que conserva las funciones públicas existentes. Las responsabilidades pasan a:

- `Language`: detección, locale, atributo `lang` y construcción de URLs;
- `Router`: rewrite rules, rutas virtuales, archivos de blog/rutas y selección de template;
- `Translation`: lectura cache-only pública y pre-generación administrativa;
- `Switcher`: HTML accesible y carga de assets;
- `Seo`: canonical de Yoast, canonical fallback y hreflang aprobado;
- `Admin`: clave write-only, prueba del proveedor y pre-generación auditada.

No cambian `MT_LANGS`, `MT_ACTIVE_LANGS`, `MT_SEO_LANGS`, los prefijos ni los slugs españoles. `functions.php` deja de poseer el router especial de `/LANG/rutas/`.

## Templates y assets

El router ya no hace `include` seguido de `exit` en `template_redirect`. Prepara el contexto compatible y entrega el archivo mediante `template_include`, permitiendo que el ciclo normal de WordPress continúe.

El selector de idioma usa:

- `assets/css/i18n-switcher.css`;
- `assets/js/i18n-switcher.js`;
- `assets/css/i18n-admin.css`.

No quedan bloques `<style>`/`<script>` en la fachada i18n. El JavaScript corrige además el foco involuntario que ocurría al hacer clic en cualquier parte de una página con el menú cerrado.

## SEO y secretos

- El canonical de Yoast conserva español y usa la ruta traducida sin query string.
- `hreflang` solo se emite para idiomas incluidos en `MT_SEO_LANGS`; `x-default` apunta a español y chino usa `zh-Hans`.
- Los idiomas no aprobados continúan en `noindex,follow` mediante el gate existente.
- `MT_TRANSLATION_API_KEY`/`mt_google_api_key` se resuelven por `Settings`, no se imprimen y se guardan con `autoload=no`.
- Google Translation recibe la clave en `X-Goog-Api-Key`, no en la URL.

## Verificación

```bash
php tests/test-i18n.php
php tests/test-i18n-routing.php
```

Las pruebas cubren prefijos válidos/inválidos, home, rutas anidadas, archivo de rutas, 404 contractual, URLs localizadas, canonical Yoast, hreflang, x-default, assets externos y manejo de la API key.

La revisión humana de contenido, 404, sitemap y hreflang recíproco en staging sigue siendo un gate externo antes de añadir idiomas a `MT_SEO_LANGS`.
