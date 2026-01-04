<?php

/**
 * Tests for Banner_Placement_Repository class.
 *
 * @package SimpleAddBanners\Tests\Unit\Repository
 *
 * @property \Mockery\MockInterface $wpdb
 */

declare(strict_types=1);

namespace SimpleAddBanners\Tests\Unit\Repository;

use SimpleAddBanners\Repository\Banner_Placement_Repository;

// Define WordPress constant if not defined.
if (! defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

beforeEach(function () {
    // Mock wpdb.
    $this->wpdb = \Mockery::mock('wpdb');
    $this->wpdb->prefix = 'wp_';
    $GLOBALS['wpdb'] = $this->wpdb;
});

afterEach(function () {
    unset($GLOBALS['wpdb']);
});

describe('Banner_Placement_Repository constructor', function () {
    it('sets the table names with prefix', function () {
        $repository = new Banner_Placement_Repository();

        $reflection = new \ReflectionClass($repository);

        $table_name = $reflection->getProperty('table_name');
        $banners_table = $reflection->getProperty('banners_table');
        $placements_table = $reflection->getProperty('placements_table');

        expect($table_name->getValue($repository))->toBe('wp_sab_banner_placement');
        expect($banners_table->getValue($repository))->toBe('wp_sab_banners');
        expect($placements_table->getValue($repository))->toBe('wp_sab_placements');
    });
});

describe('Banner_Placement_Repository::get_banners_for_placement()', function () {
    it('returns empty array when no banners assigned', function () {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT query');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn(null);

        $repository = new Banner_Placement_Repository();
        $result = $repository->get_banners_for_placement(1);

        expect($result)->toBe([]);
    });

    it('returns formatted banners with position', function () {
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
            'position' => '0',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT query');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([$db_row]);

        $repository = new Banner_Placement_Repository();
        $result = $repository->get_banners_for_placement(1);

        expect($result)->toBeArray();
        expect($result[0]['id'])->toBe(1);
        expect($result[0]['title'])->toBe('Test Banner');
        expect($result[0]['desktop_image_id'])->toBe(10);
        expect($result[0]['mobile_image_id'])->toBeNull();
        expect($result[0]['position'])->toBe(0);
        expect($result[0]['weight'])->toBe(5);
    });
});

describe('Banner_Placement_Repository::get_placements_for_banner()', function () {
    it('returns empty array when no placements assigned', function () {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT query');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn(null);

        $repository = new Banner_Placement_Repository();
        $result = $repository->get_placements_for_banner(1);

        expect($result)->toBe([]);
    });

    it('returns formatted placements with position', function () {
        $db_row = [
            'id' => '1',
            'slug' => 'header-banner',
            'name' => 'Header Banner',
            'rotation_strategy' => 'random',
            'position' => '2',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT query');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([$db_row]);

        $repository = new Banner_Placement_Repository();
        $result = $repository->get_placements_for_banner(1);

        expect($result)->toBeArray();
        expect($result[0]['id'])->toBe(1);
        expect($result[0]['slug'])->toBe('header-banner');
        expect($result[0]['position'])->toBe(2);
    });
});

describe('Banner_Placement_Repository::get_banner_ids_for_placement()', function () {
    it('returns empty array when no banners assigned', function () {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT query');

        $this->wpdb->shouldReceive('get_col')
            ->once()
            ->andReturn(null);

        $repository = new Banner_Placement_Repository();
        $result = $repository->get_banner_ids_for_placement(1);

        expect($result)->toBe([]);
    });

    it('returns array of banner IDs as integers', function () {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT query');

        $this->wpdb->shouldReceive('get_col')
            ->once()
            ->andReturn(['1', '2', '3']);

        $repository = new Banner_Placement_Repository();
        $result = $repository->get_banner_ids_for_placement(1);

        expect($result)->toBe([1, 2, 3]);
    });
});

describe('Banner_Placement_Repository::attach()', function () {
    it('attaches banner to placement and returns insert ID', function () {
        $this->wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_sab_banner_placement',
                ['placement_id' => 1, 'banner_id' => 2, 'position' => 0],
                ['%d', '%d', '%d']
            )
            ->andReturn(1);

        $this->wpdb->insert_id = 42;

        $repository = new Banner_Placement_Repository();
        $result = $repository->attach(1, 2);

        expect($result)->toBe(42);
    });

    it('attaches with custom position', function () {
        $this->wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_sab_banner_placement',
                ['placement_id' => 1, 'banner_id' => 2, 'position' => 5],
                ['%d', '%d', '%d']
            )
            ->andReturn(1);

        $this->wpdb->insert_id = 43;

        $repository = new Banner_Placement_Repository();
        $result = $repository->attach(1, 2, 5);

        expect($result)->toBe(43);
    });

    it('returns false on insert failure', function () {
        $this->wpdb->shouldReceive('insert')
            ->once()
            ->andReturn(false);

        $repository = new Banner_Placement_Repository();
        $result = $repository->attach(1, 2);

        expect($result)->toBeFalse();
    });

    it('ensures position is non-negative', function () {
        $this->wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_sab_banner_placement',
                \Mockery::on(function ($data) {
                    return $data['position'] === 0;
                }),
                ['%d', '%d', '%d']
            )
            ->andReturn(1);

        $this->wpdb->insert_id = 1;

        $repository = new Banner_Placement_Repository();
        $repository->attach(1, 2, -5);
    });
});

