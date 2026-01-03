<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Removes all plugin data including database tables and options.
 *
 * @package SimpleAddBanners
 * @since   1.0.0
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop custom tables.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}sab_statistics`" );
$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}sab_banner_placement`" );
$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}sab_placements`" );
$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}sab_banners`" );
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange

// Delete plugin options.
delete_option( 'simple_add_banners_db_version' );
delete_option( 'simple_add_banners_tracking_secret' );

// Clear any scheduled events.
$simple_add_banners_timestamp = wp_next_scheduled( 'simple_add_banners_daily_cleanup' );
if ( $simple_add_banners_timestamp ) {
	wp_unschedule_event( $simple_add_banners_timestamp, 'simple_add_banners_daily_cleanup' );
}
