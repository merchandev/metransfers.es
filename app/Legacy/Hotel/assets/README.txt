Imagen de fondo para el Flyer PDF (formato hablador)

Coloca aquí el archivo: HABLADOR - METRANSFERS.png

También puedes colocarlo en la raíz del plugin principal.

Para ajustar la posición del QR en el espacio designado del hablador,
añade en functions.php de tu tema:

  add_filter('hqp_flyer_qr_rect', function($rect) {
      return array('x' => 18, 'y' => 175, 'size' => 52); // mm (cuadro blanco izquierdo)
  });
