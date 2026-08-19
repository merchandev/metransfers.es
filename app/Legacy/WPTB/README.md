# Plugin Metransfers - Sistema Avanzado de Reservas

**Versión:** 2.0.0  
**Autor:** Metransfers  
**Compatible con:** WordPress 5.0+, WooCommerce 4.0+  
**Licencia:** Propietario

## 📋 Descripción

Sistema completo de reservas y traslados con gestión avanzada de vehículos, precios dinámicos, restricción geográfica europea e integración total con Google Maps y WooCommerce.

## ✨ Características Principales

### 🚗 Gestión de Vehículos
- Panel de administración completo
- Tipos de vehículos predefinidos (Sedán, SUV, Van, Minibús, Lujo)
- Subida de múltiples imágenes por vehículo
- Configuración individual de capacidad y equipaje
- Estado activo/inactivo

### 💰 Sistema de Precios Flexibles
- Precio mínimo general para traslados
- Precios diferenciados para "Solo Ida" e "Ida y Vuelta"
- Precio por kilómetro configurable para cada tipo
- Precio por hora opcional
- Validación automática de mínimos

### 🌍 Restricción Geográfica
- Limitado a países europeos: España, Francia, Alemania, Portugal, Andorra, Suiza, Bélgica
- Implementado en Google Places API

### 🗺️ Integración Google Maps
- Autocompletado con restricción geográfica
- Cálculo automático de distancia y duración
- Visualización de ruta en mapa interactivo
- Marcadores personalizados

### 🎨 Diseño Moderno
- Flujo de reserva en 4 pasos intuitivos
- Diseño responsive (móvil, tablet, desktop)
- Animaciones suaves y transiciones
- Tarjetas visuales para selección de vehículos

## 🚀 Instalación

1. Subir la carpeta del plugin a `/wp-content/plugins/`
2. Activar el plugin desde el panel de WordPress
3. Las tablas de base de datos se crearán automáticamente
4. Configurar Google Maps API en **Metransfers → Configuración**
5. Añadir vehículos desde **Metransfers → Vehículos**

## ⚙️ Configuración

### Google Maps API
Habilitar las siguientes APIs en Google Cloud Console:
- Maps JavaScript API
- Places API
- Distance Matrix API
- Directions API

### Añadir Vehículos
1. Ir a **Metransfers → Vehículos → Añadir Nuevo**
2. Completar información del vehículo
3. Configurar precios
4. Subir imágenes
5. Marcar como activo
6. Guardar

## 📖 Uso

### Shortcode
```
[wptb_booking]
```

Insertar en cualquier página de WordPress para mostrar el formulario de reservas.

### Flujo de Reserva

1. **Paso 1 - Búsqueda**: Usuario ingresa fecha, hora, origen, destino y pasajeros
2. **Paso 2 - Vehículo**: Selección de vehículo y tipo de viaje (Ida/Ida y Vuelta)
3. **Paso 3 - Detalles**: Formulario de datos personales + mapa de ruta
4. **Paso 4 - Checkout**: Redirección a WooCommerce para pago

## 🗄️ Base de Datos

### Tablas Creadas
- `wp_wptb_bookings` - Reservas
- `wp_wptb_vehicles` - Vehículos
- `wp_wptb_vehicle_types` - Tipos de vehículos
- `wp_wptb_vehicle_images` - Imágenes de vehículos

## 📁 Estructura de Archivos

```
wp-booking-plugin/
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── booking-app.js
│   └── images/
│       └── vehicle-placeholder.png
├── includes/
│   ├── class-wptb-activator.php
│   ├── class-wptb-admin.php
│   ├── class-wptb-loader.php
│   ├── class-wptb-pricing.php
│   ├── class-wptb-public.php
│   ├── class-wptb-vehicle-manager.php
│   └── class-wptb-vehicles-admin.php
├── templates/
│   └── booking-form.php
├── CHANGELOG.md
├── README.md
└── wp-booking-plugin.php
```

## 🔒 Seguridad

- ✅ Sanitización de todos los inputs
- ✅ Nonces en AJAX requests
- ✅ SQL prepared statements
- ✅ Validación de capabilities
- ✅ Prevención XSS

## 🛠️ Requisitos del Sistema

- PHP 7.4 o superior
- WordPress 5.0 o superior
- WooCommerce 4.0 o superior
- Google Maps API Key

## 📞 Soporte

Para documentación completa, consultar:
- `walkthrough.md` - Guía completa de uso
- `CHANGELOG.md` - Historial de cambios
- `implementation_plan.md` - Plan técnico de implementación

## 📝 Licencia

Este plugin es propietario de Metransfers. Todos los derechos reservados.

## 🔄 Actualizaciones

### Versión 2.0.0 (17/12/2024)
- Sistema completo de gestión de vehículos
- Precios dinámicos por vehículo y tipo de viaje
- Restricción geográfica europea
- Integración avanzada de Google Maps
- Diseño moderno y responsive
- Seguridad robusta

Para más detalles ver `CHANGELOG.md`

---

**Desarrollado para Metransfers** 🚗✨
