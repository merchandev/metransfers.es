<?php
declare(strict_types=1);

namespace MeTransfers\Tests\Unit;

use MeTransfers\SEO\Indexability;
use MeTransfers\SEO\LegacyUrlMap;
use MeTransfers\SEO\Policy;
use MeTransfers\SEO\Redirects;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/Support/SeoWordPress.php';

final class SeoPolicyTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['mt_seo_posts']       = array(
			1 => (object) array(
				'ID'            => 1,
				'post_status'   => 'publish',
				'post_type'     => 'ruta',
				'post_name'     => 'barcelona-salou',
				'post_title'    => 'Barcelona - Salou',
				'post_content'  => 'Contenido de Salou.',
				'post_modified' => '2026-09-08 10:00:00',
			),
		);
		$GLOBALS['mt_test_post_meta']  = array();
		$GLOBALS['mt_seo_options']     = array();
		$GLOBALS['mt_seo_404']         = false;
		$GLOBALS['mt_seo_environment'] = 'production';
		$GLOBALS['mt_test_get_posts']  = static function () {
			return array_values( $GLOBALS['mt_seo_posts'] );
		};
	}

	protected function tearDown(): void {
		unset( $GLOBALS['mt_test_get_posts'], $GLOBALS['mt_test_post_meta'], $GLOBALS['mt_seo_posts'], $GLOBALS['mt_seo_options'], $GLOBALS['mt_seo_404'], $GLOBALS['mt_seo_environment'] );
	}

	public function testAllLegacyAliasesResolveInOneHopAndPreserveLanguage(): void {
		foreach ( LegacyUrlMap::getMap() as $source => $target ) {
			foreach ( array( 'es', 'en' ) as $language ) {
				$prefix = 'es' === $language ? '/' : '/en/';
				$result = Redirects::targetForRequest( $prefix . $source . '/?utm_source=test', array( 'es', 'en' ) );
				self::assertSame( rtrim( $prefix . $target, '/' ) . '/?utm_source=test', $result );
				self::assertNull( Redirects::targetForRequest( $result, array( 'es', 'en' ) ) );
			}
		}
	}

	public function testUnknownPathsAndLanguagesAreNotRedirected(): void {
		self::assertNull( Redirects::targetForRequest( '/unknown/', array( 'es', 'en' ) ) );
		self::assertNull( Redirects::targetForRequest( '/xx/empresas/', array( 'es', 'en' ) ) );
		self::assertSame( '/corporativo-y-eventos/', Redirects::targetForRequest( '/es/empresas', array( 'es', 'en' ) ) );
	}

	public function testGrandfatheredRouteIsIndexableUntilExplicitlyRejected(): void {
		self::assertTrue( Indexability::isIndexable( 1 ) );
		self::assertTrue( Indexability::isIndexableRequest() );
		self::assertSame( array(), Policy::excludedPosts( array() ) );
		self::assertSame( array( 'index' => true ), Policy::robots( array( 'index' => true ) ) );

		$GLOBALS['mt_test_post_meta'][1]['_mt_seo_ready'] = '0';
		self::assertFalse( Indexability::isIndexable( 1 ) );
		self::assertSame( array( 1 ), Policy::excludedPosts( array() ) );
	}

	public function testReviewedRouteIsEligibleEverywhere(): void {
		$GLOBALS['mt_test_post_meta'][1]['_mt_seo_ready'] = '1';
		self::assertTrue( Indexability::isIndexable( 1 ) );
		self::assertTrue( Indexability::isIndexableRequest() );
		self::assertSame( array(), Policy::excludedPosts( array() ) );
		self::assertSame( array( 'index' => true ), Policy::robots( array( 'index' => true ) ) );
	}

	public function testManualNoindexAndCanonicalOverrideReadiness(): void {
		$GLOBALS['mt_test_post_meta'][1]['_mt_seo_ready'] = '1';
		foreach ( array(
			'_mt_seo_noindex'                  => '1',
			'_yoast_wpseo_meta-robots-noindex' => '1',
			'_yoast_wpseo_canonical'           => 'https://example.test/another/',
		) as $key => $value ) {
			$GLOBALS['mt_test_post_meta'][1][ $key ] = $value;
			self::assertFalse( Indexability::isIndexable( 1 ) );
			unset( $GLOBALS['mt_test_post_meta'][1][ $key ] );
		}
	}

	public function testLegacyAndTransactionalPagesStayExcluded(): void {
		$GLOBALS['mt_seo_posts'][2]                       = (object) array(
			'ID'          => 2,
			'post_status' => 'publish',
			'post_type'   => 'ruta',
			'post_name'   => 'barcelona-salou',
		);
		$GLOBALS['mt_test_post_meta'][2]['_mt_seo_ready'] = '1';
		$GLOBALS['mt_seo_posts'][1]->post_type            = 'page';
		foreach ( array( 'salou-traslados', 'pago', 'finalizar-pago', 'reservaciones', 'gracias' ) as $slug ) {
			$GLOBALS['mt_seo_posts'][1]->post_name = $slug;
			self::assertFalse( Indexability::isIndexable( 1 ) );
		}
	}

	public function testStagingPrivateSiteAnd404CannotBeIndexed(): void {
		$GLOBALS['mt_test_post_meta'][1]['_mt_seo_ready'] = '1';
		$GLOBALS['mt_seo_environment']                    = 'staging';
		self::assertFalse( Indexability::isIndexableRequest() );
		$GLOBALS['mt_seo_environment']            = 'production';
		$GLOBALS['mt_seo_options']['blog_public'] = '0';
		self::assertFalse( Indexability::isIndexableRequest() );
		$GLOBALS['mt_seo_options'] = array();
		$GLOBALS['mt_seo_404']     = true;
		self::assertFalse( Indexability::isIndexableRequest() );
	}

	public function testAutoloadPathsMatchCaseOnLinux(): void {
		foreach ( array( Indexability::class, LegacyUrlMap::class, Policy::class, Redirects::class ) as $class ) {
			$parts = explode( '\\', $class );
			self::assertSame( 'SEO', $parts[1] );
			self::assertContains( $parts[1], scandir( dirname( __DIR__, 2 ) . '/app' ) );
			self::assertFileExists( dirname( __DIR__, 2 ) . '/app/' . $parts[1] . '/' . $parts[2] . '.php' );
		}
	}

	public function testHreflangRequiresIndexablePostAndApprovedActiveLanguage(): void {
		self::assertSame( array( 'es' ), Indexability::languagesForPost( 1, array( 'es', 'xx' ) ) );
		$GLOBALS['mt_test_post_meta'][1]['_mt_seo_ready'] = '0';
		self::assertSame( array(), Indexability::languagesForPost( 1, array( 'es', 'xx' ) ) );
		$GLOBALS['mt_test_post_meta'][1]['_mt_seo_ready'] = '1';
		self::assertSame( array( 'es' ), Indexability::languagesForPost( 1, array( 'es', 'xx' ) ) );
		$GLOBALS['mt_test_post_meta'][1]['_yoast_wpseo_meta-robots-noindex'] = '1';
		self::assertSame( array(), Indexability::languagesForPost( 1, array( 'es' ) ) );
	}

	public function testDestinationPagesAreAlwaysConsolidatedToRoutes(): void {
		foreach ( array( 'salou', 'lloret-de-mar', 'andorra', 'unknown' ) as $slug ) {
			self::assertFalse( Indexability::isIndexableDestination( $slug ) );
		}
	}

	public function testMissingLegacyReadinessCanReceiveRedirects(): void {
		self::assertTrue( Indexability::isIndexable( 1 ) );
		self::assertSame( '/rutas/barcelona-salou/', Redirects::verifiedTarget( '/taxis-barcelona-salou/' ) );
		$GLOBALS['mt_test_post_meta'][1]['_mt_seo_ready'] = '0';
		self::assertFalse( Indexability::isIndexable( 1 ) );
		self::assertNull( Redirects::verifiedTarget( '/taxis-barcelona-salou/' ) );
	}

	public function testRedirectTargetsMustExistBeIndexableAndSelfCanonical(): void {
		self::assertNull( Redirects::verifiedTarget( '/taxis-barcelona-cadaques/' ) );
		self::assertSame( '/rutas/barcelona-salou/', Redirects::verifiedTarget( '/taxis-barcelona-salou/' ) );
		$GLOBALS['mt_test_post_meta'][1]['_yoast_wpseo_canonical'] = 'https://example.test/other/';
		self::assertNull( Redirects::verifiedTarget( '/taxis-barcelona-salou/' ) );
	}

	public function testCostaBravaAliasesResolveToCanonicalBootstrapRoute(): void {
		foreach ( array( 'costa-brava-taxis', 'costa-brava-traslados', 'taxis-barcelona-costa-brava' ) as $slug ) {
			self::assertSame( '/rutas/barcelona-costa-brava/', Redirects::targetForRequest( '/' . $slug . '/', array( 'es', 'en' ) ) );
		}
	}

	public function testVariantApprovalIsPerUrlAndInvalidatedByContentChanges(): void {
		$post = $GLOBALS['mt_seo_posts'][1];
		$GLOBALS['mt_test_post_meta'][1]['_mt_seo_ready'] = '1';
		// EN solo se anuncia/indexa cuando la variante concreta ha sido
		// revisada explícitamente; sin registro de aprobación no hay opt-in.
		self::assertSame( array( 'es' ), Indexability::languagesForPost( 1, array( 'es', 'en' ) ) );
		$GLOBALS['mt_test_post_meta'][1]['_mt_seo_variant_en'] = array(
			'translated_reviewed' => true,
			'http_status'         => 200,
			'canonical'           => 'https://example.test/en/rutas/barcelona-salou/',
			'source_hash'         => \MeTransfers\SEO\Variants::fingerprint( $post ),
		);
		self::assertSame( array( 'es', 'en' ), Indexability::languagesForPost( 1, array( 'es', 'en' ) ) );
		$post->post_content = 'Contenido nuevo sin revisar';
		self::assertSame( array( 'es' ), Indexability::languagesForPost( 1, array( 'es', 'en' ) ) );
	}

	public function testInternalLinksResolveOnlyVerifiedTargetsAndKeepFragments(): void {
		$url = 'https://metransfers.es/taxis-barcelona-salou/?utm_source=menu#faq';
		self::assertSame( 'https://metransfers.es/rutas/barcelona-salou/?utm_source=menu#faq', \MeTransfers\SEO\Links::normalize( $url ) );
		self::assertSame( 'https://other.test/taxis-barcelona-salou/', \MeTransfers\SEO\Links::normalize( 'https://other.test/taxis-barcelona-salou/' ) );
	}

	public function testRouteMetadataHasUsefulFallbacks(): void {
		$GLOBALS['mt_test_post_meta'][1]['_mt_ruta_origen']  = 'Barcelona centro';
		$GLOBALS['mt_test_post_meta'][1]['_mt_ruta_destino'] = 'Salou';
		self::assertSame( 'Transfer Barcelona - Salou | MeTransfers', \MeTransfers\SEO\Meta::routeText( $GLOBALS['mt_seo_posts'][1], 'title' ) );
		self::assertStringContainsString( 'Barcelona a Salou', \MeTransfers\SEO\Meta::routeText( $GLOBALS['mt_seo_posts'][1], 'description' ) );
	}

	public function testNavigationDoesNotLinkToDraftOrMissingTargets(): void {
		$url                                     = 'https://metransfers.es/taxis-barcelona-salou/';
		$GLOBALS['mt_seo_posts'][1]->post_status = 'draft';
		self::assertSame( $url, \MeTransfers\SEO\Links::normalize( $url ) );
		unset( $GLOBALS['mt_seo_posts'][1] );
		self::assertSame( $url, \MeTransfers\SEO\Links::normalize( $url ) );
		self::assertSame( '/traslados-privados/', Redirects::targetForRequest( '/taxis-privado-barcelona/', array( 'es' ) ) );
		self::assertNull( Redirects::targetForRequest( '/traslados-privados/', array( 'es' ) ) );
	}

	public function testUnreviewedEnglishLegacyNavigationFallsBackToSpanishCanonical(): void {
		$url = 'https://metransfers.es/en/taxis-barcelona-salou/?utm_source=menu#faq';
		self::assertSame( 'https://metransfers.es/rutas/barcelona-salou/?utm_source=menu#faq', \MeTransfers\SEO\Links::normalize( $url ) );
	}

	public function testApprovedEnglishVariantKeepsEnglishPrefix(): void {
		// Un registro explícito y válido (revisado, hash y canonical vigentes)
		// es el único camino para que un enlace conserve el prefijo /en/.
		$GLOBALS['mt_test_post_meta'][1]['_mt_seo_variant_en'] = array(
			'translated_reviewed' => true,
			'http_status'         => 200,
			'canonical'           => 'https://example.test/en/rutas/barcelona-salou/',
			'source_hash'         => \MeTransfers\SEO\Variants::fingerprint( $GLOBALS['mt_seo_posts'][1] ),
		);
		$url = 'https://metransfers.es/en/taxis-barcelona-salou/?utm_source=menu#faq';
		self::assertSame( 'https://metransfers.es/en/rutas/barcelona-salou/?utm_source=menu#faq', \MeTransfers\SEO\Links::normalize( $url ) );
	}
}
