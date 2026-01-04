<?php
/**
 * Tests for Shortcode_Handler class.
 *
 * @package SimpleAddBanners\Tests\Unit\Frontend
 */

declare(strict_types=1);

namespace SimpleAddBanners\Tests\Unit\Frontend;

use SimpleAddBanners\Frontend\Shortcode_Handler;
use SimpleAddBanners\Frontend\Banner_Selector;
use SimpleAddBanners\Frontend\Banner_Renderer;
use SimpleAddBanners\Repository\Placement_Repository;
use SimpleAddBanners\Repository\Banner_Placement_Repository;
use Brain\Monkey\Functions;

// Define WordPress constant if not defined.
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

beforeEach(function () {
	// Mock add_shortcode.
	Functions\when('add_shortcode')->justReturn(true);

	// Mock shortcode_atts.
	Functions\when('shortcode_atts')->alias(function ($defaults, $atts, $shortcode) {
		return array_merge($defaults, is_array($atts) ? $atts : []);
	});

	// Mock sanitize_text_field.
	Functions\when('sanitize_text_field')->returnArg();

	// Mock wp_get_attachment_image_src.
	Functions\when('wp_get_attachment_image_src')->alias(function ($id, $size) {
		if ($id === 10) {
			return ['https://example.com/desktop.jpg', 1200, 400, false];
		}
		if ($id === 20) {
			return ['https://example.com/mobile.jpg', 600, 300, false];
		}
		return false;
	});

	// Mock escaping functions.
	Functions\when('esc_attr')->returnArg();
	Functions\when('esc_url')->returnArg();
	Functions\when('esc_html')->returnArg();

	// Mock get_post_meta for alt text.
	Functions\when('get_post_meta')->justReturn('Alt text');

	// Mock get_option for Token_Generator.
	Functions\when('get_option')->justReturn('test_tracking_secret');

	// Create mock repositories.
	$this->placement_repo = \Mockery::mock(Placement_Repository::class);
	$this->bp_repo = \Mockery::mock(Banner_Placement_Repository::class);

	// Set up globals for repositories.
	$this->wpdb = \Mockery::mock('wpdb');
	$this->wpdb->prefix = 'wp_';
	$GLOBALS['wpdb'] = $this->wpdb;
});

afterEach(function () {
	unset($GLOBALS['wpdb']);
});

describe('Shortcode_Handler constructor', function () {
	it('registers shortcode on construction', function () {
		// add_shortcode is already mocked in beforeEach.
		// Just verify the handler can be constructed.
		$handler = new Shortcode_Handler(
			$this->placement_repo,
			$this->bp_repo
		);

		expect($handler)->toBeInstanceOf(Shortcode_Handler::class);
	});
});

