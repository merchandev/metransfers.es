<?php

function assert_admin_security( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "FAILED: $message\n" );
        exit( 1 );
    }
}

function get_current_user_id() {
    return 42;
}

function current_time( $type, $gmt = false ) {
    return '2026-08-19 20:00:00';
}

function wp_json_encode( $value ) {
    return json_encode( $value );
}

function get_post( $post_id ) {
    return (object) array(
        'post_type'   => 'hotel_partner',
        'post_author' => 7 === (int) $post_id ? 42 : 99,
    );
}

function get_userdata( $user_id ) {
    return (object) array( 'roles' => array( 'check_hoteles' ) );
}

class Admin_Audit_WPDB_Mock {
    public $prefix = 'wp_';
    public $last_insert = array();

    public function insert( $table, $data, $formats = array() ) {
        $this->last_insert = array( 'table' => $table, 'data' => $data, 'formats' => $formats );
        return 1;
    }
}

require_once __DIR__ . '/../app/Admin/Capabilities.php';
require_once __DIR__ . '/../app/Admin/AuditLog.php';

use MeTransfers\Admin\AuditLog;
use MeTransfers\Admin\Capabilities;

assert_admin_security( 'HOTE••••••••••••2345' === Capabilities::maskSecret( 'HOTEL-ABCDEFGHIJ12345' ), 'Tokens must be masked while retaining a short operator hint.' );
assert_admin_security( '••••' === Capabilities::maskSecret( 'abcd' ), 'Short secrets must be masked completely.' );
assert_admin_security(
    array( 'mt_manage_hotels' ) === Capabilities::restrictHotelOwnership( array( 'mt_manage_hotels' ), 'edit_post', 42, array( 7 ) ),
    'Hotel checkers must retain access to their own hotel.'
);
assert_admin_security(
    array( 'do_not_allow' ) === Capabilities::restrictHotelOwnership( array( 'mt_manage_hotels' ), 'edit_post', 42, array( 8 ) ),
    'Hotel checkers must be denied access to another owner hotel.'
);

$wpdb = new Admin_Audit_WPDB_Mock();
$GLOBALS['wpdb'] = $wpdb;
assert_admin_security(
    AuditLog::record(
        'Notification.Email_Resent',
        'Booking',
        123,
        array( 'channel' => 'email', 'status' => 'confirmed', 'email' => 'customer@example.test', 'api_key' => 'secret' )
    ),
    'Audit records must be persistable.'
);
$audit = $wpdb->last_insert['data'];
$context = json_decode( $audit['context_json'], true );
assert_admin_security( 42 === $audit['actor_user_id'] && 'notification.email_resent' === $audit['action_name'], 'Audit records must identify the actor and normalized action.' );
assert_admin_security( 'email' === $context['channel'] && '[redacted]' === $context['email'] && '[redacted]' === $context['api_key'], 'Audit context must retain operational facts and redact PII/secrets.' );

$root = dirname( __DIR__ );
$menu = file_get_contents( $root . '/app/Admin/Menu.php' );
$capabilities = file_get_contents( $root . '/app/Admin/Capabilities.php' );
$post_types = file_get_contents( $root . '/app/Core/PostTypes.php' );
$settings = file_get_contents( $root . '/app/Core/Settings.php' );
$notifications = file_get_contents( $root . '/app/Notifications/NotificationService.php' );
$admin = file_get_contents( $root . '/app/Legacy/WPTB/includes/class-wptb-admin.php' );
$hotel_admin = file_get_contents( $root . '/app/Legacy/Hotel/admin/class-hqp-admin.php' );
$schema = file_get_contents( $root . '/app/Core/Schema.php' );

foreach ( array( 'MANAGE_BOOKINGS', 'MANAGE_VEHICLES', 'MANAGE_HOTELS', 'VIEW_STATS', 'EXPORT_BOOKINGS', 'MANAGE_INTEGRATIONS', 'MANAGE_NOTIFICATIONS' ) as $constant ) {
    assert_admin_security( false !== strpos( $menu . $capabilities, $constant ), "Admin capability $constant is not wired." );
}
assert_admin_security( false === strpos( $menu, "'manage_options'" ), 'The integrated MeTransfers menu must use least-privilege capabilities.' );
assert_admin_security(
    false !== strpos( $capabilities, 'restrictHotelOwnership' )
        && false !== strpos( $capabilities, "array( 'do_not_allow' )" )
        && false !== strpos( $capabilities, 'self::MANAGE_VEHICLES' ),
    'Hotel checkers must be constrained to owned hotels and excluded from global fleet management.'
);
assert_admin_security( false !== strpos( $post_types, 'singleCapabilityMap' ), 'Destination and hotel CPT screens must honor the granular capabilities.' );
assert_admin_security(
    false !== strpos( $settings, "'whatsapp_api_key'" )
        && false !== strpos( $settings, 'PRIVATE_OPTIONS' )
        && false !== strpos( $settings, "autoload = 'no'" ),
    'WhatsApp and private option autoload controls must live behind Settings.'
);
assert_admin_security(
    false !== strpos( $notifications, "Settings::get( 'whatsapp_api_key'" )
        && false !== strpos( $notifications, "Settings::get( 'whatsapp_admin_phone'" ),
    'Notification delivery must not read WhatsApp secrets directly from wp_options.'
);
assert_admin_security(
    false !== strpos( $admin, 'public function resend_booking_whatsapp()' )
        && false !== strpos( $admin, 'NotificationService::sendWhatsapp' )
        && false !== strpos( $admin, 'NotificationService::sendEmails' ),
    'Email and WhatsApp manual resend actions must be explicit and separate.'
);
assert_admin_security(
    false !== strpos( $admin, 'EXPORT_BOOKINGS' )
        && false !== strpos( $admin, 'start->diff( $end )->days > 366' )
        && false === strpos( $admin, "'Token Hotel'        => 'string'" )
        && false !== strpos( $admin, 'Cache-Control: no-store' ),
    'Exports must require a dedicated capability, bounded date range, token minimization and private response headers.'
);
assert_admin_security(
    false !== strpos( $admin, 'type="password" name="wptb_whatsapp_apikey" value=""' )
        && false !== strpos( $admin, 'sanitize_whatsapp_api_key' ),
    'The WhatsApp API key must be write-only in the admin UI.'
);
assert_admin_security(
    false === strpos( $hotel_admin, 'name="hqp_token"' )
        && false !== strpos( $hotel_admin, 'random_bytes( 16 )' )
        && false !== strpos( $hotel_admin, 'Capabilities::maskSecret' )
        && false !== strpos( $hotel_admin, 'current_user_can( \'edit_post\', $post_id )' ),
    'Hotel tokens must be immutable, high entropy, masked and protected by object authorization.'
);
assert_admin_security(
    false !== strpos( $schema, 'mt_admin_audit' )
        && false !== strpos( $schema, 'action_created' )
        && false === strpos( file_get_contents( $root . '/app/Admin/AuditLog.php' ), "'customer_email'" ),
    'The append-only audit schema must exist without storing booking PII.'
);

echo "Admin security tests passed.\n";
