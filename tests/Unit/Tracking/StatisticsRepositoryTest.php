<?php
/**
 * Tests for Statistics_Repository class.
 *
 * @package SimpleAddBanners\Tests\Unit\Tracking
 */

declare(strict_types=1);

namespace SimpleAddBanners\Tests\Unit\Tracking;

use SimpleAddBanners\Tracking\Statistics_Repository;

// Define WordPress constants if not defined.
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

beforeEach(function () {
	$this->wpdb         = \Mockery::mock('wpdb');
	$this->wpdb->prefix = 'wp_';
	// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	$GLOBALS['wpdb'] = $this->wpdb;
});

afterEach(function () {
	unset($GLOBALS['wpdb']);
});

describe('Statistics_Repository constructor', function () {
	it('sets the table name with prefix', function () {
		$repo = new Statistics_Repository();

		// We can verify by checking that query uses correct table.
		$this->wpdb->shouldReceive('prepare')
			->once()
			->with(
				\Mockery::on(function ($sql) {
					return str_contains($sql, 'wp_sab_statistics');
				}),
				\Mockery::any(),
				\Mockery::any(),
				\Mockery::any()
			)
			->andReturn('prepared_sql');

		$this->wpdb->shouldReceive('query')
			->once()
			->with('prepared_sql')
			->andReturn(1);

		$repo->increment_impressions(1, 1);

		expect(true)->toBeTrue();
	});
});

describe('Statistics_Repository::increment_impressions()', function () {
	it('inserts new record when none exists', function () {
		$today = gmdate('Y-m-d');

		$this->wpdb->shouldReceive('prepare')
			->once()
			->with(
				\Mockery::on(function ($sql) {
					return str_contains($sql, 'INSERT INTO')
						&& str_contains($sql, 'ON DUPLICATE KEY UPDATE')
						&& str_contains($sql, 'impressions = impressions + 1');
				}),
				1,
				2,
				$today
			)
			->andReturn('prepared_sql');

		$this->wpdb->shouldReceive('query')
			->once()
			->with('prepared_sql')
			->andReturn(1);

		$repo   = new Statistics_Repository();
		$result = $repo->increment_impressions(1, 2);

		expect($result)->toBeTrue();
	});

	it('returns false on database error', function () {
		$this->wpdb->shouldReceive('prepare')
			->once()
			->andReturn('prepared_sql');

		$this->wpdb->shouldReceive('query')
			->once()
			->with('prepared_sql')
			->andReturn(false);

		$repo   = new Statistics_Repository();
		$result = $repo->increment_impressions(1, 2);

		expect($result)->toBeFalse();
	});

	it('uses current UTC date for stat_date', function () {
		$today = gmdate('Y-m-d');

		$this->wpdb->shouldReceive('prepare')
			->once()
			->with(
				\Mockery::any(),
				1,
				2,
				$today
			)
			->andReturn('prepared_sql');

		$this->wpdb->shouldReceive('query')
			->once()
			->andReturn(1);

		$repo = new Statistics_Repository();
		$repo->increment_impressions(1, 2);

		expect(true)->toBeTrue();
	});
});

describe('Statistics_Repository::increment_clicks()', function () {
	it('inserts new record with click count', function () {
		$today = gmdate('Y-m-d');

		$this->wpdb->shouldReceive('prepare')
			->once()
			->with(
				\Mockery::on(function ($sql) {
					return str_contains($sql, 'INSERT INTO')
						&& str_contains($sql, 'ON DUPLICATE KEY UPDATE')
						&& str_contains($sql, 'clicks = clicks + 1');
				}),
				1,
				2,
				$today
			)
			->andReturn('prepared_sql');

		$this->wpdb->shouldReceive('query')
			->once()
			->with('prepared_sql')
			->andReturn(1);

		$repo   = new Statistics_Repository();
		$result = $repo->increment_clicks(1, 2);

		expect($result)->toBeTrue();
	});

	it('returns false on database error', function () {
		$this->wpdb->shouldReceive('prepare')
			->once()
			->andReturn('prepared_sql');

		$this->wpdb->shouldReceive('query')
			->once()
			->with('prepared_sql')
			->andReturn(false);

		$repo   = new Statistics_Repository();
		$result = $repo->increment_clicks(1, 2);

		expect($result)->toBeFalse();
	});
});

