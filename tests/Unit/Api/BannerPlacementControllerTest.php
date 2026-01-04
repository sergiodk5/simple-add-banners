<?php

/**
 * Tests for Banner_Placement_Controller class.
 *
 * @package SimpleAddBanners\Tests\Unit\Api
 *
 * @property \Mockery\MockInterface $wpdb
 */

declare(strict_types=1);

namespace SimpleAddBanners\Tests\Unit\Api;

use SimpleAddBanners\Api\Banner_Placement_Controller;
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

describe('Banner_Placement_Controller constructor', function () {
    it('creates controller with correct namespace', function () {
        $controller = new Banner_Placement_Controller();

        $reflection = new \ReflectionClass($controller);
        $property = $reflection->getProperty('namespace');

        expect($property->getValue($controller))->toBe('sab/v1');
    });

    it('creates controller with correct rest base', function () {
        $controller = new Banner_Placement_Controller();

        $reflection = new \ReflectionClass($controller);
        $property = $reflection->getProperty('rest_base');

        expect($property->getValue($controller))->toBe('placements');
    });
});

describe('Banner_Placement_Controller::register_routes()', function () {
    it('registers banner assignment routes', function () {
        Functions\expect('register_rest_route')
            ->times(3); // GET/PUT, POST, DELETE routes

        $controller = new Banner_Placement_Controller();
        $controller->register_routes();
    });
});

describe('Banner_Placement_Controller permission checks', function () {
    it('get_items_permissions_check returns true for admin', function () {
        Functions\expect('current_user_can')
            ->with('manage_options')
            ->andReturn(true);

        $controller = new Banner_Placement_Controller();
        $request = Mockery::mock('WP_REST_Request');

        expect($controller->get_items_permissions_check($request))->toBeTrue();
    });

    it('get_items_permissions_check returns false for non-admin', function () {
        Functions\expect('current_user_can')
            ->with('manage_options')
            ->andReturn(false);

        $controller = new Banner_Placement_Controller();
        $request = Mockery::mock('WP_REST_Request');

        expect($controller->get_items_permissions_check($request))->toBeFalse();
    });

    it('create_item_permissions_check checks manage_options capability', function () {
        Functions\expect('current_user_can')
            ->with('manage_options')
            ->andReturn(true);

        $controller = new Banner_Placement_Controller();
        $request = Mockery::mock('WP_REST_Request');

        expect($controller->create_item_permissions_check($request))->toBeTrue();
    });

    it('update_item_permissions_check checks manage_options capability', function () {
        Functions\expect('current_user_can')
            ->with('manage_options')
            ->andReturn(true);

        $controller = new Banner_Placement_Controller();
        $request = Mockery::mock('WP_REST_Request');

        expect($controller->update_item_permissions_check($request))->toBeTrue();
    });

    it('delete_item_permissions_check checks manage_options capability', function () {
        Functions\expect('current_user_can')
            ->with('manage_options')
            ->andReturn(true);

        $controller = new Banner_Placement_Controller();
        $request = Mockery::mock('WP_REST_Request');

        expect($controller->delete_item_permissions_check($request))->toBeTrue();
    });
});

