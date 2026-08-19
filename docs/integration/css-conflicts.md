# CSS Conflicts Analysis — MeTransfers Platform Integration
**Fase:** 1 — Inventario | **Fecha:** 2026-08-19

> Este documento analiza los conflictos CSS entre el tema y el plugin, e identifica las hojas de estilo que se cargan globalmente de forma inapropiada.

---

## 1. INVENTARIO DE HOJAS DE ESTILO

### Tema (`mt120826_merchandev`)
- **`style.css` (254 KB):** Hoja principal del tema. Contiene reseteos, variables CSS, layout, estilos de componentes y paginas SEO. Se encola globalmente.

### Plugin de Reservas (`complete-booking-plugin/modules/booking/assets/css/`)
- **`style.css` (94 KB):** Estilos principales del booking publico (formulario, listados, vehiculos). **Se encola globalmente** en todo el sitio web por `WPTB_Public::enqueue_scripts()`.
- **`modal-vehicles.css`:** Estilos para el modal de seleccion de vehiculos. **Encolado globalmente.**
- **`form-fix.css`:** Ajustes/parches de formulario. **Encolado globalmente.**
- **`transfers-search.css`:** Estilos para el buscador premium (modal).

### Plugin de Hoteles (`complete-booking-plugin/modules/hotel/public/css/`)
- **`hqp-booking.css`:** Estilos del booking por QR. Condicional (aparentemente).

---

## 2. PROBLEMAS DETECTADOS

### 2.1 Carga Global Innecesaria
El plugin de reservas encola `style.css` (94 KB) y `modal-vehicles.css` en **todas las paginas del sitio web**, independiente de si existe un formulario de reserva o no. Esto penaliza el rendimiento (WPO) de la Home, articulos del blog y paginas de destino (SEO).

### 2.2 Selectores Globales y Mutacion
La hoja `style.css` del plugin asume ciertas estructuras y, frecuentemente, entra en conflicto con la hoja del tema debido a selectores demasiado genericos (ej. afectando `div`, `span`, `input`, `button` sin aislar el contexto a un wrapper como `.wptb-booking-container`).

### 2.3 Abuso de `!important`
Debido al conflicto mencionado, el plugin y el tema utilizan `!important` para sobrescribir reglas mutuamente. Esto crea una guerra de especificidad que rompe la interfaz cuando se modifica cualquier elemento.

### 2.4 Duplicacion de variables (Design Tokens)
El tema y el plugin manejan colores y tipografias independientemente. El plugin utiliza sus propios estilos incrustados o colores definidos en su CSS, causando discordancia visual (ej. botones azules del plugin vs botones del tema).

---

## 3. PLAN DE RESOLUCION (FASE DE UNIFICACION VISUAL)

1. **Aislamiento Inicial (Fase 4/5):**
   - Modificar `WPTB_Public::enqueue_scripts()` para cargar las hojas CSS del plugin **solamente** cuando se esta renderizando un shortcode o cuando se esta en una pagina de reserva (usando comprobaciones como `is_page(array('seleccionar-vehiculo', 'reservas-metransfers', 'pago'))` o una variable global estatica).

2. **Unificacion de Componentes:**
   - Analizar las reglas del plugin para botones e inputs y alinearlos con el Theme (mediante las variables `--mt-color-*` definidas en la Fase 9 del plan).
   - Aplicar el CSS del tema a los formularios del plugin usando clases como `.mt-button`, `.mt-input`.

3. **Eliminacion de Hojas Legacy (Fase 9):**
   - El objetivo final es integrar los estilos del plugin dentro del ecosistema SCSS/CSS del tema (en directorios como `assets/css/booking.css`), y retirar por completo los archivos `style.css`, `modal-vehicles.css` y `form-fix.css` del directorio Legacy.
