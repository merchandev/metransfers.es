# Reservas MeTransfers - Complete Booking Plugin v6

**Sistema avanzado de reservas de traslados y gestión de hoteles para WordPress.**

## 📖 Acerca del Proyecto (About)
Este proyecto es un plugin integral para WordPress diseñado específicamente para empresas de traslados y transfers. Su objetivo principal es facilitar la reserva de vehículos (desde sedanes hasta minivans) a través de un flujo intuitivo, con cálculo automático de tarifas basado en la distancia mediante la API de Google Maps, y ofrecer un módulo dedicado exclusivamente a la recepción de hoteles mediante el escaneo de códigos QR.

Fue **desarrollado para ME TRANSFERS** por **Merchan.Dev | Arturo Merchan**, asegurando una experiencia de usuario premium, moderna y altamente funcional.

## ✨ Características Principales

### 1. Sistema de Reservas de Traslados (Core)
- **Calculadora de Tarifas**: Cálculo automático y preciso de precios basado en distancia y rutas (Google Maps API) y tipo de vehículo.
- **Flujo de Reserva Optimizado**: Proceso paso a paso guiado (Ruta -> Selección de Vehículo -> Datos del Cliente -> Pago).
- **Gestión de Flota**: Administración de vehículos con precios personalizados, capacidad máxima de equipaje y pasajeros.
- **Múltiples Pasarelas de Pago**: Integración flexible y robusta con Stripe y WooCommerce.
- **Panel de Administración**: Gestión completa de reservas, control de estados y notificaciones automáticas por email y WhatsApp.

### 2. Módulo de Hoteles QR (Nuevo ⭐)
Una interfaz especializada y simplificada, diseñada para la recepción de hoteles y acceso rápido mediante el escaneo de códigos QR.

- **Diseño Premium**: Interfaz moderna en modo oscuro (Azul/Naranja) para una experiencia de usuario superior.
- **Flujo Simplificado**:
    - Selección rápida de vehículo: **Sedan (hasta 4 pax)** o **Minivan (hasta 7-8 pax)**.
    - **Geolocalización Inteligente**: Restricción de direcciones a Cataluña, con autocompletado de Google Maps optimizado para precisión.
    - **Cálculo de Rutas Automático**: Detección inteligente de trayectos "Desde el Hotel" o "Hacia el Hotel".
- **Integración de Pagos Directa**: Conexión directa con la pasarela **Redsys (Getnet/Santander)** sin necesidad de pasar por WooCommerce, agilizando el proceso de cobro en recepción.
- **Dashboard Específico**: Panel de administración separado ("Hoteles QR") con un diseño limpio, sin emojis y estados claros para facilitar la lectura.

## 🚀 Instalación y Configuración

1. Sube la carpeta del plugin al directorio `/wp-content/plugins/` de tu instalación de WordPress.
2. Activa el plugin **"Sistema de Reservas - Metransfers (Renovado)"** desde el panel de administración de WordPress.
3. Configura las claves de API necesarias en los ajustes del plugin:
    - **Google Maps**: Places API, Directions API, y Distance Matrix API.
    - **Stripe / Redsys**: Claves correspondientes de entorno de pruebas o producción.

## 💻 Uso de Shortcodes

Puedes integrar los formularios en cualquier página o entrada utilizando los siguientes shortcodes:

- **Formulario General (Traslados)**:
  ```text
  [wptb_booking_form]
  ```

- **Formulario Hotel QR (Especializado)**:
  ```text
  [hqp_booking_form]
  ```
  *(Nota: Este formulario detecta automáticamente el token del hotel si se pasa por parámetro en la URL, ej: `?promo=TOKEN_HOTEL`).*

## 📁 Estructura del Proyecto

- `/modules/booking/`: Núcleo del sistema de reservas general (core).
- `/modules/hotel/`: Módulo independiente para la funcionalidad específica de Hoteles QR.
- `/assets/`: Recursos estáticos globales (CSS, JS, Imágenes).
- `/includes/`: Clases PHP auxiliares e integraciones de terceros (ej. `class-unified-integration.php`).

## 👨‍💻 Créditos

Desarrollado para **ME TRANSFERS** por **Merchan.Dev | Arturo Merchan**.
