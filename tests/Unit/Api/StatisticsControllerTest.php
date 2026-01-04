<?php
/**
 * Tests for Statistics_Controller class.
 *
 * @package SimpleAddBanners\Tests\Unit\Api
 *
 * @property \Mockery\MockInterface $wpdb
 * @property \Mockery\MockInterface $repository
 */

declare(strict_types=1);

namespace SimpleAddBanners\Tests\Unit\Api;

use SimpleAddBanners\Api\Statistics_Controller;
use SimpleAddBanners\Tracking\Statistics_Repository;
use Brain\Monkey\Functions;
use Mockery;

// Define WordPress constants if not defined.
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

beforeEach(function () {
	// Mock wpdb for repository.
	$this->wpdb         = Mockery::mock('wpdb');
	$this->wpdb->prefix = 'wp_';
	$GLOBALS['wpdb']    = $this->wpdb;

	// Mock repository.
	$this->repository = Mockery::mock(Statistics_Repository::class);

	// Common WordPress function mocks.
	Functions\when('wp_parse_args')->alias(function ($args, $defaults) {
		return array_merge($defaults, $args);
	});

	Functions\when('__')->returnArg();
	Functions\when('esc_html__')->returnArg();
});

afterEach(function () {
	unset($GLOBALS['wpdb']);
});

describe('Statistics_Controller constructor', function () {
	it('creates controller with correct namespace', function () {
		$controller = new Statistics_Controller($this->repository);

		$reflection = new \ReflectionClass($controller);
		$property   = $reflection->getProperty('namespace');

		expect($property->getValue($controller))->toBe('sab/v1');
	});

	it('creates controller with correct rest base', function () {
		$controller = new Statistics_Controller($this->repository);

		$reflection = new \ReflectionClass($controller);
		$property   = $reflection->getProperty('rest_base');

		expect($property->getValue($controller))->toBe('statistics');
	});
});

describe('Statistics_Controller::register_routes()', function () {
	it('registers three routes', function () {
		Functions\expect('register_rest_route')
			->times(3);

		$controller = new Statistics_Controller($this->repository);
		$controller->register_routes();
	});
});

describe('Statistics_Controller permission checks', function () {
	it('get_items_permissions_check returns true for admin', function () {
		Functions\expect('current_user_can')
			->with('manage_options')
			->andReturn(true);

		$controller = new Statistics_Controller($this->repository);
		$request    = Mockery::mock('WP_REST_Request');

		expect($controller->get_items_permissions_check($request))->toBeTrue();
	});

	it('get_items_permissions_check returns false for non-admin', function () {
		Functions\expect('current_user_can')
			->with('manage_options')
			->andReturn(false);

		$controller = new Statistics_Controller($this->repository);
		$request    = Mockery::mock('WP_REST_Request');

		expect($controller->get_items_permissions_check($request))->toBeFalse();
	});
});

describe('Statistics_Controller::get_items()', function () {
	it('returns all banner statistics', function () {
		$mockStats = [
			[
				'banner_id'     => 1,
				'banner_title'  => 'Test Banner',
				'banner_status' => 'active',
				'impressions'   => 100,
				'clicks'        => 5,
				'ctr'           => 5.0,
			],
		];

		$this->repository
			->shouldReceive('get_all_banner_stats')
			->with(null, null)
			->once()
			->andReturn($mockStats);

		$controller = new Statistics_Controller($this->repository);

		$request = Mockery::mock('WP_REST_Request');
		$request->shouldReceive('get_param')
			->with('start_date')
			->andReturn(null);
		$request->shouldReceive('get_param')
			->with('end_date')
			->andReturn(null);

		$response = $controller->get_items($request);

		expect($response)->toBeInstanceOf('WP_REST_Response');
		expect($response->get_data())->toBe($mockStats);
		expect($response->get_status())->toBe(200);
	});

	it('passes date filters to repository', function () {
		$this->repository
			->shouldReceive('get_all_banner_stats')
			->with('2025-01-01', '2025-01-31')
			->once()
			->andReturn([]);

		$controller = new Statistics_Controller($this->repository);

		$request = Mockery::mock('WP_REST_Request');
		$request->shouldReceive('get_param')
			->with('start_date')
			->andReturn('2025-01-01');
		$request->shouldReceive('get_param')
			->with('end_date')
			->andReturn('2025-01-31');

		$controller->get_items($request);
	});
});

