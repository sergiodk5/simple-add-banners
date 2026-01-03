<?php
/**
 * Base test case for unit tests using Brain Monkey.
 *
 * @package SimpleAddBanners\Tests
 */

declare(strict_types=1);

namespace Tests;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Abstract base test case for unit tests.
 *
 * Provides Brain Monkey setup and teardown for mocking WordPress functions.
 */
abstract class TestCase extends PHPUnitTestCase {

	use MockeryPHPUnitIntegration;

	/**
	 * Sets up Brain Monkey before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		$this->stub_wordpress_functions();
	}

	/**
	 * Tears down Brain Monkey after each test.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Stubs common WordPress functions used throughout the plugin.
	 */
	protected function stub_wordpress_functions(): void {
		// Stub translation functions.
		Monkey\Functions\stubTranslationFunctions();

		// Stub escaping functions.
		Monkey\Functions\stubEscapeFunctions();

		// Stub plugin path functions with defaults.
		Monkey\Functions\stubs(
			array(
				'plugin_dir_path' => dirname( __DIR__ ) . '/',
				'plugin_dir_url'  => 'https://example.com/wp-content/plugins/simple-add-banners/',
				'is_admin'        => false,
			)
		);

		Monkey\Functions\when( 'plugin_basename' )
			->alias(
				function ( $file ) {
					return 'simple-add-banners/' . basename( $file );
				}
			);
	}

	/**
	 * Resets the Plugin singleton for testing.
	 *
	 * Uses reflection to access the private static instance property.
	 */
	protected function reset_plugin_singleton(): void {
		$reflection = new \ReflectionClass( \SimpleAddBanners\Plugin::class );
		$instance   = $reflection->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );
	}
}
