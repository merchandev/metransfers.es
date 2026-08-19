# Guía de Debugging - Plugin Metransfers v2.0.1

## 🐛 Problemas Comunes y Soluciones

### Problema 1: "No hay vehículos disponibles"

**Síntomas:**
- El paso 2 muestra "No hay vehículos disponibles"
- O aparece el mensaje antiguo en consola

**Causas Posibles:**
1. Cache del navegador
2. No hay vehículos activos en la base de datos
3. Error AJAX

**Soluciones:**

#### A. Limpiar Cache del Navegador
```
1. Presiona Ctrl + Shift + Delete
2. O Ctrl + F5 (hard refresh)
3. O usa modo incógnito
```

#### B. Verificar que hay vehículos
```
1. WordPress Admin → Metransfers → Vehículos
2. Verificar que hay al menos 1 vehículo
3. Verificar que está marcado como "Activo"
```

#### C. Debug AJAX en Consola
```javascript
1. Abre consola del navegador (F12)
2. Ve a la pestaña Console
3. Escribe: testVehiclesEndpoint()
4. Presiona Enter
5. Verifica la respuesta
```

---

### Problema 2: Filtro por tipo no funciona

**Solución:**
Verifica que los tipos de vehículos coincidan con los slugs:
- sedan → Sedán
- suv → SUV
- van → Van
- minibus → Minibús
- luxury → Lujo

---

### Problema 3: Version Antigua de JS/CSS

**Síntomas:**
- Cambios no se reflejan
- Errores de JavaScript antiguos

**Solución:**
```php
// Versiones actuales deben ser 2.0.1
wp_enqueue_script('wptb-booking-js', ..., '2.0.1', true);
wp_enqueue_style('wptb-booking-css', ..., '2.0.1');
```

---

## 🔧 Herramientas de Debug

### Comando 1: Probar Endpoint de Vehículos
```javascript
testVehiclesEndpoint()
```
**Resultado esperado:**
```
✅ Found 3 vehicles:
1. Mercedes Clase E (Sedán) - Capacity: 4
2. BMW X5 (SUV) - Capacity: 5
3. Mercedes Vito (Van) - Capacity: 8
```

### Comando 2: Probar Cálculo de Precios
```javascript
testPricingEndpoint(1, 50, 'one_way')
```
**Parámetros:**
- vehicleId: ID del vehículo
- distance: Distancia en km
- tripType: 'one_way' o 'round_trip'

**Resultado esperado:**
```
💶 Price: €75.00
```

---

## 📊 Verificar Base de Datos

### SQL Query - Ver Vehículos
```sql
SELECT v.*, t.name as type_name 
FROM wp_wptb_vehicles v
LEFT JOIN wp_wptb_vehicle_types t ON v.vehicle_type_id = t.id
WHERE v.is_active = 1;
```

### SQL Query - Ver Tipos de Vehículos
```sql
SELECT * FROM wp_wptb_vehicle_types 
ORDER BY display_order;
```

---

## 🔍 Checklist de Verificación

Antes de reportar un bug, verifica:

- [ ] Cache del navegador limpiado (Ctrl + F5)
- [ ] Hay al menos 1 vehículo activo
- [ ] El vehículo tiene imagen subida (o usa placeholder)
- [ ] El vehículo tiene precios configurados
- [ ] Google Maps API está configurada
- [ ] Las 4 APIs de Google Maps están habilitadas
- [ ] No hay errores en consola de JavaScript
- [ ] Plugin está en versión 2.0.1
- [ ] WooCommerce está activado

---

## 📞 Pasos para Reportar Bugs

Si después de verificar todo sigue sin funcionar:

1. Abre consola del navegador (F12)
2. Ve a pestaña Console
3. Reproduce el error
4. Copia TODOS los mensajes rojos
5. Ejecuta `testVehiclesEndpoint()`
6. Copia la respuesta
7. Envía toda la información

---

## 🚀 Estado del Sistema

### Versión Actual
**Plugin:** 2.0.1
**CSS:** 2.0.1
**JS:** 2.0.1

### Endpoints AJAX Activos
- ✅ `wptb_get_vehicles` - Obtener vehículos
- ✅ `wptb_calculate_price` - Calcular precios
- ✅ `wptb_save_booking` - Guardar reserva

### Features Activos
- ✅ Restricción geográfica europea
- ✅ Filtro por tipo de vehículo
- ✅ Precios por vehículo
- ✅ Tipo de viaje (ida/ida y vuelta)
- ✅ Google Maps routing

---

## 💡 Tips Adicionales

### Performance
Si tienes muchos vehículos (>20), considera:
- Pagination en el futuro
- Lazy loading de imágenes

### Compatibilidad
- WordPress 5.0+
- WooCommerce 4.0+
- PHP 7.4+
- Navegadores modernos (Chrome, Firefox, Safari, Edge)

### Cache de WordPress
Si usas plugin de cache:
```
1. Limpia cache de WordPress
2. Limpia cache de plugin (WP Rocket, W3 Total Cache, etc.)
3. Limpia cache de CDN (si aplica)
```
