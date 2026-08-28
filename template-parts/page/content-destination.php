<?php
$current_destination = me_transfers_get_current_destination( get_post() );
$page_content = trim( wp_strip_all_tags( strip_shortcodes( get_the_content() ) ) );
			$catalog = me_transfers_get_destination_catalog();
			$other_destinations = array();
			if ( ! empty( $current_destination['related'] ) ) {
				foreach ( $current_destination['related'] as $rel_slug ) {
					if ( isset( $catalog[ $rel_slug ] ) ) {
						$other_destinations[] = $catalog[ $rel_slug ];
					}
				}
			} else {
				$fallback_destinations = array_filter( $catalog, static function( $item ) use ( $current_destination ) {
					return $item['slug'] !== $current_destination['slug'];
				});
				$other_destinations = array_slice( array_values( $fallback_destinations ), 0, 6 );
			}
			$destination_service_cards = array(
				array(
					'title' => 'Recogida personalizada',
					'text'  => sprintf( 'Coordinamos salidas desde Barcelona, aeropuerto, hotel o puerto para traslados hacia %s con una operativa clara desde el primer contacto.', $current_destination['title'] ),
				),
				array(
					'title' => 'Vehiculo adecuado',
					'text'  => 'Asignamos sedan, minivan o vehiculo premium segun pasajeros, equipaje, horario y nivel de servicio que necesites.',
				),
				array(
					'title' => 'Presupuesto y tiempos claros',
					'text'  => sprintf( 'Recibes una propuesta a medida, con respuesta agil y todos los detalles del recorrido hacia %s antes de confirmar.', $current_destination['title'] ),
				),
			);
			$destination_use_cases = array(
				'Aeropuerto y estaciones',
				'Hoteles y apartamentos',
				'Viajes corporativos',
				'Familias y grupos',
				'Eventos privados',
				'Servicio por horas',
			);
			$destination_steps = array(
				array(
					'step'  => '01',
					'title' => 'Cuentanos tu ruta',
					'text'  => sprintf( 'Indicanos origen, destino %s, fecha, pasajeros y cualquier detalle relevante para preparar el servicio.', $current_destination['title'] ),
				),
				array(
					'step'  => '02',
					'title' => 'Preparamos la propuesta',
					'text'  => 'Te enviamos disponibilidad, tipo de vehiculo recomendado y condiciones del traslado con un presupuesto claro.',
				),
				array(
					'step'  => '03',
					'title' => 'Confirmamos la recogida',
					'text'  => sprintf( 'Una vez aceptado, dejamos coordinado el punto de encuentro y el trayecto hacia %s para que viajes sin friccion.', $current_destination['title'] ),
				),
			);
			$route_query = new WP_Query( array(
				'post_type'      => 'ruta',
				'post_status'    => 'publish',
				'posts_per_page' => 8,
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => '_mt_ruta_origen',
						'value'   => $current_destination['title'],
						'compare' => 'LIKE',
					),
					array(
						'key'     => '_mt_ruta_destino',
						'value'   => $current_destination['title'],
						'compare' => 'LIKE',
					),
				),
			) );

			if ( ! $route_query->have_posts() ) {
				// Fallback por si aún no tienen meta
				$route_query = new WP_Query( array(
					'post_type'      => 'ruta',
					'post_status'    => 'publish',
					'posts_per_page' => 8,
					's'              => $current_destination['title'],
				) );
			}
			$destination_routes = $route_query->posts;
			?>
			<section class="destination-page-hero">
				<div class="container destination-page-hero__inner">
					<nav class="destination-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'me-transfers' ); ?>">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Inicio', 'me-transfers' ); ?></a>
						<span aria-hidden="true">/</span>
						<a href="<?php echo esc_url( me_transfers_get_destinations_hub_url() ); ?>"><?php esc_html_e( 'Destinos', 'me-transfers' ); ?></a>
						<span aria-hidden="true">/</span>
						<span aria-current="page"><?php echo esc_html( $current_destination['title'] ); ?></span>
					</nav>

					<span class="destination-page-kicker"><?php esc_html_e( 'Solicitud de traslado premium', 'me-transfers' ); ?></span>
					<h1 class="destination-page-title"><?php echo esc_html( $current_destination['h1'] ?? $current_destination['title'] ); ?></h1>
					<p class="destination-page-summary"><?php echo esc_html( $current_destination['summary'] ); ?></p>

					<div class="destination-page-pills">
						<span><?php esc_html_e( 'Puerta a puerta', 'me-transfers' ); ?></span>
						<span><?php esc_html_e( 'Chófer profesional', 'me-transfers' ); ?></span>
						<span><?php esc_html_e( 'Respuesta rápida', 'me-transfers' ); ?></span>
					</div>
				</div>
			</section>

			<section class="destination-page-section section">
				<div class="container destination-page-layout">
					<div class="destination-page-copy">
						<div class="destination-copy-card">
							<h2><?php echo esc_html( sprintf( __( 'Información para traslados a %s', 'me-transfers' ), $current_destination['title'] ) ); ?></h2>

							<?php if ( $page_content ) : ?>
								<div class="entry-content luxury-prose">
									<?php the_content(); ?>
								</div>
							<?php else : ?>
								<div class="destination-copy-prose">
									<p><?php echo esc_html( $current_destination['travel_note'] ); ?></p>
									<p>
										<?php
										echo esc_html(
											sprintf(
												/* translators: %s: destination title. */
												__( 'Si estás organizando un traslado hacia %s, podemos prepararte una propuesta adaptada al punto de recogida, número de pasajeros, fecha estimada y tipo de servicio que necesites.', 'me-transfers' ),
												$current_destination['title']
											)
										);
										?>
									</p>

									<ul class="destination-highlights">
										<?php foreach ( $current_destination['highlights'] as $highlight ) : ?>
											<li><?php echo esc_html( $highlight ); ?></li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
						</div>

						<div class="destination-extra-card">
							<div class="destination-extra-header">
								<span class="destination-extra-kicker">Servicio premium</span>
								<h3>Un traslado mejor organizado y con mas contexto</h3>
								<p>Esta pagina te ayuda a entender mejor como trabajamos los traslados privados hacia <?php echo esc_html( $current_destination['title'] ); ?> y que puedes esperar antes de solicitar informacion.</p>
							</div>

							<div class="destination-use-cases">
								<?php foreach ( $destination_use_cases as $use_case ) : ?>
									<span><?php echo esc_html( $use_case ); ?></span>
								<?php endforeach; ?>
							</div>

							<div class="destination-mini-grid">
								<?php foreach ( $destination_service_cards as $service_card ) : ?>
									<article class="destination-mini-card">
										<h3><?php echo esc_html( $service_card['title'] ); ?></h3>
										<p><?php echo esc_html( $service_card['text'] ); ?></p>
									</article>
								<?php endforeach; ?>
							</div>
						</div>
					</div>

					<aside id="destination-request" class="destination-page-sidebar">
						<div class="destination-form-card">
							<span class="destination-form-card__eyebrow"><?php esc_html_e( 'Formulario de contacto', 'me-transfers' ); ?></span>
							<h2><?php echo esc_html( sprintf( __( 'Pide información para %s', 'me-transfers' ), $current_destination['title'] ) ); ?></h2>
							<p><?php esc_html_e( 'Cuéntanos origen, fechas y necesidades del servicio. Te responderemos con disponibilidad y propuesta personalizada.', 'me-transfers' ); ?></p>
							<?php echo me_transfers_render_destination_request_form( $current_destination ); ?>
						</div>
					</aside>
				</div>
			</section>

			<section class="destination-process-section">
				<div class="container">
					<div class="destination-section-heading">
						<span class="destination-section-kicker">Proceso de reserva</span>
						<h2>Como organizamos tu traslado a <?php echo esc_html( $current_destination['title'] ); ?></h2>
						<p>Definimos ruta, horario, tipo de vehiculo y recogida con antelacion para que el trayecto sea claro, comodo y previsible.</p>
					</div>

					<div class="destination-process-grid">
						<?php foreach ( $destination_steps as $destination_step ) : ?>
							<article class="destination-process-card">
								<span class="destination-process-step"><?php echo esc_html( $destination_step['step'] ); ?></span>
								<h3><?php echo esc_html( $destination_step['title'] ); ?></h3>
								<p><?php echo esc_html( $destination_step['text'] ); ?></p>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</section>

			<section class="destination-routes-section">
				<div class="container">
					<div class="destination-routes-card">
						<div class="destination-routes-copy">
							<span class="destination-section-kicker">Trayectos habituales</span>
							<h2>Rutas frecuentes hacia <?php echo esc_html( $current_destination['title'] ); ?></h2>
							<p>Trabajamos traslados privados desde diferentes puntos de Barcelona para adaptarnos a llegadas, salidas, hoteles, eventos y viajes de negocio.</p>
						</div>

						<ul class="destination-routes-list">
							<?php if ( ! empty( $destination_routes ) ) : ?>
								<?php foreach ( $destination_routes as $route_post ) : ?>
									<li><a href="<?php echo esc_url( get_permalink( $route_post->ID ) ); ?>"><?php echo esc_html( $route_post->post_title ); ?></a></li>
								<?php endforeach; ?>
							<?php else : ?>
								<li><?php echo esc_html( sprintf( 'Traslados directos hacia %s', $current_destination['title'] ) ); ?></li>
								<li><?php echo esc_html( sprintf( 'Aeropuerto de Barcelona a %s', $current_destination['title'] ) ); ?></li>
							<?php endif; ?>
						</ul>
					</div>
				</div>
			</section>

			<section class="destination-benefits-section">
				<div class="container">
					<div class="destination-benefits-grid">
						<article class="destination-benefit-card destination-benefit-card--pricing">
							<div class="destination-benefit-icon" aria-hidden="true">
								<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
									<path d="M20.59 13.41 11 3.83a2 2 0 0 0-1.41-.58H4a2 2 0 0 0-2 2v5.59a2 2 0 0 0 .58 1.41L12.17 22a2 2 0 0 0 2.83 0l5.59-5.59a2 2 0 0 0 0-2.83Z"/>
									<path d="M7 7h.01"/>
									<path d="M10 14c.5.55 1.22 1 2 1 1.1 0 2-.9 2-2 0-2-3-1-3-3 0-1.1.9-2 2-2 .78 0 1.5.45 2 1"/>
									<path d="M12 7v10"/>
								</svg>
							</div>
							<h2>Presupuesto a Medida</h2>
							<p>Contrata uno de nuestros servicios y paga lo que realmente contratas, sin pagos posteriores ni recargos sorpresivos.</p>
						</article>

						<article class="destination-benefit-card destination-benefit-card--private">
							<div class="destination-benefit-icon" aria-hidden="true">
								<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
									<path d="M5 12h14"/>
									<path d="m12 5 7 7-7 7"/>
									<path d="M5 5v5"/>
									<path d="M3 7h4"/>
								</svg>
							</div>
							<h2>Traslados Privados</h2>
							<p>Traslado Privado en Barcelona o Madrid desde el Aeropuerto o cualquier punto de la ciudad hacia cualquier punto.</p>
						</article>

						<article class="destination-benefit-card destination-benefit-card--flex">
							<div class="destination-benefit-icon" aria-hidden="true">
								<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
									<rect x="3" y="4" width="14" height="14" rx="2"/>
									<path d="M7 2v4M13 2v4M3 10h14"/>
									<circle cx="19" cy="17" r="4"/>
									<path d="M19 15v2l1.5 1.5"/>
								</svg>
							</div>
							<h2>Reservas Flexibles</h2>
							<p>Metransfers Barcelona ofrece una cancelación gratuita hasta 24 horas antes de su actividad, siempre hay espacio para un cambio de planes.</p>
						</article>

						<article class="destination-benefit-card destination-benefit-card--support">
							<div class="destination-benefit-icon" aria-hidden="true">
								<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
									<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.62 2h3A2 2 0 0 1 8.6 3.72c.12.92.34 1.82.67 2.69a2 2 0 0 1-.45 2.11L7.73 9.61a16 16 0 0 0 6.66 6.66l1.09-1.09a2 2 0 0 1 2.11-.45c.87.33 1.77.55 2.69.67A2 2 0 0 1 22 16.92z"/>
									<path d="M15 7a5 5 0 0 1 5 5"/>
									<path d="M15 3a9 9 0 0 1 9 9"/>
								</svg>
							</div>
							<h2>Asistencia Ininterrumpida</h2>
							<p>Nuestros agentes monitorizan tu vuelo o llegada para coordinar recogidas eficaces en cualquier momento, siempre mediante un contacto directo.</p>
						</article>
					</div>
				</div>
			</section>

			<?php if ( $other_destinations ) : ?>
				<section class="destination-other-section">
					<div class="container">
						<div class="destination-other-header">
							<h2><?php esc_html_e( 'Otros destinos destacados', 'me-transfers' ); ?></h2>
							<a href="<?php echo esc_url( me_transfers_get_destinations_hub_url() ); ?>" class="destination-other-link"><?php esc_html_e( 'Ver todos los destinos', 'me-transfers' ); ?></a>
						</div>

						<div class="destination-other-grid">
							<?php foreach ( $other_destinations as $destination ) : ?>
								<a href="<?php echo esc_url( me_transfers_get_destination_url( $destination, true ) ); ?>" class="destination-other-card">
									<span class="destination-other-card__title"><?php echo esc_html( $destination['title'] ); ?></span>
									<span class="destination-other-card__link"><?php esc_html_e( 'Solicitar info', 'me-transfers' ); ?></span>
								</a>
							<?php endforeach; ?>
						</div>
					</div>
				</section>
			<?php endif; ?>
			<?php
