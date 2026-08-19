# DB Contract — MeTransfers Platform Integration
**Fase:** 1 — Inventario | **Fecha:** 2026-08-19

> Este documento define el contrato de base de datos que DEBE preservarse
> durante la integracion. Ninguna tabla, columna ni indice puede ser
> eliminado, renombrado o modificado sin documentar la migracion correspondiente.

---

## 1. TABLAS PRINCIPALES (wp_wptb_*)

### wp_wptb_bookings

**Proposito:** Tabla central de reservas. AUTO_INCREMENT = 10000 (requisito Getnet/Redsys).

| Columna | Tipo | Nulo | Default | Notas |
|---|---|---|---|---|
| id | mediumint(9) | NO | AUTO_INCREMENT | PK, empieza en 10000 |
| booking_date | date | NO | — | Fecha del traslado |
| booking_time | time | NO | — | Hora del traslado |
| origin | text | NO | — | Origen (texto libre) |
| destination | text | NO | — | Destino (texto libre) |
| distance_km | float | SI | — | Distancia calculada |
| duration_minutes | int | SI | — | Duracion estimada |
| price | decimal(10,2) | SI | — | Precio final |
| customer_name | varchar(150) | SI | — | Nombre cliente |
| customer_email | varchar(150) | SI | — | Email cliente |
| customer_phone | varchar(50) | SI | — | Telefono cliente |
| flight_number | varchar(50) | SI | — | Numero de vuelo |
| passengers | int | SI | 1 | Numero de pasajeros |
| suitcases | int | SI | 0 | Maletas grandes |
| carry_ons | int | SI | 0 | Equipaje de mano |
| notes | text | SI | — | Notas del cliente |
| vehicle_id | mediumint(9) | SI | — | FK a wptb_vehicles |
| trip_type | varchar(20) | SI | 'one_way' | 'one_way' o 'round_trip' |
| return_pickup_address | text | SI | — | Origen vuelta |
| return_dropoff_address | text | SI | — | Destino vuelta |
| return_date | date | SI | — | Fecha vuelta |
| return_time | time | SI | — | Hora vuelta |
| status | varchar(20) | SI | 'pending' | Estado reserva |
| payment_method | varchar(50) | SI | — | Metodo de pago |
| payment_intent_id | varchar(255) | SI | — | ID transaccion Redsys/Stripe |
| payment_status | varchar(20) | SI | 'pending' | Estado del pago |
| hotel_token | varchar(255) | SI | — | Token QR hotel |
| source | varchar(50) | SI | 'Metransfers' | Origen de la reserva |
| created_at | datetime | SI | CURRENT_TIMESTAMP | Creacion |

**Indices:**
- PRIMARY KEY (id)
- KEY vehicle_id (vehicle_id)
- KEY booking_date (booking_date)
- KEY status (status)
- KEY payment_intent_id (payment_intent_id)
- KEY hotel_token (hotel_token)

**Valores de status:**
- `pending` — creada, sin pago
- `confirmed` — pago confirmado
- `cancelled` — cancelada
- `completed` — completada

**Valores de payment_status:**
- `pending`
- `paid`
- `failed`
- `refunded`

---

### wp_wptb_vehicle_types

**Proposito:** Categorias de vehículos.

| Columna | Tipo | Nulo | Default | Notas |
|---|---|---|---|---|
| id | mediumint(9) | NO | AUTO_INCREMENT | PK |
| name | varchar(100) | NO | — | Ej: "Van", "SUV" |
| slug | varchar(100) | NO | — | UNIQUE |
| description | text | SI | — | |
| icon | varchar(255) | SI | — | |
| display_order | int | SI | 0 | Orden de aparicion |
| created_at | datetime | SI | CURRENT_TIMESTAMP | |

**Indices:**
- PRIMARY KEY (id)
- UNIQUE KEY slug (slug)

**Datos por defecto (seed):**
- Sedan (slug: sedan) — orden 1
- SUV (slug: suv) — orden 2
- Van (slug: van) — orden 3
- Minibus (slug: minibus) — orden 4
- Lujo (slug: luxury) — orden 5

---

### wp_wptb_vehicles

**Proposito:** Flota de vehiculos con precios por km y minimos.

