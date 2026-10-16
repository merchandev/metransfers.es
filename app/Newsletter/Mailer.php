<?php
namespace MeTransfers\Newsletter;

use MeTransfers\Notifications\NotificationService;

/**
 * Renders and sends newsletter emails through the site's SMTP settings.
 *
 * Every campaign email carries an unsubscribe link in its footer and the
 * List-Unsubscribe headers (one-click, RFC 8058) that Gmail and Yahoo
 * require from bulk senders.
 */
final class Mailer {
	const TAGS = array(
		'{{nombre}}'    => 'Nombre del suscriptor (vacío si no lo dio)',
		'{{email}}'     => 'Correo del suscriptor',
		'{{baja}}'      => 'Enlace para darse de baja',
		'{{confirmar}}' => 'Enlace para confirmar la suscripción (pendientes)',
		'{{web}}'       => 'Dirección de la web',
	);

	/** @var string */
	private static $last_error = '';

	/** @var string */
	private static $alt_body = '';

	/** @var array<string, mixed> Transport state restored after this newsletter. */
	private static $transport_state = array();

	/** @var object|null */
	private static $transport = null;

	/**
	 * @param array<string, mixed> $campaign   subject, preheader, body.
	 * @param array<string, mixed> $subscriber Row from Subscribers.
	 * @return array{subject: string, html: string}
	 */
	public static function renderCampaign( array $campaign, array $subscriber ) {
		$language = Settings::language( (string) ( $subscriber['language'] ?? 'es' ) );
		$body     = self::personalize( wpautop( (string) $campaign['body'] ), $subscriber );
		$subject  = self::personalizeText( (string) $campaign['subject'], $subscriber );
		return array(
			'subject' => $subject,
			'html'    => self::layout( $subject, (string) ( $campaign['preheader'] ?? '' ), $body, $subscriber, $language ),
		);
	}

	public static function sendCampaign( array $campaign, array $subscriber ) {
		// Pending contacts only receive the opt-in request, never promotional content.
		if ( 'pending' === ( $subscriber['status'] ?? '' ) ) {
			if ( ! empty( $subscriber['confirmation_sent_at'] ) && strtotime( $subscriber['confirmation_sent_at'] . ' UTC' ) > time() - Subscribers::RESEND_SECONDS ) {
				self::$last_error = 'confirmation_recent';
				return false;
			}
			$sent = self::sendConfirmation( $subscriber );
			if ( $sent ) {
				Subscribers::markConfirmationSent( (int) $subscriber['id'] );
			}
			return $sent;
		}
		if ( 'subscribed' !== ( $subscriber['status'] ?? '' ) ) {
			self::$last_error = 'subscriber_not_confirmed';
			return false;
		}
		$email = self::renderCampaign( $campaign, $subscriber );
		return self::send( (string) $subscriber['email'], $email['subject'], $email['html'], Subscribers::unsubscribeUrl( $subscriber ) );
	}

	public static function sendConfirmation( array $subscriber ) {
		if ( 'pending' !== ( $subscriber['status'] ?? '' ) ) {
			self::$last_error = 'subscriber_not_pending';
			return false;
		}
		$language = Settings::language( (string) $subscriber['language'] );
		$url      = Subscribers::confirmUrl( $subscriber );
		$greeting = '' !== (string) $subscriber['name'] ? Settings::label( 'hello', $language ) . ' ' . $subscriber['name'] . ',' : Settings::label( 'hello', $language ) . ',';
		$subject  = Settings::text( 'confirm_subject', $language );
		$body     = '<h1>' . esc_html( $subject ) . '</h1><p>' . esc_html( $greeting ) . '</p><p>' . nl2br( esc_html( Settings::text( 'confirm_body', $language ) ) ) . '</p>'
			. Brand::button( $url, Settings::label( 'confirm', $language ) )
			. '<p style="margin:24px 0 0;font-size:13px;color:' . Brand::MUTED . '">' . esc_html( Settings::label( 'link_fallback', $language ) ) . '<br><a href="' . esc_url( $url ) . '" style="color:' . Brand::BLUE . ';word-break:break-all">' . esc_html( $url ) . '</a></p>';
		return self::send( (string) $subscriber['email'], $subject, self::layout( $subject, '', $body, $subscriber, $language, false ), '' );
	}

