<?php
/**
 * Tests for Token_Generator class.
 *
 * @package SimpleAddBanners\Tests\Unit\Tracking
 */

declare(strict_types=1);

namespace SimpleAddBanners\Tests\Unit\Tracking;

use SimpleAddBanners\Tracking\Token_Generator;
use Brain\Monkey\Functions;

beforeEach(function () {
	// Default secret for testing.
	$this->test_secret = 'test_secret_key_for_hmac_generation';

	Functions\when('get_option')->alias(function ($key) {
		if ($key === 'simple_add_banners_tracking_secret') {
			return $this->test_secret;
		}
		return false;
	});

	Functions\when('update_option')->justReturn(true);
	Functions\when('wp_generate_password')->justReturn('new_generated_secret_12345');
});

describe('Token_Generator::generate()', function () {
	it('generates a token string', function () {
		$generator = new Token_Generator();

		$token = $generator->generate(1, 2);

		expect($token)->toBeString();
		expect($token)->not->toBeEmpty();
	});

	it('generates consistent tokens for same inputs', function () {
		$generator = new Token_Generator();

		$token1 = $generator->generate(1, 2);
		$token2 = $generator->generate(1, 2);

		expect($token1)->toBe($token2);
	});

	it('generates different tokens for different banner IDs', function () {
		$generator = new Token_Generator();

		$token1 = $generator->generate(1, 2);
		$token2 = $generator->generate(2, 2);

		expect($token1)->not->toBe($token2);
	});

	it('generates different tokens for different placement IDs', function () {
		$generator = new Token_Generator();

		$token1 = $generator->generate(1, 2);
		$token2 = $generator->generate(1, 3);

		expect($token1)->not->toBe($token2);
	});

	it('generates 64-character hex string (SHA256)', function () {
		$generator = new Token_Generator();

		$token = $generator->generate(1, 2);

		expect(strlen($token))->toBe(64);
		expect(ctype_xdigit($token))->toBeTrue();
	});
});

describe('Token_Generator::validate()', function () {
	it('returns true for valid tokens', function () {
		$generator = new Token_Generator();

		$token = $generator->generate(1, 2);
		$valid = $generator->validate($token, 1, 2);

		expect($valid)->toBeTrue();
	});

	it('returns false for invalid tokens', function () {
		$generator = new Token_Generator();

		$valid = $generator->validate('invalid_token_string', 1, 2);

		expect($valid)->toBeFalse();
	});

	it('returns false for empty tokens', function () {
		$generator = new Token_Generator();

		$valid = $generator->validate('', 1, 2);

		expect($valid)->toBeFalse();
	});

	it('returns false for tampered tokens', function () {
		$generator = new Token_Generator();

		$token = $generator->generate(1, 2);
		// Change one character.
		$tampered = substr($token, 0, -1) . 'x';

		$valid = $generator->validate($tampered, 1, 2);

		expect($valid)->toBeFalse();
	});

	it('returns false when banner ID does not match', function () {
		$generator = new Token_Generator();

		$token = $generator->generate(1, 2);
		$valid = $generator->validate($token, 99, 2);

		expect($valid)->toBeFalse();
	});

	it('returns false when placement ID does not match', function () {
		$generator = new Token_Generator();

		$token = $generator->generate(1, 2);
		$valid = $generator->validate($token, 1, 99);

		expect($valid)->toBeFalse();
	});
});

describe('Token_Generator secret handling', function () {
	it('generates new secret when none exists', function () {
		Functions\when('get_option')->justReturn(false);

		$updateCalled = false;
		Functions\when('update_option')->alias(function ($key, $value) use (&$updateCalled) {
			if ($key === 'simple_add_banners_tracking_secret') {
				$updateCalled = true;
			}
			return true;
		});

		$generator = new Token_Generator();
		$generator->generate(1, 2);

		expect($updateCalled)->toBeTrue();
	});

	it('uses existing secret when available', function () {
		$existingSecret = 'existing_secret_from_database';
		Functions\when('get_option')->alias(function ($key) use ($existingSecret) {
			if ($key === 'simple_add_banners_tracking_secret') {
				return $existingSecret;
			}
			return false;
		});

		// Manual HMAC calculation for verification.
		$date     = gmdate('Y-m-d');
		$data     = "1:2:{$date}";
		$expected = hash_hmac('sha256', $data, $existingSecret);

		$generator = new Token_Generator();
		$token     = $generator->generate(1, 2);

		expect($token)->toBe($expected);
	});
});

describe('Token_Generator date-bound tokens', function () {
	it('generates date-dependent tokens', function () {
		$generator = new Token_Generator();

		// The token includes the date, so we can verify by checking the HMAC.
		$date     = gmdate('Y-m-d');
		$data     = "123:456:{$date}";
		$expected = hash_hmac('sha256', $data, $this->test_secret);

		$token = $generator->generate(123, 456);

		expect($token)->toBe($expected);
	});
});