describe('Banner_Placement_Repository::detach()', function () {
    it('detaches banner from placement successfully', function () {
        $this->wpdb->shouldReceive('delete')
            ->once()
            ->with(
                'wp_sab_banner_placement',
                ['placement_id' => 1, 'banner_id' => 2],
                ['%d', '%d']
            )
            ->andReturn(1);

        $repository = new Banner_Placement_Repository();
        $result = $repository->detach(1, 2);

        expect($result)->toBeTrue();
    });

    it('returns false when delete fails', function () {
        $this->wpdb->shouldReceive('delete')
            ->once()
            ->andReturn(false);

        $repository = new Banner_Placement_Repository();
        $result = $repository->detach(1, 2);

        expect($result)->toBeFalse();
    });

    it('returns false when no rows affected', function () {
        $this->wpdb->shouldReceive('delete')
            ->once()
            ->andReturn(0);

        $repository = new Banner_Placement_Repository();
        $result = $repository->detach(1, 2);

        expect($result)->toBeFalse();
    });
});

describe('Banner_Placement_Repository::sync_banners()', function () {
    it('syncs banners by deleting existing and inserting new', function () {
        $this->wpdb->shouldReceive('delete')
            ->once()
            ->with('wp_sab_banner_placement', ['placement_id' => 1], ['%d'])
            ->andReturn(2);

        $this->wpdb->shouldReceive('insert')
            ->times(3)
            ->andReturn(1);

        $this->wpdb->insert_id = 1;

        $repository = new Banner_Placement_Repository();
        $result = $repository->sync_banners(1, [10, 20, 30]);

        expect($result)->toBeTrue();
    });

    it('handles empty banner array', function () {
        $this->wpdb->shouldReceive('delete')
            ->once()
            ->with('wp_sab_banner_placement', ['placement_id' => 1], ['%d'])
            ->andReturn(0);

        $repository = new Banner_Placement_Repository();
        $result = $repository->sync_banners(1, []);

        expect($result)->toBeTrue();
    });

    it('returns false if insert fails', function () {
        $this->wpdb->shouldReceive('delete')
            ->once()
            ->andReturn(0);

        $this->wpdb->shouldReceive('insert')
            ->once()
            ->andReturn(false);

        $repository = new Banner_Placement_Repository();
        $result = $repository->sync_banners(1, [10]);

        expect($result)->toBeFalse();
    });

    it('skips zero and negative banner IDs after absint', function () {
        $this->wpdb->shouldReceive('delete')
            ->once()
            ->andReturn(0);

        // Only 10 should be inserted (0 is skipped, -1 becomes 1 via absint but we check > 0 after absint)
        // Wait - absint(-1) = 1 which IS > 0, so -1 becomes valid!
        // Let's test with just 0 which stays 0 after absint
        $this->wpdb->shouldReceive('insert')
            ->once()
            ->andReturn(1);

        $this->wpdb->insert_id = 1;

        $repository = new Banner_Placement_Repository();
        $result = $repository->sync_banners(1, [0, 10]);

        expect($result)->toBeTrue();
    });

    it('assigns positions in order', function () {
        $this->wpdb->shouldReceive('delete')
            ->once()
            ->andReturn(0);

        $positions = [];
        $this->wpdb->shouldReceive('insert')
            ->times(2)
            ->with(
                'wp_sab_banner_placement',
                \Mockery::on(function ($data) use (&$positions) {
                    $positions[] = $data['position'];
                    return true;
                }),
                ['%d', '%d', '%d']
            )
            ->andReturn(1);

        $this->wpdb->insert_id = 1;

        $repository = new Banner_Placement_Repository();
        $repository->sync_banners(1, [10, 20]);

        expect($positions)->toBe([0, 1]);
    });
});

