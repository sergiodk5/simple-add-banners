<?php

/**
 * Tests for Placement_Repository class.
 *
 * @package SimpleAddBanners\Tests\Unit\Repository
 *
 * @property \Mockery\MockInterface $wpdb
 */

declare(strict_types=1);

namespace SimpleAddBanners\Tests\Unit\Repository;

use SimpleAddBanners\Repository\Placement_Repository;
use Brain\Monkey\Functions;

// Define WordPress constant if not defined.
if (! defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

beforeEach(function () {
    // Mock wpdb.
    $this->wpdb = \Mockery::mock('wpdb');
    $this->wpdb->prefix = 'wp_';
    $GLOBALS['wpdb'] = $this->wpdb;

    // Common WordPress function mocks.
    Functions\when('wp_parse_args')->alias(function ($args, $defaults) {
        return array_merge($defaults, $args);
    });
});

afterEach(function () {
    unset($GLOBALS['wpdb']);
});

describe('Placement_Repository constructor', function () {
    it('sets the table name with prefix', function () {
        $repository = new Placement_Repository();

        $reflection = new \ReflectionClass($repository);
        $property = $reflection->getProperty('table_name');

        expect($property->getValue($repository))->toBe('wp_sab_placements');
    });
});

describe('Placement_Repository::get_all()', function () {
    it('returns empty array when no placements exist', function () {
        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn(null);

        $repository = new Placement_Repository();
        $result = $repository->get_all();

        expect($result)->toBe([]);
    });

    it('returns formatted placements', function () {
        $db_row = [
            'id' => '1',
            'slug' => 'header-banner',
            'name' => 'Header Banner',
            'rotation_strategy' => 'random',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([$db_row]);

        $repository = new Placement_Repository();
        $result = $repository->get_all();

        expect($result)->toBeArray();
        expect($result[0]['id'])->toBe(1);
        expect($result[0]['slug'])->toBe('header-banner');
        expect($result[0]['name'])->toBe('Header Banner');
        expect($result[0]['rotation_strategy'])->toBe('random');
    });

    it('applies limit and offset', function () {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT * FROM wp_sab_placements ORDER BY created_at DESC LIMIT 10 OFFSET 20');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([]);

        $repository = new Placement_Repository();
        $result = $repository->get_all(['limit' => 10, 'offset' => 20]);

        expect($result)->toBe([]);
    });

    it('orders by specified column', function () {
        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->with(\Mockery::on(function ($sql) {
                return str_contains($sql, 'ORDER BY name ASC');
            }), \Mockery::any())
            ->andReturn([]);

        $repository = new Placement_Repository();
        $repository->get_all(['order_by' => 'name', 'order' => 'ASC']);
    });
});

describe('Placement_Repository::get_by_id()', function () {
    it('returns null when placement not found', function () {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT * FROM wp_sab_placements WHERE id = 999');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn(null);

        $repository = new Placement_Repository();
        $result = $repository->get_by_id(999);

        expect($result)->toBeNull();
    });

    it('returns formatted placement when found', function () {
        $db_row = [
            'id' => '1',
            'slug' => 'sidebar-ad',
            'name' => 'Sidebar Ad',
            'rotation_strategy' => 'weighted',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT * FROM wp_sab_placements WHERE id = 1');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn($db_row);

        $repository = new Placement_Repository();
        $result = $repository->get_by_id(1);

        expect($result)->toBeArray();
        expect($result['id'])->toBe(1);
        expect($result['slug'])->toBe('sidebar-ad');
        expect($result['rotation_strategy'])->toBe('weighted');
    });
});

describe('Placement_Repository::get_by_slug()', function () {
    it('returns null when placement not found', function () {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT * FROM wp_sab_placements WHERE slug = "nonexistent"');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn(null);

        $repository = new Placement_Repository();
        $result = $repository->get_by_slug('nonexistent');

        expect($result)->toBeNull();
    });

    it('returns formatted placement when found', function () {
        $db_row = [
            'id' => '1',
            'slug' => 'footer-banner',
            'name' => 'Footer Banner',
            'rotation_strategy' => 'ordered',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT * FROM wp_sab_placements WHERE slug = "footer-banner"');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn($db_row);

        $repository = new Placement_Repository();
        $result = $repository->get_by_slug('footer-banner');

        expect($result)->toBeArray();
        expect($result['id'])->toBe(1);
        expect($result['slug'])->toBe('footer-banner');
    });
});

describe('Placement_Repository::create()', function () {
    it('creates a placement and returns the ID', function () {
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('sanitize_title')->returnArg();

        $this->wpdb->shouldReceive('insert')
            ->once()
            ->andReturn(1);

        $this->wpdb->insert_id = 42;

        $repository = new Placement_Repository();
        $result = $repository->create([
            'slug' => 'new-placement',
            'name' => 'New Placement',
        ]);

        expect($result)->toBe(42);
    });

    it('returns false on insert failure', function () {
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('sanitize_title')->returnArg();

        $this->wpdb->shouldReceive('insert')
            ->once()
            ->andReturn(false);

        $repository = new Placement_Repository();
        $result = $repository->create([
            'slug' => 'failed-placement',
            'name' => 'Failed Placement',
        ]);

        expect($result)->toBeFalse();
    });

    it('uses default rotation_strategy', function () {
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('sanitize_title')->returnArg();

        $this->wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_sab_placements',
                \Mockery::on(function ($data) {
                    return $data['rotation_strategy'] === 'random';
                }),
                \Mockery::any()
            )
            ->andReturn(1);

        $this->wpdb->insert_id = 1;

        $repository = new Placement_Repository();
        $repository->create([
            'slug' => 'test',
            'name' => 'Test',
        ]);
    });

    it('sanitizes rotation_strategy to valid value', function () {
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('sanitize_title')->returnArg();

        $this->wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_sab_placements',
                \Mockery::on(function ($data) {
                    return $data['rotation_strategy'] === 'random'; // Invalid value defaults to 'random'.
                }),
                \Mockery::any()
            )
            ->andReturn(1);

        $this->wpdb->insert_id = 1;

        $repository = new Placement_Repository();
        $repository->create([
            'slug' => 'test',
            'name' => 'Test',
            'rotation_strategy' => 'invalid_strategy',
        ]);
    });
});

describe('Placement_Repository::update()', function () {
    it('returns false when placement does not exist', function () {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT * FROM wp_sab_placements WHERE id = 999');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn(null);

        $repository = new Placement_Repository();
        $result = $repository->update(999, ['name' => 'Updated']);

        expect($result)->toBeFalse();
    });

    it('updates placement successfully', function () {
        Functions\when('sanitize_text_field')->returnArg();
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
            ->once()
            ->andReturn('SELECT * FROM wp_sab_placements WHERE id = 1');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn($db_row);

        $this->wpdb->shouldReceive('update')
            ->once()
            ->andReturn(1);

        $repository = new Placement_Repository();
        $result = $repository->update(1, ['name' => 'Updated Name']);

        expect($result)->toBeTrue();
    });

    it('returns true when no fields to update', function () {
        $db_row = [
            'id' => '1',
            'slug' => 'test',
            'name' => 'Test',
            'rotation_strategy' => 'random',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT * FROM wp_sab_placements WHERE id = 1');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn($db_row);

        $repository = new Placement_Repository();
        $result = $repository->update(1, []);

        expect($result)->toBeTrue();
    });

    it('updates rotation_strategy field', function () {
        Functions\when('sanitize_text_field')->returnArg();
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
            ->once()
            ->andReturn('SELECT * FROM wp_sab_placements WHERE id = 1');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn($db_row);

        $this->wpdb->shouldReceive('update')
            ->once()
            ->with(
                'wp_sab_placements',
                \Mockery::on(function ($data) {
                    return $data['rotation_strategy'] === 'weighted';
                }),
                ['id' => 1],
                ['%s'],
                ['%d']
            )
            ->andReturn(1);

        $repository = new Placement_Repository();
        $result = $repository->update(1, ['rotation_strategy' => 'weighted']);

        expect($result)->toBeTrue();
    });

    it('updates slug field', function () {
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('sanitize_title')->returnArg();

        $db_row = [
            'id' => '1',
            'slug' => 'old-slug',
            'name' => 'Test',
            'rotation_strategy' => 'random',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT * FROM wp_sab_placements WHERE id = 1');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn($db_row);

        $this->wpdb->shouldReceive('update')
            ->once()
            ->with(
                'wp_sab_placements',
                \Mockery::on(function ($data) {
                    return $data['slug'] === 'new-slug';
                }),
                ['id' => 1],
                ['%s'],
                ['%d']
            )
            ->andReturn(1);

        $repository = new Placement_Repository();
        $result = $repository->update(1, ['slug' => 'new-slug']);

        expect($result)->toBeTrue();
    });

    it('returns false when update query fails', function () {
        Functions\when('sanitize_text_field')->returnArg();
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
            ->once()
            ->andReturn('SELECT * FROM wp_sab_placements WHERE id = 1');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn($db_row);

        $this->wpdb->shouldReceive('update')
            ->once()
            ->andReturn(false);

        $repository = new Placement_Repository();
        $result = $repository->update(1, ['name' => 'Updated']);

        expect($result)->toBeFalse();
    });
});

describe('Placement_Repository::delete()', function () {
    it('deletes placement successfully', function () {
        $this->wpdb->shouldReceive('delete')
            ->once()
            ->with('wp_sab_placements', ['id' => 1], ['%d'])
            ->andReturn(1);

        $repository = new Placement_Repository();
        $result = $repository->delete(1);

        expect($result)->toBeTrue();
    });

    it('returns false when delete fails', function () {
        $this->wpdb->shouldReceive('delete')
            ->once()
            ->andReturn(false);

        $repository = new Placement_Repository();
        $result = $repository->delete(999);

        expect($result)->toBeFalse();
    });

    it('returns false when no rows affected', function () {
        $this->wpdb->shouldReceive('delete')
            ->once()
            ->andReturn(0);

        $repository = new Placement_Repository();
        $result = $repository->delete(999);

        expect($result)->toBeFalse();
    });
});

describe('Placement_Repository::count()', function () {
    it('returns total count', function () {
        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('5');

        $repository = new Placement_Repository();
        $result = $repository->count();

        expect($result)->toBe(5);
    });

    it('returns zero when no placements', function () {
        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn(null);

        $repository = new Placement_Repository();
        $result = $repository->count();

        expect($result)->toBe(0);
    });
});

describe('Placement_Repository::slug_exists()', function () {
    it('returns true when slug exists', function () {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT COUNT(*) FROM wp_sab_placements WHERE slug = "existing"');

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('1');

        $repository = new Placement_Repository();
        $result = $repository->slug_exists('existing');

        expect($result)->toBeTrue();
    });

    it('returns false when slug does not exist', function () {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT COUNT(*) FROM wp_sab_placements WHERE slug = "nonexistent"');

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('0');

        $repository = new Placement_Repository();
        $result = $repository->slug_exists('nonexistent');

        expect($result)->toBeFalse();
    });

    it('excludes specified ID from check', function () {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with(
                \Mockery::on(function ($sql) {
                    return str_contains($sql, 'id != %d');
                }),
                'test-slug',
                5
            )
            ->andReturn('SELECT COUNT(*) FROM wp_sab_placements WHERE slug = "test-slug" AND id != 5');

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('0');

        $repository = new Placement_Repository();
        $result = $repository->slug_exists('test-slug', 5);

        expect($result)->toBeFalse();
    });
});
