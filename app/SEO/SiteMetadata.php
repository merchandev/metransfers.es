<?php
namespace MeTransfers\SEO;

final class SiteMetadata {
	public static function text( bool $front_page, bool $posts_index, string $field ): string {
		if ( $front_page ) {
			$values = array(
				'title'       => 'Transfer Aeropuerto Barcelona y Traslados Privados | MeTransfers',
				'description' => 'Reserva tu transfer privado desde o hacia el Aeropuerto de Barcelona, centro, hotel o puerto. Chófer profesional, precio cerrado y atención personalizada 24/7.',
			);
		} elseif ( $posts_index ) {
			$values = array(
				'title'       => 'Blog de viajes y traslados desde Barcelona | MeTransfers',
				'description' => 'Guías para organizar tus viajes desde Barcelona: aeropuerto, estaciones, hoteles y destinos. Consejos de transporte y traslados privados en el blog de MeTransfers.',
			);
		} else {
			return '';
		}
		return $values[ $field ] ?? '';
	}
}
