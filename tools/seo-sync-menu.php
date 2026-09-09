<?php
/** WP-CLI: wp eval-file tools/seo-sync-menu.php [apply] */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit( 'WP-CLI required.' ); }
$apply = in_array( 'apply', $args ?? array(), true );
$changes = array();
foreach ( wp_get_nav_menus() as $menu ) {
    foreach ( wp_get_nav_menu_items( $menu->term_id ) ?: array() as $item ) {
        if ( 'custom' !== $item->type ) { continue; }
        $new_url = \MeTransfers\SEO\Links::normalize( $item->url );
        if ( $new_url !== $item->url ) {
            $changes[ $item->ID ] = array( 'before' => $item->url, 'after' => $new_url );
            WP_CLI::log( $item->ID . ': ' . $item->url . ' -> ' . $new_url );
        }
    }
}
if ( ! $apply || ! $changes ) { WP_CLI::success( $apply ? 'No changes required.' : 'Dry run. Pass apply to update these custom links.' ); return; }
$backup = 'mt_menu_backup_' . wp_generate_uuid4();
if ( ! add_option( $backup, $changes, '', false ) ) { WP_CLI::error( 'Could not save menu backup.' ); }
foreach ( $changes as $id => $change ) {
    if ( get_post_meta( $id, '_menu_item_url', true ) !== $change['before'] ) { WP_CLI::error( 'Menu changed during migration. Backup: ' . $backup ); }
    if ( ! update_post_meta( $id, '_menu_item_url', $change['after'] ) ) { WP_CLI::error( 'Could not update menu item ' . $id . '. Backup: ' . $backup ); }
}
WP_CLI::success( 'Menu URLs updated. Backup: ' . $backup );
