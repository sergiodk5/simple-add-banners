<?php
/**
 * Plugin Name:       Simple Add Banners
 * Plugin URI:        https://example.com/simple-add-banners
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
