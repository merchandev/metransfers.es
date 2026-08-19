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

### 1.2 `wptb_calculate_price`
**Descripcion:** Recalcula el precio (similar a get_vehicles, posiblemente usado en otros flujos).
- **Acceso:** Privado y publico

### 1.3 `wptb_create_booking` / `wptb_save_booking`
**Descripcion:** Guarda los datos de la reserva en base de datos.
- **Acceso:** Privado y publico
- **Parametros esperados (basado en el JS y BD):**
  - Datos de origen, destino, fechas, horas, cliente (nombre, email, telefono), vehiculo, precio, trip_type.
  - `security` (nonce).
- **Intercepcion (Peligro):** El modulo de Hotel (`class-hqp-public.php`) intercepta `wptb_save_booking` con prioridad 1 para modificar `$_POST['price']` aplicando descuentos.

### 1.4 `wptb_initiate_redsys`
**Descripcion:** Inicia el proceso de pago con Redsys. Construye los parametros HMAC-SHA256 para el formulario de redireccion.
- **Acceso:** Privado y publico
- **Parametros esperados:**
  - `booking_data` (JSON/string): Datos de la reserva (actualmente incluye el precio client-side, lo cual es una vulnerabilidad).
  - `existing_booking_id` (int/opcional): Si se reintenta un pago.
  - `security` (nonce).
- **Respuesta esperada:** JSON con url (`https://sis.redsys.es/...`) y parametros encriptados (`Ds_Signature`, `Ds_MerchantParameters`).

### 1.5 Redsys IPN (No-AJAX)
**Descripcion:** URL de callback (webhook) desde los servidores de Redsys/Getnet.
- **Hook:** `init`
- **Condicion de disparo:** Parámetro GET `?wptb_redsys_ipn=1`
- **Parametros esperados:** Payload SOAP o form-data cifrado de Redsys. Verifica firma con clave secreta del servidor y procesa el estado del pago.

---

## 2. ENDPOINTS DEL MODULO DE HOTELES (HQP)

Estos endpoints son especificos para el flujo de reservas a traves de codigos QR de hoteles asociados.

### 2.1 `hqp_get_fixed_pricing`
**Descripcion:** Devuelve los precios de los vehiculos para un hotel especifico (ignora la distancia, usa tarifas planas o descuentos).
- **Acceso:** Privado y publico
- **Parametros esperados:**
  - Identificador de hotel (presumiblemente por token o ID enviado).

### 2.2 `hqp_create_booking`
**Descripcion:** Crea una reserva en el flujo de hoteles y llama a Redsys.
- **Acceso:** Privado y publico
- **Parametros esperados:**
  - Datos de reserva de hotel + ID vehiculo.

---

## 3. ENDPOINTS DE INTEGRACION EXTERNA (Unified_Integration - Deshabilitados/Legacy)

### 3.1 `wptb_create_payment_intent` (Deshabilitado)
**Descripcion:** Integracion legacy con Stripe. Genera un PaymentIntent.
- **Acceso:** Privado y publico
- **Nota:** Actualmente interceptado y deshabilitado, pero registrado en el Loader.

### 3.2 `wptb_confirm_payment`
**Descripcion:** Confirma el pago finalizado.
- **Acceso:** Privado y publico
- **Intercepcion:** `Unified_Integration::intercept_confirm_payment` lo usa en prioridad 0 para borrar las cookies del hotel (hqp_hotel_token, hqp_hotel_discount, etc.) una vez finalizado el pago.

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

1. Los hooks `wp_ajax_` listados arriba **NO deben cambiar de nombre**.
2. Las respuestas JSON deben mantener la **misma estructura** (claves) que espera el JS actual (por ejemplo, `booking-app.js` y `redsys-payment.js`).
3. Los **nonces** deben generarse bajo las mismas claves (`wptb_vars.nonce`, `mt_lead_nonce`).
4. **Vulnerabilidad a corregir:** El endpoint `wptb_initiate_redsys` **DEBE** modificarse internamente para ignorar el `precio` enviado en el parametro `booking_data` por el cliente, y recalcularlo usando la distancia, vehiculo, tipo de viaje y tarifas minimas, asegurando que la orden de Redsys se genere con un precio autorizado por el servidor.