describe('Statistics_Repository::get_stats()', function () {
	it('returns empty array when no stats exist', function () {
		$this->wpdb->shouldReceive('prepare')
			->once()
			->andReturn('prepared_sql');

		$this->wpdb->shouldReceive('get_results')
			->once()
			->andReturn(null);

		$repo   = new Statistics_Repository();
		$result = $repo->get_stats(1);

		expect($result)->toBe([]);
	});

	it('returns formatted statistics', function () {
		$this->wpdb->shouldReceive('prepare')
			->once()
			->andReturn('prepared_sql');

		$this->wpdb->shouldReceive('get_results')
			->once()
			->andReturn([
				[
					'id'           => '1',
					'banner_id'    => '1',
					'placement_id' => '2',
					'stat_date'    => '2024-01-15',
					'impressions'  => '100',
					'clicks'       => '5',
				],
			]);

		$repo   = new Statistics_Repository();
		$result = $repo->get_stats(1);

		expect($result)->toHaveCount(1);
		expect($result[0]['id'])->toBe(1);
		expect($result[0]['banner_id'])->toBe(1);
		expect($result[0]['placement_id'])->toBe(2);
		expect($result[0]['impressions'])->toBe(100);
		expect($result[0]['clicks'])->toBe(5);
		expect($result[0]['ctr'])->toBe(5.0);
	});

	it('filters by date range', function () {
		$this->wpdb->shouldReceive('prepare')
			->once()
			->with(
				\Mockery::on(function ($sql) {
					return str_contains($sql, 'stat_date >= %s')
						&& str_contains($sql, 'stat_date <= %s');
				}),
				\Mockery::any()
			)
			->andReturn('prepared_sql');

		$this->wpdb->shouldReceive('get_results')
			->once()
			->andReturn([]);

		$repo = new Statistics_Repository();
		$repo->get_stats(1, '2024-01-01', '2024-01-31');

		expect(true)->toBeTrue();
	});
});

describe('Statistics_Repository::get_stats_by_placement()', function () {
	it('returns stats filtered by placement ID', function () {
		$this->wpdb->shouldReceive('prepare')
			->once()
			->with(
				\Mockery::on(function ($sql) {
					return str_contains($sql, 'placement_id = %d');
				}),
				\Mockery::any()
			)
			->andReturn('prepared_sql');

		$this->wpdb->shouldReceive('get_results')
			->once()
			->andReturn([]);

		$repo   = new Statistics_Repository();
		$result = $repo->get_stats_by_placement(2);

		expect($result)->toBe([]);
	});

	it('filters by date range', function () {
		$this->wpdb->shouldReceive('prepare')
			->once()
			->with(
				\Mockery::on(function ($sql) {
					return str_contains($sql, 'placement_id = %d')
						&& str_contains($sql, 'stat_date >= %s')
						&& str_contains($sql, 'stat_date <= %s');
				}),
				\Mockery::any()
			)
			->andReturn('prepared_sql');

		$this->wpdb->shouldReceive('get_results')
			->once()
			->andReturn([]);

		$repo = new Statistics_Repository();
		$repo->get_stats_by_placement(2, '2024-01-01', '2024-01-31');

		expect(true)->toBeTrue();
	});
});

