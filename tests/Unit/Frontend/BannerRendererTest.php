<?php
/**
 * Tests for Banner_Renderer class.
 *
 * @package SimpleAddBanners\Tests\Unit\Frontend
 */

declare(strict_types=1);

namespace SimpleAddBanners\Tests\Unit\Frontend;

use SimpleAddBanners\Frontend\Banner_Renderer;
use Brain\Monkey\Functions;

beforeEach(function () {
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

	// Mock get_post_meta for alt text.
	Functions\when('get_post_meta')->alias(function ($id, $key, $single) {
		if ($key === '_wp_attachment_image_alt') {
			if ($id === 10) {
				return 'Desktop alt text';
			}
			if ($id === 20) {
				return 'Mobile alt text';
			}
		}
		return '';
	});

	// Mock escaping functions to pass through for testing.
	Functions\when('esc_attr')->returnArg();
	Functions\when('esc_url')->returnArg();
	Functions\when('esc_html')->returnArg();
});

describe('Banner_Renderer::render()', function () {
	it('returns empty string when no images available', function () {
		$banner = [
			'id' => 1,
			'title' => 'Test Banner',
			'desktop_image_id' => null,
			'mobile_image_id' => null,
			'desktop_url' => 'https://example.com',
		];

		$placement = ['slug' => 'header'];

		$renderer = new Banner_Renderer();
		$result = $renderer->render($banner, $placement);

		expect($result)->toBe('');
	});

	it('renders banner with desktop image', function () {
		$banner = [
			'id' => 1,
			'title' => 'Test Banner',
			'desktop_image_id' => 10,
			'mobile_image_id' => null,
			'desktop_url' => 'https://example.com/click',
		];

		$placement = ['slug' => 'header'];

		$renderer = new Banner_Renderer();
		$result = $renderer->render($banner, $placement);

		expect($result)->toContain('class="sab-banner"');
		expect($result)->toContain('data-placement="header"');
		expect($result)->toContain('data-banner-id="1"');
		expect($result)->toContain('href="https://example.com/click"');
		expect($result)->toContain('src="https://example.com/desktop.jpg"');
		expect($result)->toContain('alt="Desktop alt text"');
	});

	it('renders banner with responsive images', function () {
		$banner = [
			'id' => 1,
			'title' => 'Test Banner',
			'desktop_image_id' => 10,
			'mobile_image_id' => 20,
			'desktop_url' => 'https://example.com/click',
		];

		$placement = ['slug' => 'header'];

		$renderer = new Banner_Renderer();
		$result = $renderer->render($banner, $placement);

		expect($result)->toContain('<picture>');
		expect($result)->toContain('</picture>');
		expect($result)->toContain('<source media="(max-width: 768px)" srcset="https://example.com/mobile.jpg">');
		expect($result)->toContain('src="https://example.com/desktop.jpg"');
	});

	it('renders banner without link when no URL provided', function () {
		$banner = [
			'id' => 1,
			'title' => 'Test Banner',
			'desktop_image_id' => 10,
			'mobile_image_id' => null,
			'desktop_url' => '',
		];

		$placement = ['slug' => 'header'];

		$renderer = new Banner_Renderer();
		$result = $renderer->render($banner, $placement);

		expect($result)->not->toContain('<a href');
		expect($result)->toContain('<picture>');
	});

	it('includes noopener noreferrer on links', function () {
		$banner = [
			'id' => 1,
			'title' => 'Test Banner',
			'desktop_image_id' => 10,
			'mobile_image_id' => null,
			'desktop_url' => 'https://example.com/click',
		];

		$placement = ['slug' => 'header'];

		$renderer = new Banner_Renderer();
		$result = $renderer->render($banner, $placement);

		expect($result)->toContain('target="_blank"');
		expect($result)->toContain('rel="noopener noreferrer"');
	});

	it('includes inline styles', function () {
		$banner = [
			'id' => 1,
			'title' => 'Test Banner',
			'desktop_image_id' => 10,
			'mobile_image_id' => null,
			'desktop_url' => 'https://example.com/click',
		];

		$placement = ['slug' => 'header'];

		$renderer = new Banner_Renderer();
		$result = $renderer->render($banner, $placement);

		expect($result)->toContain('<style>');
		expect($result)->toContain('.sab-banner{display:block}');
		expect($result)->toContain('.sab-banner img{max-width:100%;height:auto;display:block}');
	});

	it('includes lazy loading attribute', function () {
		$banner = [
			'id' => 1,
			'title' => 'Test Banner',
			'desktop_image_id' => 10,
			'mobile_image_id' => null,
			'desktop_url' => 'https://example.com',
		];

		$placement = ['slug' => 'header'];

		$renderer = new Banner_Renderer();
		$result = $renderer->render($banner, $placement);

		expect($result)->toContain('loading="lazy"');
	});
});

