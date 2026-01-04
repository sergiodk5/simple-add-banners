<?php
/**
 * Tests for Banner_Selector class.
 *
 * @package SimpleAddBanners\Tests\Unit\Frontend
 */

declare(strict_types=1);

namespace SimpleAddBanners\Tests\Unit\Frontend;

use SimpleAddBanners\Frontend\Banner_Selector;
use Brain\Monkey\Functions;

// Define WordPress constant if not defined.
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

beforeEach(function () {
	// Mock current_time to return a fixed time.
	Functions\when('current_time')->justReturn('2024-06-15 12:00:00');

	// Mock get_transient.
	Functions\when('get_transient')->justReturn(false);

	// Mock set_transient.
	Functions\when('set_transient')->justReturn(true);

	// Mock wp_rand to return predictable values for testing.
	Functions\when('wp_rand')->alias(function ($min, $max) {
		return $min; // Always return minimum for predictable tests.
	});
});

describe('Banner_Selector::filter_eligible()', function () {
	it('returns empty array when no banners provided', function () {
		$selector = new Banner_Selector();
		$result = $selector->filter_eligible([]);

		expect($result)->toBe([]);
	});

	it('filters out paused banners', function () {
		$banners = [
			[
				'id' => 1,
				'status' => 'active',
				'desktop_image_id' => 10,
				'start_date' => null,
				'end_date' => null,
			],
			[
				'id' => 2,
				'status' => 'paused',
				'desktop_image_id' => 20,
				'start_date' => null,
				'end_date' => null,
			],
		];

		$selector = new Banner_Selector();
		$result = $selector->filter_eligible($banners);

		expect($result)->toHaveCount(1);
		expect($result[0]['id'])->toBe(1);
	});

	it('filters out banners before start date', function () {
		$banners = [
			[
				'id' => 1,
				'status' => 'active',
				'desktop_image_id' => 10,
				'start_date' => '2024-07-01 00:00:00', // Future date.
				'end_date' => null,
			],
		];

		$selector = new Banner_Selector();
		$result = $selector->filter_eligible($banners);

		expect($result)->toBe([]);
	});

	it('filters out banners after end date', function () {
		$banners = [
			[
				'id' => 1,
				'status' => 'active',
				'desktop_image_id' => 10,
				'start_date' => null,
				'end_date' => '2024-01-01 00:00:00', // Past date.
			],
		];

		$selector = new Banner_Selector();
		$result = $selector->filter_eligible($banners);

		expect($result)->toBe([]);
	});

	it('includes banners within schedule window', function () {
		$banners = [
			[
				'id' => 1,
				'status' => 'active',
				'desktop_image_id' => 10,
				'start_date' => '2024-01-01 00:00:00',
				'end_date' => '2024-12-31 23:59:59',
			],
		];

		$selector = new Banner_Selector();
		$result = $selector->filter_eligible($banners);

		expect($result)->toHaveCount(1);
	});

	it('filters out banners without any images', function () {
		$banners = [
			[
				'id' => 1,
				'status' => 'active',
				'desktop_image_id' => null,
				'mobile_image_id' => null,
				'start_date' => null,
				'end_date' => null,
			],
		];

		$selector = new Banner_Selector();
		$result = $selector->filter_eligible($banners);

		expect($result)->toBe([]);
	});

	it('includes banners with only mobile image', function () {
		$banners = [
			[
				'id' => 1,
				'status' => 'active',
				'desktop_image_id' => null,
				'mobile_image_id' => 20,
				'start_date' => null,
				'end_date' => null,
			],
		];

		$selector = new Banner_Selector();
		$result = $selector->filter_eligible($banners);

		expect($result)->toHaveCount(1);
	});

	it('includes banners with only desktop image', function () {
		$banners = [
			[
				'id' => 1,
				'status' => 'active',
				'desktop_image_id' => 10,
				'mobile_image_id' => null,
				'start_date' => null,
				'end_date' => null,
			],
		];

		$selector = new Banner_Selector();
		$result = $selector->filter_eligible($banners);

		expect($result)->toHaveCount(1);
	});
});

describe('Banner_Selector::select_random()', function () {
	it('returns null for empty array', function () {
		$selector = new Banner_Selector();
		$result = $selector->select_random([]);

		expect($result)->toBeNull();
	});

	it('returns a banner from the array', function () {
		$banners = [
			['id' => 1, 'title' => 'Banner 1'],
			['id' => 2, 'title' => 'Banner 2'],
		];

		$selector = new Banner_Selector();
		$result = $selector->select_random($banners);

		expect($result)->toBeArray();
		expect($result['id'])->toBeIn([1, 2]);
	});
});

