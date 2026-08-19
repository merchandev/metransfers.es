<?php
/**
 * The template for displaying the footer
 *
 * @package Me_Transfers
 */
?>
<style>
/* ─── Footer: colores explícitos y diseño compacto ─── */
.site-footer {
	background: #0B1F35;
	color: rgba(255,255,255,0.75);
	padding: 48px 0 0;
	font-family: 'Inter', system-ui, sans-serif;
}
.footer-inner {
	max-width: 1200px;
	margin: 0 auto;
	padding: 0 24px;
}
.footer-grid {
	display: grid;
	grid-template-columns: 1.6fr 1fr 1fr 1fr 1.3fr;
	gap: 32px;
	padding-bottom: 24px;
}
.footer-brand img {
	max-width: 180px; /* 10-15% bigger */
	height: auto;
}
.footer-brand-desc {
	margin-top: 14px;
	font-size: 14.5px;
	line-height: 1.5;
	color: rgba(255,255,255,0.65);
}
.footer-col-title {
	font-family: 'Outfit', system-ui, sans-serif;
	font-size: 13px;
	font-weight: 700;
	letter-spacing: 1.5px;
	text-transform: uppercase;
	color: #ffffff;
	margin-bottom: 18px;
	display: flex;
	justify-content: space-between;
	align-items: center;
	background: transparent;
	border: none;
	padding: 0;
	width: 100%;
	text-align: left;
}
.footer-links-list {
	list-style: none;
	padding: 0;
	margin: 0;
	display: flex;
	flex-direction: column;
	gap: 10px;
}
.footer-links-list a {
	color: rgba(255,255,255,0.75);
	font-size: 14.5px;
	text-decoration: none;
	transition: color 0.2s;
}
.footer-links-list a:hover {
	color: #ffffff;
}

/* Contact / Help Block */
.footer-help-text {
	font-size: 14.5px;
	color: rgba(255,255,255,0.7);
	margin-bottom: 16px;
	line-height: 1.4;
}
.footer-wa-btn {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	background: #25d366;
	color: #fff !important;
	padding: 10px 16px;
	border-radius: 8px;
	font-weight: 600;
	font-size: 14px;
	text-decoration: none;
	border: none;
	cursor: pointer;
	font-family: inherit;
	margin-bottom: 16px;
	transition: background 0.2s;
}
.footer-wa-btn:hover { background: #1ebc59; }

.footer-contact-item {
	display: flex;
	align-items: center;
	gap: 10px;
	font-size: 14.5px;
	color: rgba(255,255,255,0.75);
	margin-bottom: 12px;
}
.footer-contact-item a {
	color: rgba(255,255,255,0.85);
	text-decoration: none;
	transition: color 0.2s;
}
.footer-contact-item a:hover { color: #ffffff; }
.footer-contact-item svg {
	flex-shrink: 0;
	color: rgba(255,255,255,0.5);
}

/* Trust Badges Row */
.footer-trust-row {
	display: flex;
	justify-content: center;
	align-items: center;
	gap: 32px;
	padding: 24px 0;
	margin-top: 16px;
	border-top: 1px solid rgba(255,255,255,0.08);
	border-bottom: 1px solid rgba(255,255,255,0.08);
	flex-wrap: wrap;
}
.trust-badge-item {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	font-size: 14.5px;
	color: rgba(255,255,255,0.85);
	text-decoration: none;
	transition: color 0.2s;
}
.trust-badge-item:hover { color: #fff; }

/* Bottom Bar */
.footer-bottom {
	padding: 24px 0;
}
.footer-bottom-inner {
	max-width: 1200px;
	margin: 0 auto;
	padding: 0 24px;
	display: flex;
	justify-content: space-between;
	align-items: center;
	flex-wrap: wrap;
	gap: 16px;
}
.footer-bottom p {
	font-size: 14px;
	color: rgba(255,255,255,0.5);
	margin: 0;
}
.footer-bottom strong { color: rgba(255,255,255,0.75); }
.footer-legal-links {
	display: flex;
	gap: 20px;
	flex-wrap: wrap;
}
.footer-legal-links a {
	font-size: 13.5px;
	color: rgba(255,255,255,0.65);
	text-decoration: none;
	transition: color 0.2s;
}
.footer-legal-links a:hover { color: #ffffff; }
.footer-lang {
	font-size: 13.5px;
	color: rgba(255,255,255,0.65);
}

/* WhatsApp Floating Button */
@keyframes wa-pulse {
  0% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.6); }
  70% { box-shadow: 0 0 0 14px rgba(37, 211, 102, 0); }
  100% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0); }
}
.whatsapp-float {
	position: fixed;
	bottom: 24px;
	right: 24px;
	background: #25d366;
	color: #fff;
	height: 56px;
	border-radius: 28px;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 10px;
	padding: 0 24px;
	box-shadow: 0 4px 16px rgba(37, 211, 102, 0.4);
	z-index: 9999;
	transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.3s ease;
	border: none;
	cursor: pointer;
	font-family: 'Inter', system-ui, sans-serif;
	font-weight: 600;
	font-size: 15px;
	animation: wa-pulse 2.5s infinite;
}
.whatsapp-float:hover {
	transform: scale(1.06) translateY(-2px);
	box-shadow: 0 8px 24px rgba(37, 211, 102, 0.5);
	animation: none;
}
.whatsapp-float svg {
	width: 28px;
	height: 28px;
}

