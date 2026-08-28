<?php
/**
 * Auto-create missing pages referenced in the footer.
 * 
 * This script runs once to populate the database with missing pages like "Sobre nosotros", "Aviso legal", etc.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mt_autocreate_missing_pages() {
    // Only run this once
    if ( get_option( 'mt_pages_created_v1' ) ) {
        return;
    }

    $pages_to_create = [
        [
            'post_title' => 'Sobre nosotros',
            'post_name'  => 'sobre-nosotros',
            'post_content' => '
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

<p>En <strong>MeTransfers Barcelona</strong> no nos limitamos a llevarte a tu destino; nos aseguramos de que disfrutes de un servicio premium, fluido y sin interrupciones desde el primer contacto hasta tu llegada.</p>',
            'post_type'  => 'page',
            'post_status' => 'publish'
        ],
        [
            'post_title' => 'Traslados privados',
            'post_name'  => 'traslados-privados',
            'post_content' => '<h2>Traslados Privados Premium en Barcelona</h2><p>Disfruta de un servicio de chófer privado adaptado a tus necesidades. Flota moderna, conductores profesionales y atención personalizada 24/7.</p>',
            'post_type'  => 'page',
            'post_status' => 'publish'
        ],
        [
            'post_title' => 'Preguntas frecuentes',
            'post_name'  => 'preguntas-frecuentes',
            'post_content' => '<h2>Preguntas Frecuentes (FAQ)</h2><p>Encuentra respuestas a las dudas más comunes sobre reservas, equipaje, cancelaciones y puntos de encuentro.</p>',
            'post_type'  => 'page',
            'post_status' => 'publish'
        ],
        [
            'post_title' => 'Política de privacidad',
            'post_name'  => 'politica-de-privacidad',
            'post_content' => '<p>Esta es la página de política de privacidad. Debes actualizarla con tu texto legal correspondiente.</p>',
            'post_type'  => 'page',
            'post_status' => 'publish'
        ],
        [
            'post_title' => 'Política de cookies',
            'post_name'  => 'politica-de-cookies',
            'post_content' => '<p>Esta es la página de política de cookies. Debes actualizarla con tu texto legal correspondiente.</p>',
            'post_type'  => 'page',
            'post_status' => 'publish'
        ],
        [
            'post_title' => 'Aviso legal',
            'post_name'  => 'aviso-legal',
            'post_content' => '<p>Este es el aviso legal. Debes actualizarlo con los datos fiscales de tu empresa.</p>',
            'post_type'  => 'page',
            'post_status' => 'publish'
        ],
        [
            'post_title' => 'Términos y condiciones',
            'post_name'  => 'terminos-y-condiciones',
            'post_content' => '<p>Estos son los términos y condiciones del servicio de Me Transfers.</p>',
            'post_type'  => 'page',
            'post_status' => 'publish'
        ],
        [
            'post_title' => 'Blog',
            'post_name'  => 'blog',
            'post_content' => '',
            'post_type'  => 'page',
            'post_status' => 'publish'
        ]
    ];

    foreach ( $pages_to_create as $page ) {
        // Use get_page_by_path to check if it exists
        $page_check = get_page_by_path( $page['post_name'] );
        if ( ! isset( $page_check->ID ) ) {
            wp_insert_post( $page );
        }
    }

    // Mark as completed so it doesn't run again
    update_option( 'mt_pages_created_v1', true );
}
add_action( 'init', 'mt_autocreate_missing_pages' );
