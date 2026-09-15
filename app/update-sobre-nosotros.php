<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mt_update_sobre_nosotros_page() {
    // Solo ejecutar una vez esta actualizacion
    if ( get_option( 'mt_sobre_nosotros_updated_v1' ) ) {
        return;
    }

    $page = get_page_by_path( 'sobre-nosotros' );
    if ( $page ) {
        $content = '
<h2>Bienvenidos a MeTransfers Barcelona</h2>
<p>Somos una empresa especialista en servicios de transporte privado y movilidad ejecutiva con sede en la vibrante ciudad de Barcelona. Nacimos con un propósito claro: <strong>redefinir la experiencia de viajar por la ciudad y sus alrededores</strong>, ofreciendo un servicio integral donde la puntualidad, el máximo confort y la discreción son nuestra norma inquebrantable.</p>
<p>Ya sea que necesites un traslado seguro al Aeropuerto Josep Tarradellas Barcelona-El Prat, coordinación logística para congresos corporativos o un chófer privado a tu entera disposición por horas, nuestro equipo de profesionales está preparado para brindarte una solución completamente a medida.</p>

<hr style="margin: 2rem 0; border-top: 1px solid #e2e8f0;">

<h2>Nuestros Servicios y Rutas Principales</h2>
<p>Nuestra oferta de movilidad se adapta a cualquier necesidad, tanto para particulares como para empresas, cubriendo los puntos más estratégicos de Cataluña y más allá:</p>
<ul>
    <li><strong>Traslados al Aeropuerto y Puerto:</strong> Conexiones rápidas, seguras y sin esperas desde y hacia el Aeropuerto de Barcelona y la terminal de cruceros del Puerto de Barcelona.</li>
    <li><strong>Disposición por Horas y Eventos Corporativos:</strong> Un chófer privado a tu disposición el tiempo que necesites, ideal para reuniones de negocios, congresos o eventos especiales.</li>
    <li><strong>Rutas y Destinos Turísticos:</strong> Conectamos Barcelona con los principales atractivos de la región. Ofrecemos traslados directos a destinos mágicos como la <strong>Costa Brava, Salou, PortAventura y la histórica ciudad de Girona</strong>.</li>
    <li><strong>Traslados para Grupos:</strong> Flota de minivans adaptadas para garantizar el confort de familias o delegaciones enteras.</li>
</ul>

<hr style="margin: 2rem 0; border-top: 1px solid #e2e8f0;">

<h2>Nuestro Compromiso y Flota</h2>
<p>Entendemos que la primera impresión y la comodidad de cada trayecto son fundamentales. Por ello, operamos con una flota de vehículos de alta gama, cuidadosamente mantenida. Contamos con categorías <strong>Business, Economy y Minivans de lujo</strong>, adaptándonos perfectamente a las necesidades de familias, directivos y grupos.</p>

<p>Nuestros pilares operativos incluyen:</p>
<ul>
    <li><strong>Puntualidad absoluta:</strong> Monitorizamos el estado de los vuelos y el tráfico en tiempo real para garantizar que tu chófer te esté esperando en el momento exacto.</li>
    <li><strong>Discreción y profesionalidad:</strong> Nuestros conductores cuentan con amplia experiencia y garantizan la máxima confidencialidad durante tus desplazamientos.</li>
    <li><strong>Transparencia total:</strong> Operamos bajo presupuestos fijos para que viajes con la tranquilidad de no encontrar cargos ocultos ni sorpresas de última hora.</li>
</ul>

<p>En <strong>MeTransfers Barcelona</strong> no nos limitamos a llevarte a tu destino; nos aseguramos de que disfrutes de un servicio premium, fluido y sin interrupciones desde el primer contacto hasta tu llegada.</p>
';

        wp_update_post( [
            'ID'           => $page->ID,
            'post_content' => $content
        ] );

        update_option( 'mt_sobre_nosotros_updated_v1', true );
    }
}
add_action( 'init', 'mt_update_sobre_nosotros_page' );