/* Modal WhatsApp */
.wa-modal {
	position: fixed; top: 0; left: 0; width: 100%; height: 100%;
	z-index: 10000; display: none; align-items: center; justify-content: center;
	opacity: 0; transition: opacity 0.3s;
}
.wa-modal.is-active { display: flex; opacity: 1; }
.wa-modal__overlay {
	position: absolute; top: 0; left: 0; width: 100%; height: 100%;
	background: rgba(11,31,53,0.8); backdrop-filter: blur(4px);
}
.wa-modal__box {
	position: relative; width: 90%; max-width: 380px;
	background: #fff; border-radius: 16px; overflow: hidden;
	box-shadow: 0 20px 40px rgba(0,0,0,0.2);
	transform: translateY(20px); transition: transform 0.3s;
}
.wa-modal.is-active .wa-modal__box { transform: translateY(0); }
.wa-modal__close {
	position: absolute; top: 12px; right: 12px;
	background: none; border: none; font-size: 28px; line-height: 1;
	color: #fff; cursor: pointer; opacity: 0.8; padding: 0; z-index: 2;
}
.wa-modal__close:hover { opacity: 1; }
.wa-modal__header {
	background: #075e54; color: #fff; padding: 24px 24px 20px 24px;
	display: flex; gap: 16px; align-items: center; position: relative;
}
.wa-modal__header strong { display: block; font-size: 16px; margin-bottom: 2px; }
.wa-modal__header span { font-size: 13px; opacity: 0.8; }
.wa-modal__body { padding: 24px; background: #e5ddd5; }
.wa-modal__bubble {
	background: #fff; padding: 12px 16px; border-radius: 0 12px 12px 12px;
	font-size: 14px; color: #303030; margin-bottom: 20px; line-height: 1.4;
	box-shadow: 0 1px 2px rgba(0,0,0,0.1); position: relative;
}
.wa-modal__bubble::before {
	content: ""; position: absolute; top: 0; left: -10px;
	width: 0; height: 0; border-style: solid;
	border-width: 0 10px 10px 0; border-color: transparent #fff transparent transparent;
}
.wa-fg { margin-bottom: 12px; }
.wa-fg input, .wa-fg textarea {
	width: 100%; padding: 12px 16px; border: none; border-radius: 20px;
	font: 400 15px sans-serif; box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}
.wa-fg input:focus, .wa-fg textarea:focus { outline: none; box-shadow: 0 0 0 2px #25d366; }
.wa-fg textarea { resize: none; height: 80px; border-radius: 12px; }
.wa-submit {
	width: 100%; background: #25d366; color: #fff;
	border: none; padding: 14px; border-radius: 24px;
	font-weight: 700; font-size: 15px; cursor: pointer;
	transition: background 0.2s; margin-top: 8px;
	display: flex; justify-content: center; align-items: center; gap: 8px;
}
.wa-submit:hover { background: #128c7e; }

/* Mobile Layout - Accordions */
@media (max-width: 1024px) {
	.footer-grid { grid-template-columns: 1fr 1fr 1fr; gap: 40px; }
	.footer-brand { grid-column: 1 / -1; }
	.footer-col--help { grid-column: 1 / -1; }
}
@media (max-width: 768px) {
	.footer-grid { display: block; padding-bottom: 0; border-bottom: none; }
	.footer-brand { margin-bottom: 32px; }
	.footer-col { 
		border-bottom: 1px solid rgba(255,255,255,0.08); 
	}
	.footer-col-title {
		margin-bottom: 0;
		padding: 20px 0;
		cursor: pointer;
	}
	.footer-col-title::after {
		content: "+"; font-size: 18px; font-weight: normal; font-family: sans-serif;
	}
	.footer-col.is-open .footer-col-title::after { content: "−"; }
	.footer-links-list { 
		display: none; 
		padding-bottom: 20px; 
	}
	.footer-col.is-open .footer-links-list { display: flex; }
	
	.footer-col--help { 
		border-bottom: none; 
		padding-top: 32px; 
		padding-bottom: 16px;
	}
	.footer-col--help .footer-col-title { display: none; } /* Hide title on mobile for Help block */
	
	.footer-trust-row { flex-direction: column; gap: 20px; align-items: flex-start; }
	.footer-bottom-inner { flex-direction: column; align-items: flex-start; gap: 24px; }
	.footer-legal-links { flex-direction: column; gap: 16px; }
	.footer-links-list a, .footer-contact-item a, .footer-wa-btn {
		padding: 6px 0; /* Min touch target */
	}
	
	.whatsapp-float {
		width: 60px;
		height: 60px;
		padding: 0;
		border-radius: 50%;
		bottom: 20px;
		right: 20px;
	}
	.whatsapp-float span {
		display: none;
	}
	.whatsapp-float svg {
		width: 32px;
		height: 32px;
	}
}
</style>

<footer id="colophon" class="site-footer" role="contentinfo">

	<div class="footer-inner">

		<!-- Grid 5 columnas -->
		<div class="footer-grid">

			<!-- Logo y Descripción -->
			<div class="footer-brand">
				<?php if ( has_custom_logo() ) :
					the_custom_logo();
				else : ?>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" style="font-family:'Outfit',system-ui,sans-serif;font-size:24px;font-weight:700;color:#fff;text-decoration:none;">
						<?php bloginfo( 'name' ); ?>
					</a>
				<?php endif; ?>
				<p class="footer-brand-desc"><?php echo mt_translate("Traslados privados, tours y servicios con chófer en Barcelona y larga distancia. Vehículos premium y atención personalizada 24/7."); ?></p>
			</div>

			<!-- Servicios -->
			<div class="footer-col js-footer-accordion">
				<button type="button" class="footer-col-title" aria-expanded="false" aria-controls="footer-services-links"><?php echo mt_translate("Servicios"); ?></button>
				<ul id="footer-services-links" class="footer-links-list">
					<li><a href="<?php echo esc_url( mt_localized_url( 'traslados-aeropuerto' ) ); ?>"><?php echo mt_translate("Aeropuerto"); ?></a></li>
					<li><a href="<?php echo esc_url( mt_localized_url( 'traslados-puerto' ) ); ?>"><?php echo mt_translate("Puerto"); ?></a></li>
					<li><a href="<?php echo esc_url( mt_localized_url( 'chofer-por-horas' ) ); ?>"><?php echo mt_translate("Por horas"); ?></a></li>
					<li><a href="<?php echo esc_url( mt_localized_url( 'corporativo-y-eventos' ) ); ?>"><?php echo mt_translate("Empresas"); ?></a></li>
					<li><a href="<?php echo esc_url( mt_localized_url( 'grupos' ) ); ?>"><?php echo mt_translate("Grupos"); ?></a></li>
				</ul>
			</div>

			<!-- Destinos y Tours -->
			<div class="footer-col js-footer-accordion">
				<button type="button" class="footer-col-title" aria-expanded="false" aria-controls="footer-destinations-links"><?php echo mt_translate("Destinos y rutas"); ?></button>
				<ul id="footer-destinations-links" class="footer-links-list">
					<li><a href="<?php echo esc_url( mt_localized_url( 'traslados-privados' ) ); ?>"><?php echo mt_translate("Traslados privados Barcelona"); ?></a></li>
					<li><a href="<?php echo esc_url( mt_localized_url( 'destinos/costa-brava' ) ); ?>"><?php echo mt_translate("Costa Brava"); ?></a></li>
					<li><a href="<?php echo esc_url( mt_localized_url( 'rutas/barcelona-salou' ) ); ?>"><?php echo mt_translate("Salou"); ?></a></li>
					<li><a href="<?php echo esc_url( mt_localized_url( 'rutas/barcelona-portaventura' ) ); ?>"><?php echo mt_translate("PortAventura"); ?></a></li>
					<li><a href="<?php echo esc_url( mt_localized_url( 'rutas/barcelona-girona' ) ); ?>"><?php echo mt_translate("Girona"); ?></a></li>
					<li><a href="<?php echo esc_url( get_post_type_archive_link( 'ruta' ) ); ?>"><?php echo mt_translate("Todas las rutas"); ?></a></li>
				</ul>
			</div>

			<!-- Información -->
			<div class="footer-col js-footer-accordion">
				<button type="button" class="footer-col-title" aria-expanded="false" aria-controls="footer-info-links"><?php echo mt_translate("Información"); ?></button>
				<ul id="footer-info-links" class="footer-links-list">
					<li><a href="<?php echo esc_url( mt_localized_url( 'sobre-nosotros' ) ); ?>"><?php echo mt_translate("Sobre nosotros"); ?></a></li>
					<li><a href="<?php echo esc_url( mt_localized_url( 'blog' ) ); ?>"><?php echo mt_translate("Blog"); ?></a></li>
					<li><a href="<?php echo esc_url( mt_localized_url( 'preguntas-frecuentes' ) ); ?>"><?php echo mt_translate("Preguntas frecuentes"); ?></a></li>
					<li><a href="<?php echo esc_url( mt_localized_url( 'contacto' ) ); ?>"><?php echo mt_translate("Contacto"); ?></a></li>
				</ul>
			</div>

			<!-- Ayuda (Contacto) -->
			<div class="footer-col footer-col--help">
				<p class="footer-col-title" style="display:none;">Ayuda</p>
				<p class="footer-help-text"><?php echo mt_translate("¿Necesitas ayuda con tu reserva?"); ?><br><?php echo mt_translate("Atención por teléfono y WhatsApp, 24 horas."); ?></p>
				
				<button type="button" class="footer-wa-btn js-wa-trigger">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0 0 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>
					<?php echo mt_translate('Hablar por WhatsApp'); ?>
				</button>
				
				<div class="footer-contact-item">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.62 2h3A2 2 0 0 1 8.6 3.72c.12.92.34 1.82.67 2.69a2 2 0 0 1-.45 2.11L7.73 9.61a16 16 0 0 0 6.66 6.66l1.09-1.09a2 2 0 0 1 2.11-.45c.87.33 1.77.55 2.69.67A2 2 0 0 1 22 16.92z"/></svg>
					<a href="tel:+34662024136">+34 662 02 41 36</a>
				</div>
				<div class="footer-contact-item">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 4 10 8 10-8"/></svg>
					<a href="mailto:info@metransfers.es">info@metransfers.es</a>
				</div>
			</div>

		</div><!-- .footer-grid -->

		<!-- Trust Badges Horizontal -->
		<div class="footer-trust-row">
			<span class="trust-badge-item">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2.5" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
				<?php echo mt_translate('Pago seguro'); ?>
			</span>
			<a href="https://www.getyourguide.com/es-es/metransfers-s12737/" target="_blank" rel="noopener noreferrer" class="trust-badge-item">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="#FFB547" stroke="none" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
				<?php echo mt_translate('Opiniones verificadas en GetYourGuide'); ?>
			</a>
			<span class="trust-badge-item">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg><?php echo mt_translate("Cancelación sujeta a condiciones"); ?></span>
		</div>

	</div><!-- .footer-inner -->

	<!-- Bottom bar -->
	<div class="footer-bottom">
		<div class="footer-bottom-inner">
			<p>&copy; <?php echo date("Y"); ?> <strong>MeTransfers Barcelona</strong>.</p>
			
			<nav class="footer-legal-links" aria-label="Legal">
				<a href="<?php echo esc_url( mt_localized_url( 'politica-de-privacidad' ) ); ?>"><?php echo mt_translate("Política de privacidad"); ?></a>
				<a href="<?php echo esc_url( mt_localized_url( 'politica-de-cookies' ) ); ?>"><?php echo mt_translate("Política de cookies"); ?></a>
				<a href="<?php echo esc_url( mt_localized_url( 'aviso-legal' ) ); ?>"><?php echo mt_translate("Aviso legal"); ?></a>
				<a href="<?php echo esc_url( mt_localized_url( 'terminos-y-condiciones' ) ); ?>"><?php echo mt_translate("Términos y condiciones"); ?></a>
			</nav>

			<div class="footer-lang">
				<a
					href="<?php echo esc_url( home_url( '/' ) ); ?>"
					style="color:rgba(255,255,255,0.65);text-decoration:none;"
				>
					Español
				</a>
				·
				<a
					href="<?php echo esc_url( home_url( '/en/' ) ); ?>"
					style="color:rgba(255,255,255,0.65);text-decoration:none;"
				>
					English
				</a>
			</div>
		</div>
	</div>

</footer><!-- #colophon -->
</div><!-- #page -->

	<?php get_template_part('template-parts/cookie-banner'); ?>
	
	<?php wp_footer(); ?>

<!-- Modal WhatsApp -->
<div class="wa-modal" id="waModal" role="dialog" aria-modal="true" aria-labelledby="wa-modal-title" aria-hidden="true">
    <div class="wa-modal__overlay"></div>
    <div class="wa-modal__box">
        <button type="button" class="wa-modal__close" aria-label="Cerrar ventana de WhatsApp">&times;</button>
        <div class="wa-modal__header">
            <svg width="32" height="32" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true"><path d="M26.576 5.363c-2.69-2.69-6.406-4.354-10.511-4.354-8.209 0-14.865 6.655-14.865 14.865 0 2.732 0.737 5.291 2.022 7.491l-0.038-0.070-2.109 7.702 7.879-2.067c2.051 1.139 4.498 1.809 7.102 1.809h0.006c8.209-0.003 14.862-6.659 14.862-14.868 0-4.103-1.662-7.817-4.349-10.507l0 0zM16.062 28.228h-0.005c-0 0-0.001 0-0.001 0-2.319 0-4.489-0.64-6.342-1.753l0.056 0.031-0.451-0.267-4.675 1.227 1.247-4.559-0.294-0.467c-1.185-1.862-1.889-4.131-1.889-6.565 0-6.822 5.531-12.353 12.353-12.353s12.353 5.531 12.353 12.353c0 6.822-5.53 12.353-12.353 12.353h-0zM22.838 18.977c-0.371-0.186-2.197-1.083-2.537-1.208-0.341-0.124-0.589-0.185-0.837 0.187-0.246 0.371-0.958 1.207-1.175 1.455-0.216 0.249-0.434 0.279-0.805 0.094-1.15-0.466-2.138-1.087-2.997-1.852l0.010 0.009c-0.799-0.74-1.484-1.587-2.037-2.521l-0.028-0.052c-0.216-0.371-0.023-0.572 0.162-0.757 0.167-0.166 0.372-0.434 0.557-0.65 0.146-0.179 0.271-0.384 0.366-0.604l0.006-0.017c0.043-0.087 0.068-0.188 0.068-0.296 0-0.131-0.037-0.253-0.101-0.357l0.002 0.003c-0.094-0.186-0.836-2.014-1.145-2.758-0.302-0.724-0.609-0.625-0.836-0.637-0.216-0.010-0.464-0.012-0.712-0.012-0.395 0.010-0.746 0.188-0.988 0.463l-0.001 0.002c-0.802 0.761-1.3 1.834-1.3 3.023 0 0.026 0 0.053 0.001 0.079l-0-0.004c0.131 1.467 0.681 2.784 1.527 3.857l-0.012-0.015c1.604 2.379 3.742 4.282 6.251 5.564l0.094 0.043c0.548 0.248 1.25 0.513 1.968 0.74l0.149 0.041c0.442 0.14 0.951 0.221 1.479 0.221 0.303 0 0.601-0.027 0.889-0.078l-0.031 0.004c1.069-0.223 1.956-0.868 2.497-1.749l0.009-0.017c0.165-0.366 0.261-0.793 0.261-1.242 0-0.185-0.016-0.366-0.047-0.542l0.003 0.019c-0.092-0.155-0.34-0.247-0.712-0.434z"/></svg>
            <div>
                <strong id="wa-modal-title">MeTransfers Barcelona</strong>
                <span>Normalmente responde al instante</span>
            </div>
        </div>
        <div class="wa-modal__body">
            <div class="wa-modal__bubble">Hola 👋<br>¿En qué podemos ayudarte? Déjanos tus datos y consulta para agilizar la gestión.</div>
            <form id="waForm">
                <div class="wa-fg">
                    <label for="wa-nombre" class="screen-reader-text">Nombre completo</label>
                    <input id="wa-nombre" type="text" name="nombre" autocomplete="name" placeholder="Tu nombre completo" required>
                </div>
                <div class="wa-fg">
                    <label for="wa-telefono" class="screen-reader-text">Teléfono</label>
                    <input id="wa-telefono" type="tel" name="telefono" autocomplete="tel" placeholder="Teléfono" required>
                </div>
                <div class="wa-fg">
                    <label for="wa-mensaje" class="screen-reader-text">Consulta</label>
                    <textarea id="wa-mensaje" name="mensaje" placeholder="Tu consulta..." required></textarea>
                </div>
                <div class="wa-fg" style="margin-bottom:15px; font-size:13px;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" name="gdpr_aceptado" value="1" required style="width:auto; margin:0;">
                        <span>He leído y acepto la política de privacidad.</span>
                    </label>
                </div>
                <button type="submit" class="wa-submit">
                	<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg> Iniciar Chat
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Botón flotante WhatsApp -->
<button type="button" class="whatsapp-float js-wa-trigger" aria-label="Contactar por WhatsApp">
	<svg viewBox="0 0 32 32" fill="currentColor" aria-hidden="true"><path d="M26.576 5.363c-2.69-2.69-6.406-4.354-10.511-4.354-8.209 0-14.865 6.655-14.865 14.865 0 2.732 0.737 5.291 2.022 7.491l-0.038-0.070-2.109 7.702 7.879-2.067c2.051 1.139 4.498 1.809 7.102 1.809h0.006c8.209-0.003 14.862-6.659 14.862-14.868 0-4.103-1.662-7.817-4.349-10.507l0 0zM16.062 28.228h-0.005c-0 0-0.001 0-0.001 0-2.319 0-4.489-0.64-6.342-1.753l0.056 0.031-0.451-0.267-4.675 1.227 1.247-4.559-0.294-0.467c-1.185-1.862-1.889-4.131-1.889-6.565 0-6.822 5.531-12.353 12.353-12.353s12.353 5.531 12.353 12.353c0 6.822-5.53 12.353-12.353 12.353h-0zM22.838 18.977c-0.371-0.186-2.197-1.083-2.537-1.208-0.341-0.124-0.589-0.185-0.837 0.187-0.246 0.371-0.958 1.207-1.175 1.455-0.216 0.249-0.434 0.279-0.805 0.094-1.15-0.466-2.138-1.087-2.997-1.852l0.010 0.009c-0.799-0.74-1.484-1.587-2.037-2.521l-0.028-0.052c-0.216-0.371-0.023-0.572 0.162-0.757 0.167-0.166 0.372-0.434 0.557-0.65 0.146-0.179 0.271-0.384 0.366-0.604l0.006-0.017c0.043-0.087 0.068-0.188 0.068-0.296 0-0.131-0.037-0.253-0.101-0.357l0.002 0.003c-0.094-0.186-0.836-2.014-1.145-2.758-0.302-0.724-0.609-0.625-0.836-0.637-0.216-0.010-0.464-0.012-0.712-0.012-0.395 0.010-0.746 0.188-0.988 0.463l-0.001 0.002c-0.802 0.761-1.3 1.834-1.3 3.023 0 0.026 0 0.053 0.001 0.079l-0-0.004c0.131 1.467 0.681 2.784 1.527 3.857l-0.012-0.015c1.604 2.379 3.742 4.282 6.251 5.564l0.094 0.043c0.548 0.248 1.25 0.513 1.968 0.74l0.149 0.041c0.442 0.14 0.951 0.221 1.479 0.221 0.303 0 0.601-0.027 0.889-0.078l-0.031 0.004c1.069-0.223 1.956-0.868 2.497-1.749l0.009-0.017c0.165-0.366 0.261-0.793 0.261-1.242 0-0.185-0.016-0.366-0.047-0.542l0.003 0.019c-0.092-0.155-0.34-0.247-0.712-0.434z"/></svg>
	<span><?php echo mt_translate('¿Te ayudamos?'); ?></span>
</button>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// Mobile Accordion Logic
	const footerAccordionQuery = window.matchMedia('(max-width: 768px)');

	function syncFooterAccordions() {
		document.querySelectorAll('.js-footer-accordion').forEach(function(section) {
			const button = section.querySelector('.footer-col-title');
			const list = section.querySelector('.footer-links-list');
			if (!button || !list) return;

			if (footerAccordionQuery.matches) {
				const isOpen = section.classList.contains('is-open');
				button.setAttribute('aria-expanded', String(isOpen));
			} else {
				section.classList.remove('is-open');
				button.setAttribute('aria-expanded', 'true');
			}
		});
	}

	syncFooterAccordions();
	footerAccordionQuery.addEventListener('change', syncFooterAccordions);

	const accordions = document.querySelectorAll('.js-footer-accordion .footer-col-title');
	accordions.forEach(button => {
		button.addEventListener('click', function() {
			if (window.innerWidth <= 768) {
				const parent = this.closest('.footer-col');
				const isOpen = parent.classList.toggle('is-open');
				this.setAttribute('aria-expanded', String(isOpen));
			}
		});
	});

	const waBtn = document.querySelector('.whatsapp-float');
	// El hover scale y transicion se manejan ahora nativamente por CSS en .whatsapp-float:hover

	// Modal Logic
	const modal = document.getElementById('waModal');
	const triggers = document.querySelectorAll('.js-wa-trigger');
	const close = document.querySelector('.wa-modal__close');
	const overlay = document.querySelector('.wa-modal__overlay');
	const waForm = document.getElementById('waForm');
	let lastFocusedElement = null;

	function openModal(e) {
		if (e) e.preventDefault();
		lastFocusedElement = document.activeElement;
		modal.classList.add('is-active');
		modal.setAttribute('aria-hidden', 'false');
		const firstField = modal.querySelector('input, textarea, button');
		if (firstField) firstField.focus();
	}

	function closeModal() {
		modal.classList.remove('is-active');
		modal.setAttribute('aria-hidden', 'true');
		if (lastFocusedElement) {
			lastFocusedElement.focus();
		}
	}

	document.addEventListener('keydown', function(e) {
		if (e.key === 'Escape' && modal.classList.contains('is-active')) {
			closeModal();
		}
	});

	function trapModalFocus(e) {
		if (e.key !== 'Tab' || !modal.classList.contains('is-active')) return;
		const focusable = Array.from(modal.querySelectorAll('button:not([disabled]), a[href], input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'));
		if (!focusable.length) return;
		const first = focusable[0];
		const last = focusable[focusable.length - 1];
		if (e.shiftKey && document.activeElement === first) {
			e.preventDefault();
			last.focus();
		} else if (!e.shiftKey && document.activeElement === last) {
			e.preventDefault();
			first.focus();
		}
	}
	document.addEventListener('keydown', trapModalFocus);

	triggers.forEach(t => t.addEventListener('click', openModal));
	if(close) close.addEventListener('click', closeModal);
	if(overlay) overlay.addEventListener('click', closeModal);

	// Form Submission
	if(waForm) {
		waForm.addEventListener('submit', function(e) {
			e.preventDefault();
			const btn = waForm.querySelector('button[type="submit"]');
			const originalHtml = btn.innerHTML;
			btn.innerHTML = 'Enviando...';
			btn.disabled = true;

			const formData = new FormData(waForm);
			formData.append('action', 'mt_save_lead');
			formData.append('security', window.mtAjax ? mtAjax.nonce : '');
			formData.append('origen', 'whatsapp');

			if(!window.mtAjax) {
				console.error('Error crítico: mtAjax no definido. Abortando envío.');
				alert('Ocurrió un error en la configuración. Por favor recargue la página.');
				btn.innerHTML = originalHtml;
				btn.disabled = false;
				return;
			}

			fetch(mtAjax.ajaxurl, {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (!data.success) {
					throw new Error(data.data?.message || 'No se pudo guardar la solicitud.');
				}
				const msg = encodeURIComponent(formData.get('mensaje') + '\n\n*Nombre:* ' + formData.get('nombre') + '\n*Teléfono:* ' + formData.get('telefono'));
				window.location.href = 'https://wa.me/34662024136?text=' + msg;
			})
			.catch(err => {
				console.error(err);
				alert(err.message || 'Error de conexión. Inténtelo de nuevo.');
			})
			.finally(() => {
				btn.innerHTML = originalHtml;
				btn.disabled = false;
				closeModal();
				waForm.reset();
			});
		});
	}
/* Event Tracking */
	document.addEventListener('click', function(e) {
		const target = e.target.closest('button, .btn, a.js-wa-trigger, a[href^="mailto:"], a[href^="tel:"]');
		if (target) {
			let buttonText = target.innerText.trim() || target.value || target.title || 'Botón sin texto';
			// Si el texto es muy largo, cortarlo
			if (buttonText.length > 50) buttonText = buttonText.substring(0, 50) + '...';
			
			const buttonClass = target.className || '';
			const pageUrl = window.location.href;

			if (window.mtAjax && mtAjax.ajaxurl) {
				const formData = new FormData();
				formData.append('action', 'mt_track_button_click');
				formData.append('security', mtAjax.nonce);
				formData.append('button_text', buttonText);
				formData.append('button_class', buttonClass);
				formData.append('page_url', pageUrl);

				fetch(mtAjax.ajaxurl, {
					method: 'POST',
					body: formData
				}).catch(err => console.log('Error tracking click'));
			}
		}
	});
});
</script>

</body>
</html>
