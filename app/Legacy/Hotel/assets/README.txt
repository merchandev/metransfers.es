Hablador PDF de hoteles

Este módulo genera un QR permanente por hotel y lo inserta en el hablador A4.
Si la plantilla personalizada falta, está dañada o FPDF no puede leerla, se
utiliza automáticamente un diseño A4 básico para no bloquear la descarga.

Plantilla incluida:
  app/Legacy/Hotel/assets/HABLADOR - METRANSFERS.png

Canvas de producción de la plantilla incluida: 2480 x 3508 px, 300 ppp.
Origen de coordenadas: esquina superior izquierda (0,0).

Recuadro dorado visible aproximado:
  x = 1004 px
  y = 2668 px
  ancho = 471 px
  alto = 431 px

Caja SEGURA usada para el QR real:
  x = 1014 px
  y = 2652 px
  ancho = 450 px
  alto = 450 px
  centro aproximado = (1240, 2883)

Conversión usada por el PDF A4:
  x = 88.0645 mm
  y = 227.1525 mm
  lado = 33.8655 mm

El QR se genera por separado a 1000 x 1000 px, conserva su quiet zone y se
inserta como un cuadrado sin deformación. La vista previa, descarga PNG y PDF
usan la misma URL permanente del hotel:
  /reservas-hotel/?promo=<TOKEN_DEL_HOTEL>

Para personalizar la posición sin editar el tema:

  add_filter('hqp_flyer_qr_rect', function($rect) {
      return array(
          'x' => 1014 * 210 / 2480,
          'y' => 2652 * 297 / 3508,
          'size' => 450 * min(210 / 2480, 297 / 3508),
      );
  });

El diseño básico de respaldo utiliza su propia posición de QR. No aplica ese
filtro. Los QR se obtienen de api.qrserver.com y se guardan en caché durante
30 días. La clave de caché incluye la URL completa, por lo que un cambio de
token o dominio genera una clave distinta. Antes de guardar o entregar una
imagen, el módulo valida el PNG completo.

Integración: forma parte del tema MeTransfers y no necesita un plugin de
hoteles separado. Instalar el ZIP completo de la versión del tema sustituye
los archivos de forma conjunta.

Las métricas de Helvetica proceden de FPDF 1.86:
https://github.com/Setasign/FPDF/tree/1.8.6/font
Su licencia está incluida en includes/font/LICENSE.txt.


Diseño 4.0.1:
- Fila de servicios desplazada 110 px hacia arriba para aumentar la separación visual respecto al QR.
- El placeholder QR mantiene la geometría usada por el generador PDF.
