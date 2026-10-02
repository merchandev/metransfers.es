<?php
declare(strict_types=1);

namespace MeTransfers\Tests\Unit;

use MeTransfers\SEO\LanguageSitemap;
use MeTransfers\SEO\VariantApproval;
use MeTransfers\SEO\Variants;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/Support/SeoWordPress.php';
require_once dirname( __DIR__ ) . '/Support/VariantWordPress.php';

final class VariantApprovalTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['mt_seo_posts'] = array();
		foreach ( array( 1, 2 ) as $id ) {
			$GLOBALS['mt_seo_posts'][ $id ] = (object) array(
				'ID'            => $id,
				'post_type'     => 'post',
				'post_status'   => 'publish',
				'post_password' => '',
				'post_name'     => 'review-' . $id,
				'post_title'    => 'Title ' . $id,
				'post_content'  => 'Content ' . $id,
				'post_excerpt'  => '',
				'post_modified' => '2026-10-02 10:00:00',
			);
		}
		$GLOBALS['mt_test_post_meta'] = array();
		$GLOBALS['mt_seo_options']    = array();
		$GLOBALS['mt_variant_http']   = array();
		$GLOBALS['mt_test_get_posts'] = static function () {
			return array_values( $GLOBALS['mt_seo_posts'] );
		};
	}

	protected function tearDown(): void {
		unset( $GLOBALS['mt_seo_posts'], $GLOBALS['mt_test_post_meta'], $GLOBALS['mt_seo_options'], $GLOBALS['mt_variant_http'], $GLOBALS['mt_variant_failed_id'], $GLOBALS['mt_test_get_posts'] );
	}

	private function rows(): array {
		return array(
			array(
				'id'                 => 1,
				'language'           => 'en',
				'editorial_approved' => true,
			),
			array(
				'id'                 => 2,
				'language'           => 'en',
				'editorial_approved' => true,
			),
		);
	}

	public function testInvalidEditorialHttpAndDuplicateRowsCannotPartiallyApprove(): void {
		$rows                          = $this->rows();
		$rows[1]['editorial_approved'] = false;
		$batch                         = VariantApproval::prepare( $rows );
		self::assertNotEmpty( VariantApproval::apply( $batch ) );
		self::assertSame( array(), $GLOBALS['mt_test_post_meta'] );
		$GLOBALS['mt_variant_http'][ Variants::canonical( 2, 'en' ) ] = 301;
		self::assertSame( array(), VariantApproval::prepare( $this->rows() )['variants'] );
		self::assertSame( array(), VariantApproval::prepare( array( $rows[0], $rows[0] ) )['variants'] );
		self::assertSame( array(), $GLOBALS['mt_test_post_meta'] );
	}

	public function testEditsDuringValidationPreventAllWrites(): void {
		$batch                                    = VariantApproval::prepare( $this->rows() );
		$GLOBALS['mt_seo_posts'][2]->post_content = 'New content';
		self::assertNotEmpty( VariantApproval::apply( $batch ) );
		self::assertSame( array(), $GLOBALS['mt_test_post_meta'] );
		$rows                   = $this->rows();
		$rows[0]['source_hash'] = 'outdated review';
		self::assertNotEmpty( VariantApproval::prepare( $rows )['errors'] );
	}

	public function testFailedWriteRestoresTheEarlierApproval(): void {
		$GLOBALS['mt_test_post_meta'][1]['_mt_seo_variant_en'] = array( 'previous' => 'review' );
		$before                          = $GLOBALS['mt_test_post_meta'];
		$GLOBALS['mt_variant_failed_id'] = 2;
		self::assertNotEmpty( VariantApproval::apply( VariantApproval::prepare( $this->rows() ) ) );
		self::assertSame( $before[1], $GLOBALS['mt_test_post_meta'][1] );
		self::assertFalse( Variants::isApproved( 2, 'en' ) );
	}

	public function testReviewedVariantsKeepApprovalOnUnchangedSaveAndExpireOnMaterialEdit(): void {
		self::assertSame( array(), VariantApproval::apply( VariantApproval::prepare( $this->rows() ) ) );
		self::assertTrue( Variants::isApproved( 1, 'en' ) );
		$GLOBALS['mt_seo_posts'][1]->post_modified = '2026-10-02 15:00:00';
		self::assertTrue( Variants::isApproved( 1, 'en' ) );
		$GLOBALS['mt_test_post_meta'][1]['_yoast_wpseo_metadesc'] = 'Different description';
		self::assertFalse( Variants::isApproved( 1, 'en' ) );
	}

	public function testLegacyApprovalIsStillAcceptedWithItsOriginalFingerprint(): void {
		$GLOBALS['mt_test_post_meta'][1]['_mt_seo_variant_en'] = array(
			'translated_reviewed' => true,
			'http_status'         => 200,
			'canonical'           => Variants::canonical( 1, 'en' ),
			'source_hash'         => Variants::legacyFingerprint( 1 ),
		);
		self::assertTrue( Variants::isApproved( 1, 'en' ) );
		$GLOBALS['mt_seo_posts'][1]->post_content = 'Changed';
		self::assertFalse( Variants::isApproved( 1, 'en' ) );
	}

	public function testSitemapIncludesOnlyReviewedPublicVariants(): void {
		self::assertSame( array(), LanguageSitemap::urls() );
		self::assertSame( array(), VariantApproval::apply( VariantApproval::prepare( $this->rows() ) ) );
		self::assertCount( 2, LanguageSitemap::urls() );
		$GLOBALS['mt_seo_posts'][2]->post_password = 'private';
		self::assertSame( array( array( 'loc' => Variants::canonical( 1, 'en' ) ) ), LanguageSitemap::urls() );
		$GLOBALS['mt_seo_options']['blog_public'] = '0';
		self::assertSame( array(), LanguageSitemap::urls() );
	}
}
