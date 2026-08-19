# Public API Contract — MeTransfers Platform Integration
**Fase:** 1 — Inventario | **Fecha:** 2026-08-19

> Este documento define el contrato de las APIs publicas (principalmente endpoints AJAX de WordPress) expuestas por el ecosistema actual (Tema + Plugin). Durante la integracion, estas firmas DEBEN respetarse para no romper el frontend legacy hasta que sea reemplazado.

---

## 1. ENDPOINTS DEL PLUGIN DE RESERVAS (WPTB)

Estos endpoints manejan la busqueda de vehiculos, el calculo de precios y la creacion/pago de la reserva normal.

### 1.1 `wptb_get_vehicles`
**Descripcion:** Obtiene los vehiculos disponibles y sus precios calculados para una ruta especifica.
- **Acceso:** Privado y publico (`wp_ajax_` y `wp_ajax_nopriv_`)
- **Metodo:** POST (asumido por convencion AJAX WP)
- **Parametros esperados:**
  - `distance_km` (float): Distancia de la ruta
  - `trip_type` (string): 'one_way' o 'round_trip'
  - `security` (string): Nonce (debe coincidir con `wptb_vars.nonce`)
- **Respuesta esperada:** JSON con array de vehiculos y `price` calculado por el servidor.

### 1.2 `wptb_get_quote`
**Descripcion:** Devuelve el presupuesto autoritativo que también consume el pago.
- **Acceso:** Privado y publico, protegido por `wptb_vars.nonce`.
- **Validaciones:** área de servicio, fecha/antelación, vuelta posterior, rutas reales, vehículo y reglas completas de pricing.
- **Protección antiabuso:** límite global por cliente antes de geocodificar o consultar rutas; devuelve HTTP `429` cuando se supera.
- **Respuesta:** precio, distancia total y por trayecto, duración, desglose, vehículo y `booking_locale`.

El endpoint heredado `wptb_calculate_price`, que aceptaba distancia y duración del navegador, fue retirado. Ningún consumidor debe utilizarlo.

### 1.3 `wptb_save_booking`
**Descripcion:** Guarda los datos de la reserva en base de datos.
- **Acceso:** Privado y publico
- **Parametros esperados (basado en el JS y BD):**
  - Datos de origen, destino, fechas, horas, cliente (nombre, email, telefono), vehiculo y trip_type.
  - `security` (nonce).
- **Garantia:** Precio, distancia y duracion enviados por el navegador se ignoran; la ruta y el precio se recalculan en servidor. El flujo Hotel ya no intercepta este endpoint.

Los nombres legacy `wptb_create_booking` y `wptb_get_pricing` no forman parte del contrato vigente: no tenían implementación ni consumidores activos y sus hooks fueron eliminados.

### 1.4 `wptb_initiate_redsys`
**Descripcion:** Inicia el proceso de pago con Redsys. Construye los parametros HMAC-SHA256 para el formulario de redireccion.
- **Acceso:** Privado y publico
- **Parametros esperados:**
  - `booking_data` (JSON/string): Datos de la reserva. Precio, distancia y duracion client-side se ignoran.
  - `booking_data.terms_accepted` debe ser booleano verdadero.
  - `booking_data.terms_version` debe coincidir exactamente con `MT_TERMS_VERSION`.
  - `security` (nonce).
- **Respuesta esperada:** JSON con la URL del entorno configurado y parametros firmados (`Ds_Signature`, `Ds_MerchantParameters`).

### 1.5 Redsys IPN (No-AJAX)
**Descripcion:** URL de callback (webhook) desde los servidores de Redsys/Getnet.
- **Hook:** `init`
- **Condicion de disparo:** Parámetro GET `?wptb_redsys_ipn=1`
- **Parametros esperados:** Form-data firmado de Redsys. Verifica version, firma HMAC, orden e importe antes de confirmar de forma idempotente.

---

## 2. ENDPOINTS DEL MODULO DE HOTELES (HQP)

Estos endpoints son especificos para el flujo de reservas a traves de codigos QR de hoteles asociados.

### 2.1 `hqp_get_fixed_pricing`
**Descripcion:** Devuelve los precios de los vehiculos para un hotel especifico (ignora la distancia, usa tarifas planas o descuentos).
- **Acceso:** Privado y publico
- **Parametros esperados:**
  - Identificador de hotel, vinculado obligatoriamente al token QR valido de la cookie segura.

### 2.2 `hqp_create_booking`
**Descripcion:** Crea una reserva en el flujo de hoteles y llama a Redsys.
- **Acceso:** Privado y publico
- **Parametros esperados:**
  - Datos de reserva de hotel + ID vehiculo.

---

## 3. INTEGRACION EXTERNA LEGACY

El shim `Unified_Integration` y sus endpoints Stripe incompletos fueron retirados del runtime. WooCommerce conserva su flujo propio y Redsys usa el gateway centralizado.

---

## 4. ENDPOINTS DEL TEMA (MeTransfers)

### 4.1 `mt_save_lead`
**Descripcion:** Guarda los datos del formulario de contacto genérico.
- **Acceso:** Privado y publico
- **Parametros esperados:**
  - `security` (string): Nonce `mt_lead_nonce`.
  - Datos del lead (nombre, email, mensaje).
- **Accion:** Guarda en DB (presumiblemente un post de CPT) y envia notificacion a `get_option('admin_email')`.

### 4.2 `mt_track_button_click`
**Descripcion:** Analitica interna. Registra el click de un boton en la tabla `mt_event_tracking`.
- **Acceso:** Privado y publico
- **Parametros esperados:**
  - `security` (nonce).
  - Datos del boton (clase, texto, url origen).

### 4.3 `admin_post_me_transfers_destination_request` (admin-post.php)
**Descripcion:** Maneja el formulario de solicitud de un destino del catalogo estatico.
- **Metodo:** POST via `/wp-admin/admin-post.php` (no AJAX tradicional).
- **Parametros esperados:**
  - `action`: `me_transfers_destination_request`
  - `full_name`, `email`, `phone`, `origin`, `destination`, `travel_date`, `passengers`, `trip_type`, `message`, `privacy_consent`, `company` (honeypot).

---

## 5. REQUISITOS DE COMPATIBILIDAD

Durante la integracion y el movimiento del codigo del plugin hacia el tema (`app/Legacy/WPTB/`):

1. Los hooks `wp_ajax_` vigentes listados arriba **NO deben cambiar de nombre**.
2. Las respuestas JSON deben mantener la **misma estructura** (claves) que espera el JS actual (por ejemplo, `booking-app.js` y `redsys-payment.js`).
3. Los **nonces** deben generarse bajo las mismas claves (`wptb_vars.nonce`, `mt_lead_nonce`).
4. `wptb_initiate_redsys` ignora como autoridad el precio del navegador, ejecuta `QuoteService` otra vez y solo genera la orden si el precio coincide, la ruta está cubierta, las fechas son válidas y existe consentimiento legal versionado.
