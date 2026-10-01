<?php

declare(strict_types=1);

namespace MeTransfers\Tests\Unit;

use MeTransfers\I18n\EditorialEnglish;
use MeTransfers\I18n\Language;
use MeTransfers\I18n\Translation;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/Support/EditorialWordPress.php';

final class EditorialEnglishTest extends TestCase {
	protected function tearDown(): void {
		Language::set( 'es' );
		unset( $GLOBALS['mt_editorial_cache'], $GLOBALS['mt_editorial_cache_reads'], $GLOBALS['mt_editorial_options'] );
	}

	public function testEnglishWorksWithoutCacheAndOverridesOldTranslation(): void {
		$text = 'Nuestra historia y valores';
		$GLOBALS['mt_editorial_cache'][ 'mt_tr_en_' . md5( $text ) ] = 'Old incorrect translation';
		self::assertSame( 'Our story and values', Translation::translate( $text, 'en' ) );
		self::assertEmpty( $GLOBALS['mt_editorial_cache_reads'] ?? array() );
		self::assertSame( $text, Translation::translate( $text, 'es' ) );
		self::assertNull( EditorialEnglish::lookup( $text, 'fr' ) );
	}

	public function testUnknownContentKeepsExistingCacheAndMarkup(): void {
		$html = '<p>Texto propio <a href="https://sales.example/">Reservar ahora</a></p>';
		self::assertSame( $html, Translation::translate( $html, 'en' ) );
		$GLOBALS['mt_editorial_options'][ 'mt_tr_en_' . md5( $html ) ] = '<p>Existing translation</p>';
		self::assertSame( '<p>Existing translation</p>', Translation::translate( $html, 'en' ) );
	}

	public function testReviewedTemplatesHaveNoMissingLiteralTranslations(): void {
		$extractor = new \ReflectionMethod( Translation::class, 'literalTranslateArguments' );
		foreach ( array( 'page-sobre-nosotros.php', 'template-servicio.php', 'header.php', 'footer.php', 'archive-ruta.php' ) as $file ) {
			$strings = $extractor->invoke( null, file_get_contents( dirname( __DIR__, 2 ) . '/' . $file ) );
			foreach ( $strings as $text ) {
				self::assertNotNull( EditorialEnglish::lookup( $text, 'en' ), $file . ': ' . $text );
			}
		}
	}

	public function testPlaceNamesBypassMachineTranslation(): void {
		Language::set( 'en' );
		foreach ( array( 'Granada', 'La Pineda', 'Palamós', 'Girona', 'Barcelona' ) as $place ) {
			self::assertSame( $place, EditorialEnglish::locationLabel( $place ) );
		}
		self::assertSame( 'Barcelona Airport T1', EditorialEnglish::locationLabel( 'Aeropuerto de Barcelona T1' ) );
		Language::set( 'es' );
		self::assertSame( 'Aeropuerto de Barcelona T1', EditorialEnglish::locationLabel( 'Aeropuerto de Barcelona T1' ) );
	}
}
