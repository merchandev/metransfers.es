<?php
/**
 * Newsletter logic without WordPress: settings, consent and double opt-in,
 * HMAC links, visited pages, email rendering and headers, sending pace and
 * imports. The SQL itself runs on MariaDB in tests/Integration/wordpress-smoke.php.
 */

define( 'ARRAY_A', 'ARRAY_A' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );

$GLOBALS['mt_options'] = array();
$GLOBALS['mt_mails']   = array();
$GLOBALS['mt_mail_ok'] = true;

function get_option( $name, $default = false ) { return $GLOBALS['mt_options'][ $name ] ?? $default; }
function sanitize_text_field( $value ) { return trim( (string) preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $value ) ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_email( $value ) { return (string) preg_replace( '/[^a-zA-Z0-9@._+\-]/', '', (string) $value ); }
function is_email( $value ) { return false !== filter_var( $value, FILTER_VALIDATE_EMAIL ); }
function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return (string) preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function wp_unslash( $value ) { return $value; }
function wp_json_encode( $value ) { return json_encode( $value ); }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function home_url( $path = '' ) { return 'https://metransfers.test' . $path; }
function admin_url( $path = '' ) { return 'https://metransfers.test/wp-admin/' . $path; }
function esc_url_raw( $url ) { return (string) $url; }
function esc_url( $url ) { return str_replace( '&', '&#038;', (string) $url ); }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function wpautop( $text ) { return '<p>' . $text . '</p>'; }
function wp_strip_all_tags( $text ) { return strip_tags( preg_replace( '#<(script|style)[^>]*>.*?</\1>#is', '', (string) $text ) ); }
function wp_salt( $scheme ) { return 'unit-salt-' . $scheme; }
function remove_accents( $text ) { return strtr( $text, array( 'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n' ) ); }
function add_query_arg( array $args, $url ) { return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . http_build_query( $args ); }
function get_theme_mod( $name ) { return ''; }
function get_template_directory_uri() { return 'https://metransfers.test/wp-content/themes/metransfers'; }
function get_transient( $key ) { return $GLOBALS['mt_transients'][ $key ]['v'] ?? false; }
function set_transient( $key, $value, $ttl ) { $GLOBALS['mt_transients'][ $key ] = array( 'v' => $value, 'ttl' => $ttl ); return true; }
function add_action( $hook, $callback, $priority = 10 ) { $GLOBALS['mt_hooks'][ $hook ][ $priority ][] = $callback; }
function remove_action( $hook, $callback, $priority = 10 ) { $GLOBALS['mt_hooks'][ $hook ][ $priority ] = array_filter( $GLOBALS['mt_hooks'][ $hook ][ $priority ] ?? array(), static fn( $registered ) => $registered !== $callback ); }
class Fake_Newsletter_Mailer {
	public $Mailer = 'mail', $Host = '', $Port = 25, $SMTPAuth = false, $Username = '', $Password = '', $SMTPSecure = '', $SMTPAutoTLS = false, $SMTPOptions = array( 'ssl' => array( 'verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true ) ), $SMTPDebug = 2, $SMTPKeepAlive = true, $Sender = 'previous@example.com', $From = 'previous@example.com', $FromName = 'Previous', $AltBody = 'Previous body', $DKIM_extraHeaders = array( 'X-Custom' );
	public function isSMTP() { $this->Mailer = 'smtp'; }
}
function wp_mail( $to, $subject, $message, $headers ) {
	if ( isset( $GLOBALS['mt_transport'] ) ) {
		$hooks = $GLOBALS['mt_hooks']['phpmailer_init'] ?? array();
		ksort( $hooks );
		foreach ( $hooks as $callbacks ) {
			foreach ( $callbacks as $callback ) { $callback( $GLOBALS['mt_transport'] ); }
		}
		$GLOBALS['mt_sent_transport'] = clone $GLOBALS['mt_transport'];
	}
	if ( ! empty( $GLOBALS['mt_mail_throw'] ) ) { throw new RuntimeException( 'Simulated transport exception' ); }
	$GLOBALS['mt_mails'][] = compact( 'to', 'subject', 'message', 'headers' );
	return $GLOBALS['mt_mail_ok'];
}

/**
 * Stand-in for the subscribers table, following the statements Subscribers sends.
 */
class Fake_Newsletter_Db {
	public $prefix    = 'wp_';
	public $insert_id = 0;
	public $rows      = array();
	public $queries   = array();

	public function prepare( $sql, ...$args ) {
		return array( 'sql' => $sql, 'args' => isset( $args[0] ) && is_array( $args[0] ) ? $args[0] : $args );
	}

	public function get_row( $query, $output ) {
		foreach ( $this->rows as $row ) {
			if ( ( false !== strpos( $query['sql'], 'id = %d' ) && (int) $row['id'] === (int) $query['args'][1] )
				|| ( false !== strpos( $query['sql'], 'email = %s' ) && $row['email'] === $query['args'][1] ) ) {
				return $row;
			}
		}
		return null;
	}

	public function insert( $table, array $data ) {
		foreach ( $this->rows as $row ) {
			if ( $row['email'] === $data['email'] ) {
				return false;
			}
		}
		$this->insert_id = count( $this->rows ) + 1;
		$this->rows[ $this->insert_id ] = $data + array(
			'id'                   => $this->insert_id,
			'confirmation_sent_at' => null,
			'unsubscribed_at'      => null,
			'last_sent_at'         => null,
			'sends'                => 0,
			'signup_page'          => '',
			'referrer'             => '',
		);
		$this->rows[ $this->insert_id ]['id'] = $this->insert_id;
		return 1;
	}

	public function update( $table, array $data, array $where ) {
		$changed = 0;
		foreach ( $this->rows as $id => $row ) {
			if ( array_intersect_assoc( array_map( 'strval', $where ), array_map( 'strval', $row ) ) == array_map( 'strval', $where ) ) {
				$this->rows[ $id ] = array_merge( $row, $data );
				++$changed;
			}
		}
		return $changed;
	}

	public function query( $query ) {
		$this->queries[] = $query;
		if ( 0 === strpos( $query['sql'], 'UPDATE %i SET status = %s' ) ) {
			$status = $query['args'][1];
			foreach ( array_slice( $query['args'], 7 ) as $id ) {
				if ( isset( $this->rows[ $id ] ) ) {
					$this->rows[ $id ]['status'] = $status;
					if ( 'unsubscribed' === $status ) {
						$this->rows[ $id ]['unsubscribed_at'] = gmdate( 'Y-m-d H:i:s' );
					}
				}
			}
		}
		return 1;
	}
}

require_once __DIR__ . '/../app/Core/Settings.php';
require_once __DIR__ . '/../app/Notifications/NotificationService.php';
foreach ( array( 'Brand', 'Settings', 'Subscribers', 'Mailer', 'Campaigns', 'Importer', 'PublicController' ) as $class ) {
	require_once __DIR__ . '/../app/Newsletter/' . $class . '.php';
}

use MeTransfers\Newsletter\Brand;
use MeTransfers\Newsletter\Campaigns;
use MeTransfers\Newsletter\Importer;
use MeTransfers\Newsletter\Mailer;
use MeTransfers\Newsletter\PublicController;
use MeTransfers\Newsletter\Settings;
use MeTransfers\Newsletter\Subscribers;

function assert_nl( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAILED: $message\n" );
		exit( 1 );
	}
}

$GLOBALS['wpdb'] = new Fake_Newsletter_Db();
$db              = $GLOBALS['wpdb'];

// 1. Settings: defaults, per-language overrides and limits.
assert_nl( true === Settings::get( 'double_opt_in' ) && 10 === Settings::get( 'delay' ), 'Double opt-in and the 10-second delay must be the defaults.' );
$clean = Settings::sanitize(
	array(
		'enabled'        => '1',
		'delay'          => '1',
		'rate_per_hour'  => '999999',
		'from_email'     => 'not-an-email',
		'excluded_paths' => "contacto/\n\n /gracias/ ",
		'texts'          => array(
			'es' => array(
				'title'  => 'Descuentos para ti',
				'button' => Settings::TEXTS['es']['button'],
			),
		),
	)
);
assert_nl( 3 === $clean['delay'] && 5000 === $clean['rate_per_hour'] && '' === $clean['from_email'] && true === $clean['double_opt_in'], 'Settings must be clamped, an invalid sender dropped and double opt-in enforced.' );
assert_nl( "/contacto/\n/gracias/" === $clean['excluded_paths'], 'Excluded paths must be normalised to /path/.' );
assert_nl( array( 'es' => array( 'title' => 'Descuentos para ti' ) ) === $clean['texts'], 'Only texts that differ from the default must be stored.' );
$GLOBALS['mt_options'][ Settings::OPTION ] = $clean;
assert_nl( 'Descuentos para ti' === Settings::text( 'title', 'es' ) && Settings::TEXTS['en']['title'] === Settings::text( 'title', 'en' ), 'Custom texts must apply per language, falling back to the default.' );
$GLOBALS['mt_options'][ Settings::OPTION ] = array();
$GLOBALS['mt_options'][ Settings::OPTION ] = array( 'double_opt_in' => false );
assert_nl( true === Settings::get( 'double_opt_in' ), 'Previously saved settings cannot bypass public ownership verification.' );
$GLOBALS['mt_options'][ Settings::OPTION ] = array();

// 2. Visited pages: only this site, clipped, the last 30.
assert_nl( '/traslados/?utm_source=x' === Subscribers::path( 'https://metransfers.test/traslados/?utm_source=x' ) && '' === Subscribers::path( 'https://evil.example/x' ) && '/en/fleet/' === Subscribers::path( '/en/fleet/' ), 'Only paths of this site may be stored.' );
$many  = array();
for ( $i = 0; $i < 40; $i++ ) {
	$many[] = array( 'u' => '/p' . $i . '/', 't' => str_repeat( 'T', 200 ), 'at' => '2026-10-16T10:00:00Z' );
}
$many[] = array( 'u' => 'https://evil.example/', 't' => 'x', 'at' => 'not a date' );
$pages  = Subscribers::sanitizePages( json_encode( $many ) );
assert_nl( 29 === count( $pages ) && '/p11/' === $pages[0]['u'] && 150 === mb_strlen( $pages[0]['t'] ) && '2026-10-16 10:00:00' === $pages[0]['at'], 'Pages must be capped to the last 30, titles clipped and dates normalised; other domains dropped.' );

// 3. Popup signup: double opt-in, no duplicate confirmations, no status leak.
$first = Subscribers::subscribe( array( 'email' => ' Ana@Example.COM ', 'name' => 'Ana <b>G</b>', 'language' => 'es', 'signup_page' => 'https://metransfers.test/flota/', 'pages' => '[{"u":"/","t":"Inicio","at":"2026-10-16T10:00:00Z"}]', 'consent_text' => 'Acepto' ) );
assert_nl( 'pending' === $first['result'] && $first['send_confirmation'] && 'ana@example.com' === $first['subscriber']['email'] && 'Ana G' === $first['subscriber']['name'], 'A new signup must be pending, normalised and confirmed by email.' );
Subscribers::markConfirmationSent( 1 );
$repeat = Subscribers::subscribe( array( 'email' => 'ana@example.com', 'pages' => '[{"u":"/flota/","t":"Flota","at":"2026-10-16T10:05:00Z"}]' ) );
assert_nl( 'pending' === $repeat['result'] && ! $repeat['send_confirmation'] && 2 === count( Subscribers::pages( $repeat['subscriber'] ) ), 'A repeated signup must not resend the confirmation within 10 minutes, but keep the new pages.' );
assert_nl( 'invalid' === Subscribers::subscribe( array( 'email' => 'no-email' ) )['result'], 'Invalid addresses must be rejected.' );

// 4. Links: HMAC per subscriber and purpose; an old confirmation never re-subscribes.
$ana = Subscribers::find( 1 );
assert_nl( Subscribers::verify( 'confirm', $ana, Subscribers::token( 'confirm', $ana ) ) && ! Subscribers::verify( 'unsubscribe', $ana, Subscribers::token( 'confirm', $ana ) ), 'A token must only work for its purpose.' );
assert_nl( ! Subscribers::verify( 'confirm', array( 'id' => 2 ) + $ana, Subscribers::token( 'confirm', $ana ) ) && ! Subscribers::verify( 'confirm', $ana, 'short' ), 'A token must only work for its own subscriber.' );
assert_nl( false !== strpos( Subscribers::unsubscribeUrl( $ana ), 'admin-post.php?action=mt_nl_unsubscribe&s=1&t=' ), 'Links must go through admin-post.php, which page caches skip.' );
assert_nl( 'confirmed' === Subscribers::confirm( 1, Subscribers::token( 'confirm', $ana ) ) && 'already' === Subscribers::confirm( 1, Subscribers::token( 'confirm', $ana ) ), 'Confirming must subscribe once and be idempotent.' );
assert_nl( 'unsubscribed' === Subscribers::unsubscribe( 1, Subscribers::token( 'unsubscribe', $ana ) ) && 'already' === Subscribers::unsubscribe( 1, Subscribers::token( 'unsubscribe', $ana ) ), 'Unsubscribing must work once and be idempotent.' );
assert_nl( 'invalid' === Subscribers::confirm( 1, Subscribers::token( 'confirm', $ana ) ), 'An old confirmation link must not re-subscribe someone who left.' );
$back = Subscribers::subscribe( array( 'email' => 'ana@example.com', 'consent_text' => 'Acepto otra vez' ) );
assert_nl( 'pending' === $back['result'] && $back['send_confirmation'] && 'Acepto otra vez' === $back['subscriber']['consent_text'] && null === $back['subscriber']['unsubscribed_at'], 'Signing up again on the website must start a fresh double opt-in.' );

// 5. Admin and import: never re-subscribe someone who left.
Subscribers::setStatus( array( 1 ), 'unsubscribed' );
assert_nl( 'blocked' === Subscribers::upsert( 'ana@example.com', 'Ana', 'es', 'subscribed', 'import', 'x' ), 'An import must not re-subscribe an unsubscribed address.' );
assert_nl( 'added' === Subscribers::upsert( 'bob@example.com', 'Bob', 'en', 'pending', 'import', 'x' ) && 'updated' === Subscribers::upsert( 'bob@example.com', 'Bob', 'en', 'subscribed', 'import', 'x' ) && 'subscribed' === Subscribers::findByEmail( 'bob@example.com' )['status'], 'An import as subscribed must confirm a pending contact.' );
assert_nl( 'added' === Subscribers::upsert( 'eve@example.com', 'Eve', 'es', 'unsubscribed', 'admin', 'x' ) && 'pending' === Subscribers::findByEmail( 'eve@example.com' )['status'], 'Admin and imports may only create pending or subscribed contacts.' );

// 6. Email: escaped personalisation, unsubscribe footer and headers.
$bob      = Subscribers::findByEmail( 'bob@example.com' );
$bob_evil = array( 'name' => '<script>alert(1)</script>' ) + $bob;
$rendered = Mailer::renderCampaign( array( 'subject' => "Hola {{nombre}}\r\nBcc: spam@example.com", 'preheader' => 'Vista previa', 'body' => 'Oferta para {{nombre}} <a href="{{baja}}">baja</a> <img src="https://metransfers.test/a.jpg">' ), $bob_evil );
assert_nl( false === strpos( $rendered['html'], '<script>alert' ) && false !== strpos( $rendered['html'], '&lt;script&gt;' ), 'Subscriber values must be escaped in the email.' );
assert_nl( false === strpos( $rendered['subject'], "\n" ) && false === strpos( $rendered['subject'], "\r" ), 'A subject must stay one header line.' );
assert_nl( false !== strpos( $rendered['html'], esc_url( Subscribers::unsubscribeUrl( $bob_evil ) ) ) && false !== strpos( $rendered['html'], 'Vista previa' ) && false !== strpos( $rendered['html'], '<img style="max-width:100%;height:auto;' ), 'The email must carry the unsubscribe link, the preheader and responsive images.' );
assert_nl( substr_count( $rendered['html'], Settings::LABELS['en']['unsubscribe'] ) >= 1, 'The footer must be in the subscriber language.' );
assert_nl( false !== strpos( $rendered['html'], 'background:' . Brand::NAVY ) && false !== strpos( $rendered['html'], '>MeTransfers</p>' ) && false !== strpos( $rendered['html'], 'background:' . Brand::NAVY_DEEP ) && false !== strpos( $rendered['html'], Brand::AMBER ) && false === strpos( $rendered['html'], 'fonts.googleapis.com' ), 'The email must retain the brand using readable text, without a remote logo or font dependency.' );
assert_nl( '<a href="' . esc_url( Subscribers::unsubscribeUrl( $bob ) ) . '">x</a>' === Mailer::personalize( '<a href="%7B%7Bbaja%7D%7D">x</a>', $bob ), 'A tag the visual editor URL-encoded inside a link must still be replaced.' );
$GLOBALS['mt_mails'] = array();
assert_nl( ! Mailer::send( 'bob@example.com', 'Hi', '<p>Text</p>', '' ) && false !== strpos( Mailer::lastError(), 'newsletter_missing_smtp_host' ) && ! $GLOBALS['mt_mails'], 'Missing SMTP must stop newsletter mail instead of falling back to PHP mail.' );
$smtp_fixture = array( 'wptb_smtp_host' => 'smtp.example.test', 'wptb_smtp_user' => 'newsletter@metransfers.test', 'wptb_smtp_password' => 'test-only-password', 'wptb_smtp_from' => 'newsletter@metransfers.test', 'wptb_smtp_encryption' => 'tls', 'wptb_smtp_port' => 587 );
$GLOBALS['mt_options'] = array_merge( $GLOBALS['mt_options'], $smtp_fixture );
$GLOBALS['mt_transport'] = new Fake_Newsletter_Mailer();
$previous_transport = clone $GLOBALS['mt_transport'];
assert_nl( Mailer::sendCampaign( array( 'subject' => 'Hi', 'body' => 'Text' ), $bob ), 'A campaign email must be sent.' );
$transport = $GLOBALS['mt_sent_transport'];
assert_nl( 'smtp' === $transport->Mailer && $transport->SMTPAuth && 'tls' === $transport->SMTPSecure && 587 === $transport->Port && $transport->SMTPAutoTLS && 0 === $transport->SMTPDebug && ! $transport->SMTPKeepAlive, 'Newsletter must use authenticated encrypted SMTP without debug output or connection reuse.' );
assert_nl( true === $transport->SMTPOptions['ssl']['verify_peer'] && true === $transport->SMTPOptions['ssl']['verify_peer_name'] && false === $transport->SMTPOptions['ssl']['allow_self_signed'], 'TLS certificate and peer verification must override insecure mailer defaults.' );
assert_nl( $transport->Sender === $transport->From && false !== strpos( $transport->AltBody, 'Text' ) && false !== strpos( $transport->AltBody, Subscribers::unsubscribeUrl( $bob ) ) && in_array( 'List-Unsubscribe-Post', $transport->DKIM_extraHeaders, true ) && in_array( 'X-Custom', $transport->DKIM_extraHeaders, true ), 'Envelope identity, readable text and DKIM unsubscribe coverage must be set while preserving existing DKIM headers.' );
assert_nl( $previous_transport == $GLOBALS['mt_transport'], 'The shared mailer must be restored so other site emails are unaffected.' );
$headers = implode( "\n", $GLOBALS['mt_mails'][0]['headers'] );
assert_nl( false !== strpos( $headers, 'List-Unsubscribe: <https://metransfers.test/wp-admin/admin-post.php?action=mt_nl_unsubscribe' ) && false !== strpos( $headers, 'List-Unsubscribe-Post: List-Unsubscribe=One-Click' ) && false !== strpos( $headers, 'Content-Type: text/html; charset=UTF-8' ), 'Campaigns must carry one-click unsubscribe headers (Gmail and Yahoo bulk sender rules).' );
$GLOBALS['mt_mails'] = array();
Mailer::sendConfirmation( Subscribers::findByEmail( 'eve@example.com' ) );
assert_nl( false !== strpos( $GLOBALS['mt_mails'][0]['message'], '<h1 style="margin:0 0 16px;font-family:&#039;Outfit&#039;' ) && false !== strpos( $GLOBALS['mt_mails'][0]['message'], 'background:' . Brand::NAVY . ';color:#ffffff;padding:15px 30px;border-radius:16px' ), 'The confirmation email must use the website headings and the navy booking button.' );
assert_nl( false === strpos( implode( "\n", $GLOBALS['mt_mails'][0]['headers'] ), 'List-Unsubscribe' ) && false !== strpos( $GLOBALS['mt_mails'][0]['message'], 'action=mt_nl_confirm' ) && 'Confirma tu suscripción a MeTransfers' === $GLOBALS['mt_mails'][0]['subject'], 'The confirmation email must carry the confirm link and no marketing headers.' );
$GLOBALS['mt_mail_ok'] = false;
assert_nl( ! Mailer::send( 'x@example.com', 'S', '<p>H</p>', '' ) && 'wp_mail_failed' === Mailer::lastError(), 'A failed send must report a reason.' );
$GLOBALS['mt_mail_ok'] = true;
assert_nl( $previous_transport == $GLOBALS['mt_transport'], 'Transport state must also be restored after a failed send.' );
$GLOBALS['mt_mail_throw'] = true;
try {
	Mailer::send( 'bob@example.com', 'S', '<p>H</p>', '' );
	assert_nl( false, 'The transport exception must propagate.' );
} catch ( RuntimeException $exception ) {
	assert_nl( 'Simulated transport exception' === $exception->getMessage(), 'Unexpected exception.' );
} finally {
	$GLOBALS['mt_mail_throw'] = false;
}
assert_nl( $previous_transport == $GLOBALS['mt_transport'], 'Transport state must also be restored after an exception.' );
$before = count( $GLOBALS['mt_mails'] );
foreach ( array( "bob@example.com\r\nBcc: eve@example.com", 'bob@example.com,eve@example.com', 'invalid' ) as $recipient ) {
	assert_nl( ! Mailer::send( $recipient, 'S', '<p>H</p>', '' ) && 'invalid_recipient' === Mailer::lastError(), 'Multiple recipients and injected recipient headers must be rejected.' );
}
assert_nl( ! Mailer::send( 'bob@example.com', 'S', '<p>H</p>', 'https://evil.example/unsubscribe' ) && 'invalid_unsubscribe_url' === Mailer::lastError(), 'One-click unsubscribe must stay on the HTTPS site.' );
$GLOBALS['mt_options']['wptb_smtp_encryption'] = 'none';
assert_nl( ! Mailer::send( 'bob@example.com', 'S', '<p>H</p>', '' ) && 'newsletter_smtp_requires_tls' === Mailer::lastError(), 'Unencrypted SMTP must be blocked.' );
$GLOBALS['mt_options']['wptb_smtp_encryption'] = 'tls';
assert_nl( $before === count( $GLOBALS['mt_mails'] ), 'Rejected messages must never reach wp_mail.' );
$pending = Subscribers::findByEmail( 'eve@example.com' );
assert_nl( Mailer::sendCampaign( array( 'subject' => 'PROMOTION', 'body' => 'PROMOTION' ), $pending ), 'A pending campaign recipient must receive a confirmation request.' );
$pending_mail = end( $GLOBALS['mt_mails'] );
assert_nl( false === strpos( $pending_mail['message'], 'PROMOTION' ) && false !== strpos( $pending_mail['message'], 'action=mt_nl_confirm' ) && false === strpos( implode( "\n", $pending_mail['headers'] ), 'List-Unsubscribe' ), 'Promotional campaign content must never reach pending contacts.' );
assert_nl( ! Mailer::sendCampaign( array( 'subject' => 'S', 'body' => 'H' ), Subscribers::findByEmail( 'eve@example.com' ) ) && 'confirmation_recent' === Mailer::lastError(), 'Pending campaign confirmations must respect the resend cooldown.' );
$mail_count = count( $GLOBALS['mt_mails'] );
assert_nl( PublicController::sendConfirmation( Subscribers::findByEmail( 'eve@example.com' ) ) && PublicController::handleEvent( 'newsletter.confirm', (int) $pending['id'] ) && $mail_count === count( $GLOBALS['mt_mails'] ), 'Admin resends and queued retries must not duplicate a recently delivered confirmation.' );
assert_nl( ! Mailer::sendCampaign( array( 'subject' => 'S', 'body' => 'H' ), array( 'status' => 'bounced' ) ) && 'subscriber_not_confirmed' === Mailer::lastError(), 'Suppressed subscribers must be blocked at the mailer too.' );
assert_nl( ! Mailer::sendConfirmation( array( 'status' => 'bounced' ) ) && 'subscriber_not_pending' === Mailer::lastError(), 'Confirmation mail must also respect suppressed states.' );
assert_nl( Mailer::isPermanentRecipientFailure( '550 5.1.1 User unknown' ) && ! Mailer::isPermanentRecipientFailure( '550 5.7.1 Authentication required' ) && ! Mailer::isPermanentRecipientFailure( '451 4.1.1 Temporary failure' ), 'Only explicit nonexistent-mailbox errors suppress the address; authentication and transient errors do not.' );
Subscribers::setStatus( array( (int) $pending['id'] ), 'bounced' );
$bounced_signup = Subscribers::subscribe( array( 'email' => $pending['email'] ) );
assert_nl( 'bounced' === $bounced_signup['subscriber']['status'] && ! $bounced_signup['send_confirmation'], 'Public signup cannot reactivate a bounced address.' );
// Restore this fixture to pending for the isolated public-link probes.
Subscribers::setStatus( array( (int) $pending['id'] ), 'pending' );

// 7. Sending pace and audiences.
assert_nl( 25 === Campaigns::batchSize( 300, 0 ) && 10 === Campaigns::batchSize( 300, 290 ) && 0 === Campaigns::batchSize( 300, 300 ) && 100 === Campaigns::batchSize( 5000, 0 ), 'Batches must follow the hourly limit: a twelfth per run, never above what the hour left or 100.' );
assert_nl( array( 'subscribed' ) === Campaigns::audienceStatuses( 'subscribed,unsubscribed,bounced' ) && array( 'subscribed' ) === Campaigns::audienceStatuses( 'bogus' ) && array( 'subscribed', 'pending' ) === Campaigns::audienceStatuses( 'subscribed,pending' ), 'A campaign can never target unsubscribed or bounced addresses.' );

// 8. Import: headers, encodings, languages and the report.
$columns = Importer::columns( array( Importer::cleanRow( array( "\xEF\xBB\xBFCorreo Electrónico", 'NOMBRE', 'Idioma' ) ) ) );
assert_nl( $columns['header'] && 0 === $columns['email'] && 1 === $columns['name'] && 2 === $columns['language'], 'Headers must be recognised despite BOM, case and accents.' );
$headless = Importer::columns( array( array( 'Luis', 'luis@example.com' ), array( 'Marta', 'marta@example.com' ) ) );
assert_nl( ! $headless['header'] && 1 === $headless['email'], 'Without headers the column with @ must be the email.' );
assert_nl( 'María' === Importer::cleanRow( array( "Mar\xEDa" ) )[0], 'Windows-1252 CSV cells (Excel on Windows) must become UTF-8.' );
assert_nl( 'en' === Importer::language( 'Inglés', 'es' ) && 'es' === Importer::language( 'Español', 'en' ) && 'en' === Importer::language( '', 'en' ), 'Languages must be recognised in Spanish and English.' );
$report = Importer::import(
	array(
		array( 'email', 'name' ),
		array( 'new@example.com', 'Nuevo' ),
		array( 'NEW@example.com', 'Repetido' ),
		array( 'ana@example.com', 'Ana' ),
		array( 'bob@example.com', 'Robert' ),
		array( 'roto', 'X' ),
		array( '', '' ),
	),
	'pending',
	'es',
	true,
	'test'
);
assert_nl( 1 === $report['added'] && 1 === $report['duplicates'] && 1 === $report['blocked'] && 1 === $report['updated'] && 1 === $report['invalid'], 'The import report must count every case: ' . json_encode( $report ) );
assert_nl( 'Robert' === Subscribers::findByEmail( 'bob@example.com' )['name'] && 2 === count( $report['errors'] ), 'Names must update and each problem row must be explained.' );
assert_nl( array( 'No se ha encontrado ninguna columna de correo electrónico.' ) === Importer::import( array( array( 'a', 'b' ), array( 'c', 'd' ) ), 'pending', 'es', true, 'x' )['errors'], 'A file without emails must say so.' );

// 9. Popup frequency: once per IP per period; only a salted hash of the IP is kept.
$GLOBALS['mt_transients'] = array();
assert_nl( PublicController::claimSlot( '203.0.113.5' ) && ! PublicController::claimSlot( '203.0.113.5' ) && PublicController::claimSlot( '203.0.113.6' ) && ! PublicController::claimSlot( '' ), 'The popup must be allowed once per IP per period.' );
$slot_keys = array_keys( $GLOBALS['mt_transients'] );
assert_nl( 2 === count( $slot_keys ) && false === strpos( implode( '', $slot_keys ), '203.0.113' ) && 24 * HOUR_IN_SECONDS === $GLOBALS['mt_transients'][ $slot_keys[0] ]['ttl'], 'Only a hash of the IP may be stored, for 24 hours by default.' );
$GLOBALS['mt_options'][ Settings::OPTION ] = array( 'frequency_hours' => 6 );
$GLOBALS['mt_transients']                  = array();
PublicController::claimSlot( '203.0.113.7' );
assert_nl( 6 * HOUR_IN_SECONDS === current( $GLOBALS['mt_transients'] )['ttl'], 'The period must follow the setting.' );
$GLOBALS['mt_options'][ Settings::OPTION ] = array();
assert_nl( 24 === Settings::get( 'frequency_hours' ) && 1 === Settings::sanitize( array( 'frequency_hours' => '0' ) )['frequency_hours'] && 720 === Settings::sanitize( array( 'frequency_hours' => '9999' ) )['frequency_hours'], 'The period must default to 24 hours and stay within 1 to 720.' );

// 10. Footer block: the consent wording links the privacy policy, text is escaped, the source is kept.
$consent = PublicController::consentHtml( 'Acepto <b>todo</b> y he leído la política de privacidad.', 'Política de privacidad', '/politica-de-privacidad/' );
assert_nl( 'Acepto &lt;b&gt;todo&lt;/b&gt; y he leído la <a href="/politica-de-privacidad/" target="_blank" rel="noopener">política de privacidad</a>.' === $consent, 'The privacy words must become the link and the rest must be escaped: ' . $consent );
assert_nl( 'I agree. <a href="/en/privacy/" target="_blank" rel="noopener">Privacy policy</a>' === PublicController::consentHtml( 'I agree.', 'Privacy policy', '/en/privacy/' ), 'A custom consent text without the words must get the link appended.' );
$footer_signup = Subscribers::subscribe(
	array(
		'email'  => 'footer@example.com',
		'source' => 'footer',
	)
);
$forged_source = Subscribers::subscribe(
	array(
		'email'  => 'forged@example.com',
		'source' => '<script>',
	)
);
assert_nl( 'footer' === $footer_signup['subscriber']['source'] && 'popup' === $forged_source['subscriber']['source'], 'Signups must record whether they came from the footer or the popup, nothing else.' );

echo "Newsletter tests passed.\n";
