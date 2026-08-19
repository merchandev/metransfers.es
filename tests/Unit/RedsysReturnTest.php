<?php

declare(strict_types=1);

namespace MeTransfers\Tests\Unit;

use InvalidArgumentException;
use MeTransfers\Payments\Redsys\Gateway;
use PHPUnit\Framework\TestCase;

final class RedsysReturnTest extends TestCase {
	public function testSuccessfulAndFailedReturnsUseOrderBoundTokens(): void {
		$order = '1234ABCD';
		$token = Gateway::confirmation_token( $order );

		self::assertTrue( Gateway::validate_confirmation_request( 'ok', $order, $token )['valid'] );
		self::assertTrue( Gateway::validate_confirmation_request( 'ko', $order, $token )['valid'] );
	}

	public function testTamperedOrderOrTokenIsRejected(): void {
		$token = Gateway::confirmation_token( '1234ABCD' );

		self::assertFalse( Gateway::validate_confirmation_request( 'ok', '1234ABCE', $token )['valid'] );
		self::assertFalse( Gateway::validate_confirmation_request( 'ok', '1234ABCD', str_repeat( '0', 64 ) )['valid'] );
		self::assertFalse( Gateway::validate_confirmation_request( 'maybe', '1234ABCD', $token )['valid'] );
	}

	public function testConfirmationUrlPreservesSupportedLanguagePrefix(): void {
		$url = Gateway::confirmation_url( '1234ABCD', 'ko', 'en' );

		self::assertStringStartsWith( 'https://example.test/en/reservas-metransfers/?payment_result=ko', $url );
		self::assertStringContainsString( '&token=', $url );
	}

	public function testEmptyOrderIsRejected(): void {
		$this->expectException( InvalidArgumentException::class );
		Gateway::confirmation_token( '' );
	}
}