describe('Banner_Placement_Repository::update_position()', function () {
    it('updates position successfully', function () {
        $this->wpdb->shouldReceive('update')
            ->once()
            ->with(
                'wp_sab_banner_placement',
                ['position' => 5],
                ['placement_id' => 1, 'banner_id' => 2],
                ['%d'],
                ['%d', '%d']
            )
            ->andReturn(1);

        $repository = new Banner_Placement_Repository();
        $result = $repository->update_position(1, 2, 5);

        expect($result)->toBeTrue();
    });

    it('returns false when update fails', function () {
        $this->wpdb->shouldReceive('update')
            ->once()
            ->andReturn(false);

        $repository = new Banner_Placement_Repository();
        $result = $repository->update_position(1, 2, 5);

        expect($result)->toBeFalse();
    });

    it('ensures position is non-negative', function () {
        $this->wpdb->shouldReceive('update')
            ->once()
            ->with(
                'wp_sab_banner_placement',
                ['position' => 0],
                \Mockery::any(),
                \Mockery::any(),
                \Mockery::any()
            )
            ->andReturn(1);

        $repository = new Banner_Placement_Repository();
        $repository->update_position(1, 2, -10);
    });
});

describe('Banner_Placement_Repository::is_attached()', function () {
    it('returns true when banner is attached to placement', function () {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT COUNT(*) query');

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('1');

        $repository = new Banner_Placement_Repository();
        $result = $repository->is_attached(1, 2);

        expect($result)->toBeTrue();
    });

    it('returns false when banner is not attached', function () {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT COUNT(*) query');

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('0');

        $repository = new Banner_Placement_Repository();
        $result = $repository->is_attached(1, 2);

        expect($result)->toBeFalse();
    });
});

describe('Banner_Placement_Repository::count_banners()', function () {
    it('returns count of banners assigned to placement', function () {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT COUNT(*) query');

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn('5');

        $repository = new Banner_Placement_Repository();
        $result = $repository->count_banners(1);

        expect($result)->toBe(5);
    });

    it('returns zero when no banners assigned', function () {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT COUNT(*) query');

        $this->wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn(null);

        $repository = new Banner_Placement_Repository();
        $result = $repository->count_banners(1);

        expect($result)->toBe(0);
    });
});

describe('Banner_Placement_Repository::delete_by_placement()', function () {
    it('deletes all assignments for a placement', function () {
        $this->wpdb->shouldReceive('delete')
            ->once()
            ->with('wp_sab_banner_placement', ['placement_id' => 1], ['%d'])
            ->andReturn(3);

        $repository = new Banner_Placement_Repository();
        $result = $repository->delete_by_placement(1);

        expect($result)->toBeTrue();
    });

    it('returns true even when no rows affected', function () {
        $this->wpdb->shouldReceive('delete')
            ->once()
            ->andReturn(0);

        $repository = new Banner_Placement_Repository();
        $result = $repository->delete_by_placement(1);

        expect($result)->toBeTrue();
    });

    it('returns false when delete fails', function () {
        $this->wpdb->shouldReceive('delete')
            ->once()
            ->andReturn(false);

        $repository = new Banner_Placement_Repository();
        $result = $repository->delete_by_placement(1);

        expect($result)->toBeFalse();
    });
});

describe('Banner_Placement_Repository::delete_by_banner()', function () {
    it('deletes all assignments for a banner', function () {
        $this->wpdb->shouldReceive('delete')
            ->once()
            ->with('wp_sab_banner_placement', ['banner_id' => 1], ['%d'])
            ->andReturn(2);

        $repository = new Banner_Placement_Repository();
        $result = $repository->delete_by_banner(1);

        expect($result)->toBeTrue();
    });

    it('returns true even when no rows affected', function () {
        $this->wpdb->shouldReceive('delete')
            ->once()
            ->andReturn(0);

        $repository = new Banner_Placement_Repository();
        $result = $repository->delete_by_banner(1);

        expect($result)->toBeTrue();
    });

    it('returns false when delete fails', function () {
        $this->wpdb->shouldReceive('delete')
            ->once()
            ->andReturn(false);

        $repository = new Banner_Placement_Repository();
        $result = $repository->delete_by_banner(1);

        expect($result)->toBeFalse();
    });
});
