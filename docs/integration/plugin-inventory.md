# Plugin Inventory — complete-booking-plugin
**Version:** 5.0.2 | **Fecha:** 2026-08-19 | **Fase:** 1 — Inventario

## 1. ESTRUCTURA DE ARCHIVOS

```
complete-booking-plugin/
├── complete-booking-plugin.php          # Entry point
├── includes/class-unified-integration.php
├── modules/booking/
│   ├── wp-booking-plugin.php
│   ├── includes/
│   │   ├── class-wptb-activator.php       (DB + páginas)
│   │   ├── class-wptb-loader.php          (bootstrapper)
│   │   ├── class-wptb-public.php          (61KB - shortcodes/AJAX/email/Redsys)
│   │   ├── class-wptb-admin.php           (74KB - panel admin completo)
│   │   ├── class-wptb-pricing.php         (motor precios)
│   │   ├── class-wptb-redsys.php          (API Redsys HMAC256)
│   │   ├── class-wptb-vehicle-manager.php
│   │   ├── class-wptb-bookings-admin.php
│   │   ├── class-wptb-vehicles-admin.php
│   │   ├── class-wptb-dashboard.php
│   │   ├── cpt-destinations.php
│   │   ├── shortcode-transfers-search.php
│   │   └── xlsxwriter.class.php           (48KB - export Excel)
│   ├── assets/css/
│   │   ├── style.css                      (94KB - CARGADO GLOBAL ⚠️)
│   │   ├── modal-vehicles.css             (7KB)
│   │   ├── form-fix.css                   (2.7KB)
│   │   ├── checkout-override.css          (8.6KB)
│   │   ├── transfers-search.css           (7.9KB)
│   │   └── [minificados x4]
│   ├── assets/js/
│   │   ├── booking-app.js                 (50KB - CARGADO GLOBAL ⚠️)
│   │   ├── redsys-payment.js              (14KB)
│   │   ├── transfers-search.js            (27KB)
│   │   └── debug-helper.js               (⚠️ NO PRODUCCION)
│   └── templates/
│       ├── booking-form.php, booking-details.php, checkout.php
│       └── [6 templates más]
└── modules/hotel/
    ├── hotel-qr-plugin.php
    ├── includes/class-hqp-loader.php, fpdf.php
    ├── admin/class-hqp-admin.php (40KB), class-hqp-vehicles-admin.php
    └── public/class-hqp-public.php, partials/hqp-booking-form.php
```

## 2. CLASES PHP

| Clase | Archivo | Función |
|---|---|---|
| WPTB_Activator | class-wptb-activator.php | Crea tablas DB, páginas WP, producto WC |
| WPTB_Loader | class-wptb-loader.php | Bootstrapper hooks |
| WPTB_Public | class-wptb-public.php | Shortcodes, AJAX, Redsys, emails |
| WPTB_Admin | class-wptb-admin.php | Panel admin completo |
| WPTB_Pricing | class-wptb-pricing.php | Motor cálculo precios |
| WPTB_Redsys_API | class-wptb-redsys.php | Firma HMAC-SHA256 Redsys |
| WPTB_Vehicle_Manager | class-wptb-vehicle-manager.php | CRUD vehículos |
| WPTB_Bookings_Admin | class-wptb-bookings-admin.php | Gestión reservas admin |
| WPTB_Vehicles_Admin | class-wptb-vehicles-admin.php | Gestión vehículos admin |
| WPTB_Dashboard | class-wptb-dashboard.php | Widgets dashboard |
| HQP_Loader | class-hqp-loader.php | Bootstrapper hotel |
| HQP_Admin | class-hqp-admin.php | Admin hotel + QR |
| HQP_Vehicles_Admin | class-hqp-vehicles-admin.php | Vehículos hotel admin |
| HQP_Public | class-hqp-public.php | Público hotel, Redsys |
| Unified_Integration | class-unified-integration.php | WC + Stripe |

## 3. SHORTCODES

| Shortcode | Método |
|---|---|
| [wptb_booking_form] | WPTB_Public::render_booking_form |
| [wptb_booking] | WPTB_Public::render_booking_form (alias) |
| [wptb_vehicle_selection] | WPTB_Public::render_vehicle_selection |
| [wptb_booking_details] | WPTB_Public::render_booking_details |
| [wptb_stripe_checkout] | WPTB_Public::render_checkout_page (compat) |
| [wptb_redsys_checkout] | WPTB_Public::render_checkout_page |
| [wptb_checkout] | WPTB_Public::render_checkout_page (genérico) |
| [wptb_popular_destinations_carousel] | WPTB_Public::render_popular_carousel |
| [wptb_popular_destinations] | alias |
| [wptb_booking_popup] | WPTB_Public::render_booking_popup |
| [premium_transfers_search] | wptb_render_transfers_search() |
| [hqp_booking_form] | HQP_Public::render_booking_form |

