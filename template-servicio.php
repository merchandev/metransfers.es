<?php
/**
 * Template Name: Página de Servicio
 *
 * Template inteligente para todas las páginas de servicios de MeTransfers.
 * Detecta el slug de la página y carga el contenido, formulario y diseño
 * específico de cada servicio.
 *
 * @package Me_Transfers
 */

// Import Material Symbols for icons
add_action('wp_head', function() {
    echo '<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet" />';
});

get_header();

global $post;
$service = me_transfers_get_current_service( $post );

// Si no se detecta un servicio, mostrar el contenido normal de la página.
if ( ! $service ) {
	?>
	<main id="primary" class="site-main">
		<section class="section container" style="padding-top: 6rem;">
			<?php while ( have_posts() ) : the_post(); the_content(); endwhile; ?>
		</section>
	</main>
	<?php
	get_footer();
	return;
}

$form_type = $service['form_type'];
$form_id   = 'svc-form-' . esc_attr( $form_type );
?>

<?php /* Estilos en style.css → sección SERVICE PAGE STYLES */ ?>

<main id="primary" class="site-main">

	<!-- ═══ HERO ════════════════════════════════════════════════════════════════ -->
	<section class="svc-hero">
		<div class="container">
			<div class="svc-hero-badge"><?php echo esc_html( mt_translate( $service['badge']  )); ?></div>
			<h1><?php echo esc_html( mt_translate( isset( $service['h1'] ) ? $service['h1'] : $service['title'] ) ); ?></h1>
			<p class="svc-hero-sub"><?php echo esc_html( mt_translate( $service['subtitle']  )); ?></p>
			<p class="svc-hero-desc"><?php echo esc_html( mt_translate( $service['hero_desc']  )); ?></p>
			<div class="svc-hero-cta-group">
				<?php
				$cta_href = '#solicitar';
				if ( ! in_array( $service['slug'], array( 'corporativo-y-eventos', 'vehiculos-para-grupos' ), true ) ) {
					$cta_href = me_transfers_get_section_url( 'panel' );
				}
				?>
				<a href="<?php echo esc_url( $cta_href ); ?>" class="btn btn-primary">
					<?php echo esc_html( mt_translate( $service['cta_text']  )); ?>
				</a>
				<button type="button" class="btn btn-whatsapp js-wa-trigger">
					<span class="material-symbols-outlined" aria-hidden="true">chat</span>
					WhatsApp
				</button>
			</div>
		</div>
	</section>

	<div class="svc-layout container">

		<!-- ═══ COLUMNA PRINCIPAL: CONTENIDO ════════════════════════════════════ -->
		<div class="svc-main">
			
			<!-- DESCRIPCIÓN COMPLETA -->
			<div class="svc-block">
				<span class="svc-label"><?php echo mt_translate("Sobre este servicio"); ?></span>
				<h2><?php echo esc_html( mt_translate( $service['subtitle']  )); ?></h2>
				<div class="svc-desc-text">
					<?php
					$paragraphs = explode( "\n\n", $service['desc_long'] );
					foreach ( $paragraphs as $p ) {
						echo '<p>' . nl2br( esc_html( trim( mt_translate( $p ) ) ) ) . '</p>';
					}
					?>
				</div>
			</div>

			<!-- BENEFICIOS -->
			<div class="svc-block">
				<span class="svc-label">¿Por qué elegir MeTransfers?</span>
				<h2>Todo lo que necesitas, incluido</h2>
				<div class="svc-features-grid">
					<?php foreach ( $service['features'] as $feat ) : ?>
					<div class="svc-feature-card">
						<span class="material-symbols-outlined svc-feature-icon"><?php echo esc_attr( $feat['icon'] ); ?></span>
						<h3><?php echo esc_html( mt_translate( $feat['title']  )); ?></h3>
						<p><?php echo esc_html( mt_translate( $feat['desc']  )); ?></p>
					</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- CÓMO FUNCIONA -->
			<div class="svc-block">
				<span class="svc-label">El proceso</span>
				<h2>Así de sencillo es reservar</h2>
				<div class="svc-steps-grid">
					<?php foreach ( $service['steps'] as $step ) : ?>
					<div class="svc-step">
						<div class="svc-step-number"><?php echo esc_html( mt_translate( $step['n']  )); ?></div>
						<div>
							<h3><?php echo esc_html( mt_translate( $step['title']  )); ?></h3>
							<p><?php echo esc_html( mt_translate( $step['desc']  )); ?></p>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
			</div>

		</div><!-- .svc-main -->

		<!-- ═══ BARRA LATERAL: FORMULARIO ═══════════════════════════════════════ -->
		<div class="svc-sidebar" id="solicitar">
			<div class="svc-form-box">
				<h2><?php echo mt_translate("Solicita presupuesto"); ?></h2>
				<p class="svc-form-sub"><?php echo mt_translate("Rellena el formulario y te respondemos en menos de 2 horas — o al instante por WhatsApp."); ?></p>

				<form id="<?php echo esc_attr( $form_id ); ?>" class="svc-form" data-service="<?php echo esc_attr( mt_translate( $form_type  )); ?>">

					<!-- Campos comunes: Nombre, Email, Teléfono -->
					<p class="svc-form-section-title">
						<span class="material-symbols-outlined" aria-hidden="true" style="font-size:18px;">person</span>
						<?php echo mt_translate("Tus datos"); ?></p>
					<div class="svc-form-row">
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-nombre"><?php echo mt_translate("Nombre completo *"); ?></label>
							<input type="text" id="<?php echo esc_attr( $form_id ); ?>-nombre" name="nombre" autocomplete="name" placeholder="<?php echo esc_attr( mt_translate( 'Ej: Juan García' ) ); ?>" required>
						</div>
					</div>
					<div class="svc-form-row two-cols">
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-telefono"><?php echo mt_translate("Teléfono *"); ?></label>
							<input type="tel" id="<?php echo esc_attr( $form_id ); ?>-telefono" name="telefono" autocomplete="tel" placeholder="+34 600 000 000" required>
						</div>
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-email"><?php echo mt_translate("Email"); ?></label>
							<input type="email" id="<?php echo esc_attr( $form_id ); ?>-email" name="email" autocomplete="email" placeholder="<?php echo esc_attr( mt_translate( 'tu@email.com' ) ); ?>">
						</div>
					</div>

					<hr class="svc-form-divider">

					<!-- ── Campos específicos por tipo de servicio ── -->

					<?php if ( $form_type === 'aeropuerto' ) : ?>
					<p class="svc-form-section-title">
						<span class="material-symbols-outlined" aria-hidden="true" style="font-size:18px;">flight</span>
						<?php echo mt_translate("Detalles del traslado"); ?></p>
					<div class="svc-form-row two-cols">
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-vuelo"><?php echo mt_translate("Nº de vuelo"); ?></label>
							<input type="text" id="<?php echo esc_attr( $form_id ); ?>-vuelo" name="extra_vuelo" placeholder="<?php echo esc_attr( mt_translate( 'Ej: VY1234' ) ); ?>">
						</div>
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-fecha"><?php echo mt_translate("Fecha *"); ?></label>
							<input type="date" id="<?php echo esc_attr( $form_id ); ?>-fecha" name="extra_fecha" required>
						</div>
					</div>
					<div class="svc-form-row two-cols">
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-hora"><?php echo mt_translate("Hora recogida"); ?></label>
							<input type="time" id="<?php echo esc_attr( $form_id ); ?>-hora" name="extra_hora">
						</div>
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-direccion"><?php echo mt_translate("Origen / Destino"); ?></label>
							<input type="text" id="<?php echo esc_attr( $form_id ); ?>-direccion" name="extra_direccion" placeholder="<?php echo esc_attr( mt_translate( 'Hotel o dirección' ) ); ?>">
						</div>
					</div>
					<div class="svc-form-row two-cols">
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-pasajeros"><?php echo mt_translate("Pasajeros"); ?></label>
							<select id="<?php echo esc_attr( $form_id ); ?>-pasajeros" name="extra_pasajeros">
								<option value=""><?php echo mt_translate("Selecciona..."); ?></option>
								<?php for ( $i = 1; $i <= 10; $i++ ) : ?>
								<option value="<?php echo $i; ?>"><?php echo $i . ' ' . mt_translate( $i > 1 ? 'pasajeros' : 'pasajero' ); ?></option>
								<?php endfor; ?>
							</select>
						</div>
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-maletas"><?php echo mt_translate("Maletas grandes"); ?></label>
							<select id="<?php echo esc_attr( $form_id ); ?>-maletas" name="extra_maletas">
								<option value=""><?php echo mt_translate("Selecciona..."); ?></option>
								<option>1</option><option>2</option><option>3</option>
								<option>4</option><option><?php echo mt_translate("5+"); ?></option>
							</select>
						</div>
					</div>

					<?php elseif ( $form_type === 'puerto' ) : ?>
					<p class="svc-form-section-title">
						<span class="material-symbols-outlined" aria-hidden="true" style="font-size:18px;">directions_boat</span>
						<?php echo mt_translate("Detalles del traslado"); ?></p>
					<div class="svc-form-row">
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-barco"><?php echo mt_translate("Nombre del barco / crucero"); ?></label>
							<input type="text" id="<?php echo esc_attr( $form_id ); ?>-barco" name="extra_barco" placeholder="<?php echo esc_attr( mt_translate( 'Ej: MSC Grandiosa' ) ); ?>">
						</div>
					</div>
					<div class="svc-form-row two-cols">
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-fecha"><?php echo mt_translate("Fecha *"); ?></label>
							<input type="date" id="<?php echo esc_attr( $form_id ); ?>-fecha" name="extra_fecha" required>
						</div>
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-terminal"><?php echo mt_translate("Terminal"); ?></label>
							<select id="<?php echo esc_attr( $form_id ); ?>-terminal" name="extra_terminal">
								<option value="">No lo sé aún</option>
								<option>Adossat A</option><option>Adossat B</option>
								<option>Adossat C</option><option>Adossat D</option>
								<option>Drassanes</option><option><?php echo mt_translate("Otra"); ?></option>
							</select>
						</div>
					</div>
					<div class="svc-form-row two-cols">
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-pasajeros"><?php echo mt_translate("Pasajeros"); ?></label>
							<select id="<?php echo esc_attr( $form_id ); ?>-pasajeros" name="extra_pasajeros">
								<option value=""><?php echo mt_translate("Selecciona..."); ?></option>
								<?php for ( $i = 1; $i <= 10; $i++ ) : ?>
								<option value="<?php echo $i; ?>"><?php echo $i . ' ' . mt_translate( $i > 1 ? 'pasajeros' : 'pasajero' ); ?></option>
								<?php endfor; ?>
							</select>
						</div>
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-origen"><?php echo mt_translate("Origen / Destino"); ?></label>
							<input type="text" id="<?php echo esc_attr( $form_id ); ?>-origen" name="extra_origen" placeholder="<?php echo esc_attr( mt_translate( 'Ej: Aeropuerto T1' ) ); ?>">
						</div>
					</div>

					<?php elseif ( $form_type === 'horas' ) : ?>
					<p class="svc-form-section-title">
						<span class="material-symbols-outlined" aria-hidden="true" style="font-size:18px;">schedule</span>
						<?php echo mt_translate("Detalles del servicio"); ?></p>
					<div class="svc-form-row two-cols">
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-fecha"><?php echo mt_translate("Fecha *"); ?></label>
							<input type="date" id="<?php echo esc_attr( $form_id ); ?>-fecha" name="extra_fecha" required>
						</div>
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-hora"><?php echo mt_translate("Hora de inicio"); ?></label>
							<input type="time" id="<?php echo esc_attr( $form_id ); ?>-hora" name="extra_hora">
						</div>
					</div>
					<div class="svc-form-row two-cols">
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-horas"><?php echo mt_translate("Horas estimadas"); ?></label>
							<select id="<?php echo esc_attr( $form_id ); ?>-horas" name="extra_horas">
								<option value=""><?php echo mt_translate("Selecciona..."); ?></option>
								<option><?php echo mt_translate("3 horas"); ?></option><option><?php echo mt_translate("4 horas"); ?></option><option><?php echo mt_translate("5 horas"); ?></option>
								<option><?php echo mt_translate("6 horas"); ?></option><option><?php echo mt_translate("8 horas"); ?></option>
								<option><?php echo mt_translate("10 horas (día completo)"); ?></option>
							</select>
						</div>
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-partida"><?php echo mt_translate("Punto de partida"); ?></label>
							<input type="text" id="<?php echo esc_attr( $form_id ); ?>-partida" name="extra_partida" placeholder="<?php echo esc_attr( mt_translate( 'Hotel o dirección' ) ); ?>">
						</div>
					</div>
					<div class="svc-form-row">
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-plan"><?php echo mt_translate("Descripción del plan"); ?></label>
							<textarea id="<?php echo esc_attr( $form_id ); ?>-plan" name="extra_plan" placeholder="<?php echo esc_attr( mt_translate( 'Describe las paradas...' ) ); ?>"></textarea>
						</div>
					</div>

					<?php elseif ( $form_type === 'corporativo' ) : ?>
					<p class="svc-form-section-title">
						<span class="material-symbols-outlined" aria-hidden="true" style="font-size:18px;">business_center</span>
						<?php echo mt_translate("Detalles del evento"); ?></p>
					<div class="svc-form-row two-cols">
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-empresa"><?php echo mt_translate("Empresa"); ?></label>
							<input type="text" id="<?php echo esc_attr( $form_id ); ?>-empresa" name="extra_empresa" placeholder="<?php echo esc_attr( mt_translate( 'Nombre de tu empresa' ) ); ?>">
						</div>
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-tipo-evento"><?php echo mt_translate("Tipo de evento"); ?></label>
							<select id="<?php echo esc_attr( $form_id ); ?>-tipo-evento" name="extra_tipo_evento">
								<option value=""><?php echo mt_translate("Selecciona..."); ?></option>
								<option><?php echo mt_translate("Congreso / Feria"); ?></option>
								<option><?php echo mt_translate("Reunión ejecutiva"); ?></option>
								<option><?php echo mt_translate("Incentivo"); ?></option>
								<option><?php echo mt_translate("Otro"); ?></option>
							</select>
						</div>
					</div>
					<div class="svc-form-row two-cols">
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-vehiculos"><?php echo mt_translate("Vehículos"); ?></label>
							<select id="<?php echo esc_attr( $form_id ); ?>-vehiculos" name="extra_vehiculos">
								<option value=""><?php echo mt_translate("Selecciona..."); ?></option>
								<option><?php echo mt_translate("1 vehículo"); ?></option><option><?php echo mt_translate("2 vehículos"); ?></option>
								<option><?php echo mt_translate("3-5 vehículos"); ?></option><option><?php echo mt_translate("Más de 5"); ?></option>
							</select>
						</div>
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-fecha"><?php echo mt_translate("Fecha inicio *"); ?></label>
							<input type="date" id="<?php echo esc_attr( $form_id ); ?>-fecha" name="extra_fecha" required>
						</div>
					</div>
					<div class="svc-form-row">
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-descripcion"><?php echo mt_translate("Descripción"); ?></label>
							<textarea id="<?php echo esc_attr( $form_id ); ?>-descripcion" name="extra_descripcion" placeholder="<?php echo esc_attr( mt_translate( 'Horarios, rutas...' ) ); ?>"></textarea>
						</div>
					</div>

					<?php elseif ( $form_type === 'tours' ) : ?>
					<p class="svc-form-section-title">
						<span class="material-symbols-outlined" aria-hidden="true" style="font-size:18px;">map</span>
						<?php echo mt_translate("Detalles del tour"); ?></p>
					<div class="svc-form-row">
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-destino"><?php echo mt_translate("Tour de interés"); ?></label>
							<select id="<?php echo esc_attr( $form_id ); ?>-destino" name="extra_destino">
								<option value=""><?php echo mt_translate("Selecciona..."); ?></option>
								<option><?php echo mt_translate("Montserrat"); ?></option>
								<option><?php echo mt_translate("Costa Brava"); ?></option>
								<option><?php echo mt_translate("Girona + Costa Brava"); ?></option>
								<option><?php echo mt_translate("Tarragona Romana"); ?></option>
								<option><?php echo mt_translate("Sitges y Penedès"); ?></option>
								<option><?php echo mt_translate("Barcelona City Tour"); ?></option>
								<option><?php echo mt_translate("Ruta personalizada"); ?></option>
							</select>
						</div>
					</div>
					<div class="svc-form-row two-cols">
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-fecha"><?php echo mt_translate("Fecha *"); ?></label>
							<input type="date" id="<?php echo esc_attr( $form_id ); ?>-fecha" name="extra_fecha" required>
						</div>
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-personas"><?php echo mt_translate("Personas"); ?></label>
							<select id="<?php echo esc_attr( $form_id ); ?>-personas" name="extra_personas">
								<option value=""><?php echo mt_translate("Selecciona..."); ?></option>
								<?php for ( $i = 1; $i <= 8; $i++ ) : ?>
								<option value="<?php echo $i; ?>"><?php echo $i; ?> persona<?php echo $i > 1 ? 's' : ''; ?></option>
								<?php endfor; ?>
							</select>
						</div>
					</div>
					<div class="svc-form-row">
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-idioma"><?php echo mt_translate("Idioma preferido"); ?></label>
							<select id="<?php echo esc_attr( $form_id ); ?>-idioma" name="extra_idioma">
								<option><?php echo mt_translate("Español"); ?></option><option><?php echo mt_translate("Inglés"); ?></option><option><?php echo mt_translate("Francés"); ?></option><option><?php echo mt_translate("Otro"); ?></option>
							</select>
						</div>
					</div>

					<?php elseif ( $form_type === 'grupos' ) : ?>
					<p class="svc-form-section-title">
						<span class="material-symbols-outlined" aria-hidden="true" style="font-size:18px;">groups</span>
						<?php echo mt_translate("Detalles del grupo"); ?></p>
					<div class="svc-form-row">
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-grupo"><?php echo mt_translate("Nombre del grupo"); ?></label>
							<input type="text" id="<?php echo esc_attr( $form_id ); ?>-grupo" name="extra_grupo" placeholder="<?php echo esc_attr( mt_translate( 'Ej: Boda García' ) ); ?>">
						</div>
					</div>
					<div class="svc-form-row two-cols">
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-tipo-evento"><?php echo mt_translate("Tipo de evento"); ?></label>
							<select id="<?php echo esc_attr( $form_id ); ?>-tipo-evento" name="extra_tipo_evento">
								<option value=""><?php echo mt_translate("Selecciona..."); ?></option>
								<option><?php echo mt_translate("Boda"); ?></option><option><?php echo mt_translate("Cumpleaños"); ?></option>
								<option><?php echo mt_translate("Incentivo de empresa"); ?></option><option><?php echo mt_translate("Excursión"); ?></option><option><?php echo mt_translate("Otro"); ?></option>
							</select>
						</div>
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-personas"><?php echo mt_translate("Personas"); ?></label>
							<select id="<?php echo esc_attr( $form_id ); ?>-personas" name="extra_personas">
								<option value=""><?php echo mt_translate("Selecciona..."); ?></option>
								<option><?php echo mt_translate("8-15 personas"); ?></option><option><?php echo mt_translate("15-30 personas"); ?></option>
								<option><?php echo mt_translate("30-50 personas"); ?></option><option><?php echo mt_translate("Más de 50"); ?></option>
							</select>
						</div>
					</div>
					<div class="svc-form-row">
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-fecha"><?php echo mt_translate("Fecha del evento *"); ?></label>
							<input type="date" id="<?php echo esc_attr( $form_id ); ?>-fecha" name="extra_fecha" required>
						</div>
					</div>
					<div class="svc-form-row">
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-descripcion"><?php echo mt_translate("Servicio necesario"); ?></label>
							<textarea id="<?php echo esc_attr( $form_id ); ?>-descripcion" name="extra_descripcion" placeholder="<?php echo esc_attr( mt_translate( 'Origen, destino, horarios...' ) ); ?>"></textarea>
						</div>
					</div>

					<?php endif; ?>

					<!-- Mensaje adicional -->
					<div class="svc-form-row">
						<div class="sfg">
							<label for="<?php echo esc_attr( $form_id ); ?>-mensaje"><?php echo mt_translate("Comentarios adicionales"); ?></label>
							<textarea id="<?php echo esc_attr( $form_id ); ?>-mensaje" name="mensaje" placeholder="<?php echo esc_attr( mt_translate( 'Cualquier detalle adicional...' ) ); ?>"></textarea>
						</div>
					</div>

					<!-- GDPR -->
					<div class="svc-gdpr">
						<input type="checkbox" id="<?php echo esc_attr( $form_id ); ?>-gdpr" name="gdpr_aceptado" value="1" required>
						<label for="<?php echo esc_attr( $form_id ); ?>-gdpr">
							<?php echo mt_translate("He leído y acepto la"); ?> <a href="<?php echo esc_url( home_url( '/politica-de-privacidad' ) ); ?>" target="_blank"><?php echo mt_translate("Política de Privacidad"); ?></a>.
						</label>
					</div>

					<button type="submit" class="svc-submit-btn">
						<span class="material-symbols-outlined" aria-hidden="true">send</span>
						<?php echo esc_html( mt_translate( $service['cta_text']  )); ?>
					</button>

					<div class="svc-form-ok" id="<?php echo esc_attr( $form_id ); ?>-ok">
						<span class="material-symbols-outlined" aria-hidden="true" style="vertical-align: middle; margin-right: 5px;">check_circle</span>
						<?php echo mt_translate("¡Solicitud enviada! Te responderemos muy pronto."); ?>
					</div>

				</form>
			</div>
		</div><!-- .svc-sidebar -->

	</div><!-- .svc-layout -->