| Columna | Tipo | Nulo | Default | Notas |
|---|---|---|---|---|
| id | mediumint(9) | NO | AUTO_INCREMENT | PK |
| name | varchar(200) | NO | — | Nombre del vehiculo |
| vehicle_type_id | mediumint(9) | NO | — | FK a wptb_vehicle_types |
| description | text | SI | — | |
| capacity | int | NO | 4 | Max pasajeros |
| luggage_capacity | int | SI | 2 | Max maletas |
| initial_fee | decimal(10,2) | SI | 0 | Cargo inicial fijo |
| min_transfer_price | decimal(10,2) | SI | 0 | Precio minimo absoluto |
| min_oneway_price | decimal(10,2) | SI | 0 | Minimo solo ida |
| min_roundtrip_price | decimal(10,2) | SI | 0 | Minimo ida y vuelta |
| price_per_km_oneway | decimal(10,2) | SI | 0 | EUR/km solo ida |
| price_per_km_roundtrip | decimal(10,2) | SI | 0 | EUR/km ida y vuelta |
| price_per_hour | decimal(10,2) | SI | 0 | EUR/hora (opcional) |
| is_active | tinyint(1) | SI | 1 | Activo en frontend |
| is_normal | tinyint(1) | SI | 1 | Aparece en booking normal |
| is_hotel | tinyint(1) | SI | 1 | Aparece en booking hotel |
| display_order | int | SI | 0 | Orden de aparicion |
| created_at | datetime | SI | CURRENT_TIMESTAMP | |
| updated_at | datetime | SI | CURRENT_TIMESTAMP ON UPDATE | |

**Indices:**
- PRIMARY KEY (id)
- KEY vehicle_type_id (vehicle_type_id)
- KEY is_active (is_active)
- KEY capacity (capacity)

---

### wp_wptb_vehicle_images

**Proposito:** Imagenes de vehiculos (multiples por vehiculo).

| Columna | Tipo | Nulo | Default | Notas |
|---|---|---|---|---|
| id | mediumint(9) | NO | AUTO_INCREMENT | PK |
| vehicle_id | mediumint(9) | NO | — | FK a wptb_vehicles |
| image_url | varchar(500) | NO | — | URL de la imagen |
| image_alt | varchar(255) | SI | — | Alt text |
| display_order | int | SI | 0 | Orden |
| is_primary | tinyint(1) | SI | 0 | Imagen principal |
| created_at | datetime | SI | CURRENT_TIMESTAMP | |

**Indices:**
- PRIMARY KEY (id)
- KEY vehicle_id (vehicle_id)
- KEY is_primary (is_primary)

---

### wp_wptb_hotel_vehicles

**Proposito:** Vehiculos especificos del modulo Hotel QR (independiente de wptb_vehicles).

| Columna | Tipo | Nulo | Default | Notas |
|---|---|---|---|---|
| id | mediumint(9) | NO | AUTO_INCREMENT | PK |
| name | varchar(200) | NO | — | |
| description | text | SI | — | |
| capacity | int | NO | 4 | |
| image_url | varchar(500) | SI | — | |
| display_order | int | SI | 0 | |
| is_active | tinyint(1) | SI | 1 | |
| created_at | datetime | SI | CURRENT_TIMESTAMP | |

---

### wp_wptb_backups

**Proposito:** Log de exportaciones Excel realizadas.

| Columna | Tipo | Nulo | Default | Notas |
|---|---|---|---|---|
| id | mediumint(9) | NO | AUTO_INCREMENT | PK |
| filename | varchar(255) | NO | — | Nombre archivo |
| filepath | text | NO | — | Ruta en disco |
| type | varchar(50) | SI | 'manual' | |
| status | varchar(20) | SI | 'active' | |
| created_at | datetime | SI | CURRENT_TIMESTAMP | |

---

## 2. POST META (wp_postmeta)

### CPT: hotel_partner