## 4. AJAX HANDLERS (público + nopriv salvo indicación)

| Action | Clase/Método |
|---|---|
| wptb_save_booking | WPTB_Public::save_booking |
| wptb_get_vehicles | WPTB_Public::ajax_get_vehicles |
| wptb_calculate_price | WPTB_Public::ajax_calculate_price |
| wptb_create_booking | WPTB_Public::ajax_create_booking |
| wptb_get_pricing | WPTB_Public::ajax_get_pricing |
| wptb_initiate_redsys | WPTB_Public::initiate_redsys_payment |
| wptb_create_payment_intent | WPTB_Public (condicional) |
| wptb_confirm_payment | WPTB_Public (condicional) |
| wptb_save_vehicle | WPTB_Vehicles_Admin (admin-only) |
| wptb_delete_vehicle | WPTB_Vehicles_Admin (admin-only) |
| wptb_upload_vehicle_image | WPTB_Vehicles_Admin (admin-only) |
| wptb_delete_vehicle_image | WPTB_Vehicles_Admin (admin-only) |
| hqp_get_fixed_pricing | HQP_Public |
| hqp_create_booking | HQP_Public |

## 5. TABLAS DE BASE DE DATOS

| Tabla | Columnas principales |
|---|---|
| wp_wptb_bookings | id(≥10000), booking_date, booking_time, origin, destination, distance_km, price, vehicle_id, trip_type, status, payment_status, payment_intent_id, hotel_token, source |
| wp_wptb_vehicle_types | id, name, slug, description |
| wp_wptb_vehicles | id, name, capacity, luggage_capacity, price_per_km_oneway, price_per_km_roundtrip, min_oneway_price, min_roundtrip_price, min_transfer_price, is_active, is_hotel |
| wp_wptb_vehicle_images | id, vehicle_id, image_url, is_primary |
| wp_wptb_hotel_vehicles | id, name, capacity, image_url, is_active |
| wp_wptb_backups | id, filename, filepath, type, status |

## 6. OPTIONS CLAVE (wp_options)

```
wptb_google_maps_api_key       # API Key Maps
wptb_redsys_merchant_code      # Código comercio
wptb_redsys_key                # ⚠️ SECRETO - clave firma
wptb_redsys_terminal           # Terminal
wptb_redsys_currency           # 978 = EUR
wptb_redsys_environment        # test | live
wptb_transfer_product_id       # ID producto WooCommerce
wptb_stripe_secret_key         # ⚠️ SECRETO - SK Stripe
wptb_stripe_publishable_key    # PK Stripe
wptb_version_453_hotel_vehicles_v2  # Migración flag
```

## 7. CPT REGISTRADOS

| CPT | Registrado en |
|---|---|
| wptb_destination | cpt-destinations.php |
| hotel_partner | class-hqp-admin.php |

## 8. PÁGINAS CREADAS POR ACTIVATOR

| Slug | Shortcode |
|---|---|
| /seleccionar-vehiculo/ | [wptb_vehicle_selection] |
| /reservas-metransfers/ | [wptb_booking_details] |
| /pago/ | [wptb_checkout] |

## 9. ASSETS GLOBALES (PROBLEMA)

CSS cargados EN TODA LA WEB (no condicional):
- style.css 94KB
- modal-vehicles.css 7KB
- form-fix.css 2.7KB
- Material Symbols desde CDN Google

JS cargados globalmente:
- booking-app.js 50KB
- redsys-payment.js 14KB
- transfers-search.js 27KB
- Google Maps API (siempre)

## 10. DEPENDENCIAS

- WooCommerce (carrito, producto, checkout)
- Google Maps API (autocomplete, distancia)
- Redsys/Getnet Banco Santander (TPV)
- jQuery (WP core)
- Material Symbols CDN
- FPDF (PDF hotel local)
- xlsxwriter (Excel local)
- SMTP hardcoded ⚠️

## 11. ARCHIVOS EXCLUIDOS DEL BUILD DE PRODUCCIÓN

```
DEBUG-GUIDE.md
FIX-STRIPE-SIMPLE.php
FORCE-STRIPE-UPDATE.php
diagnostic.php
fix-stripe-now.php
update-stripe-keys-direct.sql
update-stripe-keys.php
stripe-debug-test.html
assets/js/debug-helper.js
scratch.php
test.php
```
