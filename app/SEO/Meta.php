<?php
namespace MeTransfers\SEO;

final class Meta {
	public function register() {
		add_filter( 'wpseo_title', array( __CLASS__, 'title' ), 110 );
		add_filter( 'wpseo_metadesc', array( __CLASS__, 'description' ), 110 );
	}

	public static function routeText( $post, string $field, string $language = 'es' ): string {
		$origin      = (string) get_post_meta( $post->ID, '_mt_ruta_origen', true );
		$destination = (string) get_post_meta( $post->ID, '_mt_ruta_destino', true );
		if ( '' === $origin || '' === $destination ) {
			return '';
		}
		$origin = str_replace( 'Barcelona centro', 'Barcelona', $origin );
		if ( 'title' === $field ) {
			return sprintf( 'Transfer %s - %s | MeTransfers', $origin, $destination );
		}
		return 'en' === $language
			? sprintf( 'Book your private transfer from %s to %s. Confirm your pickup address, luggage and travel details with MeTransfers before departure.', $origin, $destination )
			: sprintf( 'Reserva tu traslado privado de %s a %s. Confirma el punto de recogida, el equipaje y los detalles del viaje con MeTransfers antes de salir.', $origin, $destination );
	}

	public static function title( $title ) {
		if ( is_post_type_archive( 'ruta' ) ) {
			return 'en' === \MeTransfers\I18n\Language::get() ? 'Private transfer routes from Barcelona | MeTransfers' : 'Rutas de transfer desde Barcelona | MeTransfers';
		}
		$post = get_queried_object();
		if ( is_singular( 'ruta' ) && $post && ! get_post_meta( $post->ID, '_yoast_wpseo_title', true ) ) {
			$generated = self::routeText( $post, 'title', \MeTransfers\I18n\Language::get() );
			return '' !== $generated ? $generated : $title;
		}
		return $title;
	}

	public static function description( $description ) {
		if ( is_post_type_archive( 'ruta' ) ) {
			return 'en' === \MeTransfers\I18n\Language::get() ? 'Explore private transfer routes from Barcelona. Choose your destination and contact MeTransfers to confirm your pickup and travel details.' : 'Consulta las rutas de traslado privado desde Barcelona. Elige tu destino y contacta con MeTransfers para confirmar la recogida y los detalles de tu viaje.';
		}
		$post = get_queried_object();
		if ( is_singular( 'ruta' ) && $post && ! get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true ) ) {
			$generated = self::routeText( $post, 'description', \MeTransfers\I18n\Language::get() );
			return '' !== $generated ? $generated : $description;
		}
		return $description;
	}
}
