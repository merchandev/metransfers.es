<?php
/**
 * Migración one-shot: asocia los 17 usuarios Hotel con sus Hotel Partner
 * y configura al supervisor check@metransfers.es con acceso global.
 *
 * Solo ejecutable con WP-CLI:
 *
 *   wp eval-file tools/migrate-hotel-users.php -- --mode=dry-run
 *   wp eval-file tools/migrate-hotel-users.php -- --mode=apply
 *
 * Es idempotente. Re-ejecutar produce el mismo resultado.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 'Este script solo puede ejecutarse con WP-CLI.' . PHP_EOL );
}

use MeTransfers\Admin\Capabilities;
use MeTransfers\Admin\AuditLog;

// ── Determinar modo ───────────────────────────────────────────────────────────
$mode = 'dry-run';
foreach ( $args as $arg ) {
	if ( '--mode=apply' === $arg ) {
		$mode = 'apply';
		break;
	}
	if ( '--mode=dry-run' === $arg ) {
		$mode = 'dry-run';
		break;
	}
}

$is_apply = ( 'apply' === $mode );
WP_CLI::log( '' );
WP_CLI::log( '================================================================' );
WP_CLI::log( $is_apply ? '  APPLY — Migración Hotel Portal' : '  DRY-RUN — Migración Hotel Portal (sin modificar nada)' );
WP_CLI::log( '================================================================' );
WP_CLI::log( '' );

// ── Mapeo exacto 17 emails → títulos de Hotel Partner (literales) ─────────────
$hotel_assignments = array(
	'lespalmeres@metransfers.es'      => 'DWO Les Palmeres',
	'garbi@metransfers.es'            => 'Checkin Garbí',
	'catalonia@metransfers.es'        => 'Checkin Catalonia',
	'sirius@metransfers.es'           => 'DWO Sirius',
	'costabrava@metransfers.es'       => 'Bakour Costa Brava',
	'montserrat@metransfers.es'       => 'Checkin Montserrat',
	'flamingo@metransfers.es'         => 'Checkin Flamingo',
	'lespalmares@metransfers.es'      => 'DWO LES PALMARES',
	'maldasingular@metransfers.es'    => 'Maldà Singular Hotel',
	'galeon@metransfers.es'           => 'Hotel Galeón',
	'andantedrassanes@metransfers.es' => 'Hotel Andante Drassanes',
	'tahiti@metransfers.es'           => 'Hotel Tahití',
	'fenalsgarden@metransfers.es'     => 'Hotel Fenals Garden',
	'urquinaonaplaza@metransfers.es'  => 'Hotel H10 Urquinaona Plaza',
	'alenti@metransfers.es'           => 'Hotel alenti',
	'dantebarcelona@metransfers.es'   => 'Hotel Dante Barcelona',
	'casanova@metransfers.es'         => 'H10 CASANOVA',
);

$supervisor_email = 'check@metransfers.es';

// Caps elevadas que los 17 hoteles normales NO deben tener
$elevated_caps = array(
	Capabilities::HOTEL_ACCESS_ALL,
	Capabilities::HOTEL_MANAGE_USERS,
	Capabilities::HOTEL_IMPORT_BOOKINGS,
);

// ── Función helper: busca hotel_partner por título EXACTO ─────────────────────
function mt_find_hotel_by_exact_title( string $title ): int {
	global $wpdb;
	$id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
             WHERE post_type = 'hotel_partner'
               AND post_status = 'publish'
               AND post_title = %s
             LIMIT 2",
			$title
		)
	);
	// Comprobar si hay más de 1 resultado
	$count = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_type = 'hotel_partner'
               AND post_status = 'publish'
               AND post_title = %s",
			$title
		)
	);
	if ( $count > 1 ) {
		return -1; // señal de ambigüedad
	}
	return $id;
}

// ════════════════════════════════════════════════════════════════════════════════
// FASE A — PRECHECK (valida todo antes de escribir absolutamente nada)
// ════════════════════════════════════════════════════════════════════════════════
WP_CLI::log( '[ FASE A — PRECHECK ]' );
WP_CLI::log( '' );

$resolved    = array();   // email => [ 'user_id' => int, 'hotel_id' => int, 'hotel_title' => string ]
$errors      = array();
$seen_hotels = array();

foreach ( $hotel_assignments as $email => $hotel_title ) {

	// 1. Usuario existe
	$user = get_user_by( 'email', $email );
	if ( ! $user ) {
		$errors[] = "[ERROR] Usuario no encontrado: {$email}";
		continue;
	}
	$user_id = (int) $user->ID;

	// 2. NO tiene manage_options (sería peligroso concederle acceso global por este script)
	if ( user_can( $user_id, 'manage_options' ) ) {
		$errors[] = "[ERROR] {$email} tiene manage_options — no puede ser una cuenta Hotel normal. Migración detenida.";
		continue;
	}

	// 3. Hotel existe con título EXACTO (exactamente 1 resultado)
	$hotel_id = mt_find_hotel_by_exact_title( $hotel_title );
	if ( $hotel_id === 0 ) {
		$errors[] = "[ERROR] Hotel no encontrado: '{$hotel_title}' (email: {$email})";
		continue;
	}
	if ( $hotel_id === -1 ) {
		$errors[] = "[ERROR] Título ambiguo (más de 1 resultado): '{$hotel_title}' (email: {$email})";
		continue;
	}

	// 4. No hay hotel duplicado en el mapping
	if ( isset( $seen_hotels[ $hotel_id ] ) ) {
		$errors[] = "[ERROR] Hotel #{$hotel_id} '{$hotel_title}' ya está asignado a {$seen_hotels[$hotel_id]}. Mapping duplicado.";
		continue;
	}
	$seen_hotels[ $hotel_id ] = $email;

	$resolved[ $email ] = array(
		'user_id'     => $user_id,
		'hotel_id'    => $hotel_id,
		'hotel_title' => $hotel_title,
		'user_login'  => $user->user_login,
	);

	WP_CLI::log( "[OK] {$email} → #{$hotel_id} '{$hotel_title}'" );
}

// Supervisor
WP_CLI::log( '' );
$supervisor = get_user_by( 'email', $supervisor_email );
if ( ! $supervisor ) {
	$errors[] = "[ERROR] Supervisor no encontrado: {$supervisor_email}";
} else {
	$sup_id = (int) $supervisor->ID;
	WP_CLI::log( "[OK] Supervisor: {$supervisor_email} (#{$sup_id})" );
	if ( ! user_can( $sup_id, Capabilities::HOTEL_PORTAL_ACCESS ) ) {
		WP_CLI::log( "[AVISO] {$supervisor_email} no tiene HOTEL_PORTAL_ACCESS. Se concederá en APPLY." );
	}
}

WP_CLI::log( '' );

// Resultado del precheck
$resolved_count = count( $resolved );
$expected_count = count( $hotel_assignments );

WP_CLI::log( '── Resultado PRECHECK ──────────────────────────────────────────' );
WP_CLI::log( "{$resolved_count}/{$expected_count} usuarios encontrados" );
WP_CLI::log( count( $seen_hotels ) . "/{$expected_count} hoteles encontrados" );
WP_CLI::log( ( $supervisor ? '1' : '0' ) . '/1 supervisor encontrado' );

if ( ! empty( $errors ) ) {
	WP_CLI::log( '' );
	foreach ( $errors as $err ) {
		WP_CLI::warning( $err );
	}
	WP_CLI::log( '' );
	WP_CLI::error( "PRECHECK FALLIDO — {$resolved_count}/{$expected_count} resueltos. Corrige los errores antes de continuar. No se modificó nada." );
	return;
}

if ( $resolved_count !== $expected_count || ! $supervisor ) {
	WP_CLI::error( 'PRECHECK FALLIDO — No se modificó nada.' );
	return;
}

WP_CLI::log( '0 conflictos' );
WP_CLI::log( '' );

if ( ! $is_apply ) {
	WP_CLI::success( "DRY RUN completado — No se modificó nada.\nEjecuta con --mode=apply para aplicar la migración." );
	return;
}

// ════════════════════════════════════════════════════════════════════════════════
// FASE B — APPLY
// ════════════════════════════════════════════════════════════════════════════════
WP_CLI::log( '[ FASE B — APPLY ]' );
WP_CLI::log( '' );

$migrated = 0;

foreach ( $resolved as $email => $data ) {
	$user_id  = $data['user_id'];
	$hotel_id = $data['hotel_id'];
	$user_obj = get_userdata( $user_id );

	// Asociar hotel: REEMPLAZA (no fusiona) — 1 usuario = exactamente 1 hotel
	update_user_meta( $user_id, 'mt_hotel_ids', array( $hotel_id ) );
	update_user_meta( $user_id, 'mt_primary_hotel_id', $hotel_id );
	update_user_meta( $user_id, 'mt_active_hotel_id', $hotel_id );

	// Retirar caps elevadas a nivel de usuario (no solo del rol)
	foreach ( $elevated_caps as $cap ) {
		$user_obj->remove_cap( $cap );
	}

	AuditLog::record(
		'hotel.portal.migration.user_mapped',
		'user',
		$user_id,
		array(
			'hotel_id' => $hotel_id,
			'email'    => $email,
		)
	);

	WP_CLI::log( "[MIGRATED] {$email} → #{$hotel_id} '{$data['hotel_title']}'" );
	++$migrated;
}

// ── Supervisor (bloque completamente separado) ────────────────────────────────
WP_CLI::log( '' );
$sup_obj = get_userdata( $sup_id );

// Conceder caps elevadas al supervisor
$sup_obj->add_cap( Capabilities::HOTEL_ACCESS_ALL );
$sup_obj->add_cap( Capabilities::HOTEL_MANAGE_USERS );
$sup_obj->add_cap( Capabilities::HOTEL_IMPORT_BOOKINGS );

// Asegurar acceso al portal
if ( ! user_can( $sup_id, Capabilities::HOTEL_PORTAL_ACCESS ) ) {
	$sup_obj->add_cap( Capabilities::HOTEL_PORTAL_ACCESS );
	WP_CLI::log( "[SUPERVISOR] Concedido HOTEL_PORTAL_ACCESS a {$supervisor_email}" );
}

// El supervisor NO recibe mt_hotel_ids — su acceso viene de HOTEL_ACCESS_ALL
AuditLog::record(
	'hotel.portal.migration.supervisor_configured',
	'user',
	$sup_id,
	array( 'email' => $supervisor_email )
);

WP_CLI::log( "[SUPERVISOR] {$supervisor_email} → HOTEL_ACCESS_ALL + HOTEL_MANAGE_USERS + HOTEL_IMPORT_BOOKINGS" );

// ── Resumen final ─────────────────────────────────────────────────────────────
WP_CLI::log( '' );
WP_CLI::log( '================================================================' );
WP_CLI::success( "{$migrated}/{$expected_count} usuarios migrados correctamente. Supervisor configurado. Migración completada." );
