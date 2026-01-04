<?php

/**
 * Unit tests for the Plugin class.
 *
 * @package SimpleAddBanners\Tests\Unit
 *
 * @method void reset_plugin_singleton()
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use SimpleAddBanners\Plugin;

beforeEach(function () {
	/** @phpstan-ignore-next-line Method inherited from TestCase via Pest binding */
	$this->reset_plugin_singleton();
});

describe('Plugin Singleton', function () {

	it('returns the same instance on multiple calls', function () {
		$instance1 = Plugin::get_instance();
		$instance2 = Plugin::get_instance();

		expect($instance1)->toBe($instance2);
	});

	it('creates instance with correct type', function () {
		$plugin = Plugin::get_instance();

		expect($plugin)->toBeInstanceOf(Plugin::class);
	});
});

describe('Plugin Version', function () {

	it('returns the correct version', function () {
		$plugin = Plugin::get_instance();

		expect($plugin->get_version())->toBe('1.0.0');
	});

	it('has VERSION constant matching expected value', function () {
		expect(Plugin::VERSION)->toBe('1.0.0');
	});
});

describe('Plugin Paths', function () {

	it('returns plugin directory path', function () {
		$plugin = Plugin::get_instance();
		$path   = $plugin->get_plugin_path();

		expect($path)->toBeString();
		expect($path)->toEndWith('/');
	});

	it('returns plugin directory URL', function () {
		$plugin = Plugin::get_instance();
		$url    = $plugin->get_plugin_url();

		expect($url)->toBeString();
		expect($url)->toStartWith('http');
	});
});

describe('Plugin Text Domain Loading', function () {

	it('loads text domain with correct parameters', function () {
		Functions\expect('load_plugin_textdomain')
			->once()
			->with('simple-add-banners', false, \Mockery::type('string'));

		$plugin = Plugin::get_instance();
		$plugin->load_textdomain();
	});
});

describe('Plugin Component Initialization', function () {

	it('initializes admin components when is_admin returns true', function () {
		Functions\when('is_admin')->justReturn(true);

		$plugin = Plugin::get_instance();
		$plugin->init_components();

		// If we reach here without error, admin init worked.
		expect(true)->toBeTrue();
	});

	it('initializes frontend components when is_admin returns false', function () {
		Functions\when('is_admin')->justReturn(false);

		$plugin = Plugin::get_instance();
		$plugin->init_components();

		// If we reach here without error, frontend init worked.
		expect(true)->toBeTrue();
	});
});

describe('Plugin REST API Routes', function () {

	it('registers banner, placement, and banner-placement REST routes', function () {
		// Mock wpdb for the controllers constructors.
		$wpdb         = Mockery::mock('wpdb');
		$wpdb->prefix = 'wp_';
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['wpdb'] = $wpdb;

		Functions\when('__')->returnArg();
		Functions\when('wp_parse_args')->alias(
			function ($args, $defaults) {
				return array_merge($defaults, $args);
			}
		);

		// 2 routes for Banner (collection + single)
		// + 2 routes for Placement (collection + single)
		// + 3 routes for Banner-Placement (GET/PUT, POST, DELETE).
		Functions\expect('register_rest_route')
			->times(7);

		$plugin = Plugin::get_instance();
		$plugin->register_rest_routes();

		unset($GLOBALS['wpdb']);
	});
});
