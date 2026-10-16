<?php
namespace MeTransfers\Newsletter;

/**
 * Newsletter settings (Newsletter → Ajustes), stored in one option.
 */
final class Settings {
	const OPTION = 'mt_newsletter_settings';

	const DEFAULTS = array(
		'enabled'         => true,
		// Signup block at the top of the site footer.
		'footer_enabled'  => true,
		'delay'           => 10,
		'dismiss_days'    => 14,
		// At most one popup per visitor (same IP or same browser) in this many hours.
		'frequency_hours' => 24,
		'double_opt_in'   => true,
		'rate_per_hour'   => 300,
		'from_name'       => '',
		'from_email'      => '',
		'footer'          => 'MeTransfers · Traslados privados en Barcelona y Cataluña · metransfers.es',
		'excluded_paths'  => '',
	);

	// Popup and email texts; each one can be overridden per language in Ajustes.
	const TEXTS = array(
		'es' => array(
			'eyebrow'            => 'Ofertas exclusivas',
			'title'              => 'Ofertas y novedades de MeTransfers',
			'text'               => 'Suscríbete y recibe descuentos en traslados, novedades y consejos para moverte por Barcelona y Cataluña.',
			'button'             => 'Suscribirme',
			'consent'            => 'Acepto recibir comunicaciones comerciales de MeTransfers y he leído la política de privacidad.',
			'success_pending'    => '¡Casi listo! Te hemos enviado un correo para confirmar tu suscripción.',
			'success_subscribed' => '¡Gracias! Ya estás suscrito a nuestras novedades.',
			'footer_title'       => 'Recibe nuestras ofertas por email',
			'footer_text'        => 'Descuentos en traslados y novedades de MeTransfers, sin spam. Puedes darte de baja cuando quieras.',
			'confirm_subject'    => 'Confirma tu suscripción a MeTransfers',
			'confirm_body'       => 'Para empezar a recibir nuestras ofertas y novedades, confirma tu suscripción con el botón de abajo. Si no te has suscrito tú, ignora este correo.',
		),
		'en' => array(
			'eyebrow'            => 'Exclusive offers',
			'title'              => 'MeTransfers offers and news',
			'text'               => 'Subscribe to get transfer discounts, news and tips for getting around Barcelona and Catalonia.',
			'button'             => 'Subscribe',
			'consent'            => 'I agree to receive marketing emails from MeTransfers and I have read the privacy policy.',
			'success_pending'    => 'Almost done! We have sent you an email to confirm your subscription.',
			'success_subscribed' => 'Thank you! You are now subscribed to our news.',
			'footer_title'       => 'Get our offers by email',
			'footer_text'        => 'Transfer discounts and MeTransfers news, no spam. Unsubscribe whenever you like.',
			'confirm_subject'    => 'Confirm your MeTransfers subscription',
			'confirm_body'       => 'To start receiving our offers and news, confirm your subscription with the button below. If you did not subscribe, just ignore this email.',
		),
	);

	// Fixed interface strings: they do not need to be edited.
	const LABELS = array(
		'es' => array(
			'name'          => 'Nombre',
			'email'         => 'Correo electrónico',
			'close'         => 'Cerrar',
			'privacy'       => 'Política de privacidad',
			'sending'       => 'Enviando…',
			'error'         => 'No hemos podido completar la suscripción. Inténtalo de nuevo en unos minutos.',
			'invalid_email' => 'Escribe un correo electrónico válido.',
			'need_consent'  => 'Para suscribirte tienes que aceptar recibir nuestras comunicaciones.',
			'confirm'       => 'Confirmar suscripción',
			'hello'         => 'Hola',
			'unsubscribe'   => 'Darme de baja',
			'why'           => 'Recibes este correo porque te suscribiste a las novedades de MeTransfers.',
			'link_fallback' => '¿No funciona el botón? Copia este enlace en tu navegador:',
		),
		'en' => array(
			'name'          => 'Name',
			'email'         => 'Email',
			'close'         => 'Close',
			'privacy'       => 'Privacy policy',
			'sending'       => 'Sending…',
			'error'         => 'We could not complete your subscription. Please try again in a few minutes.',
			'invalid_email' => 'Please enter a valid email address.',
			'need_consent'  => 'To subscribe you need to agree to receive our emails.',
			'confirm'       => 'Confirm subscription',
			'hello'         => 'Hello',
			'unsubscribe'   => 'Unsubscribe',
			'why'           => 'You are receiving this email because you subscribed to MeTransfers news.',
			'link_fallback' => 'Button not working? Copy this link into your browser:',
		),
	);

