# Security Findings — MeTransfers Platform Integration
**Fase:** 1 — Inventario | **Fecha:** 2026-08-19 | **Clasificacion:** CONFIDENCIAL

> [!CAUTION]
> Este documento contiene hallazgos de seguridad criticos. Los valores reales de secretos
> NO estan documentados aqui — solo se documenta su ubicacion y tipo.
> LOS SECRETOS DEBEN ROTARSE antes de que el repositorio sea publico.

## ESTADO DE REMEDIACION (2026-08-19)

- Credenciales SMTP, Google Maps y Redsys retiradas del codigo y centralizadas mediante `MeTransfers\Core\Settings` (constantes `MT_*` u options de WordPress).
- Distancia, duracion y precio recalculados en servidor en Redsys y WooCommerce; el flujo Hotel exige un token QR valido y obtiene su tarifa desde la base de datos.
- IPN Redsys valida firma e importe y confirma con una transicion idempotente.
- Cookies Hotel usan `Secure` cuando corresponde, `HttpOnly` y `SameSite=Lax`.
- Scripts temporales de diagnostico/configuracion con tokens incrustados fueron eliminados del artefacto.
- La confirmacion duplicada de `booking-form.php` fue eliminada; `booking-details.php` exige un token HMAC ligado a la orden y solo presenta un pago confirmado despues de comprobar `paid` y `confirmed/completed` en DB.
- Los endpoints AJAX publicos ya no devuelven nombre de tabla, errores SQL ni conteos internos.
- Gitleaks revisa cambios e historial disponible en CI, complementando el escaneo rapido de patrones.
- **Pendiente fuera del repositorio:** rotar las credenciales que estuvieron expuestas y revisar el historial Git; no se ha reescrito el historial.

### Verificacion Gitleaks redactada

El 2026-08-19, Gitleaks 8.30.1 no encontro filtraciones en el arbol actual, pero detecto seis hallazgos historicos en el commit `ef04a308a6affe219638379d5a2b6979d50c34a5`. Los metadatos redactados apuntan a las antiguas integraciones de Google Maps, Redsys y Stripe en:

- `app/Legacy/Hotel/admin/class-hqp-admin.php`;
- `app/Legacy/Hotel/public/class-hqp-public.php`;
- `app/Legacy/WPTB/includes/class-wptb-public.php`;
- `app/Legacy/WPTB/update-stripe-keys.php`.

No se documentan los valores. Antes de Live se deben rotar Google Maps y Redsys, confirmar la rotacion de SMTP indicada en el diagnostico original y revisar/renovar las credenciales Stripe si esa integracion continua activa. Reescribir el historial es una operacion separada y coordinada; no sustituye la rotacion.

Los apartados siguientes se conservan como registro del diagnostico original.

---

## RESUMEN EJECUTIVO

| Nivel | Cantidad | Descripcion |
|---|---|---|
| P0 CRITICO | 4 | Secretos hardcodeados + flujo de pago inseguro |
| P0 ALTO | 2 | display_errors habilitado + activacion DB en cada request |
| MEDIO | 3 | Precio calculado en browser + cookies sin flags seguros |
| BAJO | 3 | Emails hardcodeados + nonces duplicados |

---

## 1. SECRETOS HARDCODEADOS (P0 — CRITICO)

### 1.1 Credenciales SMTP hardcodeadas

**Archivo:** `modules/booking/includes/class-wptb-public.php`
**Lineas:** ~844-848
**Funcion:** `configure_smtp()`
**Tipo:** Contrasena SMTP de correo transaccional
**Variables afectadas:**
- `$phpmailer->Host` — host de correo
- `$phpmailer->Username` — usuario de correo
- `$phpmailer->Password` — **CONTRASENA HARDCODEADA** en texto plano

**Accion requerida:**
1. Mover a options de WordPress: `wptb_smtp_host`, `wptb_smtp_user`, `wptb_smtp_password`
2. O usar constantes PHP definidas en wp-config.php fuera del repo
3. Rotar la contrasena en el proveedor de correo INMEDIATAMENTE
4. Nunca incluir en logs

### 1.2 Clave HMAC Redsys hardcodeada (WPTB_Public)

**Archivo:** `modules/booking/includes/class-wptb-public.php`
**Lineas:** ~670 (initiate_redsys_payment) y ~726 (listen_redsys_ipn)
**Tipo:** Clave secreta HMAC-SHA256 de Redsys/Getnet
**Variables afectadas:**
- `$key` — clave de firma Redsys
- `$merchant_code` — codigo de comercio
- `$terminal` — terminal

**Accion requerida:**
1. Mover a: `get_option('wptb_redsys_key')` (ya existe el campo en settings)
2. O usar: `defined('MT_REDSYS_SECRET') ? MT_REDSYS_SECRET : ''`
3. ROTAR la clave en el panel Getnet/Redsys
4. Nunca loguear

