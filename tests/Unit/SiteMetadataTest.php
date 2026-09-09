<?php
declare(strict_types=1);

namespace MeTransfers\Tests\Unit;

use MeTransfers\SEO\SiteMetadata;
use PHPUnit\Framework\TestCase;

final class SiteMetadataTest extends TestCase {
	public function testBlogHasItsOwnTitleAndDescription(): void {
		foreach ( array( 'title', 'description' ) as $field ) {
			self::assertNotSame( SiteMetadata::text( true, false, $field ), SiteMetadata::text( false, true, $field ) );
			self::assertStringContainsString( 'blog', strtolower( SiteMetadata::text( false, true, $field ) ) );
		}
	}

	public function testFrontPageWinsWhenItAlsoContainsLatestPosts(): void {
		self::assertSame( SiteMetadata::text( true, false, 'title' ), SiteMetadata::text( true, true, 'title' ) );
		self::assertSame( '', SiteMetadata::text( false, false, 'title' ) );
	}
}
