# Seguridad y entregabilidad del newsletter — 16/10/2026

## Actualización 5.0.8: altas manuales por tandas y eliminación

- Newsletter → Ajustes permite pegar listas separadas por comas, punto y coma, espacios o saltos de línea. Las altas individuales del administrador y los archivos CSV/XLSX también usan la cola manual.
- La cola no tiene un máximo total acumulado. Se conserva en una tabla propia, con índice único por email, y se puede seguir alimentando mientras se procesa. Cada carga sigue sujeta a los límites de tamaño del servidor; los archivos mantienen el límite de 20.000 filas y 10 MB por carga.
- Primera incorporación: seis horas después de la primera carga. Después, una tanda aleatoria de 12 a 98 filas cada seis horas; el último resto puede contener menos de 12. Orden FIFO. Las cargas posteriores no posponen la próxima tanda. No hay ráfagas para recuperar ventanas perdidas.
- Popup y pie de página entran directamente sin cupo de altas acumuladas ni espera de seis horas; mantienen doble confirmación y controles contra bots. Si alguien se registra por la web mientras esperaba en la cola, su registro y consentimiento prevalecen.
- En cada carga se elige solicitar confirmación o incorporar suscritos con consentimiento acreditado. Los pendientes reciben confirmación mediante outbox cuando se incorporan; no se les envían promociones. Los duplicados, las bajas y los rebotados no se reactivan ni sobrescriben.
- La pantalla muestra contactos en espera, próxima tanda y resultado de la anterior. Permite retirar contactos individuales o vaciar la espera. WP-Cron depende del tráfico: para puntualidad, el servidor debe ejecutar el cron de WordPress regularmente.
- La eliminación tiene botón por fila y en la ficha, además de la acción masiva. Requiere permiso y nonce, borra historial y entradas manuales coincidentes, comprueba errores y revierte el borrado si falla. Comparte bloqueo con el trabajador para evitar recreaciones concurrentes.
- La cola participa en la exportación y el borrado de datos personales. Nueva migración `20261016_002_newsletter_manual_queue`, esquema `6.12.0`.
- Pruebas nuevas: `php tests/test-newsletter-manual.php` y escenarios WordPress/MariaDB sobre carga diferida, tandas, cron, borrado y privacidad.

Versión actual del tema: **5.0.12**. El apartado «Añadir por lotes · cola manual» se encuentra en Newsletter → Ajustes, está cerrado por defecto y se despliega al pulsar su título. Alcance: módulo Newsletter, migraciones de sus tablas, pruebas y metadatos del tema. Los ajustes SMTP compartidos, reservas, pagos, DNS y servidor de producción no se modifican con este cambio.

## Actualización 5.0.11: seguimiento de bienvenida y confirmación

- Panel siempre visible en Newsletter → Suscriptores, con la carga por lotes ubicada en Ajustes. Se actualiza cada 10 segundos y permite actualizar manualmente sin recargar la página.
- Muestra la cola manual, los contactos pendientes de correo, los que tienen un evento en procesamiento, los contactos con envío aceptado, fallos/rebotes y suscritos. Los contadores son globales y no dependen del filtro de la tabla de suscriptores; cuentan contactos y pueden solaparse cuando alguien tiene un envío anterior y un reintento nuevo.
- Próxima tanda con fecha y cuenta atrás, y resultado de la última tanda. Al llegar la hora se indica que está pendiente la ejecución del cron, sin inventar progreso ni adelantar el intervalo de seis horas.
- Últimos 20 contactos incorporados: correo, estado real del primer correo, intentos de la cola, fecha de aceptación, próximo intento y último error registrado. Usa únicamente el último evento `newsletter.confirm` de cada contacto; los fallos antiguos no ocultan eventos nuevos.
- Un evento outbox procesado no prueba que se haya enviado un correo: la aceptación se obtiene de `confirmation_sent_at`. Tampoco acredita entrega en bandeja de entrada. Los contactos incorporados como suscritos con consentimiento no requieren correo de confirmación y no se presentan como correos enviados.
- El correo inicial existente es una solicitud de confirmación, no un segundo mensaje de bienvenida independiente. Esta actualización añade seguimiento; no envía mensajes adicionales ni procesa colas al consultar el estado.
- La consulta exige permiso de newsletter, nonce y POST y devuelve datos sin caché. Correos y errores se escapan tanto en PHP como en JavaScript; ante un error de consulta se conservan los últimos datos.

