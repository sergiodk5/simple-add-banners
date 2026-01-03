<?php
/**
 * Tests for Banner_Controller class.
 *
 * @package SimpleAddBanners\Tests\Unit\Api
 *
 * @property \Mockery\MockInterface $wpdb
 */

declare(strict_types=1);

namespace SimpleAddBanners\Tests\Unit\Api;

use SimpleAddBanners\Api\Banner_Controller;
use Brain\Monkey\Functions;
use Mockery;

// Define WordPress constants if not defined.
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

beforeEach(function () {
	// Mock wpdb for repository.
	$this->wpdb = Mockery::mock('wpdb');
	$this->wpdb->prefix = 'wp_';
	$GLOBALS['wpdb'] = $this->wpdb;

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

describe('Banner_Controller constructor', function () {
	it('creates controller with correct namespace', function () {
		$controller = new Banner_Controller();

		$reflection = new \ReflectionClass($controller);
		$property = $reflection->getProperty('namespace');

		expect($property->getValue($controller))->toBe('sab/v1');
	});

	it('creates controller with correct rest base', function () {
		$controller = new Banner_Controller();

		$reflection = new \ReflectionClass($controller);
		$property = $reflection->getProperty('rest_base');

		expect($property->getValue($controller))->toBe('banners');
	});
});

describe('Banner_Controller::register_routes()', function () {
	it('registers collection and single item routes', function () {
		Functions\expect('register_rest_route')
			->twice();

		$controller = new Banner_Controller();
		$controller->register_routes();
	});
});

describe('Banner_Controller permission checks', function () {
	it('get_items_permissions_check returns true for admin', function () {
		Functions\expect('current_user_can')
			->with('manage_options')
			->andReturn(true);

		$controller = new Banner_Controller();
		$request = Mockery::mock('WP_REST_Request');

		expect($controller->get_items_permissions_check($request))->toBeTrue();
	});

	it('get_items_permissions_check returns false for non-admin', function () {
		Functions\expect('current_user_can')
			->with('manage_options')
			->andReturn(false);

		$controller = new Banner_Controller();
		$request = Mockery::mock('WP_REST_Request');

		expect($controller->get_items_permissions_check($request))->toBeFalse();
	});

	it('create_item_permissions_check checks manage_options capability', function () {
		Functions\expect('current_user_can')
			->with('manage_options')
			->andReturn(true);

		$controller = new Banner_Controller();
		$request = Mockery::mock('WP_REST_Request');

		expect($controller->create_item_permissions_check($request))->toBeTrue();
	});

	it('update_item_permissions_check checks manage_options capability', function () {
		Functions\expect('current_user_can')
			->with('manage_options')
			->andReturn(true);

		$controller = new Banner_Controller();
		$request = Mockery::mock('WP_REST_Request');

		expect($controller->update_item_permissions_check($request))->toBeTrue();
	});

	it('delete_item_permissions_check checks manage_options capability', function () {
		Functions\expect('current_user_can')
			->with('manage_options')
			->andReturn(true);

		$controller = new Banner_Controller();
		$request = Mockery::mock('WP_REST_Request');

		expect($controller->delete_item_permissions_check($request))->toBeTrue();
	});

	it('get_item_permissions_check checks manage_options capability', function () {
		Functions\expect('current_user_can')
			->with('manage_options')
			->andReturn(true);

		$controller = new Banner_Controller();
		$request = Mockery::mock('WP_REST_Request');

		expect($controller->get_item_permissions_check($request))->toBeTrue();
	});
});

describe('Banner_Controller::get_items()', function () {
	it('returns list of banners', function () {
		$db_rows = [
			[
				'id' => '1',
				'title' => 'Test Banner',
				'desktop_image_id' => null,
				'mobile_image_id' => null,
				'desktop_url' => 'https://example.com',
				'mobile_url' => null,
				'start_date' => null,
				'end_date' => null,
				'status' => 'active',
				'weight' => '1',
				'created_at' => '2024-01-01 00:00:00',
				'updated_at' => '2024-01-01 00:00:00',
			],
		];

		$this->wpdb->shouldReceive('prepare')
			->andReturn('SELECT * FROM wp_sab_banners LIMIT 10 OFFSET 0');

		$this->wpdb->shouldReceive('get_results')
			->once()
			->andReturn($db_rows);

		$this->wpdb->shouldReceive('get_var')
			->once()
			->andReturn('1');

		$request = Mockery::mock('WP_REST_Request');
		$request->shouldReceive('get_param')
			->with('status')
			->andReturn(null);
		$request->shouldReceive('get_param')
			->with('orderby')
			->andReturn(null);
		$request->shouldReceive('get_param')
			->with('order')
			->andReturn(null);
		$request->shouldReceive('get_param')
			->with('per_page')
			->andReturn(10);
		$request->shouldReceive('get_param')
			->with('page')
			->andReturn(1);

		Functions\when('wp_get_attachment_url')->justReturn(null);

		$controller = new Banner_Controller();
		$response = $controller->get_items($request);

		expect($response)->toBeInstanceOf(\WP_REST_Response::class);
		expect($response->get_status())->toBe(200);

		$data = $response->get_data();
		expect($data)->toBeArray();
		expect(count($data))->toBe(1);
		expect($data[0]['title'])->toBe('Test Banner');
	});
});

describe('Banner_Controller::get_item()', function () {
	it('returns banner when found', function () {
		$db_row = [
			'id' => '1',
			'title' => 'Test Banner',
			'desktop_image_id' => null,
			'mobile_image_id' => null,
			'desktop_url' => 'https://example.com',
			'mobile_url' => null,
			'start_date' => null,
			'end_date' => null,
			'status' => 'active',
			'weight' => '1',
			'created_at' => '2024-01-01 00:00:00',
			'updated_at' => '2024-01-01 00:00:00',
		];

		$this->wpdb->shouldReceive('prepare')
			->once()
			->andReturn('SELECT * FROM wp_sab_banners WHERE id = 1');

		$this->wpdb->shouldReceive('get_row')
			->once()
			->andReturn($db_row);

		$request = Mockery::mock('WP_REST_Request');
		$request->shouldReceive('get_param')
			->with('id')
			->andReturn(1);

		Functions\when('wp_get_attachment_url')->justReturn(null);

		$controller = new Banner_Controller();
		$response = $controller->get_item($request);

		expect($response)->toBeInstanceOf(\WP_REST_Response::class);
		expect($response->get_status())->toBe(200);
		expect($response->get_data()['title'])->toBe('Test Banner');
	});

	it('returns error when banner not found', function () {
		$this->wpdb->shouldReceive('prepare')
			->once()
			->andReturn('SELECT * FROM wp_sab_banners WHERE id = 999');

		$this->wpdb->shouldReceive('get_row')
			->once()
			->andReturn(null);

		$request = Mockery::mock('WP_REST_Request');
		$request->shouldReceive('get_param')
			->with('id')
			->andReturn(999);

		$controller = new Banner_Controller();
		$response = $controller->get_item($request);

		expect($response)->toBeInstanceOf(\WP_Error::class);
		expect($response->get_error_code())->toBe('rest_banner_not_found');
	});
});

describe('Banner_Controller::create_item()', function () {
	it('creates banner successfully', function () {
		Functions\when('sanitize_text_field')->returnArg();
		Functions\when('esc_url_raw')->returnArg();
		Functions\when('absint')->alias('intval');
		Functions\when('rest_url')->justReturn('https://example.com/wp-json/sab/v1/banners/1');
		Functions\when('wp_get_attachment_url')->justReturn(null);

		$this->wpdb->shouldReceive('insert')
			->once()
			->andReturn(1);

		$this->wpdb->insert_id = 1;

		$db_row = [
			'id' => '1',
			'title' => 'New Banner',
			'desktop_image_id' => null,
			'mobile_image_id' => null,
			'desktop_url' => 'https://example.com',
			'mobile_url' => null,
			'start_date' => null,
			'end_date' => null,
			'status' => 'active',
			'weight' => '1',
			'created_at' => '2024-01-01 00:00:00',
			'updated_at' => '2024-01-01 00:00:00',
		];

		$this->wpdb->shouldReceive('prepare')
			->once()
			->andReturn('SELECT * FROM wp_sab_banners WHERE id = 1');

		$this->wpdb->shouldReceive('get_row')
			->once()
			->andReturn($db_row);

		$request = Mockery::mock('WP_REST_Request');
		$request->shouldReceive('get_param')
			->with('title')
			->andReturn('New Banner');
		$request->shouldReceive('get_param')
			->with('desktop_image_id')
			->andReturn(null);
		$request->shouldReceive('get_param')
			->with('mobile_image_id')
			->andReturn(null);
		$request->shouldReceive('get_param')
			->with('desktop_url')
			->andReturn('https://example.com');
		$request->shouldReceive('get_param')
			->with('mobile_url')
			->andReturn(null);
		$request->shouldReceive('get_param')
			->with('start_date')
			->andReturn(null);
		$request->shouldReceive('get_param')
			->with('end_date')
			->andReturn(null);
		$request->shouldReceive('get_param')
			->with('status')
			->andReturn('active');
		$request->shouldReceive('get_param')
			->with('weight')
			->andReturn(1);

		$controller = new Banner_Controller();
		$response = $controller->create_item($request);

		expect($response)->toBeInstanceOf(\WP_REST_Response::class);
		expect($response->get_status())->toBe(201);
		expect($response->get_data()['title'])->toBe('New Banner');
	});

	it('returns error when create fails', function () {
		Functions\when('sanitize_text_field')->returnArg();
		Functions\when('esc_url_raw')->returnArg();
		Functions\when('absint')->alias('intval');

		$this->wpdb->shouldReceive('insert')
			->once()
			->andReturn(false);

		$request = Mockery::mock('WP_REST_Request');
		$request->shouldReceive('get_param')
			->with('title')
			->andReturn('New Banner');
		$request->shouldReceive('get_param')
			->with('desktop_image_id')
			->andReturn(null);
		$request->shouldReceive('get_param')
			->with('mobile_image_id')
			->andReturn(null);
		$request->shouldReceive('get_param')
			->with('desktop_url')
			->andReturn('https://example.com');
		$request->shouldReceive('get_param')
			->with('mobile_url')
			->andReturn(null);
		$request->shouldReceive('get_param')
			->with('start_date')
			->andReturn(null);
		$request->shouldReceive('get_param')
			->with('end_date')
			->andReturn(null);
		$request->shouldReceive('get_param')
			->with('status')
			->andReturn('active');
		$request->shouldReceive('get_param')
			->with('weight')
			->andReturn(1);

		$controller = new Banner_Controller();
		$response = $controller->create_item($request);

		expect($response)->toBeInstanceOf(\WP_Error::class);
		expect($response->get_error_code())->toBe('rest_banner_create_failed');
	});
});

describe('Banner_Controller::delete_item()', function () {
	it('deletes banner successfully', function () {
		$db_row = [
			'id' => '1',
			'title' => 'Test Banner',
			'desktop_image_id' => null,
			'mobile_image_id' => null,
			'desktop_url' => 'https://example.com',
			'mobile_url' => null,
			'start_date' => null,
			'end_date' => null,
			'status' => 'active',
			'weight' => '1',
			'created_at' => '2024-01-01 00:00:00',
			'updated_at' => '2024-01-01 00:00:00',
		];

		$this->wpdb->shouldReceive('prepare')
			->once()
			->andReturn('SELECT * FROM wp_sab_banners WHERE id = 1');

		$this->wpdb->shouldReceive('get_row')
			->once()
			->andReturn($db_row);

		$this->wpdb->shouldReceive('delete')
			->once()
			->andReturn(1);

		$request = Mockery::mock('WP_REST_Request');
		$request->shouldReceive('get_param')
			->with('id')
			->andReturn(1);

		$controller = new Banner_Controller();
		$response = $controller->delete_item($request);

		expect($response)->toBeInstanceOf(\WP_REST_Response::class);
		expect($response->get_status())->toBe(204);
	});

	it('returns error when banner not found', function () {
		$this->wpdb->shouldReceive('prepare')
			->once()
			->andReturn('SELECT * FROM wp_sab_banners WHERE id = 999');

		$this->wpdb->shouldReceive('get_row')
			->once()
			->andReturn(null);

		$request = Mockery::mock('WP_REST_Request');
		$request->shouldReceive('get_param')
			->with('id')
			->andReturn(999);

		$controller = new Banner_Controller();
		$response = $controller->delete_item($request);

		expect($response)->toBeInstanceOf(\WP_Error::class);
		expect($response->get_error_code())->toBe('rest_banner_not_found');
	});

	it('returns error when delete fails', function () {
		$db_row = [
			'id' => '1',
			'title' => 'Test Banner',
			'desktop_image_id' => null,
			'mobile_image_id' => null,
			'desktop_url' => 'https://example.com',
			'mobile_url' => null,
			'start_date' => null,
			'end_date' => null,
			'status' => 'active',
			'weight' => '1',
			'created_at' => '2024-01-01 00:00:00',
			'updated_at' => '2024-01-01 00:00:00',
		];

		$this->wpdb->shouldReceive('prepare')
			->once()
			->andReturn('SELECT * FROM wp_sab_banners WHERE id = 1');

		$this->wpdb->shouldReceive('get_row')
			->once()
			->andReturn($db_row);

		$this->wpdb->shouldReceive('delete')
			->once()
			->andReturn(false);

		$request = Mockery::mock('WP_REST_Request');
		$request->shouldReceive('get_param')
			->with('id')
			->andReturn(1);

		$controller = new Banner_Controller();
		$response = $controller->delete_item($request);

		expect($response)->toBeInstanceOf(\WP_Error::class);
		expect($response->get_error_code())->toBe('rest_banner_delete_failed');
	});
});

describe('Banner_Controller::get_collection_params()', function () {
	it('returns expected query parameters', function () {
		$controller = new Banner_Controller();
		$params = $controller->get_collection_params();

		expect($params)->toHaveKey('page');
		expect($params)->toHaveKey('per_page');
		expect($params)->toHaveKey('status');
		expect($params)->toHaveKey('orderby');
		expect($params)->toHaveKey('order');
	});
});

describe('Banner_Controller::update_item()', function () {
	it('updates banner successfully', function () {
		Functions\when('sanitize_text_field')->returnArg();
		Functions\when('esc_url_raw')->returnArg();
		Functions\when('absint')->alias('intval');
		Functions\when('wp_get_attachment_url')->justReturn(null);

		$db_row = [
			'id' => '1',
			'title' => 'Test Banner',
			'desktop_image_id' => null,
			'mobile_image_id' => null,
			'desktop_url' => 'https://example.com',
			'mobile_url' => null,
			'start_date' => null,
			'end_date' => null,
			'status' => 'active',
			'weight' => '1',
			'created_at' => '2024-01-01 00:00:00',
			'updated_at' => '2024-01-01 00:00:00',
		];

		$updated_row = [
			'id' => '1',
			'title' => 'Updated Banner',
			'desktop_image_id' => null,
			'mobile_image_id' => null,
			'desktop_url' => 'https://updated.com',
			'mobile_url' => null,
			'start_date' => null,
			'end_date' => null,
			'status' => 'active',
			'weight' => '1',
			'created_at' => '2024-01-01 00:00:00',
			'updated_at' => '2024-01-01 00:00:00',
		];

		$call_count = 0;
		$this->wpdb->shouldReceive('prepare')
			->andReturn('SELECT * FROM wp_sab_banners WHERE id = 1');

		$this->wpdb->shouldReceive('get_row')
			->andReturnUsing(function () use (&$call_count, $db_row, $updated_row) {
				$call_count++;
				// First two calls return db_row (exists check + before update), third returns updated
				return $call_count <= 2 ? $db_row : $updated_row;
			});

		$this->wpdb->shouldReceive('update')
			->once()
			->andReturn(1);

		$request = Mockery::mock('WP_REST_Request');
		$request->shouldReceive('get_param')
			->with('id')
			->andReturn(1);
		$request->shouldReceive('has_param')
			->with('title')
			->andReturn(true);
		$request->shouldReceive('get_param')
			->with('title')
			->andReturn('Updated Banner');
		$request->shouldReceive('has_param')
			->with('desktop_image_id')
			->andReturn(false);
		$request->shouldReceive('has_param')
			->with('mobile_image_id')
			->andReturn(false);
		$request->shouldReceive('has_param')
			->with('desktop_url')
			->andReturn(true);
		$request->shouldReceive('get_param')
			->with('desktop_url')
			->andReturn('https://updated.com');
		$request->shouldReceive('has_param')
			->with('mobile_url')
			->andReturn(false);
		$request->shouldReceive('has_param')
			->with('start_date')
			->andReturn(false);
		$request->shouldReceive('has_param')
			->with('end_date')
			->andReturn(false);
		$request->shouldReceive('has_param')
			->with('status')
			->andReturn(false);
		$request->shouldReceive('has_param')
			->with('weight')
			->andReturn(false);

		$controller = new Banner_Controller();
		$response = $controller->update_item($request);

		expect($response)->toBeInstanceOf(\WP_REST_Response::class);
		expect($response->get_status())->toBe(200);
		expect($response->get_data()['title'])->toBe('Updated Banner');
	});

	it('returns error when banner not found for update', function () {
		$this->wpdb->shouldReceive('prepare')
			->once()
			->andReturn('SELECT * FROM wp_sab_banners WHERE id = 999');

		$this->wpdb->shouldReceive('get_row')
			->once()
			->andReturn(null);

		$request = Mockery::mock('WP_REST_Request');
		$request->shouldReceive('get_param')
			->with('id')
			->andReturn(999);

		$controller = new Banner_Controller();
		$response = $controller->update_item($request);

		expect($response)->toBeInstanceOf(\WP_Error::class);
		expect($response->get_error_code())->toBe('rest_banner_not_found');
	});

	it('returns error when update fails', function () {
		Functions\when('sanitize_text_field')->returnArg();
		Functions\when('esc_url_raw')->returnArg();
		Functions\when('absint')->alias('intval');

		$db_row = [
			'id' => '1',
			'title' => 'Test Banner',
			'desktop_image_id' => null,
			'mobile_image_id' => null,
			'desktop_url' => 'https://example.com',
			'mobile_url' => null,
			'start_date' => null,
			'end_date' => null,
			'status' => 'active',
			'weight' => '1',
			'created_at' => '2024-01-01 00:00:00',
			'updated_at' => '2024-01-01 00:00:00',
		];

		$this->wpdb->shouldReceive('prepare')
			->andReturn('SELECT * FROM wp_sab_banners WHERE id = 1');

		$this->wpdb->shouldReceive('get_row')
			->andReturn($db_row);

		$this->wpdb->shouldReceive('update')
			->once()
			->andReturn(false);

		$request = Mockery::mock('WP_REST_Request');
		$request->shouldReceive('get_param')
			->with('id')
			->andReturn(1);
		$request->shouldReceive('has_param')
			->with('title')
			->andReturn(true);
		$request->shouldReceive('get_param')
			->with('title')
			->andReturn('Updated Banner');
		$request->shouldReceive('has_param')
			->with('desktop_image_id')
			->andReturn(false);
		$request->shouldReceive('has_param')
			->with('mobile_image_id')
			->andReturn(false);
		$request->shouldReceive('has_param')
			->with('desktop_url')
			->andReturn(false);
		$request->shouldReceive('has_param')
			->with('mobile_url')
			->andReturn(false);
		$request->shouldReceive('has_param')
			->with('start_date')
			->andReturn(false);
		$request->shouldReceive('has_param')
			->with('end_date')
			->andReturn(false);
		$request->shouldReceive('has_param')
			->with('status')
			->andReturn(false);
		$request->shouldReceive('has_param')
			->with('weight')
			->andReturn(false);

		$controller = new Banner_Controller();
		$response = $controller->update_item($request);

		expect($response)->toBeInstanceOf(\WP_Error::class);
		expect($response->get_error_code())->toBe('rest_banner_update_failed');
	});
});

describe('Banner_Controller::prepare_item_for_response() with images', function () {
	it('includes desktop and mobile image URLs when available', function () {
		Functions\when('wp_get_attachment_url')
			->alias(function ($id) {
				if ($id === 100) {
					return 'https://example.com/desktop.jpg';
				}
				if ($id === 200) {
					return 'https://example.com/mobile.jpg';
				}
				return false;
			});

		$db_row = [
			'id' => '1',
			'title' => 'Test Banner',
			'desktop_image_id' => '100',
			'mobile_image_id' => '200',
			'desktop_url' => 'https://example.com',
			'mobile_url' => null,
			'start_date' => null,
			'end_date' => null,
			'status' => 'active',
			'weight' => '1',
			'created_at' => '2024-01-01 00:00:00',
			'updated_at' => '2024-01-01 00:00:00',
		];

		$this->wpdb->shouldReceive('prepare')
			->once()
			->andReturn('SELECT * FROM wp_sab_banners WHERE id = 1');

		$this->wpdb->shouldReceive('get_row')
			->once()
			->andReturn($db_row);

		$request = Mockery::mock('WP_REST_Request');
		$request->shouldReceive('get_param')
			->with('id')
			->andReturn(1);

		$controller = new Banner_Controller();
		$response = $controller->get_item($request);

		expect($response)->toBeInstanceOf(\WP_REST_Response::class);
		$data = $response->get_data();
		expect($data['desktop_image_url'])->toBe('https://example.com/desktop.jpg');
		expect($data['mobile_image_url'])->toBe('https://example.com/mobile.jpg');
	});
});

describe('Banner_Controller::get_item_schema()', function () {
	it('returns valid JSON schema', function () {
		$controller = new Banner_Controller();
		$schema = $controller->get_item_schema();

		expect($schema)->toHaveKey('$schema');
		expect($schema)->toHaveKey('title');
		expect($schema)->toHaveKey('type');
		expect($schema)->toHaveKey('properties');
		expect($schema['title'])->toBe('banner');
		expect($schema['type'])->toBe('object');
	});

	it('schema includes all banner properties', function () {
		$controller = new Banner_Controller();
		$schema = $controller->get_item_schema();

		$properties = $schema['properties'];

		expect($properties)->toHaveKey('id');
		expect($properties)->toHaveKey('title');
		expect($properties)->toHaveKey('desktop_image_id');
		expect($properties)->toHaveKey('mobile_image_id');
		expect($properties)->toHaveKey('desktop_url');
		expect($properties)->toHaveKey('mobile_url');
		expect($properties)->toHaveKey('start_date');
		expect($properties)->toHaveKey('end_date');
		expect($properties)->toHaveKey('status');
		expect($properties)->toHaveKey('weight');
		expect($properties)->toHaveKey('created_at');
		expect($properties)->toHaveKey('updated_at');
	});

	it('caches schema on subsequent calls', function () {
		$controller = new Banner_Controller();

		$schema1 = $controller->get_item_schema();
		$schema2 = $controller->get_item_schema();

		expect($schema1)->toBe($schema2);
	});
});