| Meta Key | Tipo | Descripcion |
|---|---|---|
| _hqp_token | string | Token unico del hotel (para QR) |
| _hqp_discount_percent | int | Descuento en % (0-100) |
| _hqp_hotel_address | string | Direccion del hotel |
| _hqp_hotel_phone | string | Telefono de contacto |
| _hqp_contact_name | string | Nombre del contacto |
| _hqp_contact_email | string | Email del contacto |
| _hqp_price_vehicle_{id} | decimal | Precio fijo por vehiculo para este hotel |
| _hqp_sedan_id | int | ID vehiculo sedan (legacy) |
| _hqp_van_id | int | ID vehiculo van (legacy) |

---

## 3. OPTIONS (wp_options) — Contrato de preservacion

Todas estas opciones DEBEN ser preservadas durante la integracion:

| Option Key | Tipo | Descripcion | Usado por |
|---|---|---|---|
| wptb_google_maps_api_key | string | API Key Google Maps | Booking, Hotel, Admin |
| wptb_admin_email_notifications | string | Email admin notificaciones | WPTB_Admin, WPTB_Public |
| wptb_admin_phone_notifications | string | Telefono WhatsApp admin | WPTB_Public |
| wptb_whatsapp_apikey | string | API key CallMeBot | WPTB_Public |
| wptb_transfer_product_id | int | ID producto WooCommerce | WPTB_Activator, WPTB_Public |
| wptb_redsys_merchant_code | string | Codigo comercio Redsys | WPTB_Admin settings |
| wptb_redsys_key | string | Clave HMAC Redsys | WPTB_Admin settings |
| wptb_redsys_terminal | string | Terminal Redsys | WPTB_Admin settings |
| wptb_redsys_currency | string | Moneda Redsys (978=EUR) | WPTB_Admin settings |
| wptb_redsys_environment | string | test / live | WPTB_Admin settings |
| wptb_stripe_secret_key | string | SK Stripe (legacy) | Unified_Integration |
| wptb_stripe_publishable_key | string | PK Stripe (legacy) | Frontend |
| wptb_db_version_3_2 | string | Migration flag | WPTB_Admin |
| wptb_vehicle_page_version_1 | string | Migration flag | WPTB_Admin |
| wptb_version_453_hotel_vehicles_v2 | string | Migration flag hotel | wp-booking-plugin.php |

---

## 4. TRANSIENTS

| Key | TTL | Descripcion |
|---|---|---|
| hqp_booking_page_id | 1 dia | Cache del ID de pagina hotel booking |

---

## 5. PAGINAS GENERADAS (wp_posts)

| Slug | Shortcode | Notas |
|---|---|---|
| /seleccionar-vehiculo/ | [wptb_vehicle_selection] | Creada por activador |
| /reservas-metransfers/ | [wptb_booking_details] | Creada por activador |
| /pago/ | [wptb_checkout] | Creada por activador |
| /reservas-hotel/ | [hqp_booking_form] | Creada por HQP_Admin en admin_init |

---

## 6. REGLAS DE PRESERVACION

### NUNCA durante la primera integracion:
- Renombrar tablas `wptb_*`
- Eliminar columnas existentes
- Cambiar tipos de columna sin migracion
- Modificar AUTO_INCREMENT = 10000 de bookings
- Eliminar indices existentes
- Borrar opciones `wptb_*`
- Modificar slugs de paginas sin redirects

### PERMITIDO con migracion documentada:
- Agregar columnas nuevas (ej: booking_locale)
- Agregar indices nuevos
- Agregar nuevas opciones `mt_*`
- Crear tablas nuevas

### Migraciones que se aplicaran en Sprint 2:
- Migration_001_EnsureBookingTables (idempotente)
- Migration_002_EnsureHotelVehicles (idempotente)
- Migration_003_AddBookingLocale (nueva columna booking_locale)
- Migration_004_AddPaymentTrackingColumns (columnas para idempotencia)

---

## 7. TEST DE REGRESION PRE/POST INTEGRACION

Antes de desactivar el plugin original, comparar:

```sql
SELECT COUNT(*) FROM wp_wptb_bookings;
SELECT COUNT(*) FROM wp_wptb_vehicles;
SELECT COUNT(*) FROM wp_wptb_vehicle_types;
SELECT COUNT(*) FROM wp_wptb_vehicle_images;
SELECT COUNT(*) FROM wp_wptb_hotel_vehicles;
```

Guardar resultados en `docs/integration/db-regression-report.md`.
