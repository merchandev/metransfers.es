<?php
/**
 * Template Name: Sobre Nosotros
 *
 * @package Me_Transfers
 */

add_action('wp_head', function() {
    echo '<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet" />';
});

get_header();
?>

<main id="primary" class="site-main">

	<section class="svc-hero">
		<div class="container">
			<div class="svc-hero-badge"><?php echo mt_translate('MeTransfers Barcelona'); ?></div>
			<h1><?php echo mt_translate('Nuestra historia y valores'); ?></h1>
			<p class="svc-hero-sub"><?php echo mt_translate('Movilidad ejecutiva y turística de primer nivel en Cataluña'); ?></p>
			<p class="svc-hero-desc"><?php echo mt_translate('Con sede en la vibrante ciudad de Barcelona, nacimos con un propósito claro: redefinir la experiencia de viajar, ofreciendo un servicio integral donde la puntualidad, el máximo confort y la discreción son nuestra norma inquebrantable.'); ?></p>
		</div>
	</section>

	<div class="container" style="padding: 4rem 0 6rem; display: flex; flex-direction: column; gap: 4rem;">

		<!-- CONTENIDO PRINCIPAL -->
		<div class="svc-block">
			<span class="svc-label"><?php echo mt_translate('Sobre nosotros'); ?></span>
			<h2><?php echo mt_translate('Bienvenidos a MeTransfers Barcelona'); ?></h2>
			<div class="svc-desc-text">
				<p><?php echo mt_translate('Entendemos que la primera impresión y la comodidad de cada trayecto son fundamentales. Por ello, en MeTransfers no nos limitamos a llevarte a tu destino; nos aseguramos de que disfrutes de un servicio premium, fluido y sin interrupciones desde el primer contacto hasta tu llegada.'); ?></p>
				<p><?php echo mt_translate('Ya sea que necesites un traslado seguro al Aeropuerto Josep Tarradellas Barcelona-El Prat, coordinación logística para congresos corporativos o un chófer privado a tu entera disposición por horas, nuestro equipo de profesionales está preparado para brindarte una solución completamente a medida.'); ?></p>
			</div>
		</div>

		<!-- NUESTROS SERVICIOS -->
		<div class="svc-block">
			<span class="svc-label"><?php echo mt_translate('Lo que hacemos'); ?></span>
			<h2><?php echo mt_translate('Nuestros Servicios Principales'); ?></h2>
			<div class="svc-features-grid">
				<div class="svc-feature-card">
					<span class="material-symbols-outlined svc-feature-icon">flight_takeoff</span>
					<h3><?php echo mt_translate('Traslados al Aeropuerto y Puerto'); ?></h3>
					<p><?php echo mt_translate('Conexiones rápidas, seguras y sin esperas desde y hacia el Aeropuerto de Barcelona y la terminal de cruceros del Puerto.'); ?></p>
				</div>
				<div class="svc-feature-card">
					<span class="material-symbols-outlined svc-feature-icon">schedule</span>
					<h3><?php echo mt_translate('Disposición por Horas'); ?></h3>
					<p><?php echo mt_translate('Un chófer privado a tu disposición el tiempo que necesites, ideal para reuniones, compras o eventos.'); ?></p>
				</div>
				<div class="svc-feature-card">
					<span class="material-symbols-outlined svc-feature-icon">business_center</span>
					<h3><?php echo mt_translate('Eventos Corporativos'); ?></h3>
					<p><?php echo mt_translate('Coordinación logística para congresos (MWC, ISE), ferias y necesidades empresariales con facturación unificada.'); ?></p>
				</div>
				<div class="svc-feature-card">
					<span class="material-symbols-outlined svc-feature-icon">groups</span>
					<h3><?php echo mt_translate('Traslados para Grupos'); ?></h3>
					<p><?php echo mt_translate('Flota de minivans y furgonetas adaptadas para garantizar el confort de familias grandes o delegaciones enteras.'); ?></p>
				</div>
			</div>
		</div>

		<!-- RUTAS Y TOURS -->
		<div class="svc-block">
			<span class="svc-label"><?php echo mt_translate('Explora Cataluña'); ?></span>
			<h2><?php echo mt_translate('Rutas y Destinos Turísticos'); ?></h2>
			<div class="svc-desc-text" style="margin-bottom: 1.5rem;">
				<p><?php echo mt_translate('Conectamos Barcelona con los principales atractivos de la región. Ofrecemos tours privados de medio día o día completo con chóferes locales que conocen todos los secretos.'); ?></p>
			</div>
			<div class="svc-features-grid">
				<div class="svc-feature-card">
					<span class="material-symbols-outlined svc-feature-icon">landscape</span>
					<h3><?php echo mt_translate('Costa Brava'); ?></h3>
					<p><?php echo mt_translate('Descubre calas escondidas, pueblos pesqueros medievales y la magia del Mediterráneo a tu propio ritmo.'); ?></p>
				</div>
				<div class="svc-feature-card">
					<span class="material-symbols-outlined svc-feature-icon">attractions</span>
					<h3><?php echo mt_translate('Salou y PortAventura'); ?></h3>
					<p><?php echo mt_translate('Traslados directos para familias al parque de atracciones más famoso del sur de Europa y las playas de la Costa Dorada.'); ?></p>
				</div>
				<div class="svc-feature-card">
					<span class="material-symbols-outlined svc-feature-icon">castle</span>
					<h3><?php echo mt_translate('Girona y Montserrat'); ?></h3>
					<p><?php echo mt_translate('Explora el barrio judío de Girona o el monasterio sagrado de Montserrat en excursiones exclusivas sin grupos ni prisas.'); ?></p>
				</div>
				<div class="svc-feature-card">
					<span class="material-symbols-outlined svc-feature-icon">ac_unit</span>
					<h3><?php echo mt_translate('Andorra y Pirineos'); ?></h3>
					<p><?php echo mt_translate('Rutas de invierno para esquiar o disfrutar del paisaje montañoso y las compras libres de impuestos.'); ?></p>
				</div>
			</div>
		</div>

		<!-- NUESTRA FLOTA Y COMPROMISO -->
		<div class="svc-block">
			<span class="svc-label"><?php echo mt_translate('Garantía de calidad'); ?></span>
			<h2><?php echo mt_translate('Nuestro Compromiso y Flota'); ?></h2>
			<div class="svc-features-grid">
				<div class="svc-feature-card">
					<span class="material-symbols-outlined svc-feature-icon">directions_car</span>
					<h3><?php echo mt_translate('Flota de Alta Gama'); ?></h3>
					<p><?php echo mt_translate('Operamos con vehículos Mercedes-Benz y similares. Categorías Business, Economy y Minivans de lujo para adaptarnos a tu estilo.'); ?></p>
				</div>
				<div class="svc-feature-card">
					<span class="material-symbols-outlined svc-feature-icon">verified</span>
					<h3><?php echo mt_translate('Puntualidad Absoluta'); ?></h3>
					<p><?php echo mt_translate('Monitorizamos el estado de los vuelos y el tráfico en tiempo real para garantizar que tu chófer te esté esperando en el momento exacto.'); ?></p>
				</div>
				<div class="svc-feature-card">
					<span class="material-symbols-outlined svc-feature-icon">lock</span>
					<h3><?php echo mt_translate('Discreción y Profesionalidad'); ?></h3>
					<p><?php echo mt_translate('Nuestros conductores cuentan con amplia experiencia y garantizan la máxima confidencialidad durante tus desplazamientos.'); ?></p>
				</div>
				<div class="svc-feature-card">
					<span class="material-symbols-outlined svc-feature-icon">euro</span>
					<h3><?php echo mt_translate('Transparencia Total'); ?></h3>
					<p><?php echo mt_translate('Operamos bajo presupuestos fijos. Viaja con la tranquilidad de no encontrar cargos ocultos ni sorpresas de última hora.'); ?></p>
				</div>
			</div>
		</div>

		<!-- CTA FINAL -->
		<div class="svc-block" style="text-align: center; margin-top: 2rem; background: #f8fafc; padding: 3rem; border-radius: 16px; border: 1px solid #e2e8f0;">
			<h2 style="margin-bottom: 1rem;"><?php echo mt_translate('¿Listo para viajar con nosotros?'); ?></h2>
			<p style="margin-bottom: 2rem; color: #64748b; font-size: 1.1rem; max-width: 600px; margin-inline: auto;"><?php echo mt_translate('Contacta con nuestro equipo o reserva tu traslado online de forma rápida y segura.'); ?></p>
			<div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>#solicitar" class="btn btn-primary"><?php echo mt_translate('Reservar ahora'); ?></a>
				<a href="<?php echo esc_url( mt_localized_url( 'contacto' ) ); ?>" class="btn btn-ghost" style="border-color: #3b82f6; color: #3b82f6;"><?php echo mt_translate('Contactar'); ?></a>
			</div>
		</div>

	</div>
</main>

<?php get_footer(); ?>
