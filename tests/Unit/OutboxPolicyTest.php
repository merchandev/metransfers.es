<?php

declare(strict_types=1);

namespace MeTransfers\Tests\Unit;

use MeTransfers\Core\Outbox;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OutboxPolicyTest extends TestCase {
	#[DataProvider( 'backoffCases' )]
	public function testBackoffIsExponentialAndBounded( int $attempt, int $expected ): void {
		self::assertSame( $expected, Outbox::backoffSeconds( $attempt ) );
	}

	public static function backoffCases(): array {
		return array(
			'first'   => array( 1, 60 ),
			'second'  => array( 2, 120 ),
			'sixth'   => array( 6, 1920 ),
			'bounded' => array( 20, 3600 ),
		);
	}

	public function testSuccessfulDeliveryIsTerminal(): void {
		self::assertSame(
			array(
				'status' => 'processed',
				'delay'  => 0,
			),
			Outbox::outcomeForAttempt( 1, true )
		);
	}

	public function testMaximumAttemptEntersDeadLetterState(): void {
		self::assertSame(
			array(
				'status' => 'failed',
				'delay'  => 0,
			),
			Outbox::outcomeForAttempt( Outbox::MAX_ATTEMPTS, false )
		);
	}
}
