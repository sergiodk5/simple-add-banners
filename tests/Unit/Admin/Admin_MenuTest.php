<?php
/**
 * Unit tests for the Admin_Menu class.
 *
 * @package SimpleAddBanners\Tests\Unit\Admin
 */

declare(strict_types=1);

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use SimpleAddBanners\Admin\Admin_Menu;

describe( 'Admin_Menu constants', function () {

	it( 'has MENU_SLUG constant defined', function () {
		expect( Admin_Menu::MENU_SLUG )->toBe( 'simple-add-banners' );
	} );

	it( 'has CAPABILITY constant defined', function () {
		expect( Admin_Menu::CAPABILITY )->toBe( 'manage_options' );
	} );

} );

describe( 'Admin_Menu constructor', function () {

	it( 'registers admin_menu action hook', function () {
		Actions\expectAdded( 'admin_menu' )
			->once()
			->with( Mockery::type( 'array' ), 10, 1 );

		new Admin_Menu();
	} );

	it( 'registers admin_enqueue_scripts action hook', function () {
		Actions\expectAdded( 'admin_enqueue_scripts' )
			->once()
			->with( Mockery::type( 'array' ), 10, 1 );

		new Admin_Menu();
	} );

} );

describe( 'Admin_Menu::register_menu()', function () {

	it( 'calls add_menu_page with correct arguments', function () {
		Functions\expect( 'add_menu_page' )
			->once()
			->with(
				'Banners',
				'Banners',
				'manage_options',
				'simple-add-banners',
				Mockery::type( 'array' ),
				'dashicons-images-alt2',
				30
			);

		$menu = new Admin_Menu();
		$menu->register_menu();
	} );

} );

describe( 'Admin_Menu::render_page()', function () {

	it( 'checks user capability before rendering', function () {
		Functions\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( false );

		Functions\expect( 'wp_die' )
			->once()
			->with( Mockery::type( 'string' ) )
			->andReturnUsing(
				function () {
					throw new \Exception( 'wp_die called' );
				}
			);

		$menu = new Admin_Menu();

		expect( fn() => $menu->render_page() )->toThrow( \Exception::class, 'wp_die called' );
	} );

	it( 'outputs wrapper div when user has capability', function () {
		Functions\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( true );

		$menu = new Admin_Menu();

		ob_start();
		$menu->render_page();
		$output = ob_get_clean();

		expect( $output )->toContain( 'wrap' );
		expect( $output )->toContain( 'sab-admin-app' );
		expect( $output )->toContain( 'Loading' );
	} );

} );

