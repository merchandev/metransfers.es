<?php
/**
 * Template Name: Tours y Excursiones
 *
 * Página especial para mostrar los tours y excursiones.
 */

get_header(); ?>

<main id="primary" class="site-main">

	<!-- Hero Section -->
	<section class="hero-section hero-tours" style="background-image: linear-gradient(to bottom, rgba(0,28,56,0.7), rgba(0,28,56,0.9)), url('https://metransfers.es/wp-content/uploads/2026/07/airport-transfer-me-tranfers-me-tranfers-barcelona-espana.webp'); background-size: cover; background-position: center; padding: 120px 20px; text-align: center; color: white;">
		<div class="hero-content" style="max-width: 800px; margin: 0 auto;">
			<h1 style="color: white; font-size: clamp(2.5rem, 5vw, 4rem); margin-bottom: 20px; text-shadow: 0 4px 10px rgba(0,0,0,0.5);">Tours y Excursiones</h1>
			<p style="font-size: clamp(1.1rem, 2vw, 1.3rem); opacity: 0.9; line-height: 1.6; font-weight: 300;">Descubre Barcelona y Cataluña con experiencias privadas diseñadas para combinar cultura, paisaje y confort premium.</p>
		</div>
	</section>

	<!-- Grid Section -->
	<section class="tours-section" style="padding: 80px 20px; background-color: #f8fafc;">
		<div class="container" style="max-width: 1200px; margin: 0 auto;">
			<div class="tours-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px;">

				<!-- Tour 1 -->
				<div class="tour-card" style="background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: transform 0.3s ease, box-shadow 0.3s ease; display: flex; flex-direction: column;">
					<div class="tour-image" style="position: relative; height: 240px; background-image: url('https://images.unsplash.com/photo-1583422409516-1500d05a0fc1?q=80&w=800&auto=format&fit=crop'); background-size: cover; background-position: center;">
						<div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 50px 20px 15px; background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);">
							<h3 style="color: white; margin: 0; font-size: 1.4rem; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">Tour en Barcelona</h3>
						</div>
					</div>
					<div class="tour-content" style="padding: 25px; flex-grow: 1; display: flex; flex-direction: column;">
						<p style="color: #475569; line-height: 1.6; font-size: 0.95rem; margin-bottom: 25px; flex-grow: 1;">Descubre Barcelona con un recorrido por sus monumentos icónicos, como la Sagrada Familia, el Barrio Gótico y el Paseo de Gracia. Disfruta de la arquitectura de Gaudí y la vibrante cultura catalana en un tour inolvidable.</p>
						<a href="#contacto" class="tour-btn" style="color: #004E9A; font-weight: 700; text-decoration: none; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid transparent; transition: border-color 0.3s ease; align-self: flex-start; padding-bottom: 2px;">DETALLES</a>
					</div>
				</div>

				<!-- Tour 2 -->
				<div class="tour-card" style="background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: transform 0.3s ease, box-shadow 0.3s ease; display: flex; flex-direction: column;">
					<div class="tour-image" style="position: relative; height: 240px; background-image: url('https://images.unsplash.com/photo-1549416560-60b6416a9dd7?q=80&w=800&auto=format&fit=crop'); background-size: cover; background-position: center;">
						<div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 50px 20px 15px; background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);">
							<h3 style="color: white; margin: 0; font-size: 1.4rem; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">Tour a Montserrat</h3>
						</div>
					</div>
					<div class="tour-content" style="padding: 25px; flex-grow: 1; display: flex; flex-direction: column;">
						<p style="color: #475569; line-height: 1.6; font-size: 0.95rem; margin-bottom: 25px; flex-grow: 1;">Explora la majestuosa montaña de Montserrat y su monasterio benedictino, hogar de la Virgen de Montserrat. Disfruta de vistas panorámicas, senderos naturales y la espiritualidad de este emblemático lugar de Cataluña.</p>
						<a href="#contacto" class="tour-btn" style="color: #004E9A; font-weight: 700; text-decoration: none; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid transparent; transition: border-color 0.3s ease; align-self: flex-start; padding-bottom: 2px;">DETALLES</a>
					</div>
				</div>

				<!-- Tour 3 -->
				<div class="tour-card" style="background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: transform 0.3s ease, box-shadow 0.3s ease; display: flex; flex-direction: column;">
					<div class="tour-image" style="position: relative; height: 240px; background-image: url('https://images.unsplash.com/photo-1616421455581-229ef5e05417?q=80&w=800&auto=format&fit=crop'); background-size: cover; background-position: center;">
						<div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 50px 20px 15px; background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);">
							<h3 style="color: white; margin: 0; font-size: 1.4rem; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">Tour Costa Brava</h3>
						</div>
					</div>
					<div class="tour-content" style="padding: 25px; flex-grow: 1; display: flex; flex-direction: column;">
						<p style="color: #475569; line-height: 1.6; font-size: 0.95rem; margin-bottom: 25px; flex-grow: 1;">Sumérgete en las aguas cristalinas y paisajes únicos de la Costa Brava. Recorre encantadores pueblos pesqueros, calas escondidas y disfruta de la mejor gastronomía mediterránea en un entorno paradisíaco.</p>
						<a href="#contacto" class="tour-btn" style="color: #004E9A; font-weight: 700; text-decoration: none; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid transparent; transition: border-color 0.3s ease; align-self: flex-start; padding-bottom: 2px;">DETALLES</a>
					</div>
				</div>

				<!-- Tour 4 -->
				<div class="tour-card" style="background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: transform 0.3s ease, box-shadow 0.3s ease; display: flex; flex-direction: column;">
					<div class="tour-image" style="position: relative; height: 240px; background-image: url('https://images.unsplash.com/photo-1629813580521-2a90cd66d1ee?q=80&w=800&auto=format&fit=crop'); background-size: cover; background-position: center;">
						<div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 50px 20px 15px; background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);">
							<h3 style="color: white; margin: 0; font-size: 1.4rem; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">Tour a Girona</h3>
						</div>
					</div>
					<div class="tour-content" style="padding: 25px; flex-grow: 1; display: flex; flex-direction: column;">
						<p style="color: #475569; line-height: 1.6; font-size: 0.95rem; margin-bottom: 25px; flex-grow: 1;">Pasea por la histórica ciudad de Girona, con su impresionante casco antiguo, el barrio judío y los coloridos puentes sobre el río Onyar. Un destino lleno de historia, cultura y escenarios de película.</p>
						<a href="#contacto" class="tour-btn" style="color: #004E9A; font-weight: 700; text-decoration: none; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid transparent; transition: border-color 0.3s ease; align-self: flex-start; padding-bottom: 2px;">DETALLES</a>
					</div>
				</div>

			</div>
		</div>
	</section>

	<!-- CTA Section -->
	<section id="contacto" style="padding: 80px 20px; background-color: #0D1B2A; text-align: center; color: white;">
		<div style="max-width: 700px; margin: 0 auto;">
			<h2 style="color: white; font-size: 2.2rem; margin-bottom: 20px;">¿Buscas un viaje a medida?</h2>
			<p style="font-size: 1.1rem; opacity: 0.8; margin-bottom: 40px; line-height: 1.6;">Nuestros expertos locales pueden diseñar una experiencia exclusiva adaptada a tus preferencias. Contáctanos por WhatsApp o formulario para solicitar presupuesto sin compromiso.</p>
			
			<div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
				<a href="https://wa.me/34662024136?text=Hola,%20quiero%20reservar%20un%20tour%20privado" target="_blank" rel="noopener" class="btn btn-whatsapp" style="background-color: #25D366; color: white; padding: 15px 30px; border-radius: 100px; font-weight: bold; text-decoration: none; display: inline-flex; align-items: center; gap: 10px; transition: transform 0.3s ease;">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0 0 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>
					WhatsApp
				</a>
				<a href="<?php echo esc_url( me_transfers_get_section_url( 'panel' ) ); ?>" class="btn" style="background-color: white; color: #004E9A; padding: 15px 30px; border-radius: 100px; font-weight: bold; text-decoration: none; display: inline-flex; align-items: center; gap: 10px; transition: transform 0.3s ease;">
					Ir al formulario principal
				</a>
			</div>
		</div>
	</section>

</main>

<style>
.tour-card:hover {
	transform: translateY(-5px);
	box-shadow: 0 15px 40px rgba(0,0,0,0.1) !important;
}
.tour-btn:hover {
	border-bottom-color: #004E9A !important;
}
.btn-whatsapp:hover {
	transform: translateY(-2px);
}
.btn:hover {
	transform: translateY(-2px);
	opacity: 0.95;
}
</style>

<?php get_footer(); ?>