	/**
	 * Replaces the tags in HTML content, escaping the subscriber's values.
	 */
	public static function personalize( $html, array $subscriber ) {
		$values = array(
			'{{nombre}}'    => esc_html( (string) ( $subscriber['name'] ?? '' ) ),
			'{{email}}'     => esc_html( (string) ( $subscriber['email'] ?? '' ) ),
			'{{baja}}'      => esc_url( Subscribers::unsubscribeUrl( $subscriber ) ),
			'{{confirmar}}' => esc_url( Subscribers::confirmUrl( $subscriber ) ),
			'{{web}}'       => esc_url( home_url( '/' ) ),
		);
		// The visual editor may save a tag inside a link as %7B%7Bbaja%7D%7D.
		foreach ( array( '{{baja}}', '{{confirmar}}', '{{web}}' ) as $tag ) {
			$values[ rawurlencode( $tag ) ] = $values[ $tag ];
		}
		return strtr( (string) $html, $values );
	}

	public static function personalizeText( $text, array $subscriber ) {
		$text = strtr(
			(string) $text,
			array(
				'{{nombre}}' => (string) ( $subscriber['name'] ?? '' ),
				'{{email}}'  => (string) ( $subscriber['email'] ?? '' ),
			)
		);
		// A subject is a single header line.
		return trim( (string) preg_replace( '/\s+/', ' ', wp_strip_all_tags( $text ) ) );
	}

	public static function lastError() {
		return self::$last_error;
	}

	/** Only an explicit nonexistent-mailbox response suppresses a recipient. */
	public static function isPermanentRecipientFailure( $error ) {
		return 1 === preg_match( '/\b5\.1\.1\b/', (string) $error );
	}

	/**
	 * @param string $unsubscribe_url Empty for the confirmation email, which is not marketing.
	 */
	public static function send( $recipient, $subject, $html, $unsubscribe_url ) {
		self::$last_error = '';
		if ( ! is_string( $recipient ) || preg_match( '/[\r\n,;]/', $recipient ) || ! is_email( $recipient ) ) {
			self::failed( 'invalid_recipient' );
			return false;
		}
		$errors = self::configurationErrors();
		if ( $errors ) {
			self::failed( implode( '; ', $errors ) );
			return false;
		}
		if ( '' !== $unsubscribe_url && ( preg_match( '/[\r\n<>]/', $unsubscribe_url ) || 'https' !== wp_parse_url( $unsubscribe_url, PHP_URL_SCHEME ) || wp_parse_url( home_url( '/' ), PHP_URL_HOST ) !== wp_parse_url( $unsubscribe_url, PHP_URL_HOST ) ) ) {
			self::failed( 'invalid_unsubscribe_url' );
			return false;
		}
		$subject        = self::personalizeText( $subject, array() );
		self::$alt_body = self::plainText( $html );

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$from    = self::sender();
		if ( '' !== $from['email'] ) {
			$headers[] = 'From: ' . $from['name'] . ' <' . $from['email'] . '>';
			$headers[] = 'Reply-To: ' . $from['name'] . ' <' . $from['email'] . '>';
		}
		if ( '' !== $unsubscribe_url ) {
			$headers[] = 'List-ID: MeTransfers <newsletter.' . wp_parse_url( home_url( '/' ), PHP_URL_HOST ) . '>';
			$headers[] = 'List-Unsubscribe: <' . esc_url_raw( $unsubscribe_url ) . '>';
			$headers[] = 'List-Unsubscribe-Post: List-Unsubscribe=One-Click';
		}

		add_action( 'phpmailer_init', array( __CLASS__, 'finishMessage' ), 10000 );
		add_action( 'wp_mail_failed', array( __CLASS__, 'captureError' ) );
		try {
			$sent = wp_mail( $recipient, $subject, $html, $headers );
		} finally {
			remove_action( 'phpmailer_init', array( __CLASS__, 'finishMessage' ), 10000 );
			remove_action( 'wp_mail_failed', array( __CLASS__, 'captureError' ) );
			// WordPress reuses PHPMailer. Do not change transport for booking emails.
			if ( null !== self::$transport ) {
				foreach ( self::$transport_state as $property => $value ) {
					self::$transport->$property = $value;
				}
			}
			self::$transport       = null;
			self::$transport_state = array();
			self::$alt_body        = '';
		}
		if ( ! $sent ) {
			self::failed( 'wp_mail_failed' );
		}
		return (bool) $sent;
	}