### 1.3 Clave HMAC Redsys hardcodeada (HQP_Public)

**Archivo:** `modules/hotel/public/class-hqp-public.php`
**Lineas:** ~315-317
**Tipo:** Identicas claves Redsys al punto 1.2 (mismo valor hardcodeado)
**Variables:** `$key`, `$merchant_code`, `$terminal`

**Nota:** Duplicado del punto 1.2 — confirma que la misma clave esta en DOS lugares del codigo.

**Accion requerida:** Igual que 1.2. Una vez centralizado en options, ambos sitios deben leer de ahi.

### 1.4 API Key Google Maps hardcodeada

**Archivo 1:** `complete-booking-plugin.php` linea 58
**Archivo 2:** `modules/booking/includes/class-wptb-public.php` linea 65
**Archivo 3:** `modules/hotel/admin/class-hqp-admin.php` linea 68
**Tipo:** Google Maps API Key (visible en HTML publico del frontend)

**Nota:** Esta clave ya esta visible en el HTML source del sitio cuando se carga Google Maps.
Verificar restricciones de dominio en Google Cloud Console.

**Accion requerida:**
1. Verificar restricciones HTTP referrer en Google Cloud Console
2. Centralizar en: `get_option('wptb_google_maps_api_key')` (ya existe)
3. Eliminar fallbacks hardcodeados
4. Definir opcionalmente como: `define('MT_GOOGLE_MAPS_API_KEY', '...')` en wp-config.php

---

## 2. FLUJO DE PAGO INSEGURO (P0 — CRITICO)

### 2.1 check_return_url_payment_force — VULNERABILIDAD DE PAGO

**Archivo:** `modules/booking/includes/class-wptb-public.php`
**Lineas:** 783-823
**Hook:** `add_action('template_redirect', 'check_return_url_payment_force')`

**Descripcion del problema:**
Cuando el usuario regresa de Redsys con la URL:
```
/reservas-metransfers/?payment_result=ok&oid={order_id}
```
El codigo FUERZA la confirmacion del pago SIN verificar firma criptografica:
- Lee `$_GET['payment_result'] === 'ok'` — controlado por el usuario
- Busca la reserva por `payment_intent_id` = `$_GET['oid']`
- Si la reserva esta en estado != 'confirmed': marca como `paid` + `confirmed`
- Envia emails de confirmacion
- Envia alerta WhatsApp

**Impacto:** Un atacante que conozca el `order_id` de una reserva puede:
1. Confirmar un pago sin haber pagado
2. Recibir emails de confirmacion
3. Potencialmente obtener el servicio sin pago real

**Accion requerida:**
1. DESHABILITAR `check_return_url_payment_force` inmediatamente en produccion
2. La Return URL debe solo CONSULTAR el estado desde la DB, no modificarlo
3. La unica fuente de verdad es el IPN/callback de Redsys con firma verificada
4. Flujo correcto:
```
Redsys IPN -> listen_redsys_ipn() -> verificar firma -> marcar pagado
Browser return -> leer estado de DB -> mostrar resultado (solo lectura)
```

### 2.2 Precio calculado en browser

**Archivos:**
- `assets/js/transfers-search.js` (calcula precio client-side)
- `assets/js/redsys-payment.js` (lee precio de sessionStorage)

**Flujo actual (INSEGURO):**
```
JS calcula precio
  -> guarda en sessionStorage
  -> JS envia a wptb_initiate_redsys con booking_data.price
  -> Servidor usa ese precio para generar orden Redsys
```

**Riesgo:** Usuario puede modificar `sessionStorage.wptb_booking_data.price` antes de pagar.

**Accion requerida:**
Verificar que `wptb_initiate_redsys` en `class-wptb-public.php`:
1. Recibe `vehicle_id` + `distance_km` + `trip_type`
2. RECALCULA precio con `WPTB_Pricing::calculate_price()`
3. USA el precio calculado por servidor (no el del cliente) para Redsys
4. Rechaza si precio enviado por cliente es inferior al calculado

---

## 3. DEBUG Y ERRORES EN PRODUCCION (P0)

### 3.1 display_errors habilitado

**Archivo:** `complete-booking-plugin.php`
**Lineas:** 19-20
```php
ini_set('display_errors', 1);  // ⚠️ PRODUCCION
error_reporting(E_ALL);         // ⚠️ PRODUCCION
```

**Accion requerida:** Eliminar o condicionarlo:
```php
if (defined('WP_DEBUG') && WP_DEBUG) {
    ini_set('display_errors', 1);
}
```

### 3.2 WPTB_Activator::activate() en cada request admin

**Archivo:** `modules/booking/includes/class-wptb-public.php`
**Linea:** 42
```php
if ( is_admin() ) {
    WPTB_Activator::activate(); // Ejecuta dbDelta en CADA REQUEST ADMIN
}
```

