<?php
/**
 * Banner Renderer - Generates HTML output for banners.
 *
 * @package SimpleAddBanners\Frontend
 */

namespace SimpleAddBanners\Frontend;

/**
 * Class Banner_Renderer
 *
 * Renders banner HTML with responsive images and proper escaping.
 */
class Banner_Renderer {

	/**
	 * Render a banner as HTML.
	 *
	 * @param array $banner    Banner data with image URLs.
	 * @param array $placement Placement data.
	 * @return string HTML output.
	 */
	public function render( array $banner, array $placement ): string {
		$image_html = $this->get_image_html( $banner );

		// Return empty if no image available.
		if ( empty( $image_html ) ) {
			return '';
		}

		$link_url  = $this->get_link_url( $banner );
		$banner_id = isset( $banner['id'] ) ? (int) $banner['id'] : 0;
		$slug      = isset( $placement['slug'] ) ? $placement['slug'] : '';

		$output  = '<div class="sab-banner" data-placement="' . esc_attr( $slug ) . '" data-banner-id="' . esc_attr( $banner_id ) . '">';
		$output .= '<style>.sab-banner{display:block}.sab-banner img{max-width:100%;height:auto;display:block}</style>';

		if ( ! empty( $link_url ) ) {
			$output .= '<a href="' . esc_url( $link_url ) . '" target="_blank" rel="noopener noreferrer">';
			$output .= $image_html;
			$output .= '</a>';
		} else {
			$output .= $image_html;
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Get the image HTML with responsive picture element.
	 *
	 * @param array $banner Banner data.
	 * @return string Image HTML or empty string.
	 */
	public function get_image_html( array $banner ): string {
		$desktop_url = $this->get_image_url( $banner['desktop_image_id'] ?? null );
		$mobile_url  = $this->get_image_url( $banner['mobile_image_id'] ?? null );

		// Fallback: If only mobile, use for both.
		if ( empty( $desktop_url ) && ! empty( $mobile_url ) ) {
			$desktop_url = $mobile_url;
		}

		// Fallback: If only desktop, use for both.
		if ( ! empty( $desktop_url ) && empty( $mobile_url ) ) {
			$mobile_url = $desktop_url;
		}

		// Return empty if no images at all.
		if ( empty( $desktop_url ) ) {
			return '';
		}

		$alt = $this->get_image_alt( $banner );

		// Build picture element for responsive images.
		$html = '<picture>';

		// Add mobile source if different from desktop.
		if ( $mobile_url !== $desktop_url ) {
			$html .= '<source media="(max-width: 768px)" srcset="' . esc_url( $mobile_url ) . '">';
		}

		$html .= '<img src="' . esc_url( $desktop_url ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy">';
		$html .= '</picture>';

		return $html;
	}

	/**
	 * Get the link URL for the banner.
	 *
	 * @param array $banner Banner data.
	 * @return string URL or empty string.
	 */
	public function get_link_url( array $banner ): string {
		// Desktop URL is the primary URL.
		if ( ! empty( $banner['desktop_url'] ) ) {
			return $banner['desktop_url'];
		}

		return '';
	}

	/**
	 * Get the image URL from attachment ID.
	 *
	 * @param int|null $attachment_id WordPress attachment ID.
	 * @return string Image URL or empty string.
	 */
	private function get_image_url( ?int $attachment_id ): string {
		if ( empty( $attachment_id ) ) {
			return '';
		}

		$image = wp_get_attachment_image_src( $attachment_id, 'full' );

		if ( ! $image || empty( $image[0] ) ) {
			return '';
		}

		return $image[0];
	}

	/**
	 * Get the alt text for the banner image.
	 *
	 * @param array $banner Banner data.
	 * @return string Alt text.
	 */
	private function get_image_alt( array $banner ): string {
		// Try desktop image alt first.
		if ( ! empty( $banner['desktop_image_id'] ) ) {
			$alt = get_post_meta( $banner['desktop_image_id'], '_wp_attachment_image_alt', true );
			if ( ! empty( $alt ) ) {
				return $alt;
			}
		}

		// Try mobile image alt.
		if ( ! empty( $banner['mobile_image_id'] ) ) {
			$alt = get_post_meta( $banner['mobile_image_id'], '_wp_attachment_image_alt', true );
			if ( ! empty( $alt ) ) {
				return $alt;
			}
		}

		// Fallback to banner title.
		return $banner['title'] ?? '';
	}
}
