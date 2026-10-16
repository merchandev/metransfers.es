<?php
namespace MeTransfers\Newsletter;

use MeTransfers\Security\RequestRateLimiter;

/**
 * Public side: the popup (loaded on public pages, never during booking or
 * payment), the signup request and the confirm and unsubscribe links.
 *
 * Links go through admin-post.php, which page caches never store.
 */
final class PublicController {
	const AJAX_ACTION    = 'mt_newsletter_subscribe';
	const SLOT_ACTION    = 'mt_newsletter_slot';
	const CONFIRM_ACTION = 'mt_nl_confirm';
	const LEAVE_ACTION   = 'mt_nl_unsubscribe';

	// Booking steps where an interruption could cost a sale.
	const QUIET_PHASES = array( 'vehicle', 'details', 'payment', 'confirmation', 'hotel' );

	// A human needs at least this long to read the popup and type an address.
	const MIN_FILL_MS = 1500;

	public function register() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 110 );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( __CLASS__, 'subscribe' ) );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, array( __CLASS__, 'subscribe' ) );
		add_action( 'wp_ajax_' . self::SLOT_ACTION, array( __CLASS__, 'slot' ) );
		add_action( 'wp_ajax_nopriv_' . self::SLOT_ACTION, array( __CLASS__, 'slot' ) );
		foreach ( array(
			self::CONFIRM_ACTION => 'confirm',
			self::LEAVE_ACTION   => 'unsubscribe',
		) as $action => $method ) {
			add_action( 'admin_post_' . $action, array( __CLASS__, $method ) );
			add_action( 'admin_post_nopriv_' . $action, array( __CLASS__, $method ) );
		}
	}

	public static function shouldShow() {
		if ( ! Settings::get( 'enabled' ) || is_admin() || wp_doing_ajax() || is_feed() || is_404() ) {
			return false;
		}
		if ( \MeTransfers\HotelPortal\HotelPortal::isPortalRequest() || in_array( \MeTransfers\Core\Assets::booking_phase(), self::QUIET_PHASES, true ) ) {
			return false;
		}
		$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '/';
		$base = (string) preg_replace( '#^/en(?=/|$)#', '', $path );
		foreach ( Settings::excludedPaths() as $prefix ) {
			if ( 0 === strpos( $path, $prefix ) || 0 === strpos( '' !== $base ? $base : '/', $prefix ) ) {
				return false;
			}
		}
		return (bool) apply_filters( 'mt_newsletter_show_popup', true );
	}

	/**
	 * The footer signup block: always visible on public pages (also during
	 * booking, where it interrupts nobody), unless switched off in Ajustes.
	 */
	public static function footerEnabled() {
		if ( ! Settings::get( 'footer_enabled' ) || is_admin() || wp_doing_ajax() || is_feed() ) {
			return false;
		}
		return ! \MeTransfers\HotelPortal\HotelPortal::isPortalRequest();
	}

	public static function enqueue() {
		$popup = self::shouldShow();
		if ( ! $popup && ! self::footerEnabled() ) {
			return;
		}
		$dir = get_template_directory();
		$uri = get_template_directory_uri();
		if ( file_exists( $dir . '/assets/css/newsletter.css' ) ) {
			wp_enqueue_style( 'mt-newsletter', $uri . '/assets/css/newsletter.css', array(), (string) filemtime( $dir . '/assets/css/newsletter.css' ) );
		}
		if ( ! file_exists( $dir . '/assets/js/newsletter.js' ) ) {
			return;
		}
		wp_enqueue_script( 'mt-newsletter', $uri . '/assets/js/newsletter.js', array(), (string) filemtime( $dir . '/assets/js/newsletter.js' ), true );
		wp_add_inline_script( 'mt-newsletter', 'window.mtNewsletter=' . wp_json_encode( array( 'popup' => $popup ) + self::config() ) . ';', 'before' );
	}

	/**
	 * Footer signup block (footer.php). The same request, consent and double
	 * opt-in as the popup, with source "footer".
	 */
	public static function footerForm() {
		if ( ! self::footerEnabled() ) {
			return '';
		}
		$language = Settings::language( function_exists( 'mt_lang' ) ? mt_lang() : 'es' );
		$privacy  = function_exists( 'mt_localized_url' ) ? mt_localized_url( 'politica-de-privacidad' ) : home_url( '/politica-de-privacidad/' );
		$field    = static function ( $id, $name, $type, $label, $autocomplete, $max ) {
			return '<div class="mt-nlf__field"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>'
				. '<input id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" type="' . esc_attr( $type ) . '" autocomplete="' . esc_attr( $autocomplete ) . '" maxlength="' . (int) $max . '"' . ( 'email' === $type ? ' inputmode="email" required' : '' ) . '></div>';
		};

		return '<section class="mt-nlf" aria-labelledby="mt-nlf-title" data-mt-newsletter-footer>'
			. '<div class="mt-nlf__intro">'
			. '<p class="mt-nlf__eyebrow">' . esc_html( Settings::text( 'eyebrow', $language ) ) . '</p>'
			. '<h2 id="mt-nlf-title" class="mt-nlf__title">' . esc_html( Settings::text( 'footer_title', $language ) ) . '</h2>'
			. '<p class="mt-nlf__text">' . esc_html( Settings::text( 'footer_text', $language ) ) . '</p>'
			. '</div>'
			// POST, so the address never ends up in a URL if the script failed to load.
			. '<div class="mt-nlf__signup"><form class="mt-nlf__form" method="post" novalidate>'
			. '<div class="mt-nlf__row">'
			. $field( 'mt-nlf-name', 'name', 'text', Settings::label( 'name', $language ), 'given-name', 120 )
			. $field( 'mt-nlf-email', 'email', 'email', Settings::label( 'email', $language ), 'email', 191 )
			. '<button type="submit" class="mt-nlf__submit">' . esc_html( Settings::text( 'button', $language ) ) . '</button>'
			. '</div>'
			// Honeypot: hidden from people and assistive technology, filled by bots.
			. '<div class="mt-nl__trap" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>'
			. '<label class="mt-nlf__consent"><input type="checkbox" name="consent" value="1" required> <span>' . self::consentHtml( Settings::text( 'consent', $language ), Settings::label( 'privacy', $language ), $privacy ) . '</span></label>'
			. '</form>'
			. '<p class="mt-nlf__status" role="status" aria-live="polite"></p></div>'
			. '</section>';
	}

	/**
	 * The words "política de privacidad" in the consent text become the link;
	 * a custom text without them gets the link appended.
	 */
	public static function consentHtml( $consent, $label, $url ) {
		$link    = static function ( $text ) use ( $url ) {
			return '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $text ) . '</a>';
		};
		$consent = (string) $consent;
		$label   = (string) $label;
		// Byte offsets are safe here: both strings are UTF-8 and only the ASCII "P/p" may differ in case.
		$at = '' !== $label ? stripos( $consent, $label ) : false;
		if ( false === $at ) {
			return esc_html( $consent ) . ' ' . $link( $label );
		}
		return esc_html( substr( $consent, 0, $at ) ) . $link( substr( $consent, $at, strlen( $label ) ) ) . esc_html( substr( $consent, $at + strlen( $label ) ) );
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function config() {
		$language = Settings::language( function_exists( 'mt_lang' ) ? mt_lang() : 'es' );
		$texts    = array();
		foreach ( array( 'eyebrow', 'title', 'text', 'button', 'consent', 'success_pending', 'success_subscribed' ) as $key ) {
			$texts[ $key ] = Settings::text( $key, $language );
		}
		foreach ( array_keys( Settings::LABELS['es'] ) as $key ) {
			$texts[ $key ] = Settings::label( $key, $language );
		}
		return array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'action'      => self::AJAX_ACTION,
			'delay'       => (int) Settings::get( 'delay' ),
			'dismissDays' => (int) Settings::get( 'dismiss_days' ),
			'slotAction'  => self::SLOT_ACTION,
			'hours'       => (int) Settings::get( 'frequency_hours' ),
			'language'    => $language,
			'privacyUrl'  => function_exists( 'mt_localized_url' ) ? mt_localized_url( 'politica-de-privacidad' ) : home_url( '/politica-de-privacidad/' ),
			'texts'       => $texts,
		);
	}

	/**
	 * admin-ajax: may the popup open for this visitor? The browser asks once,
	 * only when the popup is due (not shown in this session or in the last
	 * hours on this browser); the answer is yes once per IP per period.
	 */
	public static function slot() {
		nocache_headers();
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		wp_send_json_success( array( 'show' => self::claimSlot( $ip ) ) );
	}

	/**
	 * True the first time an IP asks in each period. Only a salted hash of the
	 * IP is kept, in a transient that expires on its own.
	 */
	public static function claimSlot( $ip ) {
		$ip = trim( (string) $ip );
		if ( '' === $ip ) {
			return false;
		}
		$key = 'mt_nl_slot_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 40 );
		if ( false !== get_transient( $key ) ) {
			return false;
		}
		set_transient( $key, 1, max( 1, (int) Settings::get( 'frequency_hours' ) ) * HOUR_IN_SECONDS );
		return true;
	}

	/**
	 * admin-ajax: signup from the popup.
	 */
	public static function subscribe() {
		nocache_headers();
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			wp_send_json_error( array( 'message' => Settings::label( 'error', 'es' ) ), 405 );
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Public form on cached pages: protected by honeypot, timing, rate limit and double opt-in.
		$language = Settings::language( isset( $_POST['language'] ) ? sanitize_key( wp_unslash( $_POST['language'] ) ) : 'es' );
		$double   = (bool) Settings::get( 'double_opt_in' );
		$done     = Settings::text( $double ? 'success_pending' : 'success_subscribed', $language );

		// Bots get the normal answer so they learn nothing.
		$honeypot = isset( $_POST['website'] ) ? trim( (string) wp_unslash( $_POST['website'] ) ) : '';
		$elapsed  = isset( $_POST['elapsed'] ) ? (int) $_POST['elapsed'] : 0;
		if ( '' !== $honeypot || $elapsed < self::MIN_FILL_MS ) {
			wp_send_json_success( array( 'message' => $done ) );
		}
		if ( ! RequestRateLimiter::consume( 'newsletter_subscribe', 5, 10 * MINUTE_IN_SECONDS ) ) {
			wp_send_json_error( array( 'message' => Settings::label( 'error', $language ) ), 429 );
		}
		if ( ! isset( $_POST['consent'] ) || '1' !== (string) wp_unslash( $_POST['consent'] ) ) {
			wp_send_json_error( array( 'message' => Settings::label( 'need_consent', $language ) ), 400 );
		}

		$result = Subscribers::subscribe(
			array(
				'email'        => isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '',
				'name'         => isset( $_POST['name'] ) ? wp_unslash( $_POST['name'] ) : '',
				'language'     => $language,
				'source'       => isset( $_POST['source'] ) ? sanitize_key( wp_unslash( $_POST['source'] ) ) : 'popup',
				'signup_page'  => isset( $_POST['page'] ) ? wp_unslash( $_POST['page'] ) : '',
				'referrer'     => isset( $_POST['referrer'] ) ? wp_unslash( $_POST['referrer'] ) : '',
				'pages'        => isset( $_POST['pages'] ) ? wp_unslash( $_POST['pages'] ) : '[]',
				'consent_text' => Settings::text( 'consent', $language ),
			)
		);
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( 'invalid' === $result['result'] ) {
			wp_send_json_error( array( 'message' => Settings::label( 'invalid_email', $language ) ), 400 );
		}
		if ( ! $result['subscriber'] ) {
			wp_send_json_error( array( 'message' => Settings::label( 'error', $language ) ), 500 );
		}
		if ( $result['send_confirmation'] ) {
			self::sendConfirmation( $result['subscriber'] );
		}
		// Same answer whether the address was new or already subscribed.
		wp_send_json_success( array( 'message' => $done ) );
	}

	/**
	 * Sends now; if the mail server fails, the outbox retries later.
	 */
	public static function sendConfirmation( array $subscriber ) {
		if ( ! empty( $subscriber['confirmation_sent_at'] ) && strtotime( $subscriber['confirmation_sent_at'] . ' UTC' ) > time() - Subscribers::RESEND_SECONDS ) {
			return true;
		}
		if ( Mailer::sendConfirmation( $subscriber ) ) {
			Subscribers::markConfirmationSent( (int) $subscriber['id'] );
			return true;
		}
		if ( Mailer::isPermanentRecipientFailure( Mailer::lastError() ) ) {
			Subscribers::setStatus( array( (int) $subscriber['id'] ), 'bounced' );
			return false;
		}
		\MeTransfers\Core\Outbox::enqueue( 'newsletter.confirm', 'newsletter.confirm.' . (int) $subscriber['id'] . '.' . (int) floor( time() / Subscribers::RESEND_SECONDS ), (int) $subscriber['id'] );
		return false;
	}

	/**
	 * Outbox retry of a confirmation email.
	 */
	public static function handleEvent( $event_type, $subscriber_id ) {
		if ( 'newsletter.confirm' !== $event_type ) {
			return false;
		}
		$subscriber = Subscribers::find( $subscriber_id );
		if ( ! $subscriber || 'pending' !== $subscriber['status'] ) {
			return true;
		}
		if ( ! empty( $subscriber['confirmation_sent_at'] ) && strtotime( $subscriber['confirmation_sent_at'] . ' UTC' ) > time() - Subscribers::RESEND_SECONDS ) {
			return true;
		}
		if ( ! Mailer::sendConfirmation( $subscriber ) ) {
			if ( Mailer::isPermanentRecipientFailure( Mailer::lastError() ) ) {
				Subscribers::setStatus( array( (int) $subscriber['id'] ), 'bounced' );
				return true;
			}
			return false;
		}
		Subscribers::markConfirmationSent( (int) $subscriber['id'] );
		return true;
	}

	/**
	 * admin-post: link of the confirmation email.
	 */
	public static function confirm() {
		$post = 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' );
		// phpcs:disable WordPress.Security.NonceVerification -- The HMAC token in the link authenticates the request.
		$source = $post ? $_POST : $_GET;
		$id     = isset( $source['s'] ) ? absint( $source['s'] ) : 0;
		$token  = isset( $source['t'] ) ? sanitize_text_field( wp_unslash( $source['t'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification
		$subscriber = Subscribers::find( $id );
		$language   = Settings::language( $subscriber ? (string) $subscriber['language'] : 'es' );
		if ( ! $subscriber || ! Subscribers::verify( 'confirm', $subscriber, $token ) || ! in_array( $subscriber['status'], array( 'pending', 'subscribed' ), true ) ) {
			self::page( 'es' === $language ? 'Enlace no válido' : 'Invalid link', 'es' === $language ? 'Este enlace de confirmación no es válido o ya no se puede usar.' : 'This confirmation link is not valid or can no longer be used.', $language );
		}
		// Antivirus and mailbox previewers open GET links automatically.
		if ( ! $post && 'pending' === $subscriber['status'] ) {
			$form = '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">'
				. '<input type="hidden" name="action" value="mt_nl_confirm">'
				. '<input type="hidden" name="s" value="' . (int) $id . '">'
				. '<input type="hidden" name="t" value="' . esc_attr( $token ) . '">'
				. '<button type="submit">' . esc_html( Settings::label( 'confirm', $language ) ) . '</button></form>';
			self::page( Settings::text( 'confirm_subject', $language ), 'es' === $language ? 'Pulsa el botón para confirmar que quieres recibir nuestras novedades.' : 'Press the button to confirm you want to receive our news.', $language, $form );
		}
		$result = Subscribers::confirm( $id, $token );
		if ( 'invalid' === $result ) {
			self::page( 'es' === $language ? 'Enlace no válido' : 'Invalid link', 'es' === $language ? 'Este enlace ya no se puede usar.' : 'This link can no longer be used.', $language );
		}
		self::page( 'es' === $language ? '¡Suscripción confirmada!' : 'Subscription confirmed!', 'es' === $language ? 'Gracias. A partir de ahora recibirás nuestras ofertas y novedades.' : 'Thank you. From now on you will receive our offers and news.', $language );
	}

	/**
	 * admin-post: unsubscribe link. GET shows a button, because link scanners
	 * open every URL in an email; POST unsubscribes. Mail clients send the
	 * one-click POST of RFC 8058 straight here.
	 */
	public static function unsubscribe() {
		$post = 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET' );
		// phpcs:disable WordPress.Security.NonceVerification -- The HMAC token in the link authenticates the request.
		$source = $post ? $_POST : $_GET;
		$id     = isset( $source['s'] ) ? absint( $source['s'] ) : ( isset( $_GET['s'] ) ? absint( $_GET['s'] ) : 0 );
		$token  = isset( $source['t'] ) ? sanitize_text_field( wp_unslash( $source['t'] ) ) : ( isset( $_GET['t'] ) ? sanitize_text_field( wp_unslash( $_GET['t'] ) ) : '' );
		$click  = $post && isset( $_POST['List-Unsubscribe'] ) && 'One-Click' === sanitize_text_field( wp_unslash( $_POST['List-Unsubscribe'] ) );
		// phpcs:enable WordPress.Security.NonceVerification

		$subscriber = Subscribers::find( $id );
		$language   = Settings::language( $subscriber ? (string) $subscriber['language'] : 'es' );
		$es         = 'es' === $language;
		$valid      = $subscriber && Subscribers::verify( 'unsubscribe', $subscriber, $token );

		if ( $click ) {
			nocache_headers();
			status_header( $valid ? 200 : 400 );
			if ( $valid ) {
				Subscribers::unsubscribe( $id, $token );
			}
			exit;
		}
		if ( ! $valid ) {
			self::page( $es ? 'Enlace no válido' : 'Invalid link', $es ? 'Este enlace de baja no es válido. Si quieres dejar de recibir correos, responde a cualquiera de ellos y te daremos de baja.' : 'This unsubscribe link is not valid. To stop receiving emails, reply to any of them and we will unsubscribe you.', $language );
		}
		if ( $post ) {
			Subscribers::unsubscribe( $id, $token );
			self::page( $es ? 'Te has dado de baja' : 'You have unsubscribed', $es ? 'No volverás a recibir nuestro newsletter. Si cambias de opinión, puedes suscribirte de nuevo desde la web.' : 'You will not receive our newsletter again. If you change your mind, you can subscribe again on our website.', $language );
		}
		if ( 'unsubscribed' === $subscriber['status'] ) {
			self::page( $es ? 'Ya estás dado de baja' : 'Already unsubscribed', $es ? 'Esta dirección ya no recibe nuestro newsletter.' : 'This address no longer receives our newsletter.', $language );
		}

		$form = '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">'
			. '<input type="hidden" name="action" value="' . esc_attr( self::LEAVE_ACTION ) . '">'
			. '<input type="hidden" name="s" value="' . (int) $id . '">'
			. '<input type="hidden" name="t" value="' . esc_attr( $token ) . '">'
			. '<button type="submit">' . esc_html( $es ? 'Darme de baja' : 'Unsubscribe me' ) . '</button></form>';
		self::page( $es ? '¿Darte de baja del newsletter?' : 'Unsubscribe from the newsletter?', ( $es ? 'Dejarás de recibir nuestros correos en ' : 'You will stop receiving our emails at ' ) . $subscriber['email'] . '.', $language, $form );
	}

	/**
	 * Small standalone page for the email links.
	 */
	private static function page( $title, $message, $language, $form = '' ) {
		nocache_headers();
		// Signed tokens must not leak to external fonts or links via Referer.
		header( 'Referrer-Policy: no-referrer' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: DENY' );
		header( 'Content-Type: text/html; charset=UTF-8' );
		header( 'X-Robots-Tag: noindex, nofollow' );
		status_header( 200 );
		$home = function_exists( 'mt_localized_url' ) ? mt_localized_url( '' ) : home_url( '/' );
		// The site's look: the dark hero with its blue glow, the white logo and
		// amber eyebrow on it, and a white card like the booking form.
		$css = 'body{margin:0;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:22px;padding:32px 16px;box-sizing:border-box;'
			. 'background:radial-gradient(circle at 82% 0%,rgba(0,136,255,.18),transparent 384px),linear-gradient(180deg,' . Brand::NAVY_DEEP . ' 0%,' . Brand::NAVY . ' 100%);'
			. 'font-family:' . Brand::FONT_BODY . ';color:' . Brand::TEXT_SOFT . ';-webkit-font-smoothing:antialiased}'
			. '.mt-logo{width:190px;max-width:60vw;height:auto}'
			. '.mt-tag{margin:0;color:' . Brand::AMBER . ';font-size:13px;letter-spacing:2px;text-transform:uppercase}'
			. 'main{max-width:520px;width:100%;box-sizing:border-box;background:#fff;border-radius:24px;padding:36px 32px;text-align:center;box-shadow:0 22px 54px rgba(0,19,43,.35)}'
			. 'h1{margin:0 0 12px;font-family:' . Brand::FONT_TITLE . ';font-size:28px;line-height:1.2;font-weight:700;letter-spacing:-.5px;color:' . Brand::TEXT . '}'
			. 'p{margin:0 0 22px;font-size:16px;line-height:1.65}'
			. 'button,.mt-btn{display:inline-block;border:0;background:' . Brand::NAVY . ';color:#fff;padding:15px 30px;border-radius:16px;font-family:inherit;font-size:16px;font-weight:700;letter-spacing:.3px;text-decoration:none;cursor:pointer}'
			. 'button:hover,.mt-btn:hover{background:#12324f}'
			. '.mt-pill{display:inline-block;margin-top:6px;padding:10px 22px;border:1px solid #cbd5e1;border-radius:999px;color:' . Brand::NAVY . ';font-size:15px;font-weight:500;text-decoration:none}'
			. '.mt-pill:hover{border-color:' . Brand::NAVY . '}';
		echo '<!doctype html><html lang="' . esc_attr( $language ) . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow"><title>' . esc_html( $title ) . ' · MeTransfers</title>'
			. '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="stylesheet" href="' . esc_url( Brand::FONTS_URL ) . '">'
			. '<style>' . $css . '</style></head><body>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed stylesheet built from constants.
			. '<a href="' . esc_url( $home ) . '"><img class="mt-logo" src="' . esc_url( Brand::logoUrl() ) . '" alt="MeTransfers"></a>'
			. '<p class="mt-tag">Newsletter</p>'
			. '<main><h1>' . esc_html( $title ) . '</h1><p>' . esc_html( $message ) . '</p>'
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The form is built above from escaped parts.
			. $form
			. '<p style="margin:' . ( '' !== $form ? '18px' : '0' ) . ' 0 0"><a class="' . ( '' !== $form ? 'mt-pill' : 'mt-btn' ) . '" href="' . esc_url( $home ) . '">' . esc_html( 'es' === $language ? 'Ir a la web' : 'Go to the website' ) . '</a></p></main></body></html>';
		exit;
	}
}
