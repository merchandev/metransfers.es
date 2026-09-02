<?php

declare(strict_types=1);

namespace MeTransfers\Tests\Unit;
use MeTransfers\HotelPortal\Auth\AdminRedirects;
use MeTransfers\HotelPortal\Routing\Router;
use PHPUnit\Framework\TestCase;

final class HotelPortalRoutingTest extends TestCase {
	public function testPortalRoutesAreExplicitAndBookingDetailIsNumeric(): void {
		$rules = Router::rules();

		self::assertSame( 'index.php?mt_hotel_portal=login', $rules['^hoteles/?$'] );
		self::assertSame( 'index.php?mt_hotel_portal=dashboard', $rules['^hoteles/dashboard/?$'] );
		self::assertArrayHasKey( '^hoteles/reservas/([0-9]+)/?$', $rules );
		self::assertSame( 'index.php?mt_hotel_portal=users', $rules['^hoteles/usuarios/?$'] );
		self::assertSame( 'index.php?mt_hotel_portal=import', $rules['^hoteles/importar/?$'] );
		self::assertCount( 11, $rules );
	}

	public function testPortalQueryVarsAndUrlsAreIsolated(): void {
		self::assertSame(
			array( 'existing', 'mt_hotel_portal', 'mt_hotel_booking_id' ),
			Router::queryVars( array( 'existing' ) )
		);
		self::assertSame( 'https://example.test/hoteles/', Router::url() );
		self::assertSame( 'https://example.test/hoteles/dashboard/', Router::url( 'dashboard' ) );
		self::assertSame( 'https://example.test/hoteles/reservas/', Router::url( 'bookings' ) );
		self::assertSame( 'https://example.test/hoteles/reservas/nueva/', Router::url( 'booking-new' ) );
		self::assertSame( 'https://example.test/hoteles/clientes/', Router::url( 'customers' ) );
		self::assertSame( 'https://example.test/hoteles/estadisticas/', Router::url( 'statistics' ) );
		self::assertSame( 'https://example.test/hoteles/perfil/', Router::url( 'profile' ) );
		self::assertSame( 'https://example.test/hoteles/usuarios/', Router::url( 'users' ) );
		self::assertSame( 'https://example.test/hoteles/importar/', Router::url( 'import' ) );
	}

	public function testEverySidebarSectionIsRenderedAsALink(): void {
		$sidebar = file_get_contents( dirname( __DIR__, 2 ) . '/app/HotelPortal/Views/partials/sidebar.php' );

		self::assertSame( 10, substr_count( $sidebar, '<a ' ) );
		self::assertStringNotContainsString( 'aria-disabled="true"', $sidebar );
	}

	public function testNoVisiblePortalRouteUsesAPhasePlaceholder(): void {

		$root   = dirname( __DIR__, 2 ) . '/app/HotelPortal';
		$router = file_get_contents( $root . '/Routing/Router.php' );

		self::assertFileDoesNotExist( $root . '/Views/section.php' );
		self::assertStringNotContainsString( 'fase posterior', strtolower( $router ) );
		self::assertStringNotContainsString( 'Sección en preparación', $router );
	}

	public function testHotelAdministrationHasAnIndependentMenuAndAccessScreen(): void {

			$root  = dirname( __DIR__, 2 );
		$menu      = file_get_contents( $root . '/app/Admin/Menu.php' );
			$users = file_get_contents( $root . '/app/Admin/HotelUsersPage.php' );

		self::assertSame( 1, substr_count( $menu, "'hotel-qr-reservations'" ) );
			self::assertStringContainsString( "'mt-hoteles-hub'", $menu );
		self::assertStringContainsString( "'mt-hotel-users'", $menu );
		self::assertStringContainsString( 'Reservas de Hoteles', $menu );
		self::assertStringContainsString( 'HotelAccess::BLOCKED_META_KEY', $users );
		self::assertStringContainsString( 'wp_verify_nonce', $users );
		self::assertStringContainsString( 'hotel.user.blocked', $users );
		self::assertStringContainsString( 'hotel.user.unblocked', $users );
	}
	public function testOnlyNonAdminHotelRoleIsRedirectedFromWpAdmin(): void {
		self::assertTrue( AdminRedirects::isHotelUser( (object) array( 'roles' => array( 'check_hoteles' ) ) ) );
		self::assertFalse( AdminRedirects::isHotelUser( (object) array( 'roles' => array( 'check_hoteles', 'administrator' ) ) ) );
		self::assertFalse( AdminRedirects::isHotelUser( (object) array( 'roles' => array( 'subscriber' ) ) ) );
	}

	public function testSecurityContractsRemainInPortalSources(): void {
		$root   = dirname( __DIR__, 2 );
		$router = file_get_contents( $root . '/app/HotelPortal/Routing/Router.php' );
		$auth   = file_get_contents( $root . '/app/HotelPortal/Auth/AuthController.php' );
		$script = file_get_contents( $root . '/assets/js/hotel-portal.js' );

		self::assertStringContainsString( 'X-Robots-Tag: noindex, nofollow, noarchive', $router );
		self::assertStringContainsString( 'nocache_headers()', $router );
		self::assertStringContainsString( 'wp_verify_nonce', $auth );
		self::assertStringContainsString( 'RequestRateLimiter::consume', $auth );
		self::assertStringContainsString( "get_user_by( 'email'", $auth );
		self::assertStringContainsString( "get_user_by( 'login'", $auth );
		self::assertStringContainsString( 'wp_authenticate_username_password', $auth );
		self::assertStringContainsString( 'wp_set_auth_cookie', $auth );
		self::assertStringNotContainsString( 'wp_signon', $auth );
		self::assertStringNotContainsString( 'wp_ajax_nopriv', $router . $auth );
		self::assertStringNotContainsString( 'wptb_vars', $script );
	}

	public function testHotelBookingUsesOnlyAuthoritativeServerQuotes(): void {

		$root       = dirname( __DIR__, 2 );
		$operations = file_get_contents( $root . '/app/HotelPortal/Services/HotelOperations.php' );
		$controller = file_get_contents( $root . '/app/HotelPortal/Services/HotelBookingController.php' );
		$view       = file_get_contents( $root . '/app/HotelPortal/Views/booking-new.php' );
		$portal_js  = file_get_contents( $root . '/assets/js/hotel-portal-booking.js' );

		self::assertStringContainsString( 'QuoteService::createVehicleList', $controller );
		self::assertStringContainsString( 'QuoteService::create', $operations );
		self::assertStringContainsString( 'VehicleCapacityPolicy::validate', $operations );
		self::assertStringContainsString( 'new Money', $operations );
		self::assertStringNotContainsString( "\$get( 'price' )", $operations );
		self::assertStringNotContainsString( 'name="price"', $view );
		self::assertStringNotContainsString( 'booking-app.js', $portal_js );
	}

	public function testGlobalSupervisorUsesAggregateBookingViews(): void {

		$root          = dirname( __DIR__, 2 );
			$dashboard = file_get_contents( $root . '/app/HotelPortal/Services/HotelDashboard.php' );
		$operations    = file_get_contents( $root . '/app/HotelPortal/Services/HotelOperations.php' );
		$topbar        = file_get_contents( $root . '/app/HotelPortal/Views/partials/topbar.php' );

		self::assertStringContainsString( 'HotelAccess::hasGlobalAccess()', $dashboard );
		self::assertStringContainsString( 'HotelAccess::hasGlobalAccess()', $operations );
		self::assertStringContainsString( 'Todos los hoteles', $topbar );
	}
}
