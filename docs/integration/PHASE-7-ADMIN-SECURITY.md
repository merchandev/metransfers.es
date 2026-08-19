# Fase 7 — Seguridad administrativa

## Capacidades

El panel integrado deja de depender de `manage_options` y define capacidades separadas:

- `mt_manage_bookings`;
- `mt_manage_vehicles`;
- `mt_manage_hotels`;
- `mt_view_stats`;
- `mt_export_bookings`;
- `mt_manage_integrations`;
- `mt_manage_notifications`.

Los administradores conservan todas. El rol `metransfers_operator` recibe operación, exportación y notificaciones, pero no puede cambiar integraciones, secretos, plugins ni opciones globales. `check_hoteles` conserva acceso únicamente a sus propios hoteles; no recibe estadísticas globales, exportación, notificaciones ni gestión de flota.

Los menús, handlers AJAX/admin-post y CPT de destinos/hoteles comprueban la capacidad correspondiente; el nonce sigue siendo obligatorio y no sustituye autorización.

## Notificaciones

«Reenviar emails» llama exclusivamente a los tres canales de correo. «Reenviar WhatsApp» es una acción distinta, requiere confirmación explícita, capacidad de notificaciones y una reserva pagada/final. Ninguna de las dos acciones dispara la otra.

## Secretos y tokens

- WhatsApp se resuelve mediante `Settings`, con soporte para `MT_WHATSAPP_API_KEY` y `MT_WHATSAPP_ADMIN_PHONE`.
- Su API key es write-only en el formulario: el valor existente nunca vuelve al HTML.
- Los secretos guardados en `wp_options` se marcan `autoload = no`.
- Los tokens de hotel son inmutables, se generan con 128 bits aleatorios y se muestran enmascarados en listados, recibos y dashboards; el acceso directo a edición/QR también comprueba propiedad para `check_hoteles`.
- Los exports ya no incluyen el token de hotel.

## Exportaciones

Requieren `mt_export_bookings`, HMAC nonce y un rango explícito no superior a 366 días. La respuesta usa `no-store`, elimina tokens y registra rango/cantidad en auditoría. El archivo de respaldo permanece protegido por el control de path de Fase 1.

## Auditoría

`wp_mt_admin_audit` registra actor ID, acción, tipo/ID del objeto, contexto operacional saneado y fecha UTC. No almacena nombre, email, teléfono, dirección, notas, token, password ni API key. El administrador de integraciones puede consultar las últimas 100 acciones desde **MeTransfers → Auditoría**.

## Verificación

```bash
php tests/test-admin-security.php
```

La prueba cubre el mapa de capacidades, masking, redacción del audit log, separación de reenvíos, secretos write-only, autoload privado, límites de exportación y schema aditivo.
