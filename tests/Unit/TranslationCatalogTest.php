<?php

declare(strict_types=1);

namespace MeTransfers\Tests\Unit;

use MeTransfers\I18n\Translation;
use PHPUnit\Framework\TestCase;

final class TranslationCatalogTest extends TestCase {
	public function testCompleteCatalogIncludesBookingAndThemeStrings(): void {
		$catalog = Translation::sourceCatalog();

		self::assertContains( 'Usar mi ubicación actual', $catalog );
		self::assertContains( 'Consultar por WhatsApp', $catalog );
		self::assertContains(
			'Organizamos recogidas y llegadas desde o hacia el Aeropuerto de Barcelona, hoteles, puerto, estaciones y otras ciudades, con vehículo privado y chófer profesional.',
			$catalog
		);
		self::assertNotContains( '[wptb_booking_form]', $catalog );
	}
}
