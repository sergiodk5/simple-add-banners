<?php
/**
 * Plugin Name:       Simple Add Banners
 * Plugin URI:        https://sergiodk5.com
 * Description:       A simple plugin to add banners to your WordPress site.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Asterios Patsikas
 * Author URI:        https://sergiodk5.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       simple-add-banners
 * Domain Path:       /languages
 *
 * @package SimpleAddBanners
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'SIMPLE_ADD_BANNERS_VERSION', '1.0.0' );
define( 'SIMPLE_ADD_BANNERS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SIMPLE_ADD_BANNERS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load Composer autoloader.
if ( file_exists( SIMPLE_ADD_BANNERS_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once SIMPLE_ADD_BANNERS_PLUGIN_DIR . 'vendor/autoload.php';
}

// Load scoped dependencies autoloader (if exists).
if ( file_exists( SIMPLE_ADD_BANNERS_PLUGIN_DIR . 'lib/scoper-autoload.php' ) ) {
	require_once SIMPLE_ADD_BANNERS_PLUGIN_DIR . 'lib/scoper-autoload.php';
}

// Register activation hook.
register_activation_hook( __FILE__, array( 'SimpleAddBanners\\Activator', 'activate' ) );

// Register deactivation hook.
register_deactivation_hook( __FILE__, array( 'SimpleAddBanners\\Deactivator', 'deactivate' ) );

// Check for database updates on plugins_loaded.
add_action( 'plugins_loaded', array( 'SimpleAddBanners\\Activator', 'maybe_update' ) );

// Initialize the plugin.
SimpleAddBanners\Plugin::get_instance();
