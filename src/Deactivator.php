<?php
/**
 * Plugin deactivator.
 *
 * Handles plugin deactivation tasks.
 *
 * @package SimpleAddBanners
 * @since   1.0.0
 */

namespace SimpleAddBanners;

/**
 * Deactivator class.
 *
 * Performs temporary cleanup when the plugin is deactivated.
 * Does NOT remove data - that happens in uninstall.php.
 *
 * @since 1.0.0
 */
class Deactivator {

	/**
	 * Deactivates the plugin.
	 *
	 * Performs temporary cleanup tasks. Data is preserved
	 * in case the plugin is reactivated.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Flush rewrite rules to remove any custom endpoints.
		flush_rewrite_rules();

		// Clear any scheduled events.
		self::clear_scheduled_events();
	}

	/**
	 * Clears scheduled cron events.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function clear_scheduled_events(): void {
		$timestamp = wp_next_scheduled( 'simple_add_banners_daily_cleanup' );

		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'simple_add_banners_daily_cleanup' );
		}
	}
}
