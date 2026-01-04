<?php
/**
 * Shortcode Handler - Registers and handles the [sab_banner] shortcode.
 *
 * @package SimpleAddBanners\Frontend
 */

namespace SimpleAddBanners\Frontend;

use SimpleAddBanners\Repository\Placement_Repository;
use SimpleAddBanners\Repository\Banner_Placement_Repository;

/**
 * Class Shortcode_Handler
 *
 * Handles the [sab_banner placement="slug"] shortcode.
 */
class Shortcode_Handler {

	/**
	 * Placement repository instance.
	 *
	 * @var Placement_Repository
	 */
	private Placement_Repository $placement_repo;

	/**
	 * Banner placement repository instance.
	 *
	 * @var Banner_Placement_Repository
	 */
	private Banner_Placement_Repository $bp_repo;

	/**
	 * Banner selector instance.
	 *
	 * @var Banner_Selector
	 */
	private Banner_Selector $selector;

	/**
	 * Banner renderer instance.
	 *
	 * @var Banner_Renderer
	 */
	private Banner_Renderer $renderer;

	/**
	 * Constructor.
	 *
	 * @param Placement_Repository|null        $placement_repo Placement repository.
	 * @param Banner_Placement_Repository|null $bp_repo        Banner placement repository.
	 * @param Banner_Selector|null             $selector       Banner selector.
	 * @param Banner_Renderer|null             $renderer       Banner renderer.
	 */
	public function __construct(
		?Placement_Repository $placement_repo = null,
		?Banner_Placement_Repository $bp_repo = null,
		?Banner_Selector $selector = null,
		?Banner_Renderer $renderer = null
	) {
		$this->placement_repo = $placement_repo ?? new Placement_Repository();
		$this->bp_repo        = $bp_repo ?? new Banner_Placement_Repository();
		$this->selector       = $selector ?? new Banner_Selector();
		$this->renderer       = $renderer ?? new Banner_Renderer();

		$this->register_shortcode();
	}

	/**
	 * Register the shortcode with WordPress.
	 *
	 * @return void
	 */
	private function register_shortcode(): void {
		add_shortcode( 'sab_banner', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_shortcode( $atts ): string {
		// Parse attributes with defaults.
		$atts = shortcode_atts(
			array(
				'placement' => '',
			),
			$atts,
			'sab_banner'
		);

		// Sanitize placement slug.
		$slug = sanitize_text_field( $atts['placement'] );

		if ( empty( $slug ) ) {
			return '';
		}

		// Get placement by slug.
		$placement = $this->placement_repo->get_by_slug( $slug );

		if ( null === $placement ) {
			return '';
		}

		// Get banners assigned to this placement.
		$banners = $this->bp_repo->get_banners_for_placement( (int) $placement['id'] );

		if ( empty( $banners ) ) {
			return '';
		}

		// Enrich banners with image URLs.
		$banners = $this->enrich_banners_with_images( $banners );

		// Select a banner based on rotation strategy.
		$selected = $this->selector->select(
			$banners,
			$placement['rotation_strategy'] ?? 'random',
			(int) $placement['id']
		);

		if ( null === $selected ) {
			return '';
		}

		// Render the banner.
		return $this->renderer->render( $selected, $placement );
	}

	/**
	 * Enrich banner data with image URLs from WordPress.
	 *
	 * @param array $banners Array of banner data.
	 * @return array Banners with image URLs added.
	 */
	private function enrich_banners_with_images( array $banners ): array {
		foreach ( $banners as &$banner ) {
			// Add desktop image URL if ID exists.
			if ( ! empty( $banner['desktop_image_id'] ) ) {
				$image                       = wp_get_attachment_image_src( (int) $banner['desktop_image_id'], 'full' );
				$banner['desktop_image_url'] = $image ? $image[0] : null;
			}

			// Add mobile image URL if ID exists.
			if ( ! empty( $banner['mobile_image_id'] ) ) {
				$image                      = wp_get_attachment_image_src( (int) $banner['mobile_image_id'], 'full' );
				$banner['mobile_image_url'] = $image ? $image[0] : null;
			}
		}

		return $banners;
	}
}