describe('Banner_Renderer::get_image_html()', function () {
	it('returns empty string when no images', function () {
		$banner = [
			'desktop_image_id' => null,
			'mobile_image_id' => null,
		];

		$renderer = new Banner_Renderer();
		$result = $renderer->get_image_html($banner);

		expect($result)->toBe('');
	});

	it('returns empty string when image attachment does not exist', function () {
		// Mock wp_get_attachment_image_src to return false for this specific ID.
		Functions\when('wp_get_attachment_image_src')->alias(function ($id, $size) {
			if ($id === 999) {
				return false; // Deleted or non-existent image.
			}
			return false;
		});

		$banner = [
			'desktop_image_id' => 999, // Non-existent image.
			'mobile_image_id' => null,
		];

		$renderer = new Banner_Renderer();
		$result = $renderer->get_image_html($banner);

		expect($result)->toBe('');
	});

	it('uses mobile image for desktop when only mobile available', function () {
		$banner = [
			'desktop_image_id' => null,
			'mobile_image_id' => 20,
		];

		$renderer = new Banner_Renderer();
		$result = $renderer->get_image_html($banner);

		expect($result)->toContain('src="https://example.com/mobile.jpg"');
	});

	it('uses desktop image for mobile when only desktop available', function () {
		$banner = [
			'desktop_image_id' => 10,
			'mobile_image_id' => null,
		];

		$renderer = new Banner_Renderer();
		$result = $renderer->get_image_html($banner);

		expect($result)->toContain('src="https://example.com/desktop.jpg"');
		// Should not have source element when images are same.
		expect($result)->not->toContain('<source');
	});

	it('does not include source when both images are same', function () {
		// When only desktop is set, mobile falls back to desktop.
		$banner = [
			'desktop_image_id' => 10,
			'mobile_image_id' => null,
		];

		$renderer = new Banner_Renderer();
		$result = $renderer->get_image_html($banner);

		// No source element needed when URLs are identical.
		expect($result)->not->toContain('<source');
	});
});

describe('Banner_Renderer::get_link_url()', function () {
	it('returns desktop URL when available', function () {
		$banner = [
			'desktop_url' => 'https://example.com/desktop',
			'mobile_url' => 'https://example.com/mobile',
		];

		$renderer = new Banner_Renderer();
		$result = $renderer->get_link_url($banner);

		expect($result)->toBe('https://example.com/desktop');
	});

	it('returns empty string when no URL', function () {
		$banner = [
			'desktop_url' => '',
		];

		$renderer = new Banner_Renderer();
		$result = $renderer->get_link_url($banner);

		expect($result)->toBe('');
	});

	it('returns empty string when URL is null', function () {
		$banner = [
			'desktop_url' => null,
		];

		$renderer = new Banner_Renderer();
		$result = $renderer->get_link_url($banner);

		expect($result)->toBe('');
	});
});

describe('Banner_Renderer alt text', function () {
	it('uses desktop image alt text when available', function () {
		$banner = [
			'id' => 1,
			'title' => 'Banner Title',
			'desktop_image_id' => 10,
			'mobile_image_id' => null,
			'desktop_url' => 'https://example.com',
		];

		$placement = ['slug' => 'header'];

		$renderer = new Banner_Renderer();
		$result = $renderer->render($banner, $placement);

		expect($result)->toContain('alt="Desktop alt text"');
	});

	it('falls back to banner title when no alt text', function () {
		Functions\when('get_post_meta')->justReturn('');

		$banner = [
			'id' => 1,
			'title' => 'Banner Title Fallback',
			'desktop_image_id' => 10,
			'mobile_image_id' => null,
			'desktop_url' => 'https://example.com',
		];

		$placement = ['slug' => 'header'];

		$renderer = new Banner_Renderer();
		$result = $renderer->render($banner, $placement);

		expect($result)->toContain('alt="Banner Title Fallback"');
	});
});
