<?php

define( 'MT_PLATFORM_DB_VERSION', '6.6.0' );

$GLOBALS['mt_migration_options'] = array( 'mt_platform_db_version' => '6.4.0' );

function get_option( $name, $default = false ) {
    return array_key_exists( $name, $GLOBALS['mt_migration_options'] )
        ? $GLOBALS['mt_migration_options'][ $name ]
        : $default;
}

function update_option( $name, $value, $autoload = null ) {
    $GLOBALS['mt_migration_options'][ $name ] = $value;
    return true;
}

function assert_migration( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "FAILED: $message\n" );
        exit( 1 );
    }
}

function dbDelta( $sql ) {
    global $wpdb;
    $wpdb->last_error = ! empty( $GLOBALS['mt_schema_error'] ) ? 'simulated schema error' : '';
    return array();
}

require_once __DIR__ . '/../app/Core/Migrations.php';

class Test_Migrations extends \MeTransfers\Core\Migrations {
    public $lock_available = true;
    public $lock_attempts = 0;
    public $lock_releases = 0;
    public $journal_ready = false;
    public $journal = array();

    protected function acquireLock() {
        ++$this->lock_attempts;
        return $this->lock_available;
    }

    protected function releaseLock() {
        ++$this->lock_releases;
    }

    protected function ensureJournal() {
        $this->journal_ready = true;
    }

    protected function journalSucceeded( $migration_id ) {
        return isset( $this->journal[ $migration_id ] ) && 'succeeded' === $this->journal[ $migration_id ];
    }

    protected function markRunning( array $migration ) {
        $this->journal[ $migration['id'] ] = 'running';
    }

    protected function markSucceeded( array $migration ) {
        $this->journal[ $migration['id'] ] = 'succeeded';
    }

    protected function markFailed( array $migration, \Throwable $error ) {
        $this->journal[ $migration['id'] ] = 'failed';
    }
}

$calls = array();
$runner = new Test_Migrations(
    array(
        array( 'id' => '001_schema', 'version' => '6.6.0', 'callback' => function() use ( &$calls ) { $calls[] = 'schema'; } ),
        array( 'id' => '002_backfill', 'version' => '6.6.0', 'callback' => function() use ( &$calls ) { $calls[] = 'backfill'; } ),
    )
);

assert_migration( true === $runner->run(), 'A migration batch should complete when the lock is available.' );
assert_migration( array( 'schema', 'backfill' ) === $calls, 'Discrete migrations must execute in declared order.' );
assert_migration( $runner->journal_ready, 'The migration journal must exist before callbacks run.' );
assert_migration( 1 === $runner->lock_attempts && 1 === $runner->lock_releases, 'The migration lock must always be released.' );
assert_migration( '6.6.0' === get_option( 'mt_platform_db_version' ), 'The DB version must advance only after the whole batch succeeds.' );

$runner->run();
assert_migration( array( 'schema', 'backfill' ) === $calls, 'Completed versions must not execute again.' );

$GLOBALS['mt_migration_options']['mt_platform_db_version'] = '6.4.0';
$blocked_calls = 0;
$blocked = new Test_Migrations(
    array(
        array( 'id' => '003_blocked', 'version' => '6.6.0', 'callback' => function() use ( &$blocked_calls ) { ++$blocked_calls; } ),
    )
);
$blocked->lock_available = false;
assert_migration( false === $blocked->run() && 0 === $blocked_calls, 'A concurrent request must not run migrations without the lock.' );

$first_calls = 0;
$retry_calls = 0;
$retry = new Test_Migrations(
    array(
        array( 'id' => '004_first', 'version' => '6.6.0', 'callback' => function() use ( &$first_calls ) { ++$first_calls; } ),
        array(
            'id'       => '005_retry',
            'version'  => '6.6.0',
            'callback' => function() use ( &$retry_calls ) {
                ++$retry_calls;
                if ( 1 === $retry_calls ) {
                    throw new RuntimeException( 'simulated failure' );
                }
            },
        ),
    )
);

