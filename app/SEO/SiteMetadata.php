<?php
namespace MeTransfers\SEO;

final class SiteMetadata {
	public static function text( bool $front_page, bool $posts_index, string $field ): string {
		if ( $front_page ) {
			$values = array(
				'title'       => 'Traslados Privados y Transfer en Barcelona | MeTransfers',
				'description' => 'Traslados privados en Barcelona con recogida en aeropuerto, puerto, Sants, hotel o dirección. Reserva transfer con chófer profesional y precio confirmado.',
			);
		} elseif ( $posts_index ) {
			$values = array(
				'title'       => 'Blog de viajes y traslados desde Barcelona | MeTransfers',
				'description' => 'Guías para organizar viajes desde Barcelona: aeropuerto, estaciones, hoteles y destinos. Consejos prácticos de transporte y traslados privados.',
			);
		} else {
			return '';
		}
		return $values[ $field ] ?? '';
	}
}
