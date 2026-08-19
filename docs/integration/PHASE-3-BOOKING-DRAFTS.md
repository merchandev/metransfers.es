# Fase 3 — Booking Drafts e idempotencia del inicio de pago

## Resultado

El formulario ya no entrega la PII a la página de pago mediante `sessionStorage`. Al terminar el paso de datos personales, el servidor crea un borrador temporal y el navegador reemplaza el estado previo por:

```json
{
  "draft_token": "<token-opaco-de-256-bits>"
}
```

El token contiene 256 bits aleatorios. La base de datos conserva únicamente su hash SHA-256.

## Persistencia

La versión de plataforma y esquema pasa a `6.2.0`. La migración administrada por `WPTB_Activator` añade:

- `wp_mt_booking_drafts`, con payload, expiración, consumo y vínculo a la reserva;
- `payment_idempotency_key` en `wp_wptb_bookings`;
- índices únicos para `token_hash`, la clave del borrador y la clave de pago.

Los borradores vencen a las dos horas. Un evento horario elimina hasta 500 filas expiradas por ejecución. Que el código esté desplegado no demuestra por sí solo que la migración ya se ejecutó en producción; debe comprobarse `mt_platform_db_version` después del despliegue.

## Inicio de pago

1. El navegador envía la PII una sola vez al endpoint protegido de creación del borrador.
2. La página de pago recupera sólo un resumen permitido y lo mantiene en memoria, no en almacenamiento web.
3. `wptb_initiate_redsys` recibe el token, el consentimiento de términos y el identificador analítico opcional.
4. El servidor vuelve a calcular la cotización y valida contacto y capacidad.
5. La inserción de la reserva usa la clave idempotente del borrador.
6. Una repetición devuelve la reserva y el formulario Redsys existentes. El índice único protege también dos solicitudes concurrentes.

## Verificación

```bash
php tests/test-booking-drafts.php
```

La prueba cubre token y hash, expiración, exclusión de PII del resumen, almacenamiento del navegador y doble inicio con una sola reserva.

## Despliegue y reversión

Antes de desplegar, hacer copia de la base de datos y ejecutar la suite completa. Después, verificar que existen `wp_mt_booking_drafts` y `payment_idempotency_key`, y realizar una compra de prueba en el entorno Redsys configurado.

Para revertir el código, volver a la versión anterior. Las columnas y la tabla nuevas son aditivas y pueden permanecer sin afectar al flujo anterior; no deben eliminarse durante una reversión de emergencia. La eliminación física requiere una migración posterior y una política explícita de retención.
