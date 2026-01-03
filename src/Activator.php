<?php
/**
 * Plugin activator.
 *
 * Handles plugin activation tasks including database table creation.
 *
 * @package SimpleAddBanners
 * @since   1.0.0
 */

namespace SimpleAddBanners;

/**
 * Activator class.
 *
 * Creates database tables and sets up initial plugin options
 * when the plugin is activated.
 *
 * @since 1.0.0
 */
class Activator {

	/**
	 * Database version.
	 *
	 * Increment this when making schema changes to trigger updates.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const DB_VERSION = '1.0.0';

	/**
	 * Option name for storing database version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const DB_VERSION_OPTION = 'simple_add_banners_db_version';

	/**
	 * Activates the plugin.
	 *
	 * Creates database tables and sets initial options.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function activate(): void {
		self::create_tables();
		self::add_options();
		self::set_db_version();
	}

	/**
	 * Checks if database needs updating.
	 *
	 * Called on plugins_loaded to handle plugin updates,
	 * since activation hook doesn't fire on updates.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function maybe_update(): void {
		$installed_version = get_option( self::DB_VERSION_OPTION );

		if ( self::DB_VERSION !== $installed_version ) {
			self::create_tables();
			self::set_db_version();
		}
	}

	/**
	 * Creates all plugin database tables.
	 *
	 * Uses dbDelta() to create or update tables safely.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		self::create_banners_table( $charset_collate );
		self::create_placements_table( $charset_collate );
		self::create_banner_placement_table( $charset_collate );
		self::create_statistics_table( $charset_collate );
	}

	/**
	 * Creates the banners table.
	 *
	 * Stores banner creative assets, URLs, scheduling, and settings.
	 *
	 * @since 1.0.0
	 *
	 * @param string $charset_collate Database charset and collation.
	 * @return void
	 */
	private static function create_banners_table( string $charset_collate ): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'sab_banners';

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			desktop_image_id bigint(20) UNSIGNED DEFAULT NULL,
			mobile_image_id bigint(20) UNSIGNED DEFAULT NULL,
			desktop_url varchar(2048) NOT NULL,
			mobile_url varchar(2048) DEFAULT NULL,
			start_date datetime DEFAULT NULL,
			end_date datetime DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			weight int(11) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY start_date (start_date),
			KEY end_date (end_date)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Creates the placements table.
	 *
	 * Stores placement configuration including rotation strategy.
	 *
	 * @since 1.0.0
	 *
	 * @param string $charset_collate Database charset and collation.
	 * @return void
	 */
	private static function create_placements_table( string $charset_collate ): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'sab_placements';

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			slug varchar(100) NOT NULL,
			name varchar(255) NOT NULL,
			rotation_strategy varchar(20) NOT NULL DEFAULT 'random',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Creates the banner-placement pivot table.
	 *
	 * Links banners to placements with optional ordering.
	 *
	 * @since 1.0.0
	 *
	 * @param string $charset_collate Database charset and collation.
	 * @return void
	 */
	private static function create_banner_placement_table( string $charset_collate ): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'sab_banner_placement';

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			banner_id bigint(20) UNSIGNED NOT NULL,
			placement_id bigint(20) UNSIGNED NOT NULL,
			position int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY banner_placement (banner_id,placement_id),
			KEY placement_id (placement_id)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Creates the statistics table.
	 *
	 * Stores aggregated daily impression and click data.
	 *
	 * @since 1.0.0
	 *
	 * @param string $charset_collate Database charset and collation.
	 * @return void
	 */
	private static function create_statistics_table( string $charset_collate ): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'sab_statistics';

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			banner_id bigint(20) UNSIGNED NOT NULL,
			placement_id bigint(20) UNSIGNED NOT NULL,
			stat_date date NOT NULL,
			impressions bigint(20) UNSIGNED NOT NULL DEFAULT 0,
			clicks bigint(20) UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY daily_stats (banner_id,placement_id,stat_date),
			KEY stat_date (stat_date)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Adds default plugin options.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function add_options(): void {
		add_option( 'simple_add_banners_tracking_secret', wp_generate_password( 32, true, true ) );
	}

	/**
	 * Updates the stored database version.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function set_db_version(): void {
		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}
}
