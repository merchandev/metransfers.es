# 🚀 PLUGIN METRANSFERS V2.0.0 - CHANGELOG

## Version 2.0.0 - Mejora Integral (2024-12-17)

### ✨ Nuevas Funcionalidades

#### Sistema de Gestión de Vehículos
- ✅ Panel completo de administración para gestionar vehículos
- ✅ CRUD completo: Crear, Editar, Eliminar vehículos
- ✅ Subida y gestión de múltiples imágenes por vehículo
- ✅ 5 tipos de vehículos predefinidos (Sedán, SUV, Van, Minibús, Lujo)
- ✅ Configuración individual de capacidad y equipaje

#### Sistema de Precios Avanzado
- ✅ Precio mínimo general para traslados
- ✅ Precio mínimo específico para "Solo Ida"
- ✅ Precio mínimo específico para "Ida y Vuelta"
- ✅ Precio por kilómetro diferenciado (ida vs ida y vuelta)
- ✅ Precio por hora (opcional)
- ✅ Validación automática de precios mínimos

#### Restricción Geográfica Europea
- ✅ Limitación a países europeos conectados con España
- ✅ Países permitidos: ES, FR, DE, PT, AD, CH, BE
- ✅ Implementado en Google Places API

#### Flujo de Reserva Mejorado
- ✅ Proceso dividido en 4 pasos claros
- ✅ Paso 2: Selección visual de vehículos con tarjetas
- ✅ Toggle moderno para "Solo Ida" / "Ida y Vuelta"
- ✅ Cálculo dinámico de precios en tiempo real
- ✅ Filtrado automático por capacidad

#### Integración Google Maps
- ✅ Autocompletado con restricción geográfica
- ✅ Cálculo automático de distancia y duración
- ✅ Visualización de ruta en mapa interactivo
- ✅ Marcadores en origen y destino
- ✅ Panel de instrucciones de ruta

#### Mejoras de Diseño
- ✅ Diseño moderno con tarjetas de vehículos
- ✅ Animaciones suaves y transiciones CSS
- ✅ 100% responsive (móvil, tablet, desktop)
- ✅ Diseño Bento para resumen de reserva
- ✅ Progress bar con 4 pasos visuales

### 🗄️ Base de Datos

#### Nuevas Tablas
- `wp_wptb_vehicle_types` - Tipos de vehículos
- `wp_wptb_vehicles` - Vehículos con precios
- `wp_wptb_vehicle_images` - Imágenes de vehículos

#### Tabla Actualizada
- `wp_wptb_bookings` - Añadidos campos:
  - `vehicle_id` - Vehículo seleccionado
  - `trip_type` - Tipo de viaje (one_way/round_trip)
  - `duration_minutes` - Duración estimada

### 🔒 Seguridad

- ✅ Sanitización de todos los inputs
- ✅ Nonces en todos los AJAX requests
- ✅ Validación de capabilities (solo admin)
- ✅ SQL prepared statements
- ✅ Validación de tipos de archivo
- ✅ Prevención XSS

### 🔧 Archivos Nuevos

**Backend:**
- `includes/class-wptb-vehicle-manager.php`
- `includes/class-wptb-pricing.php`
- `includes/class-wptb-vehicles-admin.php`

**Assets:**
- `assets/images/vehicle-placeholder.png`

### 📝 Archivos Modificados

- `includes/class-wptb-activator.php` - Creación de tablas
- `includes/class-wptb-loader.php` - Carga de nuevas clases
- `includes/class-wptb-public.php` - AJAX y WooCommerce
- `templates/booking-form.php` - Nuevo template de 4 pasos
- `assets/js/booking-app.js` - Lógica completa reescrita
- `assets/css/style.css` - CSS moderno y responsive
- `wp-booking-plugin.php` - Versión actualizada a 2.0.0

### 🎯 Integración WooCommerce

- ✅ Metadata de vehículo en carrito y pedidos
- ✅ Metadata de tipo de viaje
- ✅ Toda la información visible en emails
- ✅ Pre-llenado de campos de checkout

### 📚 Documentación

- ✅ Walkthrough completo con guía de uso
- ✅ Instrucciones de instalación
- ✅ Guía de testing
- ✅ Ejemplos de configuración

---

## Instrucciones de Actualización

1. **Hacer backup** de la base de datos
2. **Desactivar** el plugin en WordPress
3. **Reemplazar** carpeta del plugin con la nueva versión
4. **Activar** el plugin (esto creará las nuevas tablas)
5. **Añadir vehículos** desde Metransfers → Vehículos
6. **Configurar precios** para cada vehículo
7. **Probar** el flujo de reserva

---

## Próximos Pasos Recomendados

1. Añadir vehículos reales con fotos
2. Configurar precios según tarifas de la empresa
3. Verificar que Google Maps API tenga las APIs necesarias habilitadas
4. Probar flujo completo de reserva
5. Configurar métodos de pago en WooCommerce

---

## Soporte

Para dudas o problemas, consultar el archivo `walkthrough.md` que contiene:
- Guía completa de uso
- Instrucciones detalladas
- Ejemplos de configuración
- Procedimientos de testing