describe('Banner_Selector::select_weighted()', function () {
	it('returns null for empty array', function () {
		$selector = new Banner_Selector();
		$result = $selector->select_weighted([]);

		expect($result)->toBeNull();
	});

	it('returns banner based on weight', function () {
		$banners = [
			['id' => 1, 'weight' => 1],
			['id' => 2, 'weight' => 3],
		];

		$selector = new Banner_Selector();
		$result = $selector->select_weighted($banners);

		// With wp_rand mocked to return min (1), first banner should be selected.
		expect($result)->toBeArray();
		expect($result['id'])->toBe(1);
	});

	it('uses default weight of 1 when not specified', function () {
		$banners = [
			['id' => 1], // No weight specified.
			['id' => 2, 'weight' => 2],
		];

		$selector = new Banner_Selector();
		$result = $selector->select_weighted($banners);

		expect($result)->toBeArray();
	});

	it('handles banners with zero weight as weight 1', function () {
		$banners = [
			['id' => 1, 'weight' => 0],
			['id' => 2, 'weight' => 1],
		];

		$selector = new Banner_Selector();
		$result = $selector->select_weighted($banners);

		expect($result)->toBeArray();
	});
});

describe('Banner_Selector::select_sequential()', function () {
	it('returns null for empty array', function () {
		$selector = new Banner_Selector();
		$result = $selector->select_sequential([], 1);

		expect($result)->toBeNull();
	});

	it('returns first banner on initial request', function () {
		$banners = [
			['id' => 1, 'position' => 0],
			['id' => 2, 'position' => 1],
		];

		$selector = new Banner_Selector();
		$result = $selector->select_sequential($banners, 1);

		expect($result['id'])->toBe(1);
	});

	it('sorts banners by position', function () {
		$banners = [
			['id' => 2, 'position' => 1],
			['id' => 1, 'position' => 0],
		];

		$selector = new Banner_Selector();
		$result = $selector->select_sequential($banners, 1);

		// Should return the one with position 0 first.
		expect($result['id'])->toBe(1);
	});

	it('returns second banner when transient indicates index 1', function () {
		Functions\when('get_transient')->justReturn(1);

		$banners = [
			['id' => 1, 'position' => 0],
			['id' => 2, 'position' => 1],
		];

		$selector = new Banner_Selector();
		$result = $selector->select_sequential($banners, 1);

		expect($result['id'])->toBe(2);
	});

	it('wraps around when index exceeds banner count', function () {
		Functions\when('get_transient')->justReturn(5); // Index beyond array size.

		$banners = [
			['id' => 1, 'position' => 0],
			['id' => 2, 'position' => 1],
		];

		$selector = new Banner_Selector();
		$result = $selector->select_sequential($banners, 1);

		// Should wrap to index 0.
		expect($result['id'])->toBe(1);
	});
});

describe('Banner_Selector::select()', function () {
	it('returns null when no eligible banners', function () {
		$banners = [
			['id' => 1, 'status' => 'paused', 'desktop_image_id' => 10],
		];

		$selector = new Banner_Selector();
		$result = $selector->select($banners, 'random');

		expect($result)->toBeNull();
	});

	it('uses random strategy by default', function () {
		$banners = [
			['id' => 1, 'status' => 'active', 'desktop_image_id' => 10],
		];

		$selector = new Banner_Selector();
		$result = $selector->select($banners, 'random');

		expect($result['id'])->toBe(1);
	});

	it('uses weighted strategy when specified', function () {
		$banners = [
			['id' => 1, 'status' => 'active', 'desktop_image_id' => 10, 'weight' => 1],
			['id' => 2, 'status' => 'active', 'desktop_image_id' => 20, 'weight' => 5],
		];

		$selector = new Banner_Selector();
		$result = $selector->select($banners, 'weighted');

		expect($result)->toBeArray();
	});

	it('uses sequential strategy when specified', function () {
		$banners = [
			['id' => 1, 'status' => 'active', 'desktop_image_id' => 10, 'position' => 0],
			['id' => 2, 'status' => 'active', 'desktop_image_id' => 20, 'position' => 1],
		];

		$selector = new Banner_Selector();
		$result = $selector->select($banners, 'sequential', 1);

		expect($result['id'])->toBe(1);
	});

	it('defaults to random for unknown strategy', function () {
		$banners = [
			['id' => 1, 'status' => 'active', 'desktop_image_id' => 10],
		];

		$selector = new Banner_Selector();
		$result = $selector->select($banners, 'unknown_strategy');

		expect($result['id'])->toBe(1);
	});
});