## Actualización 5.0.10: arranque y recuperación de campañas

- La acción «Enviar» congela los destinatarios e intenta una primera tanda inmediatamente, sin esperar a WP-Cron. El método de bajo nivel `queue()` sigue disponible para encolar sin enviar.
- La pantalla de campañas con correos pendientes comprueba la cola por AJAX cada 30 segundos. El trabajador autenticado exige permiso, nonce y POST, y limita los intentos automáticos a una tanda por minuto mediante el mismo bloqueo de base de datos que usa el cron. Conserva el límite de correos por hora y los reintentos con espera.
- Este mecanismo también recupera campañas existentes si el cron no se ejecuta. Muestra el último error de una entrega pendiente y el límite horario cuando corresponde. Al terminar indica que se deben actualizar los contadores y revisar los fallos; aceptar por SMTP no acredita la llegada a la bandeja de entrada.
- Si se cierra la pantalla, las tandas restantes siguen dependiendo del cron del servidor o de WP-Cron. No se configura el cron real del alojamiento desde el tema.
- Las altas manuales conservan su cola separada de 12 a 98 cada seis horas. El trabajador de campañas no adelanta esas altas.
- La cabecera del correo utiliza el PNG solicitado: `https://metransfers.es/wp-content/uploads/2026/03/MT-MeTransfers-2048x475.png`, con dimensiones y texto alternativo. El recurso respondió HTTP 200 con tipo image/png al comprobarlo. Los clientes de correo pueden bloquear imágenes externas.
- SMTP tiene un tiempo de espera de 15 segundos para no mantener indefinidamente un intento pendiente; se restaura después del envío junto con las demás propiedades del transporte.

## Hallazgos

La captura aportada muestra `soporte@merchan.dev` mediante `gmadm1037.siteground.biz`. Gmail indica similitud con mensajes anteriormente identificados como spam; la captura no incluye los resultados de autenticación. No permite atribuir el problema únicamente a SPF o DKIM.

Consulta DNS pública realizada el 16/10/2026:

| Registro | Resultado |
| --- | --- |
| SPF `merchan.dev` | `v=spf1 include:_spf.mail.hostinger.com ~all` |
| DMARC `merchan.dev` | `v=DMARC1; p=none` |
| SPF `metransfers.es` | `v=spf1 a mx include:spf.ipzmarketing.com include:metransfers.es.spf.auto.dnssmarthost.net ~all` |
| DMARC `metransfers.es` | `v=DMARC1; p=none; aspf=r; adkim=r;` |

El SPF de `merchan.dev` autoriza explícitamente a Hostinger. Su aparente uso a través de SiteGround requiere comprobar el Return-Path y Authentication-Results del mensaje original; DKIM alineado podría permitir DMARC aunque SPF no esté alineado. DKIM no se ha verificado: falta conocer el selector del mensaje. La política DMARC `p=none` permite supervisar y no constituye por sí sola un fallo de autenticación.

## Refuerzos aplicados

