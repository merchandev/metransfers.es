<?php
namespace MeTransfers\Core;

class Migrations {
    const OPTION_NAME = 'mt_platform_db_version';

    public function register() {
        add_action( 'after_switch_theme', array( $this, 'run' ) );
        add_action( 'admin_init', array( $this, 'maybe_run' ), 1 );
        add_action( 'init', array( $this, 'maybe_run' ), 1 );
    }

    public function maybe_run() {
        $installed_version = (string) get_option( self::OPTION_NAME, '0.0.0' );
        if ( version_compare( $installed_version, MT_PLATFORM_DB_VERSION, '>=' ) ) {
            return;
        }

        $this->run();
    }

    public function run() {
        if ( ! class_exists( 'WPTB_Activator' ) ) {
            throw new \RuntimeException( 'WPTB_Activator is unavailable.' );
        }

        \WPTB_Activator::activate();
        update_option( self::OPTION_NAME, MT_PLATFORM_DB_VERSION, false );
    }
}
