# Seguridad y entregabilidad del newsletter — 16/10/2026

Versión del tema: **5.0.7**. Alcance: módulo Newsletter, sus pruebas, incorporación de sus pruebas de enlaces a CI y metadatos de versión/descripción del tema. Los ajustes SMTP compartidos, reservas, pagos, DNS y servidor de producción no se modifican con este cambio.

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
- Correos con marca en texto en vez del logotipo remoto roto y sin hoja externa de fuentes. La confirmación omite el reclamo promocional. Se mantiene HTML con idioma y versión en texto plano.
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