describe( 'Admin_Menu::enqueue_assets()', function () {

	beforeEach( function () {
		// Define constants for tests.
		if ( ! defined( 'SIMPLE_ADD_BANNERS_PLUGIN_DIR' ) ) {
			define( 'SIMPLE_ADD_BANNERS_PLUGIN_DIR', '/var/www/wp-content/plugins/simple-add-banners/' );
		}
		if ( ! defined( 'SIMPLE_ADD_BANNERS_PLUGIN_URL' ) ) {
			define( 'SIMPLE_ADD_BANNERS_PLUGIN_URL', 'https://example.com/wp-content/plugins/simple-add-banners/' );
		}
		if ( ! defined( 'SIMPLE_ADD_BANNERS_VERSION' ) ) {
			define( 'SIMPLE_ADD_BANNERS_VERSION', '1.0.0' );
		}
	} );

	it( 'does nothing when not on plugin admin page', function () {
		Functions\expect( 'wp_enqueue_script' )->never();
		Functions\expect( 'wp_enqueue_style' )->never();

		$menu = new Admin_Menu();
		$menu->enqueue_assets( 'toplevel_page_other-plugin' );
	} );

	it( 'enqueues production assets when built files exist', function () {
		Functions\expect( 'wp_enqueue_media' )->once();

		Functions\expect( 'file_exists' )
			->with( SIMPLE_ADD_BANNERS_PLUGIN_DIR . 'assets/admin/js/admin.js' )
			->andReturn( true );

		Functions\expect( 'file_exists' )
			->with( SIMPLE_ADD_BANNERS_PLUGIN_DIR . 'assets/admin/css/admin.css' )
			->andReturn( true );

		Functions\expect( 'wp_enqueue_script' )
			->once()
			->with(
				'sab-admin',
				SIMPLE_ADD_BANNERS_PLUGIN_URL . 'assets/admin/js/admin.js',
				array(),
				SIMPLE_ADD_BANNERS_VERSION,
				true
			);

		Functions\expect( 'wp_enqueue_style' )
			->once()
			->with(
				'sab-admin',
				SIMPLE_ADD_BANNERS_PLUGIN_URL . 'assets/admin/css/admin.css',
				array(),
				SIMPLE_ADD_BANNERS_VERSION
			);

		Functions\expect( 'wp_localize_script' )
			->once()
			->with( 'sab-admin', 'sabAdmin', Mockery::type( 'array' ) );

		Functions\expect( 'rest_url' )->andReturn( 'https://example.com/wp-json/sab/v1' );
		Functions\expect( 'wp_create_nonce' )->andReturn( 'test_nonce' );
		Functions\expect( 'admin_url' )->andReturn( 'https://example.com/wp-admin/' );

		$menu = new Admin_Menu();
		$menu->enqueue_assets( 'toplevel_page_simple-add-banners' );
	} );

	it( 'enqueues dev server assets when built files do not exist', function () {
		Functions\expect( 'wp_enqueue_media' )->once();

		Functions\expect( 'file_exists' )
			->with( SIMPLE_ADD_BANNERS_PLUGIN_DIR . 'assets/admin/js/admin.js' )
			->andReturn( false );

		Functions\expect( 'wp_enqueue_script' )
			->once()
			->with(
				'sab-admin-vite',
				'http://localhost:5173/@vite/client',
				array(),
				null,
				true
			);

		Functions\expect( 'wp_enqueue_script' )
			->once()
			->with(
				'sab-admin',
				'http://localhost:5173/src/main.ts',
				array( 'sab-admin-vite' ),
				null,
				true
			);

		Filters\expectAdded( 'script_loader_tag' )
			->once()
			->with( Mockery::type( 'array' ), 10, 2 );

		Functions\expect( 'wp_localize_script' )
			->once()
			->with( 'sab-admin', 'sabAdmin', Mockery::type( 'array' ) );

		Functions\expect( 'rest_url' )->andReturn( 'https://example.com/wp-json/sab/v1' );
		Functions\expect( 'wp_create_nonce' )->andReturn( 'test_nonce' );
		Functions\expect( 'admin_url' )->andReturn( 'https://example.com/wp-admin/' );

		$menu = new Admin_Menu();
		$menu->enqueue_assets( 'toplevel_page_simple-add-banners' );
	} );

	it( 'passes correct data to wp_localize_script', function () {
		Functions\expect( 'wp_enqueue_media' )->once();
		Functions\expect( 'file_exists' )->andReturn( true );
		Functions\expect( 'wp_enqueue_script' );
		Functions\expect( 'wp_enqueue_style' );

		Functions\expect( 'rest_url' )
			->once()
			->with( 'sab/v1' )
			->andReturn( 'https://example.com/wp-json/sab/v1' );

		Functions\expect( 'wp_create_nonce' )
			->once()
			->with( 'wp_rest' )
			->andReturn( 'test_nonce_123' );

		Functions\expect( 'admin_url' )
			->once()
			->andReturn( 'https://example.com/wp-admin/' );

		Functions\expect( 'wp_localize_script' )
			->once()
			->with(
				'sab-admin',
				'sabAdmin',
				array(
					'apiUrl'   => 'https://example.com/wp-json/sab/v1',
					'nonce'    => 'test_nonce_123',
					'adminUrl' => 'https://example.com/wp-admin/',
				)
			);

		$menu = new Admin_Menu();
		$menu->enqueue_assets( 'toplevel_page_simple-add-banners' );
	} );

} );

describe( 'Admin_Menu::add_module_type()', function () {

	it( 'adds type="module" to sab-admin script', function () {
		$tag      = '<script src="http://example.com/admin.js"></script>';
		$expected = '<script type="module" src="http://example.com/admin.js"></script>';

		$menu   = new Admin_Menu();
		$result = $menu->add_module_type( $tag, 'sab-admin' );

		expect( $result )->toBe( $expected );
	} );

	it( 'adds type="module" to sab-admin-vite script', function () {
		$tag      = '<script src="http://localhost:5173/@vite/client"></script>';
		$expected = '<script type="module" src="http://localhost:5173/@vite/client"></script>';

		$menu   = new Admin_Menu();
		$result = $menu->add_module_type( $tag, 'sab-admin-vite' );

		expect( $result )->toBe( $expected );
	} );

	it( 'does not modify other scripts', function () {
		$tag = '<script src="http://example.com/other.js"></script>';

		$menu   = new Admin_Menu();
		$result = $menu->add_module_type( $tag, 'jquery' );

		expect( $result )->toBe( $tag );
	} );

} );
