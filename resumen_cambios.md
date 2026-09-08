# Resumen de Cambios (Septiembre 2026)

Este documento detalla las modificaciones recientes orientadas a mejorar el diseño, funcionalidad y experiencia de usuario en las páginas de reservas y catálogo de rutas.

## 1. Corrección de Carga del Formulario de Reservas (WPTB)
- **Archivo:** `app/Core/Assets.php`
- **Cambio:** Se modificó la función `booking_phase()` para incluir una condición que detecta si el slug o la URL original (i18n) contiene `traslados-`. Esto permite que los estilos y scripts del motor de reservas WPTB se carguen correctamente en las nuevas landing pages de traslados.

## 2. Rediseño y Mejora UX en el Catálogo de Rutas (`/rutas/`)
- **Archivos:** `archive-ruta.php`, `style.css`, `functions.php`
- **Ajuste del Hero:** Se eliminó la restricción de altura `min-height: 100svh` para la portada del catálogo, estableciéndola en `55vh`. Esto asegura que el buscador y el inicio del catálogo (estadísticas y destinos) queden visibles por encima del pliegue (above the fold).
- **Filtros Rápidos (Píldoras):** Se agregó una fila de botones rápidos interactivos (Costa Dorada, Costa Brava, Sitges, Andorra) justo debajo del buscador, estilizados con efecto *glassmorphism* (`backdrop-filter: blur`).
- **Auto-scroll Inteligente:** Se implementó lógica en JavaScript para que, al teclear la primera letra en el buscador, la página realice un scroll suave automáticamente hacia la grilla de resultados si el usuario aún no había bajado.
- **Rediseño de Tarjetas (`.ruta-card`):** Las tarjetas de rutas se modernizaron con bordes más finos, mayor padding, una sombra pronunciada al hacer hover (`box-shadow`), y una decoración superior animada mediante un gradiente corporativo (`transform: scaleX`).
- **Animaciones GSAP:** Se añadió la condición `is_post_type_archive('ruta')` en `functions.php` para que la librería GSAP se cargue en el catálogo de rutas, habilitando la animación fluida (`.gs-reveal`) de aparición de los elementos al hacer scroll.
