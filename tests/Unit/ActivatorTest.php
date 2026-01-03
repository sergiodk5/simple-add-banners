<?php
/**
 * Unit tests for the Activator class.
 *
 * @package SimpleAddBanners\Tests\Unit
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use SimpleAddBanners\Activator;

describe( 'Activator constants', function () {

	it( 'has DB_VERSION constant defined', function () {
		expect( Activator::DB_VERSION )->toBe( '1.0.0' );
	} );

	it( 'has DB_VERSION_OPTION constant defined', function () {
		expect( Activator::DB_VERSION_OPTION )->toBe( 'simple_add_banners_db_version' );
	} );

	it( 'is a static callable', function () {
		expect( is_callable( array( Activator::class, 'activate' ) ) )->toBeTrue();
	} );

} );

describe( 'Activator::activate()', function () {

	beforeEach( function () {
		global $wpdb;
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'get_charset_collate' )
			->andReturn( 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci' );
	} );

	afterEach( function () {
		global $wpdb;
		$wpdb = null;
	} );

	it( 'creates tables and sets options on activation', function () {
		Functions\expect( 'dbDelta' )->times( 4 );

		Functions\expect( 'wp_generate_password' )
			->once()
			->with( 32, true, true )
			->andReturn( 'test_secret_key_12345678901234567' );

		Functions\expect( 'add_option' )
			->once()
			->with( 'simple_add_banners_tracking_secret', 'test_secret_key_12345678901234567' );

		Functions\expect( 'update_option' )
			->once()
			->with( 'simple_add_banners_db_version', '1.0.0' );

		Activator::activate();
	} );

} );

describe( 'Activator::maybe_update()', function () {

	beforeEach( function () {
		global $wpdb;
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'get_charset_collate' )
			->andReturn( 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci' );
	} );

	afterEach( function () {
		global $wpdb;
		$wpdb = null;
	} );

	it( 'runs update when version mismatch', function () {
		Functions\expect( 'get_option' )
			->once()
			->with( 'simple_add_banners_db_version' )
			->andReturn( '0.9.0' );

		Functions\expect( 'dbDelta' )->times( 4 );

		Functions\expect( 'update_option' )
			->once()
			->with( 'simple_add_banners_db_version', '1.0.0' );

		Activator::maybe_update();
	} );

	it( 'skips update when version matches', function () {
		Functions\expect( 'get_option' )
			->once()
			->with( 'simple_add_banners_db_version' )
			->andReturn( '1.0.0' );

		Functions\expect( 'dbDelta' )->never();
		Functions\expect( 'update_option' )->never();

		Activator::maybe_update();
	} );

	it( 'runs update when no version exists', function () {
		Functions\expect( 'get_option' )
			->once()
			->with( 'simple_add_banners_db_version' )
			->andReturn( false );

		Functions\expect( 'dbDelta' )->times( 4 );

		Functions\expect( 'update_option' )
			->once()
			->with( 'simple_add_banners_db_version', '1.0.0' );

		Activator::maybe_update();
	} );

} );

describe( 'Activator table schemas', function () {

	beforeEach( function () {
		global $wpdb;
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'get_charset_collate' )
			->andReturn( 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci' );
	} );

	afterEach( function () {
		global $wpdb;
		$wpdb = null;
	} );

	it( 'creates banners table with correct columns', function () {
		Functions\expect( 'dbDelta' )
			->times( 4 )
			->andReturnUsing(
				function ( $sql ) {
					if ( str_contains( $sql, 'sab_banners' ) ) {
						expect( $sql )->toContain( 'title varchar(255)' );
						expect( $sql )->toContain( 'desktop_url varchar(2048)' );
						expect( $sql )->toContain( 'status varchar(20)' );
						expect( $sql )->toContain( 'PRIMARY KEY' );
					}
					return array();
				}
			);

		Functions\expect( 'wp_generate_password' )->andReturn( 'secret' );
		Functions\expect( 'add_option' );
		Functions\expect( 'update_option' );

		Activator::activate();
	} );

	it( 'creates placements table with unique slug', function () {
		Functions\expect( 'dbDelta' )
			->times( 4 )
			->andReturnUsing(
				function ( $sql ) {
					if ( str_contains( $sql, 'sab_placements' ) ) {
						expect( $sql )->toContain( 'slug varchar(100)' );
						expect( $sql )->toContain( 'UNIQUE KEY slug' );
					}
					return array();
				}
			);

		Functions\expect( 'wp_generate_password' )->andReturn( 'secret' );
		Functions\expect( 'add_option' );
		Functions\expect( 'update_option' );

		Activator::activate();
	} );

	it( 'creates statistics table with daily index', function () {
		Functions\expect( 'dbDelta' )
			->times( 4 )
			->andReturnUsing(
				function ( $sql ) {
					if ( str_contains( $sql, 'sab_statistics' ) ) {
						expect( $sql )->toContain( 'impressions bigint(20)' );
						expect( $sql )->toContain( 'clicks bigint(20)' );
						expect( $sql )->toContain( 'UNIQUE KEY daily_stats' );
					}
					return array();
				}
			);

		Functions\expect( 'wp_generate_password' )->andReturn( 'secret' );
		Functions\expect( 'add_option' );
		Functions\expect( 'update_option' );

		Activator::activate();
	} );

} );
