<?php
/**
 * Token Generator - Generates and validates signed tracking tokens.
 *
 * @package SimpleAddBanners\Tracking
 */

namespace SimpleAddBanners\Tracking;

/**
 * Class Token_Generator
 *
 * Generates HMAC-SHA256 signed tokens for tracking requests.
 * Tokens are date-bound to prevent replay attacks.
 */
class Token_Generator {

	/**
	 * Option key for the tracking secret.
	 *
	 * @var string
	 */
	private const SECRET_OPTION = 'simple_add_banners_tracking_secret';

	/**
	 * Generate a tracking token for a banner and placement.
	 *
	 * @param int $banner_id    The banner ID.
	 * @param int $placement_id The placement ID.
	 * @return string The generated token.
	 */
	public function generate( int $banner_id, int $placement_id ): string {
		$secret = $this->get_secret();
		$date   = $this->get_current_date();
		$data   = $this->build_data_string( $banner_id, $placement_id, $date );

		return hash_hmac( 'sha256', $data, $secret );
	}

	/**
	 * Validate a tracking token.
	 *
	 * @param string $token        The token to validate.
	 * @param int    $banner_id    The banner ID.
	 * @param int    $placement_id The placement ID.
	 * @return bool True if valid, false otherwise.
	 */
	public function validate( string $token, int $banner_id, int $placement_id ): bool {
		if ( empty( $token ) ) {
			return false;
		}

		$expected = $this->generate( $banner_id, $placement_id );

		return hash_equals( $expected, $token );
	}

	/**
	 * Get the tracking secret from options.
	 *
	 * @return string The secret key.
	 */
	private function get_secret(): string {
		$secret = get_option( self::SECRET_OPTION );

		if ( empty( $secret ) ) {
			// Generate and store a new secret if one doesn't exist.
			$secret = wp_generate_password( 32, true, true );
			update_option( self::SECRET_OPTION, $secret );
		}

		return $secret;
	}

	/**
	 * Get the current date in UTC.
	 *
	 * @return string The date in Y-m-d format.
	 */
	private function get_current_date(): string {
		return gmdate( 'Y-m-d' );
	}

	/**
	 * Build the data string for HMAC generation.
	 *
	 * @param int    $banner_id    The banner ID.
	 * @param int    $placement_id The placement ID.
	 * @param string $date         The date string.
	 * @return string The data string.
	 */
	private function build_data_string( int $banner_id, int $placement_id, string $date ): string {
		return "{$banner_id}:{$placement_id}:{$date}";
	}
}
