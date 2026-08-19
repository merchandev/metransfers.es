# Fase 4 — Cotización server-side de vehículos

## Resultado

`wptb_get_vehicles` dejó de ser un catálogo con coeficientes tarifarios. Ahora recibe el contexto de la ruta, lo valida una sola vez y devuelve el precio autoritativo de cada vehículo activo:

```json
{
  "vehicles": [
    {
      "id": 7,
      "price": 185.0,
      "currency": "EUR",
      "capacity": 4,
      "luggage_capacity": 3
    }
  ],
  "route": {
    "total_distance_km": 92.4,
    "duration_minutes": 78
  }
}
```

El payload público no incluye mínimos, precios por kilómetro, precio por hora ni el desglose tarifario. `booking-app.js` y `transfers-search.js` renderizan `vehicle.price` sin recalcularlo.

## Ruta compartida

`RouteContext` concentra:

- fecha y hora;
- política de área de servicio;
- distancia y duración de ida;
- distancia y duración de vuelta;
- distancia utilizada por pricing.

Una solicitud cotiza todos los vehículos con el mismo contexto, por lo que no repite llamadas de Maps por vehículo. Durante la selección, una ida y vuelta sin datos de retorno todavía usa el regreso inverso; el formulario de detalles vuelve a cotizar con el retorno exacto antes de crear el draft.

## Capacidad y equipaje

`VehicleCapacityPolicy` es la regla compartida por:

- WooCommerce legacy;
- creación de booking drafts;
- inicio Redsys;
- disponibilidad de la lista de vehículos.

Las maletas grandes y de mano consumen juntas `luggage_capacity`. Se eliminaron los límites browser-only que inferían capacidad por tipo de vehículo.

## Verificación

```bash
php tests/test-server-vehicle-quotes.php
```

La prueba comprueba una sola resolución de ruta para varios vehículos, igualdad entre precio de UI y quote de pago, ausencia de coeficientes públicos y reglas unificadas de pasajeros/equipaje.

Esta fase cambia la versión de aplicación a `6.3.0` y no modifica el esquema `6.2.0`.
