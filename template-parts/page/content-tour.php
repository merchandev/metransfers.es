<?php
// $args is passed from page.php via get_template_part( ..., array( 'tour' => $tour ) )
$tour = isset( $args['tour'] ) ? $args['tour'] : null;

if ( ! $tour ) {
	return; // Nothing to render if tour data is missing
}

$other_tours = array_filter(
				me_transfers_get_tour_catalog(),
				static function( $item ) use ( $tour ) {
					return $item['slug'] !== $tour['slug'];
				}
			);
			?>
			<style>
				/* Fix layout globally for tour pages */
				.destination-page-layout {
					display: grid !important;
					grid-template-columns: 1.5fr 1fr !important;
					gap: 4rem !important;
					align-items: start !important;
				}
				.destination-page-sidebar {
					position: sticky !important;
					top: 100px !important;
				}
				.destination-process-step {
					background: #fff8eb !important;
					color: #FFB547 !important;
					font-weight: 800 !important;
					border: 1px solid rgba(255,181,71,0.3) !important;
				}
				@media (max-width: 1024px) {
					.destination-page-layout {
						grid-template-columns: 1fr !important;
						gap: 3rem !important;
					}
					.destination-page-sidebar {
						order: -1 !important; /* Move form to the top on mobile/tablet */
						position: static !important;
					}
				}
			</style>
			<!-- TOUR HERO -->
			<section class="destination-page-hero">
				<div class="container destination-page-hero__inner">
					<nav class="destination-breadcrumbs" aria-label="Breadcrumb">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Inicio</a>
						<span aria-hidden="true">/</span>
						<a href="<?php echo esc_url( home_url( '/#tours' ) ); ?>">Tours</a>
						<span aria-hidden="true">/</span>
						<span aria-current="page"><?php echo esc_html( $tour['title'] ); ?></span>
					</nav>
					<span class="destination-page-kicker">Tour Privado Premium</span>
					<h1 class="destination-page-title"><?php echo esc_html( $tour['title'] ); ?></h1>
					<p class="destination-page-summary"><?php echo esc_html( $tour['desc'] ); ?></p>
					<div class="destination-page-pills">
						<span><?php echo esc_html( $tour['duration'] ); ?></span>
						<span><?php echo esc_html( $tour['group_size'] ); ?></span>
						<span>Presupuestar</span>
						<span>Chofer privado</span>
					</div>
				</div>
			</section>

			<!-- TOUR CONTENT + BOOKING -->
			<section class="destination-page-section section">
				<div class="container destination-page-layout">
					<div class="destination-page-copy">
						<div class="destination-copy-card">
							<img src="<?php echo esc_url( $tour['img'] ); ?>" alt="<?php echo esc_attr( $tour['title'] ); ?>" style="width:100%;border-radius:16px;margin-bottom:1.5rem;object-fit:cover;max-height:420px;">
							<h2>Sobre este tour</h2>
							<div class="destination-copy-prose">
								<?php
								$tour_page_content = trim( wp_strip_all_tags( strip_shortcodes( get_the_content() ) ) );
								if ( $tour_page_content ) {
									echo '<div class="entry-content luxury-prose">';
									the_content();
									echo '</div>';
								} else {
									$paragraphs = isset( $tour['full_desc'] ) ? explode( "\n\n", $tour['full_desc'] ) : array( $tour['desc'] );
									foreach ( $paragraphs as $p ) :
										$p = trim( $p );
										if ( $p ) : ?>
										<p><?php echo esc_html( $p ); ?></p>
									<?php endif; endforeach;
								}
								?>
							</div>
						</div>
						<?php if ( ! empty( $tour['itinerary'] ) ) : ?>
						<div class="destination-extra-card">
							<div class="destination-extra-header">
								<span class="destination-extra-kicker">Itinerario del dia</span>
								<h3>Recorrido paso a paso</h3>
								<p>Itinerario sugerido, completamente flexible y personalizable.</p>
							</div>
							<div style="display:grid;gap:0.8rem;">
							<?php foreach ( $tour['itinerary'] as $idx => $step ) : ?>
								<div style="display:flex;align-items:flex-start;gap:0.85rem;">
									<span class="destination-process-step" style="flex-shrink:0;width:2.2rem;height:2.2rem;font-size:0.72rem;"><?php echo esc_html( str_pad( (string) ( $idx + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
									<p style="margin:0;padding-top:0.3rem;"><?php echo esc_html( $step ); ?></p>
								</div>
							<?php endforeach; ?>
							</div>
						</div>
						<?php endif; ?>
						<?php if ( ! empty( $tour['includes'] ) ) : ?>
						<div class="destination-extra-card">
							<div class="destination-extra-header">
								<span class="destination-extra-kicker">Todo incluido</span>
								<h3>Que incluye el tour</h3>
							</div>
							<ul style="list-style:none;padding:0;margin:0;display:grid;gap:0.75rem;">
							<?php foreach ( $tour['includes'] as $inc ) : ?>
								<li style="display:flex;align-items:center;gap:0.65rem;">
									<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FFB547" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
									<span><?php echo esc_html( $inc ); ?></span>
								</li>
							<?php endforeach; ?>
							</ul>
						</div>
						<?php endif; ?>
						<?php if ( ! empty( $tour['highlights'] ) ) : ?>
						<div class="destination-extra-card">
							<div class="destination-extra-header">
								<span class="destination-extra-kicker">Puntos destacados</span>
								<h3>Lugares que visitaras</h3>
							</div>
							<div class="destination-use-cases">
							<?php foreach ( $tour['highlights'] as $hl ) : ?>
								<span><?php echo esc_html( $hl ); ?></span>
							<?php endforeach; ?>
							</div>
						</div>
						<?php endif; ?>
					</div>

					<!-- Booking Form Sidebar -->
					<aside id="tour-booking" class="destination-page-sidebar">
						<div class="destination-form-card">
							<span class="destination-form-card__eyebrow">Reserva este tour</span>
							<h2><?php echo esc_html( $tour['title'] ); ?></h2>
							<p style="margin-bottom:1.2rem;">Completa tus datos y te contactamos por WhatsApp para confirmar disponibilidad.</p>
							<form id="mt-tour-booking-form" class="destination-request-grid" novalidate>
								<input type="hidden" name="action" value="me_transfers_tour_booking">
								<input type="hidden" name="security" value="">
								<input type="hidden" name="tour_name" value="<?php echo esc_attr( $tour['title'] ); ?>">
								<div style="position:absolute;left:-9999px;" aria-hidden="true"><input type="text" name="mt_website" tabindex="-1" autocomplete="off"></div>
								<div class="destination-request-field"><label for="tb-name">Nombre completo *</label><input type="text" id="tb-name" name="name" required placeholder="Tu nombre"></div>
								<div class="destination-request-field"><label for="tb-country">Pais</label><input type="text" id="tb-country" name="country" placeholder="Ej: Espana, Colombia..."></div>
								<div class="destination-request-field"><label for="tb-phone">Telefono *</label><input type="tel" id="tb-phone" name="phone" required placeholder="+34 600 000 000"></div>
								<div class="destination-request-field"><label for="tb-email">Correo electronico *</label><input type="email" id="tb-email" name="email" required placeholder="tu@email.com"></div>
								<div class="destination-request-field"><label for="tb-date">Fecha del tour</label><input type="date" id="tb-date" name="tour_date"></div>
								<div class="destination-request-field destination-request-field--full" style="padding-top:0.5rem;">
									<button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;gap:0.5rem;">
										<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0 0 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>
										Reservar por WhatsApp
									</button>
								</div>
							</form>
							<div id="mt-tour-booking-msg" style="display:none;margin-top:1rem;padding:1rem;border-radius:12px;text-align:center;font-weight:600;"></div>
							<p style="text-align:center;font-size:0.78rem;color:var(--text-muted);margin-top:1rem;">Tu informacion se guarda de forma segura.</p>
						</div>
					</aside>
				</div>
			</section>

			<!-- Benefits -->
			<section class="destination-benefits-section">
				<div class="container">
					<div class="destination-benefits-grid">
						<article class="destination-benefit-card"><div class="destination-benefit-icon" aria-hidden="true"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41 11 3.83a2 2 0 0 0-1.41-.58H4a2 2 0 0 0-2 2v5.59a2 2 0 0 0 .58 1.41L12.17 22a2 2 0 0 0 2.83 0l5.59-5.59a2 2 0 0 0 0-2.83Z"/><path d="M7 7h.01"/></svg></div><h2>Presupuesto a medida</h2><p>Solicita tu presupuesto personalizado sin compromiso.</p></article>
						<article class="destination-benefit-card"><div class="destination-benefit-icon" aria-hidden="true"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="14" height="14" rx="2"/><path d="M7 2v4M13 2v4M3 10h14"/><circle cx="19" cy="17" r="4"/><path d="M19 15v2l1.5 1.5"/></svg></div><h2>Cancelacion Flexible</h2><p>Cancelacion gratuita hasta 24 horas antes del tour.</p></article>
						<article class="destination-benefit-card"><div class="destination-benefit-icon" aria-hidden="true"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg></div><h2>Tour 100% Privado</h2><p>Sin otros grupos. Solo tu con chofer privado y vehiculo Mercedes.</p></article>
						<article class="destination-benefit-card"><div class="destination-benefit-icon" aria-hidden="true"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.62 2h3A2 2 0 0 1 8.6 3.72c.12.92.34 1.82.67 2.69a2 2 0 0 1-.45 2.11L7.73 9.61a16 16 0 0 0 6.66 6.66l1.09-1.09a2 2 0 0 1 2.11-.45c.87.33 1.77.55 2.69.67A2 2 0 0 1 22 16.92z"/></svg></div><h2>Soporte 24/7</h2><p>Atencion por telefono, email o WhatsApp las 24 horas.</p></article>
					</div>
				</div>
			</section>

			<!-- Removed Other Tours section -->

			<script>
			(function(){
				var form = document.getElementById('mt-tour-booking-form');
				if (!form) return;
				var nf = form.querySelector('input[name="security"]');
				if (nf && typeof meTransfers !== 'undefined') nf.value = meTransfers.tourBookingNonce;
				form.addEventListener('submit', function(e) {
					e.preventDefault();
					var btn = form.querySelector('button[type="submit"]');
					var msg = document.getElementById('mt-tour-booking-msg');
					var orig = btn.innerHTML;
					var n = form.querySelector('[name="name"]').value.trim();
					var p = form.querySelector('[name="phone"]').value.trim();
					var em = form.querySelector('[name="email"]').value.trim();
					if (!n||!p||!em) { msg.style.display='block'; msg.style.background='rgba(220,38,38,0.08)'; msg.style.color='#991b1b'; msg.textContent='Por favor, completa los campos obligatorios.'; return; }
					btn.disabled=true; btn.textContent='Enviando...';
					fetch(meTransfers.ajaxUrl,{method:'POST',body:new FormData(form)})
					.then(function(r){return r.json()})
					.then(function(res){
						msg.style.display='block';
						if(res.success){msg.style.background='rgba(22,163,74,0.08)';msg.style.color='#166534';msg.textContent=res.data.message;form.reset();if(res.data.whatsapp_url)setTimeout(function(){window.open(res.data.whatsapp_url,'_blank')},800);}
						else{msg.style.background='rgba(220,38,38,0.08)';msg.style.color='#991b1b';msg.textContent=res.data&&res.data.message?res.data.message:'Error al enviar.';}
						btn.disabled=false;btn.innerHTML=orig;
					}).catch(function(){msg.style.display='block';msg.style.background='rgba(220,38,38,0.08)';msg.style.color='#991b1b';msg.textContent='Error de conexion.';btn.disabled=false;btn.innerHTML=orig;});
				});
			})();
			</script>
			<?php