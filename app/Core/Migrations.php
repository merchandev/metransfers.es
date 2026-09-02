<?php
namespace MeTransfers\Core;

class Migrations {
	const OPTION_NAME = 'mt_platform_db_version';

	private $migration_steps;

	public function __construct( $migration_steps = null ) {
		if ( null !== $migration_steps && ! is_array( $migration_steps ) ) {
			throw new \InvalidArgumentException( 'Migration steps must be an array.' );
		}
		$this->migration_steps = $migration_steps;
	}

	public function register() {
		add_action( 'after_switch_theme', array( $this, 'run' ) );
		add_action( 'admin_init', array( $this, 'maybe_run' ), 1 );
		add_action( 'init', array( $this, 'maybe_run' ), 1 );
	}

	public function maybe_run() {
		$installed_version = (string) get_option( self::OPTION_NAME, '0.0.0' );
		if ( version_compare( $installed_version, MT_PLATFORM_DB_VERSION, '>=' ) ) {
			return true;
		}

		return $this->run();
	}

	public function run() {
		$installed_version = (string) get_option( self::OPTION_NAME, '0.0.0' );
		if ( version_compare( $installed_version, MT_PLATFORM_DB_VERSION, '>=' ) ) {
			return true;
		}

		if ( ! $this->acquireLock() ) {
			return false;
		}

		try {
			// Another request may have completed while this one waited for the lock.
			$installed_version = (string) get_option( self::OPTION_NAME, '0.0.0' );
			if ( version_compare( $installed_version, MT_PLATFORM_DB_VERSION, '>=' ) ) {
				return true;
			}

			$this->ensureJournal();
			$seen = array();
			foreach ( $this->migrations() as $migration ) {
				$this->validateMigration( $migration, $seen );
				$seen[] = $migration['id'];
				if ( version_compare( $migration['version'], $installed_version, '<=' )
					|| $this->journalSucceeded( $migration['id'] ) ) {
					continue;
				}

				$this->markRunning( $migration );
				try {
					call_user_func( $migration['callback'] );
					$this->markSucceeded( $migration );
				} catch ( \Throwable $error ) {
					$this->markFailed( $migration, $error );
					throw $error;
				}
			}

			update_option( self::OPTION_NAME, MT_PLATFORM_DB_VERSION, false );
			return true;
		} finally {
			$this->releaseLock();
		}
	}

	protected function migrations() {
		if ( null !== $this->migration_steps ) {
			return $this->migration_steps;
		}

		return array(
			array(
				'id'       => '20260819_001_core_schema',
				'version'  => '6.5.0',
				'callback' => array( Schema::class, 'installCoreTables' ),
			),
			array(
				'id'       => '20260819_002_event_schema',
				'version'  => '6.5.0',
				'callback' => array( Schema::class, 'installEventTables' ),
			),
			array(
				'id'       => '20260819_003_fleet_schema',
				'version'  => '6.5.0',
				'callback' => array( Schema::class, 'installFleetTables' ),
			),
			array(
				'id'       => '20260819_004_price_cents_backfill',
				'version'  => '6.5.0',
				'callback' => array( DataMigrations::class, 'backfillPriceCents' ),
			),
			array(
				'id'       => '20260819_005_booking_id_floor',
				'version'  => '6.5.0',
				'callback' => array( DataMigrations::class, 'ensureBookingIdFloor' ),
			),
			array(
				'id'       => '20260901_001_hotel_portal_booking_schema',
				'version'  => '6.6.0',
				'callback' => array( Schema::class, 'installCoreTables' ),
			),
			array(
				'id'       => '20260901_002_hotel_booking_backfill',
				'version'  => '6.6.0',
				'callback' => array( DataMigrations::class, 'backfillHotelBookingRelations' ),
			),
			array(
				'id'       => '20260901_003_hotel_user_assignments',
				'version'  => '6.6.0',
				'callback' => array( DataMigrations::class, 'backfillHotelUserAssignments' ),
			),
		);
	}

	protected function acquireLock() {
		global $wpdb;
		$result = $wpdb->get_var(
			$wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', $this->lockName() )
		);
		return '1' === (string) $result;
	}

	protected function releaseLock() {
		global $wpdb;
		$wpdb->get_var(
			$wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $this->lockName() )
		);
	}

	protected function ensureJournal() {
		Schema::installMigrationJournal();
	}

	protected function journalSucceeded( $migration_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'mt_schema_migrations';
		return 'succeeded' === (string) $wpdb->get_var(
			$wpdb->prepare( "SELECT status FROM $table WHERE migration_id = %s", $migration_id )
		);
	}

	protected function markRunning( array $migration ) {
		global $wpdb;
		$table  = $wpdb->prefix . 'mt_schema_migrations';
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO $table (migration_id, version, status, started_at, finished_at, error_code)
                 VALUES (%s, %s, 'running', %s, NULL, NULL)
                 ON DUPLICATE KEY UPDATE version = VALUES(version), status = 'running', started_at = VALUES(started_at), finished_at = NULL, error_code = NULL",
				$migration['id'],
				$migration['version'],
				gmdate( 'Y-m-d H:i:s' )
			)
		);
		if ( false === $result ) {
			throw new \RuntimeException( 'Unable to start schema migration journal entry.' );
		}
	}

	protected function markSucceeded( array $migration ) {
		global $wpdb;
		$table  = $wpdb->prefix . 'mt_schema_migrations';
		$result = $wpdb->update(
			$table,
			array(
				'status'      => 'succeeded',
				'finished_at' => gmdate( 'Y-m-d H:i:s' ),
				'error_code'  => null,
			),
			array( 'migration_id' => $migration['id'] ),
			array( '%s', '%s', '%s' ),
			array( '%s' )
		);
		if ( false === $result ) {
			throw new \RuntimeException( 'Unable to complete schema migration journal entry.' );
		}
	}

	protected function markFailed( array $migration, \Throwable $error ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'mt_schema_migrations',
			array(
				'status'      => 'failed',
				'finished_at' => gmdate( 'Y-m-d H:i:s' ),
				'error_code'  => substr( hash( 'sha256', get_class( $error ) . ':' . (string) $error->getCode() ), 0, 32 ),
			),
			array( 'migration_id' => $migration['id'] ),
			array( '%s', '%s', '%s' ),
			array( '%s' )
		);
	}

	private function lockName() {
		global $wpdb;
		$database = defined( 'DB_NAME' ) ? DB_NAME : '';
		return 'mt_schema_' . substr( hash( 'sha256', $database . '|' . $wpdb->prefix ), 0, 40 );
	}

	private function validateMigration( $migration, array $seen ) {
		if ( ! is_array( $migration )
			|| empty( $migration['id'] )
			|| empty( $migration['version'] )
			|| empty( $migration['callback'] )
			|| ! preg_match( '/^[a-z0-9_]+$/', (string) $migration['id'] )
			|| in_array( $migration['id'], $seen, true )
			|| ! is_callable( $migration['callback'] )
			|| version_compare( $migration['version'], MT_PLATFORM_DB_VERSION, '>' ) ) {
			throw new \LogicException( 'Invalid schema migration definition.' );
		}
	}
}