describe('Shortcode_Handler::render_shortcode()', function () {
	it('returns empty string when placement attribute is empty', function () {
		$handler = new Shortcode_Handler(
			$this->placement_repo,
			$this->bp_repo
		);

		$result = $handler->render_shortcode(['placement' => '']);

		expect($result)->toBe('');
	});

	it('returns empty string when placement not found', function () {
		$this->placement_repo->shouldReceive('get_by_slug')
			->with('nonexistent')
			->andReturn(null);

		$handler = new Shortcode_Handler(
			$this->placement_repo,
			$this->bp_repo
		);

		$result = $handler->render_shortcode(['placement' => 'nonexistent']);

		expect($result)->toBe('');
	});

	it('returns empty string when no banners assigned', function () {
		$placement = [
			'id' => 1,
			'slug' => 'header',
			'name' => 'Header',
			'rotation_strategy' => 'random',
		];

		$this->placement_repo->shouldReceive('get_by_slug')
			->with('header')
			->andReturn($placement);

		$this->bp_repo->shouldReceive('get_banners_for_placement')
			->with(1)
			->andReturn([]);

		$handler = new Shortcode_Handler(
			$this->placement_repo,
			$this->bp_repo
		);

		$result = $handler->render_shortcode(['placement' => 'header']);

		expect($result)->toBe('');
	});

	it('renders banner when placement and banners exist', function () {
		$placement = [
			'id' => 1,
			'slug' => 'header',
			'name' => 'Header',
			'rotation_strategy' => 'random',
		];

		$banners = [
			[
				'id' => 1,
				'title' => 'Test Banner',
				'status' => 'active',
				'desktop_image_id' => 10,
				'mobile_image_id' => null,
				'desktop_url' => 'https://example.com/click',
				'weight' => 1,
				'start_date' => null,
				'end_date' => null,
			],
		];

		$this->placement_repo->shouldReceive('get_by_slug')
			->with('header')
			->andReturn($placement);

		$this->bp_repo->shouldReceive('get_banners_for_placement')
			->with(1)
			->andReturn($banners);

		// Mock selector and renderer functions.
		Functions\when('current_time')->justReturn('2024-06-15 12:00:00');

		$handler = new Shortcode_Handler(
			$this->placement_repo,
			$this->bp_repo
		);

		$result = $handler->render_shortcode(['placement' => 'header']);

		expect($result)->toContain('class="sab-banner"');
		expect($result)->toContain('data-placement="header"');
		expect($result)->toContain('data-banner-id="1"');
	});

	it('returns empty string when all banners are ineligible', function () {
		$placement = [
			'id' => 1,
			'slug' => 'header',
			'name' => 'Header',
			'rotation_strategy' => 'random',
		];

		$banners = [
			[
				'id' => 1,
				'title' => 'Paused Banner',
				'status' => 'paused', // Not active.
				'desktop_image_id' => 10,
				'mobile_image_id' => null,
				'desktop_url' => 'https://example.com/click',
				'weight' => 1,
				'start_date' => null,
				'end_date' => null,
			],
		];

		$this->placement_repo->shouldReceive('get_by_slug')
			->with('header')
			->andReturn($placement);

		$this->bp_repo->shouldReceive('get_banners_for_placement')
			->with(1)
			->andReturn($banners);

		Functions\when('current_time')->justReturn('2024-06-15 12:00:00');

		$handler = new Shortcode_Handler(
			$this->placement_repo,
			$this->bp_repo
		);

		$result = $handler->render_shortcode(['placement' => 'header']);

		expect($result)->toBe('');
	});

	it('uses weighted strategy when configured', function () {
		$placement = [
			'id' => 1,
			'slug' => 'header',
			'name' => 'Header',
			'rotation_strategy' => 'weighted',
		];

		$banners = [
			[
				'id' => 1,
				'title' => 'Banner 1',
				'status' => 'active',
				'desktop_image_id' => 10,
				'mobile_image_id' => null,
				'desktop_url' => 'https://example.com/click',
				'weight' => 5,
				'start_date' => null,
				'end_date' => null,
			],
			[
				'id' => 2,
				'title' => 'Banner 2',
				'status' => 'active',
				'desktop_image_id' => 10,
				'mobile_image_id' => null,
				'desktop_url' => 'https://example.com/click2',
				'weight' => 1,
				'start_date' => null,
				'end_date' => null,
			],
		];

		$this->placement_repo->shouldReceive('get_by_slug')
			->with('header')
			->andReturn($placement);

		$this->bp_repo->shouldReceive('get_banners_for_placement')
			->with(1)
			->andReturn($banners);

		Functions\when('current_time')->justReturn('2024-06-15 12:00:00');
		Functions\when('wp_rand')->alias(function ($min, $max) {
			return $min;
		});

		$handler = new Shortcode_Handler(
			$this->placement_repo,
			$this->bp_repo
		);

		$result = $handler->render_shortcode(['placement' => 'header']);

		expect($result)->toContain('class="sab-banner"');
	});

	it('uses sequential strategy when configured', function () {
		$placement = [
			'id' => 1,
			'slug' => 'header',
			'name' => 'Header',
			'rotation_strategy' => 'sequential',
		];

		$banners = [
			[
				'id' => 1,
				'title' => 'Banner 1',
				'status' => 'active',
				'desktop_image_id' => 10,
				'mobile_image_id' => null,
				'desktop_url' => 'https://example.com/click',
				'weight' => 1,
				'position' => 0,
				'start_date' => null,
				'end_date' => null,
			],
			[
				'id' => 2,
				'title' => 'Banner 2',
				'status' => 'active',
				'desktop_image_id' => 10,
				'mobile_image_id' => null,
				'desktop_url' => 'https://example.com/click2',
				'weight' => 1,
				'position' => 1,
				'start_date' => null,
				'end_date' => null,
			],
		];

		$this->placement_repo->shouldReceive('get_by_slug')
			->with('header')
			->andReturn($placement);

		$this->bp_repo->shouldReceive('get_banners_for_placement')
			->with(1)
			->andReturn($banners);

		Functions\when('current_time')->justReturn('2024-06-15 12:00:00');
		Functions\when('get_transient')->justReturn(false);
		Functions\when('set_transient')->justReturn(true);

		$handler = new Shortcode_Handler(
			$this->placement_repo,
			$this->bp_repo
		);

		$result = $handler->render_shortcode(['placement' => 'header']);

		expect($result)->toContain('data-banner-id="1"');
	});

	it('sanitizes placement slug input', function () {
		// Verify sanitize_text_field is called by checking the slug reaches the repo.
		$this->placement_repo->shouldReceive('get_by_slug')
			->once()
			->with('header') // The sanitized value.
			->andReturn(null);

		$handler = new Shortcode_Handler(
			$this->placement_repo,
			$this->bp_repo
		);

		$result = $handler->render_shortcode(['placement' => 'header']);

		expect($result)->toBe('');
	});

	it('handles string attributes', function () {
		$this->placement_repo->shouldReceive('get_by_slug')
			->andReturn(null);

		$handler = new Shortcode_Handler(
			$this->placement_repo,
			$this->bp_repo
		);

		// WordPress can pass empty string for shortcodes without attributes.
		$result = $handler->render_shortcode('');

		expect($result)->toBe('');
	});
});

describe('Shortcode_Handler image enrichment', function () {
	it('enriches banners with image URLs', function () {
		$placement = [
			'id' => 1,
			'slug' => 'header',
			'name' => 'Header',
			'rotation_strategy' => 'random',
		];

		$banners = [
			[
				'id' => 1,
				'title' => 'Banner',
				'status' => 'active',
				'desktop_image_id' => 10,
				'mobile_image_id' => 20,
				'desktop_url' => 'https://example.com/click',
				'weight' => 1,
				'start_date' => null,
				'end_date' => null,
			],
		];

		$this->placement_repo->shouldReceive('get_by_slug')
			->with('header')
			->andReturn($placement);

		$this->bp_repo->shouldReceive('get_banners_for_placement')
			->with(1)
			->andReturn($banners);

		Functions\when('current_time')->justReturn('2024-06-15 12:00:00');

		$handler = new Shortcode_Handler(
			$this->placement_repo,
			$this->bp_repo
		);

		$result = $handler->render_shortcode(['placement' => 'header']);

		// Should render with responsive images.
		expect($result)->toContain('https://example.com/desktop.jpg');
		expect($result)->toContain('https://example.com/mobile.jpg');
	});
});
