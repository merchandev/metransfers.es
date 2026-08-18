<?php
$destinations = me_transfers_get_destination_catalog();
$hub_content_plain = trim( wp_strip_all_tags( strip_shortcodes( get_the_content() ) ) );
?>
			<section class="destinations-hub-hero">
				<div class="container destinations-hub-hero__inner gs-reveal-up">
					<span class="destinations-hub-eyebrow"><?php esc_html_e( 'Destinos más solicitados', 'me-transfers' ); ?></span>
					<h1 class="destinations-hub-title"><?php esc_html_e( 'Destinos', 'me-transfers' ); ?></h1>
					<p class="destinations-hub-intro">
						<?php
						if ( $hub_content_plain ) {
							echo esc_html( $hub_content_plain );
						} else {
							esc_html_e( 'Explora los destinos más solicitados y accede a una ficha rápida para pedir información de traslados privados, recogidas en aeropuerto, hoteles, puertos y rutas personalizadas.', 'me-transfers' );
						}
						?>
					</p>
				</div>
			</section>

			<section class="destinations-directory section">
				<div class="container">
					<div class="destinations-directory-grid">
						<?php foreach ( $destinations as $destination ) : ?>
							<article class="destination-card gs-stagger">
								<span class="destination-card-index"><?php echo esc_html( str_pad( (string) $destination['order'], 2, '0', STR_PAD_LEFT ) ); ?></span>
								<h2 class="destination-card-title"><?php echo esc_html( $destination['title'] ); ?></h2>
								<p class="destination-card-copy">
									<?php
									echo esc_html(
										sprintf(
											/* translators: %s: destination title. */
											__( 'Consulta disponibilidad y presupuesto para traslados privados hacia %s.', 'me-transfers' ),
											$destination['title']
										)
									);
									?>
								</p>
								<a href="<?php echo esc_url( me_transfers_get_destination_url( $destination, true ) ); ?>" class="destination-card-link">
									<?php esc_html_e( 'Solicitar información', 'me-transfers' ); ?>
								</a>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
			<?php