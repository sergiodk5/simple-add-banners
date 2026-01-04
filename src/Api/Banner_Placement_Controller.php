<?php
/**
 * Banner Placement REST API controller.
 *
 * Handles REST API endpoints for banner-placement assignments.
 *
 * @package SimpleAddBanners\Api
 * @since   1.0.0
 */

declare(strict_types=1);

namespace SimpleAddBanners\Api;

use SimpleAddBanners\Repository\Banner_Placement_Repository;
use SimpleAddBanners\Repository\Placement_Repository;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * Banner Placement REST API controller class.
 *
 * @since 1.0.0
 */
class Banner_Placement_Controller extends WP_REST_Controller {

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
	protected $rest_base = 'placements';

	/**
	 * Banner placement repository.
	 *
	 * @since 1.0.0
	 * @var Banner_Placement_Repository
	 */
	private Banner_Placement_Repository $repository;

	/**
	 * Placement repository.
	 *
	 * @since 1.0.0
	 * @var Placement_Repository
	 */
	private Placement_Repository $placement_repository;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->repository           = new Banner_Placement_Repository();
		$this->placement_repository = new Placement_Repository();
	}

	/**
	 * Registers the routes for the controller.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// GET and PUT for /placements/{id}/banners.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/banners',
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
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'sync_items' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => array(
						'banner_ids' => array(
							'description' => __( 'Array of banner IDs to assign to the placement.', 'simple-add-banners' ),
							'type'        => 'array',
							'items'       => array(
								'type' => 'integer',
							),
							'required'    => true,
						),
					),
				),
			)
		);

		// POST for adding a single banner.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/banners',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'Unique identifier for the placement.', 'simple-add-banners' ),
						'type'        => 'integer',
						'required'    => true,
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => array(
						'banner_id' => array(
							'description' => __( 'Banner ID to assign.', 'simple-add-banners' ),
							'type'        => 'integer',
							'required'    => true,
						),
						'position'  => array(
							'description' => __( 'Position in the rotation order.', 'simple-add-banners' ),
							'type'        => 'integer',
							'default'     => 0,
						),
					),
				),
			)
		);

		// DELETE for removing a single banner.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/banners/(?P<banner_id>[\d]+)',
			array(
				'args' => array(
					'id'        => array(
						'description' => __( 'Unique identifier for the placement.', 'simple-add-banners' ),
						'type'        => 'integer',
						'required'    => true,
					),
					'banner_id' => array(
						'description' => __( 'Unique identifier for the banner.', 'simple-add-banners' ),
						'type'        => 'integer',
						'required'    => true,
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Retrieves banners assigned to a placement.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ): WP_REST_Response|WP_Error {
		$placement_id = (int) $request->get_param( 'id' );

		// Check if placement exists.
		$placement = $this->placement_repository->get_by_id( $placement_id );
		if ( ! $placement ) {
			return new WP_Error(
				'rest_placement_not_found',
				__( 'Placement not found.', 'simple-add-banners' ),
				array( 'status' => 404 )
			);
		}

		$banners = $this->repository->get_banners_for_placement( $placement_id );

		$data = array();
		foreach ( $banners as $banner ) {
			$data[] = $this->prepare_banner_for_response( $banner );
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Syncs banners for a placement (replaces all assignments).
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function sync_items( $request ): WP_REST_Response|WP_Error {
		$placement_id = (int) $request->get_param( 'id' );

		// Check if placement exists.
		$placement = $this->placement_repository->get_by_id( $placement_id );
		if ( ! $placement ) {
			return new WP_Error(
				'rest_placement_not_found',
				__( 'Placement not found.', 'simple-add-banners' ),
				array( 'status' => 404 )
			);
		}

		$banner_ids = $request->get_param( 'banner_ids' );

		$result = $this->repository->sync_banners( $placement_id, $banner_ids );

		if ( ! $result ) {
			return new WP_Error(
				'rest_banner_placement_sync_failed',
				__( 'Failed to sync banner assignments.', 'simple-add-banners' ),
				array( 'status' => 500 )
			);
		}

		// Return updated list of assigned banners.
		$banners = $this->repository->get_banners_for_placement( $placement_id );

		$data = array();
		foreach ( $banners as $banner ) {
			$data[] = $this->prepare_banner_for_response( $banner );
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Adds a banner to a placement.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ): WP_REST_Response|WP_Error {
		$placement_id = (int) $request->get_param( 'id' );
		$banner_id    = (int) $request->get_param( 'banner_id' );
		$position     = (int) $request->get_param( 'position' );

		// Check if placement exists.
		$placement = $this->placement_repository->get_by_id( $placement_id );
		if ( ! $placement ) {
			return new WP_Error(
				'rest_placement_not_found',
				__( 'Placement not found.', 'simple-add-banners' ),
				array( 'status' => 404 )
			);
		}

		// Check if already attached.
		if ( $this->repository->is_attached( $placement_id, $banner_id ) ) {
			return new WP_Error(
				'rest_banner_already_attached',
				__( 'Banner is already assigned to this placement.', 'simple-add-banners' ),
				array( 'status' => 400 )
			);
		}

		$result = $this->repository->attach( $placement_id, $banner_id, $position );

		if ( ! $result ) {
			return new WP_Error(
				'rest_banner_placement_attach_failed',
				__( 'Failed to assign banner to placement.', 'simple-add-banners' ),
				array( 'status' => 500 )
			);
		}

		// Return updated list of assigned banners.
		$banners = $this->repository->get_banners_for_placement( $placement_id );

		$data = array();
		foreach ( $banners as $banner ) {
			$data[] = $this->prepare_banner_for_response( $banner );
		}

		$response = new WP_REST_Response( $data, 201 );

		return $response;
	}

	/**
	 * Removes a banner from a placement.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ): WP_REST_Response|WP_Error {
		$placement_id = (int) $request->get_param( 'id' );
		$banner_id    = (int) $request->get_param( 'banner_id' );

		// Check if placement exists.
		$placement = $this->placement_repository->get_by_id( $placement_id );
		if ( ! $placement ) {
			return new WP_Error(
				'rest_placement_not_found',
				__( 'Placement not found.', 'simple-add-banners' ),
				array( 'status' => 404 )
			);
		}

		// Check if attached.
		if ( ! $this->repository->is_attached( $placement_id, $banner_id ) ) {
			return new WP_Error(
				'rest_banner_not_attached',
				__( 'Banner is not assigned to this placement.', 'simple-add-banners' ),
				array( 'status' => 404 )
			);
		}

		$result = $this->repository->detach( $placement_id, $banner_id );

		if ( ! $result ) {
			return new WP_Error(
				'rest_banner_placement_detach_failed',
				__( 'Failed to remove banner from placement.', 'simple-add-banners' ),
				array( 'status' => 500 )
			);
		}

		return new WP_REST_Response( null, 204 );
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
	 * Checks if a given request has access to create items.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has create access, WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ): bool|WP_Error {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Checks if a given request has access to update items.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has update access, WP_Error object otherwise.
	 */
	public function update_item_permissions_check( $request ): bool|WP_Error {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Checks if a given request has access to delete items.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has delete access, WP_Error object otherwise.
	 */
	public function delete_item_permissions_check( $request ): bool|WP_Error {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Prepares a banner for response.
	 *
	 * @since 1.0.0
	 *
	 * @param array $banner Banner data with position.
	 * @return array Prepared banner data.
	 */
	private function prepare_banner_for_response( array $banner ): array {
		$data = array(
			'id'               => $banner['id'],
			'title'            => $banner['title'],
			'desktop_image_id' => $banner['desktop_image_id'],
			'mobile_image_id'  => $banner['mobile_image_id'],
			'desktop_url'      => $banner['desktop_url'],
			'mobile_url'       => $banner['mobile_url'],
			'start_date'       => $banner['start_date'],
			'end_date'         => $banner['end_date'],
			'status'           => $banner['status'],
			'weight'           => $banner['weight'],
			'position'         => $banner['position'],
			'created_at'       => $banner['created_at'],
			'updated_at'       => $banner['updated_at'],
		);

		// Add image URLs if available.
		if ( $banner['desktop_image_id'] ) {
			$data['desktop_image_url'] = wp_get_attachment_url( $banner['desktop_image_id'] );
		}

		if ( $banner['mobile_image_id'] ) {
			$data['mobile_image_url'] = wp_get_attachment_url( $banner['mobile_image_id'] );
		}

		return $data;
	}
}
