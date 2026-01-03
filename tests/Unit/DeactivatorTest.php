<?php
/**
 * Unit tests for the Deactivator class.
 *
 * @package SimpleAddBanners\Tests\Unit
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use SimpleAddBanners\Deactivator;

describe( 'Deactivator::deactivate()', function () {

	it( 'flushes rewrite rules on deactivation', function () {
		Functions\expect( 'flush_rewrite_rules' )->once();
		Functions\when( 'wp_next_scheduled' )->justReturn( false );

		Deactivator::deactivate();

		expect( true )->toBeTrue();
	} );

	it( 'clears scheduled cron events when they exist', function () {
		$timestamp = 1704067200;

		Functions\expect( 'flush_rewrite_rules' )->once();
		Functions\expect( 'wp_next_scheduled' )
			->once()
			->with( 'simple_add_banners_daily_cleanup' )
			->andReturn( $timestamp );
		Functions\expect( 'wp_unschedule_event' )
			->once()
			->with( $timestamp, 'simple_add_banners_daily_cleanup' );

		Deactivator::deactivate();
	} );

	it( 'does not unschedule when no cron exists', function () {
		Functions\expect( 'flush_rewrite_rules' )->once();
		Functions\expect( 'wp_next_scheduled' )
			->once()
			->with( 'simple_add_banners_daily_cleanup' )
			->andReturn( false );
		Functions\expect( 'wp_unschedule_event' )->never();

		Deactivator::deactivate();
	} );

} );

describe( 'Deactivator static methods', function () {

	it( 'has deactivate as a static callable', function () {
		expect( is_callable( array( Deactivator::class, 'deactivate' ) ) )->toBeTrue();
	} );

} );
