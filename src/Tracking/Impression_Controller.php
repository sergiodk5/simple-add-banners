<?php
/**
 * Impression tracking REST API controller.
 *
 * Handles the impression tracking endpoint.
 *
 * @package SimpleAddBanners\Tracking
 */

declare(strict_types=1);

namespace SimpleAddBanners\Tracking;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * Class Impression_Controller
 *
 * REST API endpoint for tracking banner impressions.
 */
class Impression_Controller extends WP_REST_Controller {

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'sab/v1';

	/**
	 * Resource base.
	 *
	 * @var string
	 */
	protected $rest_base = 'track';

	/**
	 * Token generator instance.
	 *
	 * @var Token_Generator
	 */
	private Token_Generator $token_generator;

	/**
	 * Statistics repository instance.
	 *
	 * @var Statistics_Repository
	 */
	private Statistics_Repository $stats_repo;

	/**
	 * Constructor.
	 *
	 * @param Token_Generator|null       $token_generator Token generator instance.
	 * @param Statistics_Repository|null $stats_repo      Statistics repository instance.
	 */
	public function __construct(
		?Token_Generator $token_generator = null,
		?Statistics_Repository $stats_repo = null
	) {
		$this->token_generator = $token_generator ?? new Token_Generator();
		$this->stats_repo      = $stats_repo ?? new Statistics_Repository();
	}

	/**
	 * Registers the routes for the controller.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/impression',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'track_impression' ),
					'permission_callback' => '__return_true', // Public endpoint.
					'args'                => array(
						'banner_id'    => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'validate_callback' => function ( $param ) {
								return is_numeric( $param ) && $param > 0;
							},
						),
						'placement_id' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'validate_callback' => function ( $param ) {
								return is_numeric( $param ) && $param > 0;
							},
						),
						'token'        => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
	}

	/**
	 * Track a banner impression.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error on failure.
	 */
	public function track_impression( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$banner_id    = (int) $request->get_param( 'banner_id' );
		$placement_id = (int) $request->get_param( 'placement_id' );
		$token        = (string) $request->get_param( 'token' );

		// Validate the token.
		if ( ! $this->token_generator->validate( $token, $banner_id, $placement_id ) ) {
			return new WP_Error(
				'invalid_token',
				__( 'Invalid tracking token.', 'simple-add-banners' ),
				array( 'status' => 400 )
			);
		}

		// Record the impression.
		$result = $this->stats_repo->increment_impressions( $banner_id, $placement_id );

		if ( ! $result ) {
			return new WP_Error(
				'tracking_failed',
				__( 'Failed to record impression.', 'simple-add-banners' ),
				array( 'status' => 500 )
			);
		}

		return new WP_REST_Response(
			array( 'success' => true ),
			200
		);
	}
}
