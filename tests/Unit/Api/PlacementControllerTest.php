<?php

/**
 * Tests for Placement_Controller class.
 *
 * @package SimpleAddBanners\Tests\Unit\Api
 *
 * @property \Mockery\MockInterface $wpdb
 */

declare(strict_types=1);

namespace SimpleAddBanners\Tests\Unit\Api;

use SimpleAddBanners\Api\Placement_Controller;
use Brain\Monkey\Functions;
use Mockery;

// Define WordPress constants if not defined.
if (! defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
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

describe('Placement_Controller constructor', function () {
    it('creates controller with correct namespace', function () {
        $controller = new Placement_Controller();

        $reflection = new \ReflectionClass($controller);
        $property = $reflection->getProperty('namespace');

        expect($property->getValue($controller))->toBe('sab/v1');
    });

    it('creates controller with correct rest base', function () {
        $controller = new Placement_Controller();

        $reflection = new \ReflectionClass($controller);
        $property = $reflection->getProperty('rest_base');

        expect($property->getValue($controller))->toBe('placements');
    });
});

describe('Placement_Controller::register_routes()', function () {
    it('registers collection and single item routes', function () {
        Functions\expect('register_rest_route')
            ->twice();

        $controller = new Placement_Controller();
        $controller->register_routes();
    });
});

describe('Placement_Controller permission checks', function () {
    it('get_items_permissions_check returns true for admin', function () {
        Functions\expect('current_user_can')
            ->with('manage_options')
            ->andReturn(true);

        $controller = new Placement_Controller();
        $request = Mockery::mock('WP_REST_Request');

        expect($controller->get_items_permissions_check($request))->toBeTrue();
    });

    it('get_items_permissions_check returns false for non-admin', function () {
        Functions\expect('current_user_can')
            ->with('manage_options')
            ->andReturn(false);

        $controller = new Placement_Controller();
        $request = Mockery::mock('WP_REST_Request');

        expect($controller->get_items_permissions_check($request))->toBeFalse();
    });

    it('create_item_permissions_check checks manage_options capability', function () {
        Functions\expect('current_user_can')
            ->with('manage_options')
            ->andReturn(true);

        $controller = new Placement_Controller();
        $request = Mockery::mock('WP_REST_Request');

        expect($controller->create_item_permissions_check($request))->toBeTrue();
    });

    it('update_item_permissions_check checks manage_options capability', function () {
        Functions\expect('current_user_can')
            ->with('manage_options')
            ->andReturn(true);

        $controller = new Placement_Controller();
        $request = Mockery::mock('WP_REST_Request');

        expect($controller->update_item_permissions_check($request))->toBeTrue();
    });

    it('delete_item_permissions_check checks manage_options capability', function () {
        Functions\expect('current_user_can')
            ->with('manage_options')
            ->andReturn(true);

        $controller = new Placement_Controller();
        $request = Mockery::mock('WP_REST_Request');

        expect($controller->delete_item_permissions_check($request))->toBeTrue();
    });

    it('get_item_permissions_check checks manage_options capability', function () {
        Functions\expect('current_user_can')
            ->with('manage_options')
            ->andReturn(true);

        $controller = new Placement_Controller();
        $request = Mockery::mock('WP_REST_Request');

        expect($controller->get_item_permissions_check($request))->toBeTrue();
    });
});

describe('Placement_Controller::get_items()', function () {
    it('returns list of placements', function () {
        $db_rows = [
            [
                'id' => '1',
                'slug' => 'header-banner',
                'name' => 'Header Banner',
                'rotation_strategy' => 'random',
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => '2024-01-01 00:00:00',
            ],
        ];

        $this->wpdb->shouldReceive('prepare')
            ->andReturn('SELECT * FROM wp_sab_placements LIMIT 10 OFFSET 0');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($db_rows);

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('1');

        $request = Mockery::mock('WP_REST_Request');
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

        $controller = new Placement_Controller();
        $response = $controller->get_items($request);

        expect($response)->toBeInstanceOf(\WP_REST_Response::class);
        expect($response->get_status())->toBe(200);

        $data = $response->get_data();
        expect($data)->toBeArray();
        expect(count($data))->toBe(1);
        expect($data[0]['name'])->toBe('Header Banner');
        expect($data[0]['slug'])->toBe('header-banner');
    });

    it('returns empty array when no placements exist', function () {
        $this->wpdb->shouldReceive('prepare')
            ->andReturn('SELECT * FROM wp_sab_placements LIMIT 10 OFFSET 0');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([]);

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('0');

        $request = Mockery::mock('WP_REST_Request');
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

        $controller = new Placement_Controller();
        $response = $controller->get_items($request);

        expect($response)->toBeInstanceOf(\WP_REST_Response::class);
        expect($response->get_data())->toBe([]);
    });
});

describe('Placement_Controller::get_item()', function () {
    it('returns placement when found', function () {
        $db_row = [
            'id' => '1',
            'slug' => 'sidebar-ad',
            'name' => 'Sidebar Ad',
            'rotation_strategy' => 'weighted',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $this->wpdb->shouldReceive('prepare')
            ->andReturn('SELECT * FROM wp_sab_placements WHERE id = 1');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn($db_row);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('id')
            ->andReturn(1);

        $controller = new Placement_Controller();
        $response = $controller->get_item($request);

        expect($response)->toBeInstanceOf(\WP_REST_Response::class);
        expect($response->get_status())->toBe(200);
        expect($response->get_data()['name'])->toBe('Sidebar Ad');
    });

    it('returns error when placement not found', function () {
        $this->wpdb->shouldReceive('prepare')
            ->andReturn('SELECT * FROM wp_sab_placements WHERE id = 999');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn(null);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('id')
            ->andReturn(999);

        $controller = new Placement_Controller();
        $response = $controller->get_item($request);

        expect($response)->toBeInstanceOf(\WP_Error::class);
        expect($response->get_error_code())->toBe('rest_placement_not_found');
    });
});

describe('Placement_Controller::create_item()', function () {
    it('creates placement successfully', function () {
        Functions\when('sanitize_title')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();

        // Check slug doesn't exist.
        $this->wpdb->shouldReceive('prepare')
            ->andReturn('SELECT COUNT(*) FROM wp_sab_placements WHERE slug = "new-placement"');

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('0');

        // Insert.
        $this->wpdb->shouldReceive('insert')
            ->once()
            ->andReturn(1);
        $this->wpdb->insert_id = 42;

        // Get created placement.
        $this->wpdb->shouldReceive('prepare')
            ->andReturn('SELECT * FROM wp_sab_placements WHERE id = 42');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn([
                'id' => '42',
                'slug' => 'new-placement',
                'name' => 'New Placement',
                'rotation_strategy' => 'random',
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => '2024-01-01 00:00:00',
            ]);

        Functions\when('rest_url')->returnArg();

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('slug')
            ->andReturn('new-placement');
        $request->shouldReceive('get_param')
            ->with('name')
            ->andReturn('New Placement');
        $request->shouldReceive('get_param')
            ->with('rotation_strategy')
            ->andReturn('random');

        $controller = new Placement_Controller();
        $response = $controller->create_item($request);

        expect($response)->toBeInstanceOf(\WP_REST_Response::class);
        expect($response->get_status())->toBe(201);
        expect($response->get_data()['slug'])->toBe('new-placement');
    });

    it('returns error when slug already exists', function () {
        Functions\when('sanitize_title')->returnArg();

        $this->wpdb->shouldReceive('prepare')
            ->andReturn('SELECT COUNT(*) FROM wp_sab_placements WHERE slug = "existing"');

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('1');

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('slug')
            ->andReturn('existing');

        $controller = new Placement_Controller();
        $response = $controller->create_item($request);

        expect($response)->toBeInstanceOf(\WP_Error::class);
        expect($response->get_error_code())->toBe('rest_placement_slug_exists');
    });

    it('returns error when create fails', function () {
        Functions\when('sanitize_title')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();

        // Slug doesn't exist.
        $this->wpdb->shouldReceive('prepare')
            ->andReturn('SELECT COUNT(*) FROM wp_sab_placements WHERE slug = "new-placement"');

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('0');

        // Insert fails.
        $this->wpdb->shouldReceive('insert')
            ->once()
            ->andReturn(false);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('slug')
            ->andReturn('new-placement');
        $request->shouldReceive('get_param')
            ->with('name')
            ->andReturn('New Placement');
        $request->shouldReceive('get_param')
            ->with('rotation_strategy')
            ->andReturn('random');

        $controller = new Placement_Controller();
        $response = $controller->create_item($request);

        expect($response)->toBeInstanceOf(\WP_Error::class);
        expect($response->get_error_code())->toBe('rest_placement_create_failed');
    });
});

describe('Placement_Controller::update_item()', function () {
    it('updates placement successfully', function () {
        Functions\when('sanitize_title')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();

        // Get existing.
        $db_row = [
            'id' => '1',
            'slug' => 'test',
            'name' => 'Test',
            'rotation_strategy' => 'random',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $updated_row = [
            'id' => '1',
            'slug' => 'test',
            'name' => 'Updated Name',
            'rotation_strategy' => 'weighted',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:01',
        ];

        $this->wpdb->shouldReceive('prepare')
            ->andReturn('SELECT * FROM wp_sab_placements WHERE id = 1');

        $this->wpdb->shouldReceive('get_row')
            ->andReturn($db_row, $db_row, $updated_row);

        $this->wpdb->shouldReceive('update')
            ->once()
            ->andReturn(1);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('id')
            ->andReturn(1);
        $request->shouldReceive('has_param')
            ->with('slug')
            ->andReturn(false);
        $request->shouldReceive('has_param')
            ->with('name')
            ->andReturn(true);
        $request->shouldReceive('get_param')
            ->with('name')
            ->andReturn('Updated Name');
        $request->shouldReceive('has_param')
            ->with('rotation_strategy')
            ->andReturn(true);
        $request->shouldReceive('get_param')
            ->with('rotation_strategy')
            ->andReturn('weighted');

        $controller = new Placement_Controller();
        $response = $controller->update_item($request);

        expect($response)->toBeInstanceOf(\WP_REST_Response::class);
        expect($response->get_status())->toBe(200);
    });

    it('returns error when placement not found', function () {
        $this->wpdb->shouldReceive('prepare')
            ->andReturn('SELECT * FROM wp_sab_placements WHERE id = 999');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn(null);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('id')
            ->andReturn(999);

        $controller = new Placement_Controller();
        $response = $controller->update_item($request);

        expect($response)->toBeInstanceOf(\WP_Error::class);
        expect($response->get_error_code())->toBe('rest_placement_not_found');
    });

    it('returns error when updating to existing slug', function () {
        Functions\when('sanitize_title')->returnArg();

        $db_row = [
            'id' => '1',
            'slug' => 'test',
            'name' => 'Test',
            'rotation_strategy' => 'random',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $this->wpdb->shouldReceive('prepare')
            ->andReturn('mock query');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn($db_row);

        // slug_exists check.
        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('1');

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('id')
            ->andReturn(1);
        $request->shouldReceive('has_param')
            ->with('slug')
            ->andReturn(true);
        $request->shouldReceive('get_param')
            ->with('slug')
            ->andReturn('existing-slug');

        $controller = new Placement_Controller();
        $response = $controller->update_item($request);

        expect($response)->toBeInstanceOf(\WP_Error::class);
        expect($response->get_error_code())->toBe('rest_placement_slug_exists');
    });

    it('returns error when update fails', function () {
        Functions\when('sanitize_title')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();

        $db_row = [
            'id' => '1',
            'slug' => 'test',
            'name' => 'Test',
            'rotation_strategy' => 'random',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $this->wpdb->shouldReceive('prepare')
            ->andReturn('mock query');

        $this->wpdb->shouldReceive('get_row')
            ->twice()
            ->andReturn($db_row);

        // Update returns false.
        $this->wpdb->shouldReceive('update')
            ->once()
            ->andReturn(false);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('id')
            ->andReturn(1);
        $request->shouldReceive('has_param')
            ->with('slug')
            ->andReturn(false);
        $request->shouldReceive('has_param')
            ->with('name')
            ->andReturn(true);
        $request->shouldReceive('get_param')
            ->with('name')
            ->andReturn('Updated Name');
        $request->shouldReceive('has_param')
            ->with('rotation_strategy')
            ->andReturn(false);

        $controller = new Placement_Controller();
        $response = $controller->update_item($request);

        expect($response)->toBeInstanceOf(\WP_Error::class);
        expect($response->get_error_code())->toBe('rest_placement_update_failed');
    });
});

describe('Placement_Controller::delete_item()', function () {
    it('deletes placement successfully', function () {
        $db_row = [
            'id' => '1',
            'slug' => 'test',
            'name' => 'Test',
            'rotation_strategy' => 'random',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $this->wpdb->shouldReceive('prepare')
            ->andReturn('SELECT * FROM wp_sab_placements WHERE id = 1');

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

        $controller = new Placement_Controller();
        $response = $controller->delete_item($request);

        expect($response)->toBeInstanceOf(\WP_REST_Response::class);
        expect($response->get_status())->toBe(204);
    });

    it('returns error when placement not found', function () {
        $this->wpdb->shouldReceive('prepare')
            ->andReturn('SELECT * FROM wp_sab_placements WHERE id = 999');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn(null);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('id')
            ->andReturn(999);

        $controller = new Placement_Controller();
        $response = $controller->delete_item($request);

        expect($response)->toBeInstanceOf(\WP_Error::class);
        expect($response->get_error_code())->toBe('rest_placement_not_found');
    });

    it('returns error when delete fails', function () {
        $db_row = [
            'id' => '1',
            'slug' => 'test',
            'name' => 'Test',
            'rotation_strategy' => 'random',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $this->wpdb->shouldReceive('prepare')
            ->andReturn('SELECT * FROM wp_sab_placements WHERE id = 1');

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

        $controller = new Placement_Controller();
        $response = $controller->delete_item($request);

        expect($response)->toBeInstanceOf(\WP_Error::class);
        expect($response->get_error_code())->toBe('rest_placement_delete_failed');
    });
});

describe('Placement_Controller::get_collection_params()', function () {
    it('returns correct collection params', function () {
        $controller = new Placement_Controller();
        $params = $controller->get_collection_params();

        expect($params)->toHaveKey('page');
        expect($params)->toHaveKey('per_page');
        expect($params)->toHaveKey('orderby');
        expect($params)->toHaveKey('order');

        expect($params['page']['default'])->toBe(1);
        expect($params['per_page']['default'])->toBe(10);
        expect($params['orderby']['default'])->toBe('created_at');
        expect($params['order']['default'])->toBe('DESC');
    });
});

describe('Placement_Controller::get_item_schema()', function () {
    it('returns correct item schema', function () {
        $controller = new Placement_Controller();
        $schema = $controller->get_item_schema();

        expect($schema)->toHaveKey('properties');
        expect($schema['properties'])->toHaveKey('id');
        expect($schema['properties'])->toHaveKey('slug');
        expect($schema['properties'])->toHaveKey('name');
        expect($schema['properties'])->toHaveKey('rotation_strategy');
        expect($schema['properties'])->toHaveKey('created_at');
        expect($schema['properties'])->toHaveKey('updated_at');

        expect($schema['properties']['rotation_strategy']['enum'])->toBe(['random', 'weighted', 'ordered']);
    });

    it('caches schema on subsequent calls', function () {
        $controller = new Placement_Controller();

        // First call creates the schema.
        $schema1 = $controller->get_item_schema();

        // Second call should return cached schema.
        $schema2 = $controller->get_item_schema();

        expect($schema1)->toBe($schema2);
    });
});
