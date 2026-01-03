<?php
/**
 * Tests for Banner_Repository class.
 *
 * @package SimpleAddBanners\Tests\Unit\Repository
 *
 * @property \Mockery\MockInterface $wpdb
 */

declare(strict_types=1);

namespace SimpleAddBanners\Tests\Unit\Repository;

use SimpleAddBanners\Repository\Banner_Repository;
use Brain\Monkey\Functions;

// Define WordPress constant if not defined.
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
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

describe('Banner_Repository constructor', function () {
	it('sets the table name with prefix', function () {
		$repository = new Banner_Repository();

		$reflection = new \ReflectionClass($repository);
		$property = $reflection->getProperty('table_name');

		expect($property->getValue($repository))->toBe('wp_sab_banners');
	});
});

describe('Banner_Repository::get_all()', function () {
	it('returns empty array when no banners exist', function () {
		$this->wpdb->shouldReceive('get_results')
			->once()
			->andReturn(null);

		$repository = new Banner_Repository();
		$result = $repository->get_all();

		expect($result)->toBe([]);
	});

	it('returns formatted banners', function () {
		$db_row = [
			'id' => '1',
			'title' => 'Test Banner',
			'desktop_image_id' => '10',
			'mobile_image_id' => null,
			'desktop_url' => 'https://example.com',
			'mobile_url' => null,
			'start_date' => '2024-01-01 00:00:00',
			'end_date' => null,
			'status' => 'active',
			'weight' => '5',
			'created_at' => '2024-01-01 00:00:00',
			'updated_at' => '2024-01-01 00:00:00',
		];

		$this->wpdb->shouldReceive('get_results')
			->once()
			->andReturn([$db_row]);

		$repository = new Banner_Repository();
		$result = $repository->get_all();

		expect($result)->toBeArray();
		expect($result[0]['id'])->toBe(1);
		expect($result[0]['title'])->toBe('Test Banner');
		expect($result[0]['desktop_image_id'])->toBe(10);
		expect($result[0]['mobile_image_id'])->toBeNull();
		expect($result[0]['weight'])->toBe(5);
	});

	it('filters by status when provided', function () {
		$this->wpdb->shouldReceive('prepare')
			->once()
			->andReturn('SELECT * FROM wp_sab_banners WHERE status = \'active\' ORDER BY created_at DESC');

		$this->wpdb->shouldReceive('get_results')
			->once()
			->andReturn([]);

		$repository = new Banner_Repository();
		$result = $repository->get_all(['status' => 'active']);

		expect($result)->toBe([]);
	});

	it('applies limit and offset', function () {
		$this->wpdb->shouldReceive('prepare')
			->once()
			->andReturn('SELECT * FROM wp_sab_banners ORDER BY created_at DESC LIMIT 10 OFFSET 20');

		$this->wpdb->shouldReceive('get_results')
			->once()
			->andReturn([]);

		$repository = new Banner_Repository();
		$result = $repository->get_all(['limit' => 10, 'offset' => 20]);

		expect($result)->toBe([]);
	});

	it('orders by specified column', function () {
		$this->wpdb->shouldReceive('get_results')
			->once()
			->with(\Mockery::on(function ($sql) {
				return str_contains($sql, 'ORDER BY title ASC');
			}), \Mockery::any())
			->andReturn([]);

		$repository = new Banner_Repository();
		$repository->get_all(['order_by' => 'title', 'order' => 'ASC']);
	});
});

