# Correcciones de indexación y contenido — 2 de octubre de 2026

## Estado de esta entrega

La auditoría inicial se conserva en [AUDITORIA-INDEXACION-TRAFICO-2026-10-02.md](AUDITORIA-INDEXACION-TRAFICO-2026-10-02.md). Este documento distingue cambios guardados en WordPress de código pendiente de instalación. La comprobación HTTP posterior al despliegue se añadirá al cerrar la intervención.

## Respaldo comprobado

WPvivid terminó una copia manual de **base de datos y archivos** el 2 de octubre de 2026, a las **13:48 UTC**. Identificador `wpvivid-2073ca51bbebc`, tamaño **423,00 MB**, cuatro componentes. El panel muestra **Succeeded**. Se almacena en el servidor en `wp-content/wpvividbackups`; no se han enviado reservas ni credenciales a GitHub.

## Cambios guardados directamente en producción

- Descripciones SEO de las rutas 31241–31248: Taüll, Besalú, Morella, Altea, Valderrobres, Alquézar, Collioure y Carcassonne. Se describe el servicio existente sin inventar precios, distancias o tiempos.
- Título SEO de la entrada 27519: **Barcelona a Andorra: esquí y compras | MeTransfers**, diferenciado del artículo general 1017.
- Enlace contextual de Montserrat en la entrada 29556: apunta a `/tour-a-montserrat/` y muestra «excursión privada a Montserrat». Se conserva el resto del cuerpo y el enlace comercial externo existente.
- Página 29076: la guía de IVA/DIVA se recupera en `/recuperar-iva-aeropuerto-barcelona-tax-free/`, con respuesta 200 y canonical propio. El sitemap de páginas ya contiene la dirección nueva y no la anterior terminada en `-2`.
- Dos reglas regex de Yoast corrigen los destinos históricos de IVA hacia la guía, con sus capturas de idioma y parámetros. La primera comprobación detectó una respuesta española de `-2` todavía servida por otra regla o caché; su cadena se verificará de nuevo tras la purga.

Las copias de contenido y metadatos originales están fuera de Git, junto a las verificaciones públicas. No se cambian precios, reservas, usuarios, pagos ni credenciales.

## Código preparado para el tema 5.0.7 / plataforma 6.9.5

### Navegación inglesa

`Router::archiveRequest()` reconoce el blog paginado y las categorías, incluida su jerarquía y una base personalizada. La consulta recupera entradas publicadas sin contraseña y copia la paginación al contexto principal de WordPress. Las páginas fuera de rango y las categorías inexistentes conservan una respuesta 404 real.

### Revisión SEO desde WordPress

La nueva pantalla **Herramientas → Revisión SEO de idiomas** permite aprobar traducciones seleccionadas. Requiere el permiso de integraciones existente, permiso de edición de cada contenido y nonce; no amplía roles. Cada aprobación exige revisión editorial expresa y respuesta HTTP 200 sin redirecciones.

La validación completa del lote ocurre antes de escribir. Un elemento inválido impide aprobar los demás. Se comprueban nuevamente las huellas de contenido antes de guardar y se restauran los valores anteriores si falla una escritura. Las incidencias de restauración se notifican.

La huella nueva incluye título, cuerpo, extracto, metadatos SEO, plantilla utilizada y catálogo editorial inglés. Guardar un contenido idéntico ya no revoca la aprobación por cambiar solamente `post_modified`. Se mantiene compatibilidad con aprobaciones anteriores mediante su huella original.

### Sitemap y enlaces alternativos

`/mt-language-sitemap.xml` contiene únicamente variantes publicadas, aptas y revisadas. Se incorpora al índice de Yoast; la alternativa nativa de WordPress también registra un proveedor. Las páginas privadas, de reservas/pago y traducciones pendientes no se incluyen.

La portada puede anunciar enlaces `hreflang` recíprocos cuando su traducción está aprobada. Los listados de blog y rutas conservan su política editorial hasta revisar su contenido completo. El alias `/cookies/` deja de aparecer como página canónica en los sitemaps y conserva su redirección legal.

### Paquete de producción

El generador de ZIP admite `-ThemeSlug`, validado como un solo nombre de directorio. Se ha comprobado que el tema activo utiliza **mtseo01102026**; la actualización debe conservar ese identificador para mantener sus ajustes.

## Validación previa

- PHPUnit: **114 pruebas y 984 aserciones**, sin avisos del ejecutor.
- PHPStan y WPCS: sin errores después de las correcciones.
- Regresiones heredadas: reservas, precios, direcciones, Redsys, hoteles, recibos, seguridad y traducción pasan. La prueba negativa de vehículo incompleto emite avisos PHP de su fixture; no se ha alterado el cálculo de precios para ocultarlos.
- Se añaden contratos HTTP de WordPress real: categorías y paginación inglesa, exclusión de contenido protegido y descubrimiento de variantes aprobadas en los sitemaps nativo/Yoast. Su ejecución en CI está pendiente al crear esta entrega.

## Tráfico: conclusión respaldada por datos

Comparación de períodos iguales de 28 días: 1–28 de septiembre frente a 4–31 de agosto de 2026.

| Señal | Septiembre | Agosto | Cambio |
|---|---:|---:|---:|
| Impresiones de Search Console | 14.920 | 17.979 | −17,0 % |
| Clics de Search Console | 128 | 109 | +17,4 % |
| Suma de usuarios diarios de Analytics | 219 | 524 | −58,2 % |
| Organic Search: suma diaria | 66 | 31 | +112,9 % |
| Paid Search: suma diaria | 0 | 135 | desaparece |
| Cross-network: suma diaria | 0 | 190 | desaparece |

Las sumas de usuarios diarios no equivalen a usuarios únicos de 28 días, sesiones o ventas. La pérdida de tráfico se asocia principalmente a los canales de pago. Falta comprobar en Google Ads si hubo pausa, presupuesto, rechazo o un cambio de medición. No se han reactivado campañas ni aumentado gasto.

## Límites de comprobación

La web española no presenta un bloqueo global de indexación. El control de traducciones del tema sí explica el `noindex` inglés. Habilitar una variante no garantiza que Google la indexe ni una posición determinada.

Search Console requiere comprobar todavía motivos de exclusión por URL con la cuenta propietaria. Las mediciones móviles de laboratorio de Site Kit variaron entre LCP 4,3 y 13,5 segundos; no equivalen a datos de usuarios reales. La imagen principal mide 182.642 bytes, por lo que su tamaño aislado no demuestra la causa del retraso. La validación final incluirá el flujo de cotización, sin registrar una reserva ni efectuar un pago real.
