<?php
/**
 * Pest PHP configuration file.
 *
 * @package SimpleAddBanners\Tests
 */

declare(strict_types=1);

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Bind the custom TestCase (with Brain Monkey setup) to all unit tests.
|
*/

pest()->extend( TestCase::class )->in( 'Unit' );

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| Custom expectations for banner-related assertions.
|
*/

expect()->extend(
	'toBeActiveBanner',
	function () {
		return $this->toBeArray()
			->toHaveKey( 'status' )
			->and( $this->value['status'] )->toBe( 'active' );
	}
);

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| Global helper functions available in all tests.
|
*/

/**
 * Gets the plugin directory path for tests.
 *
 * @return string Plugin directory path.
 */
function get_plugin_path(): string {
	return dirname( __DIR__ ) . '/';
}
