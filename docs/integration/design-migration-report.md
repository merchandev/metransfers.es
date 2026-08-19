# Design Migration Report
**Fase:** 9 — Unificacion Visual | **Fecha:** 2026-08-19

> **Objetivo:** Eliminar la guerra de estilos CSS entre el tema y el plugin, creando un Design System unificado donde el tema es la unica fuente visual de verdad.

---

## 1. SISTEMA DE DISEÑO (Design System)

Se han creado los siguientes archivos CSS dentro del tema (`assets/css/`):

- **`tokens.css`**: Contiene las variables CSS (custom properties) globales (`--mt-color-primary`, `--mt-font-body`, etc.) para unificar colores, tipografías y espaciados.
- **`components.css`**: Estilos base para componentes reutilizables (formularios `.mt-form`, inputs `.mt-input`, botones `.mt-button` y tarjetas `.mt-card`).
- **`booking.css`**: Estilos específicos para la vista de selección de vehículos y el resumen pegajoso (sticky summary).
- **`checkout.css`**: Layout CSS Grid específico para la página de datos de cliente y pago.

Estos archivos han sido registrados y encolados en `functions.php`.

---

## 2. ELIMINACIÓN DE CARGA GLOBAL DEL PLUGIN

El problema de rendimiento y conflictos se originaba porque el plugin `WPTB` inyectaba unos 120KB de CSS (incluyendo `style.css`, `modal-vehicles.css` y `form-fix.css`) en la cabecera de todas las páginas del sitio web (incluso en artículos del blog y páginas SEO donde no había funcionalidad de booking).

**Acción tomada:**
Se ha modificado `WPTB_Public::enqueue_scripts()` en el código legacy (`app/Legacy/WPTB/includes/class-wptb-public.php`) aislando la carga de estos assets. Ahora **solo se cargan** si el usuario está visitando las páginas específicas del flujo de compra:
- `seleccionar-vehiculo`
- `reservas-metransfers`
- `pago`
- `reservas-hotel`

---

## 3. PRÓXIMOS PASOS REQUERIDOS (Manual)

Para terminar de adoptar el nuevo Design System, es necesario que los desarrolladores Frontend actualicen el marcado (HTML) en los templates del código legado:

**Archivos a editar:** `app/Legacy/WPTB/templates/`
1. Reemplazar las clases de botones (ej. `.btn`, `.button-primary`) por `.mt-button`.
2. Envolver los formularios en `.mt-form` y los inputs en `.mt-input` / `.mt-select`.
3. Utilizar `.mt-card` para la visualización de vehículos (`booking-vehicles.php`).

Al estar basado en tokens, cualquier cambio futuro de color o tipografía se hará de forma centralizada en `tokens.css`.
