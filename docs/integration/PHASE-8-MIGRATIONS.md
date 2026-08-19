# Fase 8 — Migraciones reanudables

## Orquestación

`MeTransfers\Core\Migrations` deja de ejecutar el activador monolítico. Cada cambio tiene un identificador estable, una versión objetivo y un callback independiente. La versión global solo avanza cuando todos los pasos terminan correctamente.

La ejecución adquiere un advisory lock de MySQL/MariaDB con `GET_LOCK`, vuelve a leer la versión después del lock y lo libera en un bloque `finally`. Una petición concurrente que no obtiene el lock no modifica schema ni datos; otro request volverá a evaluar el gate.

## Journal

`wp_mt_schema_migrations` registra por paso:

- identificador único;
- versión;
- estado `running`, `succeeded` o `failed`;
- inicio y fin UTC;
- código de error no reversible y sin mensajes SQL potencialmente sensibles.

Si un paso falla, la versión global no cambia. El siguiente intento conserva los pasos `succeeded` y reintenta únicamente el fallido y los pendientes.

## Responsabilidades separadas

- `Schema`: únicamente definiciones `dbDelta` agrupadas por core, eventos y flota.
- `DataMigrations`: backfills idempotentes y compatibilidad del rango de IDs.
- `Seeds`: tipos de vehículo, producto WooCommerce y páginas iniciales; se ejecutan en activación, fuera del journal de schema.
- `WPTB_Activator`: fachada legacy sin SQL propio.

No se eliminan tablas, columnas, reservas, IDs, slugs ni shortcodes. El backfill de `price_cents` continúa siendo repetible y conserva la columna decimal para lectores legacy.

## Verificación

```bash
php tests/test-migrations.php
```

La prueba ejecuta orden, lock, journal, fallo parcial, liberación en `finally` y reanudación sin repetir pasos completados.