	/**
	 * Plain-text part (better deliverability) and the newsletter sender, which
	 * the SMTP settings would otherwise overwrite.
	 *
	 * @param object $mailer PHPMailer instance.
	 */
	public static function finishMessage( $mailer ) {
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer properties.
		self::$transport = $mailer;
		foreach ( array( 'Mailer', 'Host', 'Port', 'SMTPAuth', 'Username', 'Password', 'SMTPSecure', 'SMTPAutoTLS', 'SMTPOptions', 'SMTPDebug', 'SMTPKeepAlive', 'Timeout', 'Sender', 'From', 'FromName', 'AltBody', 'DKIM_extraHeaders' ) as $property ) {
			self::$transport_state[ $property ] = $mailer->$property;
		}
		NotificationService::configureSmtp( $mailer );
		$mailer->SMTPAutoTLS        = true;
		$mailer->SMTPDebug          = 0;
		$mailer->SMTPKeepAlive      = false;
		$mailer->Timeout            = 15;
		$mailer->SMTPOptions['ssl'] = array_merge(
			$mailer->SMTPOptions['ssl'] ?? array(),
			array(
				'verify_peer'       => true,
				'verify_peer_name'  => true,
				'allow_self_signed' => false,
			)
		);
		$mailer->AltBody            = self::$alt_body;
		$from                       = self::sender();
		$mailer->From               = $from['email'];
		$mailer->FromName           = $from['name'];
		$mailer->Sender             = $from['email'];
		// RFC 8058: cover both unsubscribe headers when PHPMailer signs DKIM.
		// If the relay signs instead, its DKIM configuration must cover them too.
		$mailer->DKIM_extraHeaders = array_values( array_unique( array_merge( $mailer->DKIM_extraHeaders, array( 'List-Unsubscribe', 'List-Unsubscribe-Post', 'List-ID' ) ) ) );
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/** @return array<int, string> Local checks; authentication still needs DNS and a real received message. */
	public static function configurationErrors() {
		$errors = array();
		foreach ( array( 'smtp_host', 'smtp_user', 'smtp_password' ) as $key ) {
			if ( '' === trim( (string) \MeTransfers\Core\Settings::get( $key, '' ) ) ) {
				$errors[] = 'newsletter_missing_' . $key;
			}
		}
		if ( ! in_array( (string) \MeTransfers\Core\Settings::get( 'smtp_encryption', 'ssl' ), array( 'ssl', 'tls' ), true ) ) {
			$errors[] = 'newsletter_smtp_requires_tls';
		}
		$port = (int) \MeTransfers\Core\Settings::get( 'smtp_port', 465 );
		if ( $port < 1 || $port > 65535 ) {
			$errors[] = 'newsletter_invalid_smtp_port';
		}
		if ( '' === self::sender()['email'] ) {
			$errors[] = 'newsletter_invalid_sender';
		}
		return $errors;
	}

	/**
	 * wp_mail_failed: keeps the mail server's reason for the delivery log.
	 *
	 * @param mixed $error
	 */
	public static function captureError( $error ) {
		self::$last_error = substr( sanitize_text_field( $error instanceof \WP_Error ? $error->get_error_message() : 'wp_mail_failed' ), 0, 250 );
	}

	// A reason from wp_mail_failed wins over the generic one.
	private static function failed( $reason ) {
		if ( '' === self::$last_error ) {
			self::$last_error = $reason;
		}
	}

	/**
	 * @return array{name: string, email: string}
	 */
	public static function sender() {
		$name  = (string) Settings::get( 'from_name' );
		$email = (string) Settings::get( 'from_email' );
		if ( '' === $name ) {
			$name = 'MeTransfers';
		}
		if ( '' === $email ) {
			$email = sanitize_email( (string) \MeTransfers\Core\Settings::get( 'smtp_from', \MeTransfers\Core\Settings::get( 'smtp_user', '' ) ) );
		}
		return array(
			'name'  => str_replace( array( "\r", "\n", '<', '>' ), '', $name ),
			'email' => is_email( $email ) ? $email : '',
		);
	}

	/**
	 * The site's look: navy header with the white logo and an amber eyebrow,
	 * a white card with Outfit headings and Inter text, and the dark footer.
	 * Every style is inline because most mail clients drop <style>.
	 */
	private static function layout( $title, $preheader, $body, array $subscriber, $language, $marketing = true ) {
		$footer = '<p style="margin:0 0 10px">' . nl2br( esc_html( (string) Settings::get( 'footer' ) ) ) . '</p>';
		if ( $marketing ) {
			$footer .= '<p style="margin:0">' . esc_html( Settings::label( 'why', $language ) ) . ' <a href="' . esc_url( Subscribers::unsubscribeUrl( $subscriber ) ) . '" style="color:#ffffff;text-decoration:underline">' . esc_html( Settings::label( 'unsubscribe', $language ) ) . '</a></p>';
		}
		$font = esc_attr( Brand::FONT_BODY );

		return '<!doctype html><html lang="' . esc_attr( $language ) . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="color-scheme" content="light"><meta name="supported-color-schemes" content="light"><title>' . esc_html( $title ) . '</title>'
			. '</head>'
			. '<body style="margin:0;padding:0;background:' . Brand::PAGE . ';font-family:' . $font . ';color:' . Brand::TEXT_SOFT . '">'
			. ( '' !== $preheader ? '<div style="display:none;max-height:0;overflow:hidden;opacity:0">' . esc_html( $preheader ) . '</div>' : '' )
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:' . Brand::PAGE . '"><tr><td align="center" style="padding:28px 12px">'
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border:1px solid ' . Brand::BORDER . ';border-radius:16px;overflow:hidden">'
			. Brand::header( $marketing ? Settings::text( 'eyebrow', $language ) : '', true )
			. '<tr><td style="padding:32px 32px 28px;font-family:' . $font . ';font-size:16px;line-height:1.65;color:' . Brand::TEXT_SOFT . '">' . self::brandContent( $body ) . '</td></tr>'
			. '<tr><td style="background:' . Brand::NAVY_DEEP . ';padding:22px 28px;text-align:center;font-family:' . $font . ';font-size:13px;line-height:1.6;color:' . Brand::ON_DARK . '">' . $footer . '</td></tr>'
			. '</table></td></tr></table></body></html>';
	}

	/**
	 * Gives the editor's headings, paragraphs, links, lists and images the
	 * site's typography, unless the author styled them.
	 */
	private static function brandContent( $html ) {
		$title  = Brand::FONT_TITLE;
		$styles = array(
			'h1'  => 'margin:0 0 16px;font-family:' . $title . ';font-size:28px;line-height:1.2;font-weight:700;letter-spacing:-0.5px;color:' . Brand::TEXT,
			'h2'  => 'margin:26px 0 12px;font-family:' . $title . ';font-size:22px;line-height:1.25;font-weight:700;letter-spacing:-0.3px;color:' . Brand::TEXT,
			'h3'  => 'margin:22px 0 10px;font-family:' . $title . ';font-size:18px;line-height:1.3;font-weight:700;color:' . Brand::TEXT,
			'p'   => 'margin:0 0 16px',
			'a'   => 'color:' . Brand::BLUE . ';font-weight:600',
			'li'  => 'margin:0 0 8px',
			'img' => 'max-width:100%;height:auto;border:0;border-radius:12px',
		);
		foreach ( $styles as $tag => $style ) {
			$html = (string) preg_replace( '/<' . $tag . '(?=[\s>])(?![^>]*\sstyle=)/i', '<' . $tag . ' style="' . esc_attr( $style ) . '"', (string) $html );
		}
		return $html;
	}

	private static function plainText( $html ) {
		$html = (string) preg_replace( '#<a\s[^>]*href="([^"]+)"[^>]*>(.*?)</a>#is', '$2 ($1)', (string) $html );
		$html = (string) preg_replace( '#<(br|/p|/tr|/h[1-6]|/li)[^>]*>#i', "\n", $html );
		$html = (string) preg_replace( '#<head[^>]*>.*?</head>#is', '', $html );
		$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' );
		return trim( (string) preg_replace( "/\n{3,}/", "\n\n", (string) preg_replace( '/[ \t]+/', ' ', $text ) ) );
	}
}