try {
    $retry->run();
    assert_migration( false, 'A failed migration must propagate its failure.' );
} catch ( RuntimeException $error ) {
    assert_migration( 'simulated failure' === $error->getMessage(), 'The original migration failure must be preserved.' );
}
assert_migration( '6.4.0' === get_option( 'mt_platform_db_version' ), 'A partial batch must not advance the DB version.' );
assert_migration( 'succeeded' === $retry->journal['004_first'] && 'failed' === $retry->journal['005_retry'], 'The journal must distinguish completed and failed steps.' );

assert_migration( true === $retry->run(), 'A failed migration must be resumable.' );
assert_migration( 1 === $first_calls && 2 === $retry_calls, 'Resume must skip successful steps and retry only the failed step.' );
assert_migration( '6.6.0' === get_option( 'mt_platform_db_version' ), 'A resumed successful batch must advance the DB version.' );
assert_migration( 2 === $retry->lock_releases, 'The lock must be released after both failure and success.' );

class Test_Schema_WPDB {
    public $prefix = 'wp_';
    public $last_error = '';

    public function get_charset_collate() {
        return '';
    }
}

$wpdb = new Test_Schema_WPDB();
$GLOBALS['wpdb'] = $wpdb;
$GLOBALS['mt_schema_error'] = false;
require_once __DIR__ . '/../app/Core/Schema.php';
\MeTransfers\Core\Schema::installMigrationJournal();
$GLOBALS['mt_schema_error'] = true;
$schema_failure_detected = false;
try {
    \MeTransfers\Core\Schema::installMigrationJournal();
} catch ( RuntimeException $error ) {
    $schema_failure_detected = true;
}
assert_migration( $schema_failure_detected, 'A dbDelta failure must abort the migration instead of being journaled as successful.' );

$root = dirname( __DIR__ );
$migration_source = file_get_contents( $root . '/app/Core/Migrations.php' );
$schema_source = file_get_contents( $root . '/app/Core/Schema.php' );
$seed_source = file_get_contents( $root . '/app/Core/Seeds.php' );
$activator_source = file_get_contents( $root . '/app/Legacy/WPTB/includes/class-wptb-activator.php' );
assert_migration( false !== strpos( $migration_source, 'SELECT GET_LOCK' ) && false !== strpos( $migration_source, 'SELECT RELEASE_LOCK' ), 'Production migrations must use a connection-scoped advisory lock.' );
assert_migration( false !== strpos( $schema_source, 'mt_schema_migrations' ) && false !== strpos( $schema_source, 'UNIQUE KEY migration_id' ), 'The schema must include a unique migration journal.' );
assert_migration( false !== strpos( $schema_source, 'hotel_id bigint(20) unsigned DEFAULT NULL' ) && false !== strpos( $schema_source, 'created_by_user_id bigint(20) unsigned DEFAULT NULL' ), 'The booking schema must persist Hotel ownership and creator attribution.' );
assert_migration( false !== strpos( $schema_source, 'KEY hotel_status_date (hotel_id, status, booking_date)' ), 'The booking schema must index Hotel-scoped operational queries.' );
assert_migration( false !== strpos( $migration_source, '20260901_001_hotel_portal_booking_schema' ) && false !== strpos( $migration_source, '20260901_003_hotel_user_assignments' ), 'Hotel Portal migration identifiers must remain registered and immutable.' );
assert_migration( false === strpos( $migration_source, 'wp_insert_post' ) && false !== strpos( $seed_source, 'ensurePage' ), 'Content seeds must not run inside schema migration callbacks.' );
assert_migration( false === strpos( $activator_source, 'dbDelta(' ) && false === strpos( $activator_source, 'CREATE TABLE' ), 'The legacy activator must remain a compatibility facade rather than own schema SQL.' );

echo "Migration orchestration tests passed.\n";