</main><!-- #primary -->

<script>
(function() {
	var form = document.getElementById('<?php echo esc_js( $form_id ); ?>');
	if (!form) return;

	form.addEventListener('submit', function(e) {
		e.preventDefault();

		var btn = form.querySelector('.svc-submit-btn');
		var ok  = document.getElementById('<?php echo esc_js( $form_id ); ?>-ok');
		var orig = btn.innerHTML;
		btn.innerHTML = '<span class="material-symbols-outlined" aria-hidden="true" style="animation: spin 1s linear infinite;">hourglass_empty</span> Enviando...';
		btn.disabled = true;

		var data = new FormData(form);
		data.append('action',   'mt_save_lead');
		data.append('security', (window.mtAjax && mtAjax.nonce) ? mtAjax.nonce : '');
		data.append('origen',   'formulario-<?php echo esc_js( $form_type ); ?>');
		data.append('servicio', '<?php echo esc_js( $service['title'] ); ?>');

		// Collect extra fields into the message
		var extras = [];
		['extra_vuelo','extra_fecha','extra_hora','extra_direccion','extra_pasajeros',
		 'extra_maletas','extra_barco','extra_terminal','extra_origen',
		 'extra_horas','extra_partida','extra_plan','extra_empresa','extra_tipo_evento',
		 'extra_vehiculos','extra_descripcion','extra_destino','extra_personas',
		 'extra_idioma','extra_grupo'].forEach(function(key){
			var el = form.querySelector('[name="' + key + '"]');
			if (el && el.value.trim()) {
				extras.push(key.replace('extra_','').replace(/_/g,' ').replace(/\b\w/g,l=>l.toUpperCase()) + ': ' + el.value.trim());
			}
		});
		var baseMensaje = (data.get('mensaje') || '').trim();
		var fullMensaje = (extras.length ? extras.join('\n') + '\n\n' : '') + baseMensaje;
		data.set('mensaje', fullMensaje);

		// Añadir fecha y hora de aceptación GDPR
		data.set('gdpr_fecha', new Date().toISOString());

		var url = (window.mtAjax && mtAjax.ajaxurl) ? mtAjax.ajaxurl : '/wp-admin/admin-ajax.php';

		fetch(url, { method: 'POST', body: data })
			.then(function(r) { return r.json(); })
			.then(function(res) {
				if (!res.success) {
					throw new Error(res.data && res.data.message ? res.data.message : 'No se pudo enviar la solicitud.');
				}
				if (ok) {
					ok.textContent = res.data && res.data.message ? res.data.message : '¡Solicitud recibida correctamente! Te responderemos muy pronto.';
					ok.classList.remove('error');
					ok.classList.add('active');
				}
				form.reset();
			})
			.catch(function(err) {
				if (ok) {
					ok.textContent = err && err.message ? err.message : 'No se pudo enviar la solicitud. Por favor, inténtalo de nuevo.';
					ok.classList.add('active', 'error');
				}
			})
			.finally(function() {
				btn.innerHTML = orig;
				btn.disabled = false;
				if (ok) { ok.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
			});
	});
})();
</script>
<style>
@keyframes spin { 100% { transform: rotate(360deg); } }
</style>

<?php get_footer(); ?>

