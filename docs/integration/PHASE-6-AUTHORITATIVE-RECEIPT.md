# Fase 6 — Recibo canónico y verificable

## Resultado

El recibo público ya no se genera desde `sessionStorage`, un draft ni `window.lastBookingData`. El enlace mostrado tras la confirmación contiene una referencia Redsys y un HMAC ligado a esa referencia.

`ReceiptService` acepta el enlace únicamente cuando:

- la referencia conserva su formato exacto;
- el HMAC es válido;
- existe una reserva con esa referencia;
- `payment_status = paid`;
- el estado es `confirmed` o `completed`.

El DTO se reconstruye desde `wp_wptb_bookings`, resuelve el vehículo en servidor y formatea el total desde `price_cents`.

## Entrega

`ReceiptController` entrega HTML imprimible y permite usar la función nativa del navegador «Imprimir o guardar como PDF». Se retiraron jsPDF, su CDN, el logo hardcodeado y toda autoridad financiera del navegador.

La respuesta aplica:

- `Cache-Control`/cabeceras WordPress de no-cache;
- `X-Robots-Tag: noindex, nofollow, noarchive`;
- `Referrer-Policy: no-referrer`;
- `X-Content-Type-Options: nosniff`;
- CSP limitada a assets propios;
- respuesta 404 para token, referencia o estado no válidos.

## Compatibilidad

- Se conserva `/reservas-metransfers/` y sus prefijos de idioma.
- La pista `payment_result` nunca modifica el pago: una reserva pagada en DB se muestra confirmada incluso si la pista del retorno es `ko`.
- La confirmación sigue exponiendo el mismo estado seguro para el tracking de compra.
- El checkout conserva su flujo y su draft opaco; la confirmación carga solamente un script mínimo para eliminar ese token de sesión.

## Verificación

```bash
php tests/test-authoritative-receipt.php
```

La prueba cubre token válido e inválido, referencia malformada, rechazo de una reserva pendiente, preferencia de `price_cents`, vehículo/locale de DB, URL localizada y guardas de privacidad del endpoint.