describe('Banner_Repository::get_by_id()', function () {
	it('returns null when banner not found', function () {
		$this->wpdb->shouldReceive('prepare')
			->once()
			->andReturn('SELECT * FROM wp_sab_banners WHERE id = 999');

		$this->wpdb->shouldReceive('get_row')
			->once()
			->andReturn(null);

		$repository = new Banner_Repository();
		$result = $repository->get_by_id(999);

		expect($result)->toBeNull();
	});

	it('returns formatted banner when found', function () {
		$db_row = [
			'id' => '1',
			'title' => 'Test Banner',
			'desktop_image_id' => '10',
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

		$repository = new Banner_Repository();
		$result = $repository->get_by_id(1);

		expect($result)->toBeArray();
		expect($result['id'])->toBe(1);
		expect($result['title'])->toBe('Test Banner');
	});
});

describe('Banner_Repository::create()', function () {
	it('creates a banner and returns the ID', function () {
		Functions\when('sanitize_text_field')->returnArg();
		Functions\when('esc_url_raw')->returnArg();
		Functions\when('absint')->alias('intval');

		$this->wpdb->shouldReceive('insert')
			->once()
			->andReturn(1);

		$this->wpdb->insert_id = 42;

		$repository = new Banner_Repository();
		$result = $repository->create([
			'title' => 'New Banner',
			'desktop_url' => 'https://example.com',
		]);

		expect($result)->toBe(42);
	});

	it('returns false on insert failure', function () {
		Functions\when('sanitize_text_field')->returnArg();
		Functions\when('esc_url_raw')->returnArg();
		Functions\when('absint')->alias('intval');

		$this->wpdb->shouldReceive('insert')
			->once()
			->andReturn(false);

		$repository = new Banner_Repository();
		$result = $repository->create([
			'title' => 'Test',
			'desktop_url' => 'https://example.com',
		]);

		expect($result)->toBeFalse();
	});
});

describe('Banner_Repository::update()', function () {
	it('returns false when banner does not exist', function () {
		$this->wpdb->shouldReceive('prepare')
			->once()
			->andReturn('SELECT * FROM wp_sab_banners WHERE id = 999');

		$this->wpdb->shouldReceive('get_row')
			->once()
			->andReturn(null);

		$repository = new Banner_Repository();
		$result = $repository->update(999, ['title' => 'Updated']);

		expect($result)->toBeFalse();
	});

	it('returns true when no data to update', function () {
		$db_row = [
			'id' => '1',
			'title' => 'Test',
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

		$repository = new Banner_Repository();
		$result = $repository->update(1, []);

		expect($result)->toBeTrue();
	});

	it('updates banner fields successfully', function () {
		$db_row = [
			'id' => '1',
			'title' => 'Test',
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

		Functions\when('sanitize_text_field')->returnArg();

		$this->wpdb->shouldReceive('update')
			->once()
			->andReturn(1);

		$repository = new Banner_Repository();
		$result = $repository->update(1, ['title' => 'Updated Title']);

		expect($result)->toBeTrue();
	});

	it('returns false when update fails', function () {
		$db_row = [
			'id' => '1',
			'title' => 'Test',
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

		Functions\when('sanitize_text_field')->returnArg();

		$this->wpdb->shouldReceive('update')
			->once()
			->andReturn(false);

		$repository = new Banner_Repository();
		$result = $repository->update(1, ['title' => 'Updated']);

		expect($result)->toBeFalse();
	});
});

describe('Banner_Repository::delete()', function () {
	it('deletes a banner successfully', function () {
		$this->wpdb->shouldReceive('delete')
			->once()
			->with('wp_sab_banners', ['id' => 1], ['%d'])
			->andReturn(1);

		$repository = new Banner_Repository();
		$result = $repository->delete(1);

		expect($result)->toBeTrue();
	});

	it('returns false when delete fails', function () {
		$this->wpdb->shouldReceive('delete')
			->once()
			->andReturn(false);

		$repository = new Banner_Repository();
		$result = $repository->delete(999);

		expect($result)->toBeFalse();
	});

	it('returns false when no rows deleted', function () {
		$this->wpdb->shouldReceive('delete')
			->once()
			->andReturn(0);

		$repository = new Banner_Repository();
		$result = $repository->delete(999);

		expect($result)->toBeFalse();
	});
});

describe('Banner_Repository::count()', function () {
	it('returns total count without status filter', function () {
		$this->wpdb->shouldReceive('get_var')
			->once()
			->with('SELECT COUNT(*) FROM wp_sab_banners')
			->andReturn('5');

		$repository = new Banner_Repository();
		$result = $repository->count();

		expect($result)->toBe(5);
	});

	it('returns count filtered by status', function () {
		$this->wpdb->shouldReceive('prepare')
			->once()
			->with(
				'SELECT COUNT(*) FROM wp_sab_banners WHERE status = %s',
				'active'
			)
			->andReturn('SELECT COUNT(*) FROM wp_sab_banners WHERE status = \'active\'');

		$this->wpdb->shouldReceive('get_var')
			->once()
			->andReturn('3');

		$repository = new Banner_Repository();
		$result = $repository->count('active');

		expect($result)->toBe(3);
	});
});

describe('Banner_Repository::update() with all fields', function () {
	it('updates desktop_image_id field', function () {
		$db_row = [
			'id' => '1',
			'title' => 'Test',
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

		$this->wpdb->shouldReceive('prepare')->andReturn('SELECT');
		$this->wpdb->shouldReceive('get_row')->andReturn($db_row);

		Functions\when('absint')->alias('intval');

		$this->wpdb->shouldReceive('update')->once()->andReturn(1);

		$repository = new Banner_Repository();
		$result = $repository->update(1, ['desktop_image_id' => 10]);

		expect($result)->toBeTrue();
	});

	it('updates mobile_image_id field', function () {
		$db_row = [
			'id' => '1',
			'title' => 'Test',
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

		$this->wpdb->shouldReceive('prepare')->andReturn('SELECT');
		$this->wpdb->shouldReceive('get_row')->andReturn($db_row);

		Functions\when('absint')->alias('intval');

		$this->wpdb->shouldReceive('update')->once()->andReturn(1);

		$repository = new Banner_Repository();
		$result = $repository->update(1, ['mobile_image_id' => 20]);

		expect($result)->toBeTrue();
	});

	it('updates desktop_url field', function () {
		$db_row = [
			'id' => '1',
			'title' => 'Test',
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

		$this->wpdb->shouldReceive('prepare')->andReturn('SELECT');
		$this->wpdb->shouldReceive('get_row')->andReturn($db_row);

		Functions\when('esc_url_raw')->returnArg();

		$this->wpdb->shouldReceive('update')->once()->andReturn(1);

		$repository = new Banner_Repository();
		$result = $repository->update(1, ['desktop_url' => 'https://new.example.com']);

		expect($result)->toBeTrue();
	});

	it('updates mobile_url field', function () {
		$db_row = [
			'id' => '1',
			'title' => 'Test',
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

		$this->wpdb->shouldReceive('prepare')->andReturn('SELECT');
		$this->wpdb->shouldReceive('get_row')->andReturn($db_row);

		Functions\when('esc_url_raw')->returnArg();

		$this->wpdb->shouldReceive('update')->once()->andReturn(1);

		$repository = new Banner_Repository();
		$result = $repository->update(1, ['mobile_url' => 'https://mobile.example.com']);

		expect($result)->toBeTrue();
	});

	it('updates start_date field', function () {
		$db_row = [
			'id' => '1',
			'title' => 'Test',
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

		$this->wpdb->shouldReceive('prepare')->andReturn('SELECT');
		$this->wpdb->shouldReceive('get_row')->andReturn($db_row);

		$this->wpdb->shouldReceive('update')->once()->andReturn(1);

		$repository = new Banner_Repository();
		$result = $repository->update(1, ['start_date' => '2024-06-01 10:00:00']);

		expect($result)->toBeTrue();
	});

	it('updates end_date field', function () {
		$db_row = [
			'id' => '1',
			'title' => 'Test',
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

		$this->wpdb->shouldReceive('prepare')->andReturn('SELECT');
		$this->wpdb->shouldReceive('get_row')->andReturn($db_row);

		$this->wpdb->shouldReceive('update')->once()->andReturn(1);

		$repository = new Banner_Repository();
		$result = $repository->update(1, ['end_date' => '2024-12-31 23:59:59']);

		expect($result)->toBeTrue();
	});

	it('updates status field', function () {
		$db_row = [
			'id' => '1',
			'title' => 'Test',
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

		$this->wpdb->shouldReceive('prepare')->andReturn('SELECT');
		$this->wpdb->shouldReceive('get_row')->andReturn($db_row);

		$this->wpdb->shouldReceive('update')->once()->andReturn(1);

		$repository = new Banner_Repository();
		$result = $repository->update(1, ['status' => 'paused']);

		expect($result)->toBeTrue();
	});

	it('updates weight field', function () {
		$db_row = [
			'id' => '1',
			'title' => 'Test',
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

		$this->wpdb->shouldReceive('prepare')->andReturn('SELECT');
		$this->wpdb->shouldReceive('get_row')->andReturn($db_row);

		Functions\when('absint')->alias('intval');

		$this->wpdb->shouldReceive('update')->once()->andReturn(1);

		$repository = new Banner_Repository();
		$result = $repository->update(1, ['weight' => 10]);

		expect($result)->toBeTrue();
	});

	it('handles null values for optional fields', function () {
		$db_row = [
			'id' => '1',
			'title' => 'Test',
			'desktop_image_id' => '5',
			'mobile_image_id' => '6',
			'desktop_url' => 'https://example.com',
			'mobile_url' => 'https://mobile.example.com',
			'start_date' => '2024-01-01',
			'end_date' => '2024-12-31',
			'status' => 'active',
			'weight' => '1',
			'created_at' => '2024-01-01 00:00:00',
			'updated_at' => '2024-01-01 00:00:00',
		];

		$this->wpdb->shouldReceive('prepare')->andReturn('SELECT');
		$this->wpdb->shouldReceive('get_row')->andReturn($db_row);

		Functions\when('absint')->alias('intval');

		$this->wpdb->shouldReceive('update')->once()->andReturn(1);

		$repository = new Banner_Repository();
		$result = $repository->update(1, [
			'desktop_image_id' => null,
			'mobile_image_id' => null,
			'mobile_url' => null,
		]);

		expect($result)->toBeTrue();
	});
});

describe('Banner_Repository create with dates', function () {
	it('creates banner with valid dates', function () {
		Functions\when('sanitize_text_field')->returnArg();
		Functions\when('esc_url_raw')->returnArg();
		Functions\when('absint')->alias('intval');

		$this->wpdb->shouldReceive('insert')
			->once()
			->andReturn(1);

		$this->wpdb->insert_id = 1;

		$repository = new Banner_Repository();
		$result = $repository->create([
			'title' => 'Test',
			'desktop_url' => 'https://example.com',
			'start_date' => '2024-06-01 10:00:00',
			'end_date' => '2024-12-31 23:59:59',
		]);

		expect($result)->toBe(1);
	});

	it('handles invalid datetime by returning null', function () {
		Functions\when('sanitize_text_field')->returnArg();
		Functions\when('esc_url_raw')->returnArg();
		Functions\when('absint')->alias('intval');

		$this->wpdb->shouldReceive('insert')
			->once()
			->andReturn(1);

		$this->wpdb->insert_id = 1;

		$repository = new Banner_Repository();
		$result = $repository->create([
			'title' => 'Test',
			'desktop_url' => 'https://example.com',
			'start_date' => 'invalid-date',
		]);

		expect($result)->toBe(1);
	});

	it('handles invalid status by defaulting to active', function () {
		Functions\when('sanitize_text_field')->returnArg();
		Functions\when('esc_url_raw')->returnArg();
		Functions\when('absint')->alias('intval');

		$this->wpdb->shouldReceive('insert')
			->once()
			->with(
				'wp_sab_banners',
				\Mockery::on(function ($data) {
					return $data['status'] === 'active';
				}),
				\Mockery::any()
			)
			->andReturn(1);

		$this->wpdb->insert_id = 1;

		$repository = new Banner_Repository();
		$result = $repository->create([
			'title' => 'Test',
			'desktop_url' => 'https://example.com',
			'status' => 'invalid-status',
		]);

		expect($result)->toBe(1);
	});
});