describe('Statistics_Controller::get_banner_stats()', function () {
	it('returns detailed statistics for a banner', function () {
		$mockDaily = [
			[
				'id'           => 1,
				'banner_id'    => 5,
				'placement_id' => 2,
				'stat_date'    => '2025-01-15',
				'impressions'  => 50,
				'clicks'       => 3,
				'ctr'          => 6.0,
			],
		];

		$mockAggregated = [
			'impressions' => 50,
			'clicks'      => 3,
			'ctr'         => 6.0,
		];

		$this->repository
			->shouldReceive('get_stats')
			->with(5, null, null)
			->once()
			->andReturn($mockDaily);

		$this->repository
			->shouldReceive('get_aggregated_stats')
			->with(5, null, null)
			->once()
			->andReturn($mockAggregated);

		$controller = new Statistics_Controller($this->repository);

		$request = Mockery::mock('WP_REST_Request');
		$request->shouldReceive('get_param')
			->with('id')
			->andReturn('5');
		$request->shouldReceive('get_param')
			->with('start_date')
			->andReturn(null);
		$request->shouldReceive('get_param')
			->with('end_date')
			->andReturn(null);

		$response = $controller->get_banner_stats($request);

		expect($response)->toBeInstanceOf('WP_REST_Response');

		$data = $response->get_data();
		expect($data['banner_id'])->toBe(5);
		expect($data['totals'])->toBe($mockAggregated);
		expect($data['daily'])->toBe($mockDaily);
		expect($response->get_status())->toBe(200);
	});
});

describe('Statistics_Controller::get_placement_stats()', function () {
	it('returns detailed statistics for a placement', function () {
		$mockDaily = [
			[
				'id'           => 1,
				'banner_id'    => 5,
				'placement_id' => 3,
				'stat_date'    => '2025-01-15',
				'impressions'  => 100,
				'clicks'       => 10,
				'ctr'          => 10.0,
			],
		];

		$this->repository
			->shouldReceive('get_stats_by_placement')
			->with(3, null, null)
			->once()
			->andReturn($mockDaily);

		$controller = new Statistics_Controller($this->repository);

		$request = Mockery::mock('WP_REST_Request');
		$request->shouldReceive('get_param')
			->with('id')
			->andReturn('3');
		$request->shouldReceive('get_param')
			->with('start_date')
			->andReturn(null);
		$request->shouldReceive('get_param')
			->with('end_date')
			->andReturn(null);

		$response = $controller->get_placement_stats($request);

		expect($response)->toBeInstanceOf('WP_REST_Response');

		$data = $response->get_data();
		expect($data['placement_id'])->toBe(3);
		expect($data['totals']['impressions'])->toBe(100);
		expect($data['totals']['clicks'])->toBe(10);
		expect($data['totals']['ctr'])->toBe(10.0);
		expect($data['daily'])->toBe($mockDaily);
		expect($response->get_status())->toBe(200);
	});

	it('calculates CTR correctly from daily stats', function () {
		$mockDaily = [
			[
				'impressions' => 200,
				'clicks'      => 4,
			],
			[
				'impressions' => 300,
				'clicks'      => 6,
			],
		];

		$this->repository
			->shouldReceive('get_stats_by_placement')
			->andReturn($mockDaily);

		$controller = new Statistics_Controller($this->repository);

		$request = Mockery::mock('WP_REST_Request');
		$request->shouldReceive('get_param')
			->with('id')
			->andReturn('1');
		$request->shouldReceive('get_param')
			->with('start_date')
			->andReturn(null);
		$request->shouldReceive('get_param')
			->with('end_date')
			->andReturn(null);

		$response = $controller->get_placement_stats($request);
		$data     = $response->get_data();

		// 500 total impressions, 10 total clicks = 2% CTR.
		expect($data['totals']['impressions'])->toBe(500);
		expect($data['totals']['clicks'])->toBe(10);
		expect($data['totals']['ctr'])->toBe(2.0);
	});

	it('returns zero CTR when no impressions', function () {
		$this->repository
			->shouldReceive('get_stats_by_placement')
			->andReturn([]);

		$controller = new Statistics_Controller($this->repository);

		$request = Mockery::mock('WP_REST_Request');
		$request->shouldReceive('get_param')
			->with('id')
			->andReturn('1');
		$request->shouldReceive('get_param')
			->with('start_date')
			->andReturn(null);
		$request->shouldReceive('get_param')
			->with('end_date')
			->andReturn(null);

		$response = $controller->get_placement_stats($request);
		$data     = $response->get_data();

		expect($data['totals']['ctr'])->toBe(0);
	});
});

describe('Statistics_Controller::get_date_filter_params()', function () {
	it('returns date filter parameters', function () {
		$controller = new Statistics_Controller($this->repository);
		$params     = $controller->get_date_filter_params();

		expect($params)->toBeArray();
		expect($params)->toHaveKey('start_date');
		expect($params)->toHaveKey('end_date');
		expect($params['start_date']['type'])->toBe('string');
		expect($params['end_date']['type'])->toBe('string');
	});
});

describe('Statistics_Controller::get_collection_params()', function () {
	it('returns collection params including date filters', function () {
		$controller = new Statistics_Controller($this->repository);
		$params     = $controller->get_collection_params();

		expect($params)->toBeArray();
		expect($params)->toHaveKey('start_date');
		expect($params)->toHaveKey('end_date');
	});
});
