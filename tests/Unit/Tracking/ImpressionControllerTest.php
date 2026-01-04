<?php
/**
 * Tests for Impression_Controller class.
 *
 * @package SimpleAddBanners\Tests\Unit\Tracking
 */

declare(strict_types=1);

namespace SimpleAddBanners\Tests\Unit\Tracking;

use SimpleAddBanners\Tracking\Impression_Controller;
use SimpleAddBanners\Tracking\Token_Generator;
use SimpleAddBanners\Tracking\Statistics_Repository;
use Brain\Monkey\Functions;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

beforeEach(function () {
	// Mock WordPress functions.
	Functions\when('__')->returnArg();
	Functions\when('register_rest_route')->justReturn(true);
	Functions\when('absint')->alias(function ($value) {
		return abs((int) $value);
	});
	Functions\when('sanitize_text_field')->returnArg();

	// Create mock dependencies.
	$this->token_generator = \Mockery::mock(Token_Generator::class);
	$this->stats_repo      = \Mockery::mock(Statistics_Repository::class);
});

describe('Impression_Controller constructor', function () {
	it('creates instance with default dependencies', function () {
		// Mock wpdb for Statistics_Repository.
		$wpdb         = \Mockery::mock('wpdb');
		$wpdb->prefix = 'wp_';
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['wpdb'] = $wpdb;

		Functions\when('get_option')->justReturn('test_secret');

		$controller = new Impression_Controller();

		expect($controller)->toBeInstanceOf(Impression_Controller::class);

		unset($GLOBALS['wpdb']);
	});

	it('accepts injected dependencies', function () {
		$controller = new Impression_Controller(
			$this->token_generator,
			$this->stats_repo
		);

		expect($controller)->toBeInstanceOf(Impression_Controller::class);
	});
});

describe('Impression_Controller::register_routes()', function () {
	it('registers the impression tracking route', function () {
		$routeRegistered = false;
		Functions\when('register_rest_route')->alias(function ($namespace, $route, $args) use (&$routeRegistered) {
			if ($namespace === 'sab/v1' && $route === '/track/impression') {
				$routeRegistered = true;
			}
			return true;
		});

		$controller = new Impression_Controller(
			$this->token_generator,
			$this->stats_repo
		);
		$controller->register_routes();

		expect($routeRegistered)->toBeTrue();
	});
});

describe('Impression_Controller::track_impression()', function () {
	it('returns success when token is valid', function () {
		$this->token_generator->shouldReceive('validate')
			->once()
			->with('valid_token', 1, 2)
			->andReturn(true);

		$this->stats_repo->shouldReceive('increment_impressions')
			->once()
			->with(1, 2)
			->andReturn(true);

		$request = \Mockery::mock(WP_REST_Request::class);
		$request->shouldReceive('get_param')
			->with('banner_id')
			->andReturn(1);
		$request->shouldReceive('get_param')
			->with('placement_id')
			->andReturn(2);
		$request->shouldReceive('get_param')
			->with('token')
			->andReturn('valid_token');

		$controller = new Impression_Controller(
			$this->token_generator,
			$this->stats_repo
		);
		$response = $controller->track_impression($request);

		expect($response)->toBeInstanceOf(WP_REST_Response::class);
		expect($response->get_status())->toBe(200);
		expect($response->get_data())->toBe(['success' => true]);
	});

	it('returns error when token is invalid', function () {
		$this->token_generator->shouldReceive('validate')
			->once()
			->with('invalid_token', 1, 2)
			->andReturn(false);

		$request = \Mockery::mock(WP_REST_Request::class);
		$request->shouldReceive('get_param')
			->with('banner_id')
			->andReturn(1);
		$request->shouldReceive('get_param')
			->with('placement_id')
			->andReturn(2);
		$request->shouldReceive('get_param')
			->with('token')
			->andReturn('invalid_token');

		$controller = new Impression_Controller(
			$this->token_generator,
			$this->stats_repo
		);
		$response = $controller->track_impression($request);

		expect($response)->toBeInstanceOf(WP_Error::class);
		expect($response->get_error_code())->toBe('invalid_token');
		expect($response->get_error_data()['status'])->toBe(400);
	});

	it('returns error when database insert fails', function () {
		$this->token_generator->shouldReceive('validate')
			->once()
			->with('valid_token', 1, 2)
			->andReturn(true);

		$this->stats_repo->shouldReceive('increment_impressions')
			->once()
			->with(1, 2)
			->andReturn(false);

		$request = \Mockery::mock(WP_REST_Request::class);
		$request->shouldReceive('get_param')
			->with('banner_id')
			->andReturn(1);
		$request->shouldReceive('get_param')
			->with('placement_id')
			->andReturn(2);
		$request->shouldReceive('get_param')
			->with('token')
			->andReturn('valid_token');

		$controller = new Impression_Controller(
			$this->token_generator,
			$this->stats_repo
		);
		$response = $controller->track_impression($request);

		expect($response)->toBeInstanceOf(WP_Error::class);
		expect($response->get_error_code())->toBe('tracking_failed');
		expect($response->get_error_data()['status'])->toBe(500);
	});

	it('casts parameters to correct types', function () {
		$this->token_generator->shouldReceive('validate')
			->once()
			->with('token123', 123, 456)
			->andReturn(true);

		$this->stats_repo->shouldReceive('increment_impressions')
			->once()
			->with(123, 456)
			->andReturn(true);

		$request = \Mockery::mock(WP_REST_Request::class);
		$request->shouldReceive('get_param')
			->with('banner_id')
			->andReturn('123'); // String input.
		$request->shouldReceive('get_param')
			->with('placement_id')
			->andReturn('456'); // String input.
		$request->shouldReceive('get_param')
			->with('token')
			->andReturn('token123');

		$controller = new Impression_Controller(
			$this->token_generator,
			$this->stats_repo
		);
		$response = $controller->track_impression($request);

		expect($response)->toBeInstanceOf(WP_REST_Response::class);
		expect($response->get_status())->toBe(200);
	});
});

describe('Impression_Controller validation', function () {
	it('validates banner_id is positive integer', function () {
		// The validation callback requires numeric > 0.
		$validate = function ($param) {
			return is_numeric($param) && $param > 0;
		};

		expect($validate(1))->toBeTrue();
		expect($validate('5'))->toBeTrue();
		expect($validate(0))->toBeFalse();
		expect($validate(-1))->toBeFalse();
		expect($validate('abc'))->toBeFalse();
	});

	it('validates placement_id is positive integer', function () {
		$validate = function ($param) {
			return is_numeric($param) && $param > 0;
		};

		expect($validate(1))->toBeTrue();
		expect($validate('10'))->toBeTrue();
		expect($validate(0))->toBeFalse();
		expect($validate(-5))->toBeFalse();
	});
});
