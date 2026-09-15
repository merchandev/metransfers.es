<?php
/**
 * Template Name: Tours y Excursiones
 *
 * Página especial para mostrar los tours y excursiones.
 */

get_header(); ?>

<main id="primary" class="site-main">

	<!-- Hero Section -->
	<section class="hero-section hero-tours">
		<div class="hero-content hc-tours">
			<h1 class="h1-tours"><?php echo mt_translate('Tours y Excursiones'); ?></h1>
			<p class="p-tours"><?php echo mt_translate('Descubre Barcelona y Cataluña con experiencias privadas diseñadas para combinar cultura, paisaje y confort premium.'); ?></p>
		</div>
	</section>

	<!-- Grid Section -->
	<section class="tours-section ts-wrapper">
		<div class="container c-tours">
			<div class="tours-grid tg-wrapper">

				<!-- Tour 1 -->
				<div class="tour-card tc-wrapper">
					<div class="tour-image ti-1">
						<div class="tour-gradient">
							<h3 class="th3-tours"><?php echo mt_translate('Tour en Barcelona'); ?></h3>
						</div>
					</div>
					<div class="tour-content tc-content">
						<p class="tp-tours"><?php echo mt_translate('Descubre Barcelona con un recorrido por sus monumentos icónicos, como la Sagrada Familia, el Barrio Gótico y el Paseo de Gracia. Disfruta de la arquitectura de Gaudí y la vibrante cultura catalana en un tour inolvidable.'); ?></p>
						<a href="#contacto" class="tour-btn tb-tours"><?php echo mt_translate('DETALLES'); ?></a>
					</div>
				</div>

				<!-- Tour 2 -->
				<div class="tour-card tc-wrapper">
					<div class="tour-image ti-2">
						<div class="tour-gradient">
							<h3 class="th3-tours"><?php echo mt_translate('Tour a Montserrat'); ?></h3>
						</div>
					</div>
					<div class="tour-content tc-content">
						<p class="tp-tours"><?php echo mt_translate('Explora la majestuosa montaña de Montserrat y su monasterio benedictino, hogar de la Virgen de Montserrat. Disfruta de vistas panorámicas, senderos naturales y la espiritualidad de este emblemático lugar de Cataluña.'); ?></p>
						<a href="#contacto" class="tour-btn tb-tours"><?php echo mt_translate('DETALLES'); ?></a>
					</div>
				</div>

				<!-- Tour 3 -->
				<div class="tour-card tc-wrapper">
					<div class="tour-image ti-3">
						<div class="tour-gradient">
							<h3 class="th3-tours"><?php echo mt_translate('Tour Costa Brava'); ?></h3>
						</div>
					</div>
					<div class="tour-content tc-content">
						<p class="tp-tours"><?php echo mt_translate('Sumérgete en las aguas cristalinas y paisajes únicos de la Costa Brava. Recorre encantadores pueblos pesqueros, calas escondidas y disfruta de la mejor gastronomía mediterránea en un entorno paradisíaco.'); ?></p>
						<a href="#contacto" class="tour-btn tb-tours"><?php echo mt_translate('DETALLES'); ?></a>
					</div>
				</div>

				<!-- Tour 4 -->
				<div class="tour-card tc-wrapper">
					<div class="tour-image ti-4">
						<div class="tour-gradient">
							<h3 class="th3-tours"><?php echo mt_translate('Tour a Girona'); ?></h3>
						</div>
					</div>
					<div class="tour-content tc-content">
						<p class="tp-tours"><?php echo mt_translate('Pasea por la histórica ciudad de Girona, con su impresionante casco antiguo, el barrio judío y los coloridos puentes sobre el río Onyar. Un destino lleno de historia, cultura y escenarios de película.'); ?></p>
						<a href="#contacto" class="tour-btn tb-tours"><?php echo mt_translate('DETALLES'); ?></a>
					</div>
				</div>

			</div>
		</div>
	</section>

	<!-- CTA Section -->
	<section id="contacto" class="cta-tours">
		<div class="cta-content">
			<h2 class="cta-h2"><?php echo mt_translate('¿Buscas un viaje a medida?'); ?></h2>
			<p class="cta-p"><?php echo mt_translate('Nuestros expertos locales pueden diseñar una experiencia exclusiva adaptada a tus preferencias. Contáctanos por WhatsApp o formulario para solicitar presupuesto sin compromiso.'); ?></p>
			
			<div class="cta-buttons">
				<a href="https://wa.me/34662024136?text=Hola,%20quiero%20reservar%20un%20tour%20privado" target="_blank" rel="noopener" class="btn btn-whatsapp bw-tours">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0 0 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>
					WhatsApp
				</a>
				<a href="<?php echo esc_url( me_transfers_get_section_url( 'panel' ) ); ?>" class="btn bc-tours">
					<?php echo mt_translate('Ir al formulario principal'); ?>
				</a>
			</div>
		</div>
	</section>

</main>

<?php get_footer(); ?>