	/**
	 * @return array<string, mixed>
	 */
	public static function all() {
		$saved    = get_option( self::OPTION, array() );
		$saved    = is_array( $saved ) ? $saved : array();
		$settings = array_merge( self::DEFAULTS, array_intersect_key( $saved, self::DEFAULTS ) ) + array( 'texts' => isset( $saved['texts'] ) && is_array( $saved['texts'] ) ? $saved['texts'] : array() );
		// Public signup always verifies ownership, including previously saved settings.
		$settings['double_opt_in'] = true;
		return $settings;
	}

	public static function get( $key ) {
		$settings = self::all();
		return $settings[ $key ] ?? null;
	}

	public static function language( $language ) {
		return 'en' === $language ? 'en' : 'es';
	}

	public static function text( $key, $language ) {
		$language = self::language( $language );
		$custom   = self::all()['texts'][ $language ][ $key ] ?? '';
		if ( '' !== trim( (string) $custom ) ) {
			return (string) $custom;
		}
		return self::TEXTS[ $language ][ $key ] ?? self::LABELS[ $language ][ $key ] ?? '';
	}

	public static function label( $key, $language ) {
		return self::LABELS[ self::language( $language ) ][ $key ] ?? '';
	}

	/**
	 * Cleans the Ajustes form before it is stored.
	 *
	 * @param array<string, mixed> $input Raw form values.
	 * @return array<string, mixed>
	 */
	public static function sanitize( array $input ) {
		$texts = array();
		foreach ( self::TEXTS as $language => $defaults ) {
			foreach ( array_keys( $defaults ) as $key ) {
				$value = isset( $input['texts'][ $language ][ $key ] ) ? (string) $input['texts'][ $language ][ $key ] : '';
				$value = in_array( $key, array( 'text', 'confirm_body', 'footer_text' ), true ) ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
				// Saving a default unchanged keeps following future default improvements.
				if ( '' !== $value && $value !== $defaults[ $key ] ) {
					$texts[ $language ][ $key ] = $value;
				}
			}
		}

		$paths = array();
		foreach ( preg_split( '/\R/', (string) ( $input['excluded_paths'] ?? '' ) ) as $path ) {
			$path = trim( sanitize_text_field( $path ) );
			if ( '' !== $path ) {
				$paths[] = '/' . ltrim( $path, '/' );
			}
		}

		$from_email = sanitize_email( (string) ( $input['from_email'] ?? '' ) );
		return array(
			'enabled'         => ! empty( $input['enabled'] ),
			'footer_enabled'  => ! empty( $input['footer_enabled'] ),
			'delay'           => max( 3, min( 120, (int) ( $input['delay'] ?? self::DEFAULTS['delay'] ) ) ),
			'dismiss_days'    => max( 1, min( 365, (int) ( $input['dismiss_days'] ?? self::DEFAULTS['dismiss_days'] ) ) ),
			'frequency_hours' => max( 1, min( 720, (int) ( $input['frequency_hours'] ?? self::DEFAULTS['frequency_hours'] ) ) ),
			'double_opt_in'   => true,
			'rate_per_hour'   => max( 10, min( 5000, (int) ( $input['rate_per_hour'] ?? self::DEFAULTS['rate_per_hour'] ) ) ),
			'from_name'       => sanitize_text_field( (string) ( $input['from_name'] ?? '' ) ),
			'from_email'      => is_email( $from_email ) ? $from_email : '',
			'footer'          => sanitize_textarea_field( (string) ( $input['footer'] ?? '' ) ),
			'excluded_paths'  => implode( "\n", array_unique( $paths ) ),
			'texts'           => $texts,
		);
	}

	/**
	 * @return array<int, string> Path prefixes where the popup never appears.
	 */
	public static function excludedPaths() {
		return array_values( array_filter( preg_split( '/\R/', (string) self::get( 'excluded_paths' ) ) ) );
	}
}
