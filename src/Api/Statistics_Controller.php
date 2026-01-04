<?php
/**
 * Statistics REST API controller.
 *
 * Handles REST API endpoints for viewing banner statistics.
 *
 * @package SimpleAddBanners\Api
 * @since   1.0.0
 */

declare(strict_types=1);

namespace SimpleAddBanners\Api;

use SimpleAddBanners\Tracking\Statistics_Repository;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * Statistics REST API controller class.
 *
 * @since 1.0.0
 */
class Statistics_Controller extends WP_REST_Controller {

	/**
	 * API namespace.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $namespace = 'sab/v1';

	/**
	 * Resource base.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $rest_base = 'statistics';

	/**
	 * Statistics repository.
	 *
	 * @since 1.0.0
	 * @var Statistics_Repository
	 */
	private Statistics_Repository $repository;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Statistics_Repository|null $repository Optional repository instance.
	 */
	public function __construct( ?Statistics_Repository $repository = null ) {
		$this->repository = $repository ?? new Statistics_Repository();
	}

	/**
	 * Registers the routes for the controller.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// GET /statistics - All banners with aggregated stats.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
			)
		);

		// GET /statistics/banners/{id} - Stats for a specific banner.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/banners/(?P<id>[\d]+)',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'Unique identifier for the banner.', 'simple-add-banners' ),
						'type'        => 'integer',
						'required'    => true,
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_banner_stats' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_date_filter_params(),
				),
			)
		);

		// GET /statistics/placements/{id} - Stats for a specific placement.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/placements/(?P<id>[\d]+)',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'Unique identifier for the placement.', 'simple-add-banners' ),
						'type'        => 'integer',
						'required'    => true,
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_placement_stats' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_date_filter_params(),
				),
			)
		);
	}

	/**
	 * Retrieves aggregated statistics for all banners.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ): WP_REST_Response|WP_Error {
		$start_date = $request->get_param( 'start_date' );
		$end_date   = $request->get_param( 'end_date' );

		$stats = $this->repository->get_all_banner_stats( $start_date, $end_date );

		return new WP_REST_Response( $stats, 200 );
	}

	/**
	 * Retrieves statistics for a specific banner.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_banner_stats( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$banner_id  = (int) $request->get_param( 'id' );
		$start_date = $request->get_param( 'start_date' );
		$end_date   = $request->get_param( 'end_date' );

		$daily_stats      = $this->repository->get_stats( $banner_id, $start_date, $end_date );
		$aggregated_stats = $this->repository->get_aggregated_stats( $banner_id, $start_date, $end_date );

		return new WP_REST_Response(
			array(
				'banner_id'  => $banner_id,
				'totals'     => $aggregated_stats,
				'daily'      => $daily_stats,
				'start_date' => $start_date,
				'end_date'   => $end_date,
			),
			200
		);
	}

	/**
	 * Retrieves statistics for a specific placement.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_placement_stats( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$placement_id = (int) $request->get_param( 'id' );
		$start_date   = $request->get_param( 'start_date' );
		$end_date     = $request->get_param( 'end_date' );

		$daily_stats = $this->repository->get_stats_by_placement( $placement_id, $start_date, $end_date );

		// Calculate totals from daily stats.
		$total_impressions = 0;
		$total_clicks      = 0;

		foreach ( $daily_stats as $stat ) {
			$total_impressions += $stat['impressions'];
			$total_clicks      += $stat['clicks'];
		}

		$ctr = $total_impressions > 0 ? round( ( $total_clicks / $total_impressions ) * 100, 2 ) : 0;

		return new WP_REST_Response(
			array(
				'placement_id' => $placement_id,
				'totals'       => array(
					'impressions' => $total_impressions,
					'clicks'      => $total_clicks,
					'ctr'         => $ctr,
				),
				'daily'        => $daily_stats,
				'start_date'   => $start_date,
				'end_date'     => $end_date,
			),
			200
		);
	}

	/**
	 * Checks if a given request has access to get items.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ): bool|WP_Error {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Retrieves the query params for collections.
	 *
	 * @since 1.0.0
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params(): array {
		return array_merge(
			$this->get_date_filter_params(),
			array()
		);
	}

	/**
	 * Retrieves the date filter parameters.
	 *
	 * @since 1.0.0
	 *
	 * @return array Date filter parameters.
	 */
	public function get_date_filter_params(): array {
		return array(
			'start_date' => array(
				'description'       => __( 'Filter statistics from this date (Y-m-d format).', 'simple-add-banners' ),
				'type'              => 'string',
				'format'            => 'date',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'end_date'   => array(
				'description'       => __( 'Filter statistics until this date (Y-m-d format).', 'simple-add-banners' ),
				'type'              => 'string',
				'format'            => 'date',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}
}