- SMTP con credenciales completas y cifrado `ssl` (TLS implícito) o `tls` (STARTTLS). Sin configuración válida, newsletter devuelve un error y no recurre al correo PHP del alojamiento.
- Validación de certificados y del nombre del servidor; certificados autofirmados rechazados. Depuración SMTP desactivada.
- Remitente explícito y envelope sender alineados en PHPMailer. El proveedor todavía debe autorizar ese buzón y puede reescribir el envelope sender.
- Estado del transporte y texto alternativo restaurados después de cada envío, incluso si falla, para no contaminar los mensajes de reservas que usan el mismo PHPMailer.
- Un destinatario válido por envío; asuntos de una sola línea y rechazo de cabeceras inyectadas. URL de baja solo HTTPS y en el host del sitio.
- Cabeceras List-ID y baja RFC 8058. Inclusión de las cabeceras de baja en la firma cuando PHPMailer tiene DKIM configurado; si firma el proveedor, este debe cubrirlas en su firma.
- Doble confirmación obligatoria para las altas públicas, incluso si estaba desactivada en ajustes guardados. Los pendientes incluidos en una campaña reciben únicamente el correo estándar de confirmación, sin contenido promocional. Se registra el envío y se respeta la espera de 10 minutos en campañas, reenvíos administrativos y reintentos de outbox. Bajas y rebotados se rechazan también en Mailer.
- Las respuestas SMTP explícitas `5.1.1` (buzón inexistente) suprimen el destinatario sin reintentar y cancelan sus campañas pendientes. Los fallos temporales y los errores de autenticación no se confunden con un buzón inexistente. Una alta pública no reactiva una dirección rebotada. Los rebotes asíncronos y las quejas requieren integración con el proveedor.
- La identidad por defecto del newsletter es MeTransfers y la dirección procede del remitente SMTP o su usuario, sin recurrir al correo administrativo del sitio.
- Las visitas GET a confirmación muestran un formulario; únicamente el POST firmado confirma. Esto evita altas por previsualizadores y antivirus. Se conserva la baja POST RFC 8058 sin inicio de sesión.
- Páginas de enlaces firmados con `Referrer-Policy: no-referrer`, protección contra marcos y `nosniff`. Altas públicas limitadas a POST.
- Correos con el PNG de marca verificado y sin hoja externa de fuentes. La confirmación omite el reclamo promocional. Se mantiene HTML con idioma y versión en texto plano.
- Ajustes de newsletter muestran errores de configuración y avisan si el dominio del remitente difiere del sitio; no presentan una configuración local completa como autenticación ya verificada.

## Comprobaciones pendientes en producción

1. Usar un buzón real y autorizado por el proveedor SMTP. Preferiblemente uno de `metransfers.es`, si existe y está habilitado. No basta con escribir una dirección distinta en el campo From.
2. Si se mantiene `soporte@merchan.dev` con SiteGround, obtener del proveedor su autorización SPF y configuración DKIM para **merchan.dev**. Integrar la autorización en un único registro SPF conservando los servicios que todavía envían. No copiar registros de otro dominio ni crear un segundo SPF.
3. Activar/verificar DKIM del dominio remitente y su alineación con From. Confirmar SPF, DKIM y DMARC en Gmail → Mostrar original, con una nueva suscripción de prueba tras desplegar. Revisar también Return-Path y `h=` de la firma DKIM para las cabeceras de baja.
4. Verificar TLS, PTR/rDNS e identidad del servidor con el proveedor de correo. Revisar límites del plan y ajustar el máximo por hora en Newsletter.
5. Mantener doble confirmación, importar solo contactos con consentimiento acreditado y suprimir rebotes permanentes y quejas. El módulo permite marcar rebotados, pero no integra un webhook automático de rebotes/quejas del proveedor.
6. Comprobar reputación del dominio/IP y, cuando haya volumen suficiente, Google Postmaster Tools. Ninguna plantilla o cabecera garantiza llegar a la bandeja de entrada.

Referencia: [Directrices oficiales para remitentes de Gmail](https://support.google.com/mail/answer/81126?hl=es).

## Validación local

```powershell
php tests/test-newsletter.php
php tests/test-newsletter-links.php
php vendor/bin/phpcs --standard=phpcs.xml.dist app/Newsletter
php vendor/bin/phpstan analyse --configuration=phpstan.neon --no-progress --memory-limit=2G
```

Las pruebas de transporte usan un doble de PHPMailer; las de enlaces ejecutan los controladores en procesos PHP separados y verifican los cambios de estado. La prueba de integración WordPress mantiene una configuración SMTP ficticia mediante filtros temporales y no envía mensajes reales. Su ejecución requiere una instalación WordPress y base de datos de pruebas.

## Actualización 5.0.12: carga por lotes en Ajustes

- El desplegable de carga y cancelación de la cola manual pasa de Suscriptores a Ajustes, fuera del formulario de configuración para evitar formularios anidados.
- Las cargas, cancelaciones y sus avisos regresan a Ajustes. El seguimiento de bienvenida sigue visible en Suscriptores.
