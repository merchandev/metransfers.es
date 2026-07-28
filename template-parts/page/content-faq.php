<?php
$faq_items        = me_transfers_get_faq_items();
			$privacy_url      = get_privacy_policy_url();
			$cancellation_url = '';
			$cancellation_slugs = array( 'politica-de-cancelacion', 'cancelacion', 'politica-cancelacion' );

			foreach ( $cancellation_slugs as $slug ) {
				$cancellation_page = get_page_by_path( $slug, OBJECT, 'page' );

				if ( $cancellation_page instanceof WP_Post ) {
					$cancellation_url = get_permalink( $cancellation_page );
					break;
				}
			}

			$faq_schema = array(
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'mainEntity' => array_map(
					static function( $item ) {
						return array(
							'@type'          => 'Question',
							'name'           => $item['question'],
							'acceptedAnswer' => array(
								'@type' => 'Answer',
								'text'  => implode( ' ', $item['answer'] ),
							),
						);
					},
					$faq_items
				),
			);
			?>
			<script type="application/ld+json"><?php echo wp_json_encode( $faq_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>

			<section class="faq-page-hero">
				<div class="container faq-page-hero__inner">
					<span class="faq-page-kicker"><?php esc_html_e( 'Centro de ayuda', 'me-transfers' ); ?></span>
					<h1 class="faq-page-title">Preguntas Frecuentes</h1>
					<p class="faq-page-summary">
						Encuentra respuestas claras y rápidas a las dudas más comunes sobre nuestros servicios de transporte privado. Desde reservas y tarifas hasta detalles sobre nuestras rutas y vehículos, aquÍ encontrarás toda la información que necesitas para planificar tu traslado con metransfers.es. ¿Tienes más preguntas frecuentes? Contáctanos y estaremos encantados de ayudarte.
					</p>
				</div>
			</section>

			<section class="faq-page-section section">
				<div class="container faq-page-shell">
					<div class="faq-page-intro">
						<h2>Todo lo que necesitas saber antes de reservar</h2>
						<p>Hemos reunido las consultas más habituales sobre reservas, vehículos, aeropuertos, pagos, cancelaciones y atención al cliente para que encuentres la información de forma rápida y ordenada.</p>
					</div>

					<div class="faq-accordion">
						<?php foreach ( $faq_items as $index => $faq_item ) : ?>
							<details class="faq-item" <?php echo 0 === $index ? 'open' : ''; ?>>
								<summary class="faq-item__summary">
									<span class="faq-item__number"><?php echo esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
									<span class="faq-item__question"><?php echo esc_html( $faq_item['question'] ); ?></span>
									<span class="faq-item__icon" aria-hidden="true"></span>
								</summary>
								<div class="faq-item__content">
									<?php foreach ( $faq_item['answer'] as $paragraph ) : ?>
										<p><?php echo esc_html( $paragraph ); ?></p>
									<?php endforeach; ?>

									<?php if ( 2 === $index ) : ?>
										<p>
											<?php if ( $cancellation_url ) : ?>
												Puedes acceder a nuestra polÍtica de cancelación completa <a href="<?php echo esc_url( $cancellation_url ); ?>">haciendo clic aquÍ</a>.
											<?php else : ?>
												Puedes consultar nuestra polÍtica de cancelación completa contactando con nuestro equipo.
											<?php endif; ?>
										</p>
									<?php endif; ?>

									<?php if ( 10 === $index && $privacy_url ) : ?>
										<p>Puedes acceder a nuestra polÍtica de privacidad completa <a href="<?php echo esc_url( $privacy_url ); ?>">haciendo clic aquÍ</a>.</p>
									<?php endif; ?>
								</div>
							</details>
						<?php endforeach; ?>
					</div>

					<div class="faq-page-cta">
						<span class="faq-page-cta__kicker">Reserva Ahora</span>
						<h2>Reserva online, rápido y con pago seguro</h2>
						<p>metransfers.es es una de las principales agencias de transporte privado en Barcelona. Reserva online, rápido y con pago seguro. Ofrecemos alquiler de coches con conductor, visitas guiadas y servicio de chofer 24/7.</p>
						<a href="<?php echo esc_url( me_transfers_get_section_url( 'search' ) ); ?>" class="btn btn-primary">Reservar ahora</a>
					</div>
				</div>
			</section>
			<?php