**Riesgo:** Performance degradada + posibles race conditions en migraciones
**Accion requerida:** Usar migration flags como ya hace `WPTB_Admin`:
```php
if (get_option('wptb_schema_version') < MT_SCHEMA_VERSION) {
    WPTB_Activator::activate();
    update_option('wptb_schema_version', MT_SCHEMA_VERSION);
}
```

---

## 4. PROBLEMAS DE SEGURIDAD MEDIOS

### 4.1 Cookies del Hotel sin flags de seguridad

**Archivo:** `modules/hotel/public/class-hqp-public.php`
Cookies `hqp_hotel_token`, `hqp_hotel_id` se setean sin:
- `httponly: true`
- `secure: true` (en HTTPS)
- `samesite: Strict`

**Accion requerida:** Usar `setcookie()` con los flags correctos.

### 4.2 `$_POST['price']` mutado directamente

**Archivo:** `modules/hotel/public/class-hqp-public.php`
`intercept_booking_submission()` sobreescribe `$_POST['price']` — fragil y potencialmente explotable si el orden de prioridad de hooks cambia.

### 4.3 Fallback placeholder Stripe

**Archivo:** `includes/class-unified-integration.php` linea 237
```php
$stripe_secret = get_option('wptb_stripe_secret_key', 'sk_test_YOUR_TEST_SECRET_KEY');
```
Si la opcion no existe, se usaria el placeholder como clave — que fallaria silenciosamente.

---

## 5. CODIGO DEBUG EN PRODUCCION

**Archivos que NO deben existir en produccion:**

| Archivo | Riesgo |
|---|---|
| `modules/booking/assets/js/debug-helper.js` | Expone informacion de debugging en browser |
| `modules/booking/diagnostic.php` | Endpoint de diagnostico accesible sin auth |
| `modules/booking/DEBUG-GUIDE.md` | Informacion de arquitectura interna |
| `complete-booking-plugin.php` L85-93 | Endpoint `?fix_10001` para modificar DB sin auth |

### Endpoint fix_10001 (CRITICO)

```php
// En complete-booking-plugin.php linea 86-93
add_action('admin_init', function() {
    if ( isset($_GET['fix_10001']) ) {
        // Ejecuta UPDATE en DB y muestra resultado
        die('<h1>Exito!</h1>...');
    }
});
```

Aunque esta en `admin_init` (requiere admin autenticado), este tipo de endpoints ad-hoc son peligrosos y deben eliminarse.

---

## 6. ENCODING

**Archivo:** `modules/hotel/admin/class-hqp-admin.php` (linea ~280)
Texto con encoding roto: `C-ó-d-i-g-o. Redsys` → visible como mojibake en admin.

Revisar todos los archivos PHP con: `charset=UTF-8` en sus headers.

---

## 7. PLAN DE REMEDIACION PRIORITARIO

### Sprint 1 — Antes de copiar al proyecto integrado

- [ ] Eliminar o deshabilitar `check_return_url_payment_force`
- [ ] Eliminar `display_errors = 1` / `error_reporting(E_ALL)`
- [ ] Mover credenciales SMTP a `get_option()` + documentar rotacion
- [ ] Mover claves Redsys a `get_option()` (ya tienen campos en settings)
- [ ] Centralizar Google Maps API key en options (sin fallback hardcodeado)
- [ ] Eliminar endpoint `?fix_10001`
- [ ] Verificar que `wptb_initiate_redsys` recalcula precio server-side

### Sprint 2 — Post-integracion

- [ ] Migrar SMTP a constantes en wp-config.php
- [ ] Migrar Redsys secret a constante `MT_REDSYS_SECRET`
- [ ] Agregar `secure`, `httponly`, `samesite` a cookies hotel
- [ ] Implementar `MT_Environment::isProduction()` para flags de debug
- [ ] Auditar todos los `error_log()` para no loguear datos sensibles

### Sprint 3 — Refactor

- [ ] Crear `app/Core/Settings.php` como API centralizada
- [ ] Crear `app/Payments/Redsys/RedsysService.php` con secretos desde env
- [ ] Implementar `MT_Log` wrapper para logs sanitizados
- [ ] Tests unitarios para flujo Redsys (firma valida/invalida, callback duplicado)

---

## 8. SECRETOS QUE DEBEN ROTARSE

Los siguientes secretos estan expuestos en el repositorio y deben rotarse en sus proveedores correspondientes:

1. **Contrasena SMTP** — Contactar al proveedor de correo barcelonatours.email
2. **Clave HMAC Redsys** — Panel Getnet/Redsys Banco Santander → regenerar clave
3. **Google Maps API Key** — Google Cloud Console → verificar restricciones
4. **Stripe Secret Key** (si alguna vez fue real) — Dashboard Stripe

> [!IMPORTANT]
> La rotacion debe hacerse ANTES de que este repositorio se haga publico
> o se suba a un servicio de CI/CD con acceso externo.
