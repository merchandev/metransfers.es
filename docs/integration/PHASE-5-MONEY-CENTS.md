# Fase 5 — Dinero entero en céntimos

## Resultado

El importe financiero se representa mediante `MeTransfers\Pricing\Money`. El objeto acepta céntimos enteros, rechaza negativos y convierte el decimal de compatibilidad en un único punto controlado.

`wp_wptb_bookings` incorpora:

```sql
price_cents BIGINT UNSIGNED NULL
```

La migración rellena únicamente filas aún no convertidas usando aritmética `DECIMAL` de MySQL:

```sql
UPDATE wp_wptb_bookings
SET price_cents = CAST(ROUND(price * 100) AS UNSIGNED)
WHERE price_cents IS NULL
  AND price IS NOT NULL
  AND price >= 0;
```

## Compatibilidad

Durante esta transición se aplica dual-write:

- `price_cents` es el importe preferido;
- `price` continúa escrito para admin, informes y extensiones legacy;
- `Money::fromBooking()` usa `price_cents` y sólo recurre a `price` para filas antiguas.

La creación web, WooCommerce legacy y Hotel QR escriben ambas columnas.

## Bordes financieros

Redsys recibe directamente `price_cents`. La verificación del importe en el IPN también compara contra el entero almacenado, sin ejecutar `price * 100` en PHP.

Analytics convierte el entero a decimal sólo al formar el payload GA4. Emails y WhatsApp formatean desde `Money`.

## Verificación

```bash
php tests/test-money.php
```

La prueba cubre conversión y redondeo, rechazo de negativos, lectura legacy, preferencia de céntimos, schema/backfill, dual-write y ausencia de conversiones float en los bordes Redsys.

La aplicación pasa a `6.4.0` y el esquema a `6.3.0`. Antes del despliegue debe existir una copia de la base de datos; después debe comprobarse el porcentaje de filas con `price_cents IS NULL`, que debe ser cero para reservas con precio no negativo.
