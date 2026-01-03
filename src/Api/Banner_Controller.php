<?php
/**
 * Banner REST API controller.
 *
 * Handles REST API endpoints for banner CRUD operations.
 *
 * @package SimpleAddBanners\Api
 * @since   1.0.0
 */

declare(strict_types=1);

namespace SimpleAddBanners\Api;

use SimpleAddBanners\Repository\Banner_Repository;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * Banner REST API controller class.
 *
 * @since 1.0.0
 */
class Banner_Controller extends WP_REST_Controller {

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
	protected $rest_base = 'banners';

	/**
	 * Banner repository.
	 *
	 * @since 1.0.0
	 * @var Banner_Repository
	 */
	private Banner_Repository $repository;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->repository = new Banner_Repository();
	}

	/**
	 * Registers the routes for the controller.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
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
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the banner.', 'simple-add-banners' ),
						'type'        => 'integer',
						'required'    => true,
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Retrieves a collection of banners.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ): WP_REST_Response|WP_Error {
		$args = array(
			'status'   => $request->get_param( 'status' ) ?? '',
			'order_by' => $request->get_param( 'orderby' ) ?? 'created_at',
			'order'    => $request->get_param( 'order' ) ?? 'DESC',
			'limit'    => $request->get_param( 'per_page' ) ?? 10,
			'offset'   => ( ( $request->get_param( 'page' ) ?? 1 ) - 1 ) * ( $request->get_param( 'per_page' ) ?? 10 ),
		);

		$banners = $this->repository->get_all( $args );
		$total   = $this->repository->count( $args['status'] );

		$data = array();
		foreach ( $banners as $banner ) {
			$data[] = $this->prepare_item_for_response( $banner, $request )->get_data();
		}

		$response = new WP_REST_Response( $data, 200 );

		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', ceil( $total / $args['limit'] ) );

		return $response;
	}

	/**
	 * Retrieves a single banner.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ): WP_REST_Response|WP_Error {
		$banner = $this->repository->get_by_id( (int) $request->get_param( 'id' ) );

		if ( ! $banner ) {
			return new WP_Error(
				'rest_banner_not_found',
				__( 'Banner not found.', 'simple-add-banners' ),
				array( 'status' => 404 )
			);
		}

		return $this->prepare_item_for_response( $banner, $request );
	}

	/**
	 * Creates a single banner.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ): WP_REST_Response|WP_Error {
		$data = array(
			'title'            => $request->get_param( 'title' ),
			'desktop_image_id' => $request->get_param( 'desktop_image_id' ),
			'mobile_image_id'  => $request->get_param( 'mobile_image_id' ),
			'desktop_url'      => $request->get_param( 'desktop_url' ),
			'mobile_url'       => $request->get_param( 'mobile_url' ),
			'start_date'       => $request->get_param( 'start_date' ),
			'end_date'         => $request->get_param( 'end_date' ),
			'status'           => $request->get_param( 'status' ) ?? 'active',
			'weight'           => $request->get_param( 'weight' ) ?? 1,
		);

		$id = $this->repository->create( $data );

		if ( ! $id ) {
			return new WP_Error(
				'rest_banner_create_failed',
				__( 'Failed to create banner.', 'simple-add-banners' ),
				array( 'status' => 500 )
			);
		}

		$banner = $this->repository->get_by_id( $id );

		$response = $this->prepare_item_for_response( $banner, $request );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $id ) ) );

		return $response;
	}

	/**
	 * Updates a single banner.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ): WP_REST_Response|WP_Error {
		$id     = (int) $request->get_param( 'id' );
		$banner = $this->repository->get_by_id( $id );

		if ( ! $banner ) {
			return new WP_Error(
				'rest_banner_not_found',
				__( 'Banner not found.', 'simple-add-banners' ),
				array( 'status' => 404 )
			);
		}

		$data = array();

		$fields = array(
			'title',
			'desktop_image_id',
			'mobile_image_id',
			'desktop_url',
			'mobile_url',
			'start_date',
			'end_date',
			'status',
			'weight',
		);

		foreach ( $fields as $field ) {
			if ( $request->has_param( $field ) ) {
				$data[ $field ] = $request->get_param( $field );
			}
		}

		$result = $this->repository->update( $id, $data );

		if ( ! $result ) {
			return new WP_Error(
				'rest_banner_update_failed',
				__( 'Failed to update banner.', 'simple-add-banners' ),
				array( 'status' => 500 )
			);
		}

		$banner = $this->repository->get_by_id( $id );

		return $this->prepare_item_for_response( $banner, $request );
	}

	/**
	 * Deletes a single banner.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ): WP_REST_Response|WP_Error {
		$id     = (int) $request->get_param( 'id' );
		$banner = $this->repository->get_by_id( $id );

		if ( ! $banner ) {
			return new WP_Error(
				'rest_banner_not_found',
				__( 'Banner not found.', 'simple-add-banners' ),
				array( 'status' => 404 )
			);
		}

		$result = $this->repository->delete( $id );

		if ( ! $result ) {
			return new WP_Error(
				'rest_banner_delete_failed',
				__( 'Failed to delete banner.', 'simple-add-banners' ),
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
	 * Checks if a given request has access to get a specific item.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ): bool|WP_Error {
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
	 * Checks if a given request has access to update a specific item.
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
	 * Checks if a given request has access to delete a specific item.
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
	 * Prepares a single banner output for response.
	 *
	 * @since 1.0.0
	 *
	 * @param array           $banner  Banner data.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $banner, $request ): WP_REST_Response {
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

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Retrieves the query params for collections.
	 *
	 * @since 1.0.0
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params(): array {
		return array(
			'page'     => array(
				'description'       => __( 'Current page of the collection.', 'simple-add-banners' ),
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'description'       => __( 'Maximum number of items to be returned in result set.', 'simple-add-banners' ),
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
			),
			'status'   => array(
				'description'       => __( 'Limit result set to banners with a specific status.', 'simple-add-banners' ),
				'type'              => 'string',
				'enum'              => array( 'active', 'paused', 'scheduled' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'orderby'  => array(
				'description'       => __( 'Sort collection by banner attribute.', 'simple-add-banners' ),
				'type'              => 'string',
				'default'           => 'created_at',
				'enum'              => array( 'id', 'title', 'status', 'weight', 'start_date', 'end_date', 'created_at', 'updated_at' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'order'    => array(
				'description'       => __( 'Order sort attribute ascending or descending.', 'simple-add-banners' ),
				'type'              => 'string',
				'default'           => 'DESC',
				'enum'              => array( 'ASC', 'DESC' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Retrieves the banner schema, conforming to JSON Schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema(): array {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'banner',
			'type'       => 'object',
			'properties' => array(
				'id'               => array(
					'description' => __( 'Unique identifier for the banner.', 'simple-add-banners' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'title'            => array(
					'description' => __( 'The title for the banner.', 'simple-add-banners' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'required'    => true,
				),
				'desktop_image_id' => array(
					'description' => __( 'The attachment ID for the desktop image.', 'simple-add-banners' ),
					'type'        => array( 'integer', 'null' ),
					'context'     => array( 'view', 'edit' ),
				),
				'mobile_image_id'  => array(
					'description' => __( 'The attachment ID for the mobile image.', 'simple-add-banners' ),
					'type'        => array( 'integer', 'null' ),
					'context'     => array( 'view', 'edit' ),
				),
				'desktop_url'      => array(
					'description' => __( 'The destination URL for desktop.', 'simple-add-banners' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'required'    => true,
				),
				'mobile_url'       => array(
					'description' => __( 'The destination URL for mobile.', 'simple-add-banners' ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
				),
				'start_date'       => array(
					'description' => __( 'The date the banner becomes active.', 'simple-add-banners' ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'end_date'         => array(
					'description' => __( 'The date the banner expires.', 'simple-add-banners' ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'status'           => array(
					'description' => __( 'The status of the banner.', 'simple-add-banners' ),
					'type'        => 'string',
					'enum'        => array( 'active', 'paused', 'scheduled' ),
					'default'     => 'active',
					'context'     => array( 'view', 'edit' ),
				),
				'weight'           => array(
					'description' => __( 'The weight for banner rotation.', 'simple-add-banners' ),
					'type'        => 'integer',
					'minimum'     => 1,
					'default'     => 1,
					'context'     => array( 'view', 'edit' ),
				),
				'created_at'       => array(
					'description' => __( 'The date the banner was created.', 'simple-add-banners' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'updated_at'       => array(
					'description' => __( 'The date the banner was last updated.', 'simple-add-banners' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $this->schema );
	}
}