describe('Statistics_Repository::get_aggregated_stats()', function () {
	it('returns aggregated totals', function () {
		$this->wpdb->shouldReceive('prepare')
			->once()
			->with(
				\Mockery::on(function ($sql) {
					return str_contains($sql, 'SUM(impressions)')
						&& str_contains($sql, 'SUM(clicks)');
				}),
				\Mockery::any()
			)
			->andReturn('prepared_sql');

		$this->wpdb->shouldReceive('get_row')
			->once()
			->andReturn([
				'total_impressions' => '500',
				'total_clicks'      => '25',
			]);

		$repo   = new Statistics_Repository();
		$result = $repo->get_aggregated_stats(1);

		expect($result['impressions'])->toBe(500);
		expect($result['clicks'])->toBe(25);
		expect($result['ctr'])->toBe(5.0);
	});

	it('returns zero CTR when no impressions', function () {
		$this->wpdb->shouldReceive('prepare')
			->once()
			->andReturn('prepared_sql');

		$this->wpdb->shouldReceive('get_row')
			->once()
			->andReturn([
				'total_impressions' => null,
				'total_clicks'      => null,
			]);

		$repo   = new Statistics_Repository();
		$result = $repo->get_aggregated_stats(1);

		expect($result['impressions'])->toBe(0);
		expect($result['clicks'])->toBe(0);
		expect($result['ctr'])->toBe(0);
	});

	it('filters aggregation by date range', function () {
		$this->wpdb->shouldReceive('prepare')
			->once()
			->with(
				\Mockery::on(function ($sql) {
					return str_contains($sql, 'stat_date >= %s')
						&& str_contains($sql, 'stat_date <= %s');
				}),
				\Mockery::any()
			)
			->andReturn('prepared_sql');

		$this->wpdb->shouldReceive('get_row')
			->once()
			->andReturn([
				'total_impressions' => '100',
				'total_clicks'      => '10',
			]);

		$repo = new Statistics_Repository();
		$repo->get_aggregated_stats(1, '2024-01-01', '2024-01-31');

		expect(true)->toBeTrue();
	});
});

describe('Statistics_Repository::get_by_banner_placement_date()', function () {
	it('returns null when record not found', function () {
		$this->wpdb->shouldReceive('prepare')
			->once()
			->andReturn('prepared_sql');

		$this->wpdb->shouldReceive('get_row')
			->once()
			->andReturn(null);

		$repo   = new Statistics_Repository();
		$result = $repo->get_by_banner_placement_date(1, 2, '2024-01-15');

		expect($result)->toBeNull();
	});

	it('returns formatted record when found', function () {
		$this->wpdb->shouldReceive('prepare')
			->once()
			->with(
				\Mockery::on(function ($sql) {
					return str_contains($sql, 'banner_id = %d')
						&& str_contains($sql, 'placement_id = %d')
						&& str_contains($sql, 'stat_date = %s');
				}),
				\Mockery::any(),
				\Mockery::any(),
				\Mockery::any()
			)
			->andReturn('prepared_sql');

		$this->wpdb->shouldReceive('get_row')
			->once()
			->andReturn([
				'id'           => '1',
				'banner_id'    => '1',
				'placement_id' => '2',
				'stat_date'    => '2024-01-15',
				'impressions'  => '50',
				'clicks'       => '2',
			]);

		$repo   = new Statistics_Repository();
		$result = $repo->get_by_banner_placement_date(1, 2, '2024-01-15');

		expect($result)->not->toBeNull();
		expect($result['id'])->toBe(1);
		expect($result['banner_id'])->toBe(1);
		expect($result['placement_id'])->toBe(2);
		expect($result['impressions'])->toBe(50);
		expect($result['clicks'])->toBe(2);
		expect($result['ctr'])->toBe(4.0);
	});
});

describe('Statistics_Repository CTR calculation', function () {
	it('calculates CTR correctly', function () {
		$this->wpdb->shouldReceive('prepare')
			->once()
			->andReturn('prepared_sql');

		$this->wpdb->shouldReceive('get_results')
			->once()
			->andReturn([
				[
					'id'           => '1',
					'banner_id'    => '1',
					'placement_id' => '2',
					'stat_date'    => '2024-01-15',
					'impressions'  => '1000',
					'clicks'       => '35',
				],
			]);

		$repo   = new Statistics_Repository();
		$result = $repo->get_stats(1);

		expect($result[0]['ctr'])->toBe(3.5);
	});

	it('returns zero CTR when impressions is zero', function () {
		$this->wpdb->shouldReceive('prepare')
			->once()
			->andReturn('prepared_sql');

		$this->wpdb->shouldReceive('get_results')
			->once()
			->andReturn([
				[
					'id'           => '1',
					'banner_id'    => '1',
					'placement_id' => '2',
					'stat_date'    => '2024-01-15',
					'impressions'  => '0',
					'clicks'       => '0',
				],
			]);

		$repo   = new Statistics_Repository();
		$result = $repo->get_stats(1);

		expect($result[0]['ctr'])->toBe(0);
	});
});