describe('Banner_Placement_Controller::get_items()', function () {
    it('returns banners assigned to placement', function () {
        // Mock placement exists.
        $placement_row = [
            'id' => '1',
            'slug' => 'header',
            'name' => 'Header',
            'rotation_strategy' => 'random',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $banner_row = [
            'id' => '10',
            'title' => 'Test Banner',
            'desktop_image_id' => '100',
            'mobile_image_id' => null,
            'desktop_url' => 'https://example.com',
            'mobile_url' => null,
            'start_date' => null,
            'end_date' => null,
            'status' => 'active',
            'weight' => '1',
            'position' => '0',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $this->wpdb->shouldReceive('prepare')->andReturn('SELECT query');
        $this->wpdb->shouldReceive('get_row')->once()->andReturn($placement_row);
        $this->wpdb->shouldReceive('get_results')->once()->andReturn([$banner_row]);

        Functions\when('wp_get_attachment_url')->justReturn('https://example.com/image.jpg');

        $controller = new Banner_Placement_Controller();

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('id')
            ->andReturn(1);

        $response = $controller->get_items($request);

        expect($response)->toBeInstanceOf('WP_REST_Response');
        expect($response->get_status())->toBe(200);

        $data = $response->get_data();
        expect($data)->toBeArray();
        expect($data[0]['id'])->toBe(10);
        expect($data[0]['title'])->toBe('Test Banner');
        expect($data[0]['position'])->toBe(0);
    });

    it('returns error when placement not found', function () {
        $this->wpdb->shouldReceive('prepare')->andReturn('SELECT query');
        $this->wpdb->shouldReceive('get_row')->once()->andReturn(null);

        $controller = new Banner_Placement_Controller();

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('id')
            ->andReturn(999);

        $response = $controller->get_items($request);

        expect($response)->toBeInstanceOf('WP_Error');
        expect($response->get_error_code())->toBe('rest_placement_not_found');
    });

    it('returns empty array when no banners assigned', function () {
        $placement_row = [
            'id' => '1',
            'slug' => 'header',
            'name' => 'Header',
            'rotation_strategy' => 'random',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $this->wpdb->shouldReceive('prepare')->andReturn('SELECT query');
        $this->wpdb->shouldReceive('get_row')->once()->andReturn($placement_row);
        $this->wpdb->shouldReceive('get_results')->once()->andReturn([]);

        $controller = new Banner_Placement_Controller();

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('id')
            ->andReturn(1);

        $response = $controller->get_items($request);

        expect($response)->toBeInstanceOf('WP_REST_Response');
        expect($response->get_data())->toBe([]);
    });
});

describe('Banner_Placement_Controller::sync_items()', function () {
    it('syncs banners successfully', function () {
        $placement_row = [
            'id' => '1',
            'slug' => 'header',
            'name' => 'Header',
            'rotation_strategy' => 'random',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $this->wpdb->shouldReceive('prepare')->andReturn('SELECT query');
        $this->wpdb->shouldReceive('get_row')->once()->andReturn($placement_row);
        $this->wpdb->shouldReceive('delete')->once()->andReturn(0);
        $this->wpdb->shouldReceive('insert')->times(2)->andReturn(1);
        $this->wpdb->insert_id = 1;
        $this->wpdb->shouldReceive('get_results')->once()->andReturn([]);

        $controller = new Banner_Placement_Controller();

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('id')
            ->andReturn(1);
        $request->shouldReceive('get_param')
            ->with('banner_ids')
            ->andReturn([10, 20]);

        $response = $controller->sync_items($request);

        expect($response)->toBeInstanceOf('WP_REST_Response');
        expect($response->get_status())->toBe(200);
    });

    it('returns synced banners with image URLs', function () {
        $placement_row = [
            'id' => '1',
            'slug' => 'header',
            'name' => 'Header',
            'rotation_strategy' => 'random',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $banner_row = [
            'id' => '10',
            'title' => 'Synced Banner',
            'desktop_image_id' => '100',
            'mobile_image_id' => '200',
            'desktop_url' => 'https://example.com',
            'mobile_url' => 'https://m.example.com',
            'start_date' => '2024-01-01 00:00:00',
            'end_date' => '2024-12-31 23:59:59',
            'status' => 'active',
            'weight' => '5',
            'position' => '0',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $this->wpdb->shouldReceive('prepare')->andReturn('SELECT query');
        $this->wpdb->shouldReceive('get_row')->once()->andReturn($placement_row);
        $this->wpdb->shouldReceive('delete')->once()->andReturn(0);
        $this->wpdb->shouldReceive('insert')->once()->andReturn(1);
        $this->wpdb->insert_id = 1;
        $this->wpdb->shouldReceive('get_results')->once()->andReturn([$banner_row]);

        Functions\when('wp_get_attachment_url')->alias(function ($id) {
            return "https://example.com/image-{$id}.jpg";
        });

        $controller = new Banner_Placement_Controller();

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('id')
            ->andReturn(1);
        $request->shouldReceive('get_param')
            ->with('banner_ids')
            ->andReturn([10]);

        $response = $controller->sync_items($request);

        expect($response)->toBeInstanceOf('WP_REST_Response');
        expect($response->get_status())->toBe(200);

        $data = $response->get_data();
        expect($data)->toBeArray();
        expect($data)->toHaveCount(1);
        expect($data[0]['id'])->toBe(10);
        expect($data[0]['title'])->toBe('Synced Banner');
        expect($data[0]['desktop_image_url'])->toBe('https://example.com/image-100.jpg');
        expect($data[0]['mobile_image_url'])->toBe('https://example.com/image-200.jpg');
    });

    it('returns error when placement not found', function () {
        $this->wpdb->shouldReceive('prepare')->andReturn('SELECT query');
        $this->wpdb->shouldReceive('get_row')->once()->andReturn(null);

        $controller = new Banner_Placement_Controller();

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('id')
            ->andReturn(999);
        $request->shouldReceive('get_param')
            ->with('banner_ids')
            ->andReturn([10]);

        $response = $controller->sync_items($request);

        expect($response)->toBeInstanceOf('WP_Error');
        expect($response->get_error_code())->toBe('rest_placement_not_found');
    });

    it('returns error when sync fails', function () {
        $placement_row = [
            'id' => '1',
            'slug' => 'header',
            'name' => 'Header',
            'rotation_strategy' => 'random',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $this->wpdb->shouldReceive('prepare')->andReturn('SELECT query');
        $this->wpdb->shouldReceive('get_row')->once()->andReturn($placement_row);
        $this->wpdb->shouldReceive('delete')->once()->andReturn(0);
        $this->wpdb->shouldReceive('insert')->once()->andReturn(false);

        $controller = new Banner_Placement_Controller();

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('id')
            ->andReturn(1);
        $request->shouldReceive('get_param')
            ->with('banner_ids')
            ->andReturn([10]);

        $response = $controller->sync_items($request);

        expect($response)->toBeInstanceOf('WP_Error');
        expect($response->get_error_code())->toBe('rest_banner_placement_sync_failed');
    });
});

describe('Banner_Placement_Controller::create_item()', function () {
    it('attaches banner to placement successfully', function () {
        $placement_row = [
            'id' => '1',
            'slug' => 'header',
            'name' => 'Header',
            'rotation_strategy' => 'random',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $this->wpdb->shouldReceive('prepare')->andReturn('SELECT query');
        $this->wpdb->shouldReceive('get_row')->once()->andReturn($placement_row);
        $this->wpdb->shouldReceive('get_var')->once()->andReturn('0'); // is_attached check.
        $this->wpdb->shouldReceive('insert')->once()->andReturn(1);
        $this->wpdb->insert_id = 42;
        $this->wpdb->shouldReceive('get_results')->once()->andReturn([]);

        $controller = new Banner_Placement_Controller();

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('id')
            ->andReturn(1);
        $request->shouldReceive('get_param')
            ->with('banner_id')
            ->andReturn(10);
        $request->shouldReceive('get_param')
            ->with('position')
            ->andReturn(0);

        $response = $controller->create_item($request);

        expect($response)->toBeInstanceOf('WP_REST_Response');
        expect($response->get_status())->toBe(201);
    });

    it('returns attached banners with formatted response', function () {
        $placement_row = [
            'id' => '1',
            'slug' => 'header',
            'name' => 'Header',
            'rotation_strategy' => 'random',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $banner_row = [
            'id' => '10',
            'title' => 'Attached Banner',
            'desktop_image_id' => '100',
            'mobile_image_id' => null,
            'desktop_url' => 'https://example.com',
            'mobile_url' => null,
            'start_date' => null,
            'end_date' => null,
            'status' => 'active',
            'weight' => '1',
            'position' => '0',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $this->wpdb->shouldReceive('prepare')->andReturn('SELECT query');
        $this->wpdb->shouldReceive('get_row')->once()->andReturn($placement_row);
        $this->wpdb->shouldReceive('get_var')->once()->andReturn('0');
        $this->wpdb->shouldReceive('insert')->once()->andReturn(1);
        $this->wpdb->insert_id = 42;
        $this->wpdb->shouldReceive('get_results')->once()->andReturn([$banner_row]);

        Functions\when('wp_get_attachment_url')->justReturn('https://example.com/image.jpg');

        $controller = new Banner_Placement_Controller();

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('id')
            ->andReturn(1);
        $request->shouldReceive('get_param')
            ->with('banner_id')
            ->andReturn(10);
        $request->shouldReceive('get_param')
            ->with('position')
            ->andReturn(0);

        $response = $controller->create_item($request);

        expect($response)->toBeInstanceOf('WP_REST_Response');
        expect($response->get_status())->toBe(201);

        $data = $response->get_data();
        expect($data)->toBeArray();
        expect($data)->toHaveCount(1);
        expect($data[0]['id'])->toBe(10);
        expect($data[0]['title'])->toBe('Attached Banner');
    });

    it('returns error when placement not found', function () {
        $this->wpdb->shouldReceive('prepare')->andReturn('SELECT query');
        $this->wpdb->shouldReceive('get_row')->once()->andReturn(null);

        $controller = new Banner_Placement_Controller();

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('id')
            ->andReturn(999);
        $request->shouldReceive('get_param')
            ->with('banner_id')
            ->andReturn(10);
        $request->shouldReceive('get_param')
            ->with('position')
            ->andReturn(0);

        $response = $controller->create_item($request);

        expect($response)->toBeInstanceOf('WP_Error');
        expect($response->get_error_code())->toBe('rest_placement_not_found');
    });

    it('returns error when banner already attached', function () {
        $placement_row = [
            'id' => '1',
            'slug' => 'header',
            'name' => 'Header',
            'rotation_strategy' => 'random',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $this->wpdb->shouldReceive('prepare')->andReturn('SELECT query');
        $this->wpdb->shouldReceive('get_row')->once()->andReturn($placement_row);
        $this->wpdb->shouldReceive('get_var')->once()->andReturn('1'); // is_attached returns true.

        $controller = new Banner_Placement_Controller();

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('id')
            ->andReturn(1);
        $request->shouldReceive('get_param')
            ->with('banner_id')
            ->andReturn(10);
        $request->shouldReceive('get_param')
            ->with('position')
            ->andReturn(0);

        $response = $controller->create_item($request);

        expect($response)->toBeInstanceOf('WP_Error');
        expect($response->get_error_code())->toBe('rest_banner_already_attached');
    });

    it('returns error when attach fails', function () {
        $placement_row = [
            'id' => '1',
            'slug' => 'header',
            'name' => 'Header',
            'rotation_strategy' => 'random',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $this->wpdb->shouldReceive('prepare')->andReturn('SELECT query');
        $this->wpdb->shouldReceive('get_row')->once()->andReturn($placement_row);
        $this->wpdb->shouldReceive('get_var')->once()->andReturn('0');
        $this->wpdb->shouldReceive('insert')->once()->andReturn(false);

        $controller = new Banner_Placement_Controller();

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('id')
            ->andReturn(1);
        $request->shouldReceive('get_param')
            ->with('banner_id')
            ->andReturn(10);
        $request->shouldReceive('get_param')
            ->with('position')
            ->andReturn(0);

        $response = $controller->create_item($request);

        expect($response)->toBeInstanceOf('WP_Error');
        expect($response->get_error_code())->toBe('rest_banner_placement_attach_failed');
    });
});

describe('Banner_Placement_Controller::delete_item()', function () {
    it('detaches banner from placement successfully', function () {
        $placement_row = [
            'id' => '1',
            'slug' => 'header',
            'name' => 'Header',
            'rotation_strategy' => 'random',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $this->wpdb->shouldReceive('prepare')->andReturn('SELECT query');
        $this->wpdb->shouldReceive('get_row')->once()->andReturn($placement_row);
        $this->wpdb->shouldReceive('get_var')->once()->andReturn('1'); // is_attached returns true.
        $this->wpdb->shouldReceive('delete')->once()->andReturn(1);

        $controller = new Banner_Placement_Controller();

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('id')
            ->andReturn(1);
        $request->shouldReceive('get_param')
            ->with('banner_id')
            ->andReturn(10);

        $response = $controller->delete_item($request);

        expect($response)->toBeInstanceOf('WP_REST_Response');
        expect($response->get_status())->toBe(204);
    });

    it('returns error when placement not found', function () {
        $this->wpdb->shouldReceive('prepare')->andReturn('SELECT query');
        $this->wpdb->shouldReceive('get_row')->once()->andReturn(null);

        $controller = new Banner_Placement_Controller();

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('id')
            ->andReturn(999);
        $request->shouldReceive('get_param')
            ->with('banner_id')
            ->andReturn(10);

        $response = $controller->delete_item($request);

        expect($response)->toBeInstanceOf('WP_Error');
        expect($response->get_error_code())->toBe('rest_placement_not_found');
    });

    it('returns error when banner not attached', function () {
        $placement_row = [
            'id' => '1',
            'slug' => 'header',
            'name' => 'Header',
            'rotation_strategy' => 'random',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $this->wpdb->shouldReceive('prepare')->andReturn('SELECT query');
        $this->wpdb->shouldReceive('get_row')->once()->andReturn($placement_row);
        $this->wpdb->shouldReceive('get_var')->once()->andReturn('0'); // is_attached returns false.

        $controller = new Banner_Placement_Controller();

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('id')
            ->andReturn(1);
        $request->shouldReceive('get_param')
            ->with('banner_id')
            ->andReturn(10);

        $response = $controller->delete_item($request);

        expect($response)->toBeInstanceOf('WP_Error');
        expect($response->get_error_code())->toBe('rest_banner_not_attached');
    });

    it('returns error when detach fails', function () {
        $placement_row = [
            'id' => '1',
            'slug' => 'header',
            'name' => 'Header',
            'rotation_strategy' => 'random',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $this->wpdb->shouldReceive('prepare')->andReturn('SELECT query');
        $this->wpdb->shouldReceive('get_row')->once()->andReturn($placement_row);
        $this->wpdb->shouldReceive('get_var')->once()->andReturn('1');
        $this->wpdb->shouldReceive('delete')->once()->andReturn(false);

        $controller = new Banner_Placement_Controller();

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('id')
            ->andReturn(1);
        $request->shouldReceive('get_param')
            ->with('banner_id')
            ->andReturn(10);

        $response = $controller->delete_item($request);

        expect($response)->toBeInstanceOf('WP_Error');
        expect($response->get_error_code())->toBe('rest_banner_placement_detach_failed');
    });
});
