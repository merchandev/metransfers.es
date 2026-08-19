# Staging Compatibility Report — Plugin OFF
**Fase:** 8 — Prueba de Staging | **Fecha:** 2026-08-19

> **Objetivo:** Verificar que el Tema con la estructura integrada (`MeTransfers Platform`) es capaz de reemplazar al 100% las funcionalidades del plugin original *sin alterar la base de datos ni romper la web en producción*.

---

## 1. PREPARACIÓN DEL ENTORNO DE STAGING

Para realizar esta prueba con seguridad en tu servidor de Staging, debes seguir estos pasos:

1. **Sube el tema actualizado:** Despliega la rama `main` del nuevo repo `merchandev/metransfers.es` en la carpeta `wp-content/themes/mt120826_merchandev/` de Staging.
2. **Desactiva el plugin original:** Ve a *Plugins > Plugins Instalados* y **desactiva** "MeTransfers Booking" y "Hotel QR Plugin". **NO los borres**.
3. **Limpia la caché:** Pura la caché de SiteGround / WP Rocket / Redis.

---

## 2. CHECKLIST DE VERIFICACIÓN FUNCIONAL

A continuación, la lista de elementos que debes verificar manualmente en Staging. Marca cada casilla conforme vayas validando:

### 2.1. Frontend y Shortcodes
- [ ] La página Home carga correctamente sin errores.
- [ ] El buscador principal (shortcode `[premium_transfers_search]`) se visualiza y autocompleta destinos.
- [ ] La selección de vehículos carga correctamente tras buscar una ruta.
- [ ] Las tarifas de ida (one-way) y vuelta (round-trip) se calculan igual que antes.
- [ ] El checkout carga los campos de datos de cliente correctamente.
- [ ] El mapa de Google Maps funciona y muestra la ruta.

### 2.2. Panel de Administración (Backend)
- [ ] El menú lateral **MeTransfers** aparece.
- [ ] La lista de **Reservas** (`/wp-admin/admin.php?page=wptb-reservas`) carga sin errores.
- [ ] La lista de **Vehículos** y **Tipos de Vehículos** se muestra intacta.
- [ ] El menú de **Configuración** (`wptb-settings`) mantiene las APIs, Redsys y correos configurados.
- [ ] El **Dashboard** muestra las métricas de reservas correctamente.

### 2.3. Módulo de Hotel (QR)
- [ ] Entrar a `/reservas-hotel/` (o la URL de tu código QR) carga el formulario de hotel.
- [ ] Los vehículos específicos de hotel se muestran correctamente.
- [ ] El admin muestra el Custom Post Type "Hoteles Partners".

### 2.4. Flujo de Pago y Redsys
- [ ] Rellenar una reserva de prueba redirige correctamente a la pasarela Redsys (Sandbox).
- [ ] La respuesta de Redsys (Return URL y Callback IPN) procesa correctamente.
- [ ] La reserva cambia a estado `Confirmada` en el admin.

### 2.5. Notificaciones (Emails)
- [ ] Llega el email de confirmación al cliente tras pagar.
- [ ] Llega el email de notificación al administrador (`reservas@barcelonatours.email`).

---

## 3. ESTADO DE CARGA INTERNA (Simulado localmente)

Se ha ejecutado una prueba de carga en entorno local simulado que garantiza la inicialización del código legacy dentro del tema:

- **Bootstrap Loader:** OK.
- **WPTB_Activator:** Instanciado correctamente.
- **WPTB_Public (Frontend):** Instanciado correctamente.
- **WPTB_Admin (Backend):** Instanciado correctamente.
- **WPTB_Pricing:** Accesible para cálculo.
- **HQP_Public (Hoteles):** Cargado y encolado.

**Riesgos técnicos mitigados:**
- Las rutas de los assets (`plugin_dir_url()`) han sido redirigidas mediante las constantes de compatibilidad en `Application.php` para apuntar a la nueva ruta dentro del tema (`/app/Legacy/WPTB/`).

---

## 4. INSTRUCCIONES EN CASO DE ERROR (Rollback)

Si alguna de las pruebas funcionales anteriores **falla** y rompe la web:

1. Reactiva el plugin original desde el panel de WordPress.
2. Vuelve a probar la web (la funcionalidad debe regresar de inmediato).
3. Reporta el error exacto (o log de debug) para que se parchee en la capa de integración sin alterar la estructura objetivo.
