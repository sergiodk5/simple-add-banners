<?php
/**
 * Test bootstrap file.
 *
 * Sets up the testing environment for Brain Monkey.
 *
 * @package SimpleAddBanners\Tests
 */

declare(strict_types=1);

// Get the plugin directory.
$plugin_dir = dirname( __DIR__ );

// Define WordPress constants that may be used in the plugin.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( $plugin_dir, 3 ) . '/' );
}

if ( ! defined( 'SIMPLE_ADD_BANNERS_VERSION' ) ) {
	define( 'SIMPLE_ADD_BANNERS_VERSION', '1.0.0' );
}

if ( ! defined( 'SIMPLE_ADD_BANNERS_PLUGIN_DIR' ) ) {
	define( 'SIMPLE_ADD_BANNERS_PLUGIN_DIR', $plugin_dir . '/' );
}

if ( ! defined( 'SIMPLE_ADD_BANNERS_PLUGIN_URL' ) ) {
	define( 'SIMPLE_ADD_BANNERS_PLUGIN_URL', 'https://example.com/wp-content/plugins/simple-add-banners/' );
}

// Load Composer autoloader.
require_once $plugin_dir . '/vendor/autoload.php';
