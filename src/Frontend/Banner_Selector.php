<?php
/**
 * Banner Selector - Handles rotation strategies for banner selection.
 *
 * @package SimpleAddBanners\Frontend
 */

namespace SimpleAddBanners\Frontend;

/**
 * Class Banner_Selector
 *
 * Selects a banner from a list based on rotation strategy and eligibility rules.
 */
class Banner_Selector {

	/**
	 * Select a banner based on rotation strategy.
	 *
	 * @param array  $banners      Array of banner data.
	 * @param string $strategy     Rotation strategy: 'random', 'weighted', or 'sequential'.
	 * @param int    $placement_id Placement ID (used for sequential strategy).
	 * @return array|null Selected banner or null if none eligible.
	 */
	public function select( array $banners, string $strategy, int $placement_id = 0 ): ?array {
		$eligible = $this->filter_eligible( $banners );

		if ( empty( $eligible ) ) {
			return null;
		}

		switch ( $strategy ) {
			case 'weighted':
				return $this->select_weighted( $eligible );
			case 'sequential':
				return $this->select_sequential( $eligible, $placement_id );
			case 'random':
			default:
				return $this->select_random( $eligible );
		}
	}

	/**
	 * Filter banners to only include eligible ones.
	 *
	 * Eligibility rules:
	 * - Status must be 'active'
	 * - If start_date exists, current time must be >= start_date
	 * - If end_date exists, current time must be <= end_date
	 * - Banner must have at least one image (desktop or mobile)
	 *
	 * @param array $banners Array of banner data.
	 * @return array Filtered array of eligible banners.
	 */
	public function filter_eligible( array $banners ): array {
		$now = current_time( 'mysql', true );

		return array_values(
			array_filter(
				$banners,
				function ( $banner ) use ( $now ) {
					// Must be active.
					if ( ! isset( $banner['status'] ) || 'active' !== $banner['status'] ) {
						return false;
					}

					// Check start date.
					if ( ! empty( $banner['start_date'] ) && $now < $banner['start_date'] ) {
						return false;
					}

					// Check end date.
					if ( ! empty( $banner['end_date'] ) && $now > $banner['end_date'] ) {
						return false;
					}

					// Must have at least one image.
					if ( empty( $banner['desktop_image_id'] ) && empty( $banner['mobile_image_id'] ) ) {
						return false;
					}

					return true;
				}
			)
		);
	}

	/**
	 * Select a random banner.
	 *
	 * @param array $banners Array of eligible banners.
	 * @return array|null Selected banner or null if empty.
	 */
	public function select_random( array $banners ): ?array {
		if ( empty( $banners ) ) {
			return null;
		}

		$index = array_rand( $banners );
		return $banners[ $index ];
	}

	/**
	 * Select a banner based on weight values.
	 *
	 * Higher weight = higher probability of selection.
	 *
	 * @param array $banners Array of eligible banners.
	 * @return array|null Selected banner or null if empty.
	 */
	public function select_weighted( array $banners ): ?array {
		if ( empty( $banners ) ) {
			return null;
		}

		// Calculate total weight.
		$total_weight = 0;
		foreach ( $banners as $banner ) {
			$total_weight += max( 1, (int) ( $banner['weight'] ?? 1 ) );
		}

		// Generate random number between 1 and total weight.
		$random = wp_rand( 1, $total_weight );

		// Find the banner whose cumulative weight range contains the random number.
		$cumulative = 0;
		foreach ( $banners as $banner ) {
			$cumulative += max( 1, (int) ( $banner['weight'] ?? 1 ) );
			if ( $random <= $cumulative ) {
				return $banner;
			}
		}

		// Fallback to first banner (shouldn't happen).
		return $banners[0];
	}

	/**
	 * Select a banner sequentially based on position.
	 *
	 * Uses a transient to track the current index and cycles through banners.
	 *
	 * @param array $banners      Array of eligible banners.
	 * @param int   $placement_id Placement ID for tracking position.
	 * @return array|null Selected banner or null if empty.
	 */
	public function select_sequential( array $banners, int $placement_id ): ?array {
		if ( empty( $banners ) ) {
			return null;
		}

		// Sort by position if available.
		usort(
			$banners,
			function ( $a, $b ) {
				$pos_a = (int) ( $a['position'] ?? 0 );
				$pos_b = (int) ( $b['position'] ?? 0 );
				return $pos_a - $pos_b;
			}
		);

		$transient_key = 'sab_seq_' . $placement_id;
		$current_index = (int) get_transient( $transient_key );

		// Ensure index is within bounds.
		if ( $current_index >= count( $banners ) ) {
			$current_index = 0;
		}

		$selected = $banners[ $current_index ];

		// Increment and save for next request.
		$next_index = ( $current_index + 1 ) % count( $banners );
		set_transient( $transient_key, $next_index, HOUR_IN_SECONDS );

		return $selected;
	}
}
