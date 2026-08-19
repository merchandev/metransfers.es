<?php

class WPTB_Activator {
    /**
     * Compatibility facade for legacy activation callers.
     * Schema migrations and content seeds intentionally have separate owners.
     */
    public static function activate() {
        if ( defined( 'MT_PLATFORM_DB_VERSION' ) ) {
            $migrations = new \MeTransfers\Core\Migrations();
            if ( ! $migrations->run() ) {
                return false;
            }
        } else {
            \MeTransfers\Core\Schema::installCurrent();
            \MeTransfers\Core\DataMigrations::backfillPriceCents();
            \MeTransfers\Core\DataMigrations::ensureBookingIdFloor();
        }

        \MeTransfers\Core\Seeds::run();
        return true;
    }
}
