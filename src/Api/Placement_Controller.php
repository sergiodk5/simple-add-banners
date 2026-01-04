<?php
/**
 * Placement REST API controller.
 *
 * Handles REST API endpoints for placement CRUD operations.
 *
 * @package SimpleAddBanners\Api
 * @since   1.0.0
 */

declare(strict_types=1);

namespace SimpleAddBanners\Api;

use SimpleAddBanners\Repository\Placement_Repository;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * Placement REST API controller class.
 *
 * @since 1.0.0
 */
class Placement_Controller extends WP_REST_Controller {


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
	 * Placement repository.
	 *
	 * @since 1.0.0
	 * @var Placement_Repository
	 */
	private Placement_Repository $repository;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->repository = new Placement_Repository();
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
						'description' => __( 'Unique identifier for the placement.', 'simple-add-banners' ),
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
	 * Retrieves a collection of placements.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ): WP_REST_Response|WP_Error {
		$args = array(
			'order_by' => $request->get_param( 'orderby' ) ?? 'created_at',
			'order'    => $request->get_param( 'order' ) ?? 'DESC',
			'limit'    => $request->get_param( 'per_page' ) ?? 10,
			'offset'   => ( ( $request->get_param( 'page' ) ?? 1 ) - 1 ) * ( $request->get_param( 'per_page' ) ?? 10 ),
		);

		$placements = $this->repository->get_all( $args );
		$total      = $this->repository->count();

		$data = array();
		foreach ( $placements as $placement ) {
			$data[] = $this->prepare_item_for_response( $placement, $request )->get_data();
		}

		$response = new WP_REST_Response( $data, 200 );

		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', ceil( $total / $args['limit'] ) );

		return $response;
	}

	/**
	 * Retrieves a single placement.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ): WP_REST_Response|WP_Error {
		$placement = $this->repository->get_by_id( (int) $request->get_param( 'id' ) );

		if ( ! $placement ) {
			return new WP_Error(
				'rest_placement_not_found',
				__( 'Placement not found.', 'simple-add-banners' ),
				array( 'status' => 404 )
			);
		}

		return $this->prepare_item_for_response( $placement, $request );
	}

	/**
	 * Creates a single placement.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ): WP_REST_Response|WP_Error {
		$slug = sanitize_title( $request->get_param( 'slug' ) );

		// Check if slug already exists.
		if ( $this->repository->slug_exists( $slug ) ) {
			return new WP_Error(
				'rest_placement_slug_exists',
				__( 'A placement with this slug already exists.', 'simple-add-banners' ),
				array( 'status' => 400 )
			);
		}

		$data = array(
			'slug'              => $slug,
			'name'              => $request->get_param( 'name' ),
			'rotation_strategy' => $request->get_param( 'rotation_strategy' ) ?? 'random',
		);

		$id = $this->repository->create( $data );

		if ( ! $id ) {
			return new WP_Error(
				'rest_placement_create_failed',
				__( 'Failed to create placement.', 'simple-add-banners' ),
				array( 'status' => 500 )
			);
		}

		$placement = $this->repository->get_by_id( $id );

		$response = $this->prepare_item_for_response( $placement, $request );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $id ) ) );

		return $response;
	}

	/**
	 * Updates a single placement.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ): WP_REST_Response|WP_Error {
		$id        = (int) $request->get_param( 'id' );
		$placement = $this->repository->get_by_id( $id );

		if ( ! $placement ) {
			return new WP_Error(
				'rest_placement_not_found',
				__( 'Placement not found.', 'simple-add-banners' ),
				array( 'status' => 404 )
			);
		}

		// Check if slug already exists (excluding current placement).
		if ( $request->has_param( 'slug' ) ) {
			$slug = sanitize_title( $request->get_param( 'slug' ) );
			if ( $this->repository->slug_exists( $slug, $id ) ) {
				return new WP_Error(
					'rest_placement_slug_exists',
					__( 'A placement with this slug already exists.', 'simple-add-banners' ),
					array( 'status' => 400 )
				);
			}
		}

		$data = array();

		$fields = array(
			'slug',
			'name',
			'rotation_strategy',
		);

		foreach ( $fields as $field ) {
			if ( $request->has_param( $field ) ) {
				$data[ $field ] = $request->get_param( $field );
			}
		}

		$result = $this->repository->update( $id, $data );

		if ( ! $result ) {
			return new WP_Error(
				'rest_placement_update_failed',
				__( 'Failed to update placement.', 'simple-add-banners' ),
				array( 'status' => 500 )
			);
		}

		$placement = $this->repository->get_by_id( $id );

		return $this->prepare_item_for_response( $placement, $request );
	}

	/**
	 * Deletes a single placement.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ): WP_REST_Response|WP_Error {
		$id        = (int) $request->get_param( 'id' );
		$placement = $this->repository->get_by_id( $id );

		if ( ! $placement ) {
			return new WP_Error(
				'rest_placement_not_found',
				__( 'Placement not found.', 'simple-add-banners' ),
				array( 'status' => 404 )
			);
		}

		$result = $this->repository->delete( $id );

		if ( ! $result ) {
			return new WP_Error(
				'rest_placement_delete_failed',
				__( 'Failed to delete placement.', 'simple-add-banners' ),
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
	 * Prepares a single placement output for response.
	 *
	 * @since 1.0.0
	 *
	 * @param array           $placement Placement data.
	 * @param WP_REST_Request $request   Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $placement, $request ): WP_REST_Response {
		$data = array(
			'id'                => $placement['id'],
			'slug'              => $placement['slug'],
			'name'              => $placement['name'],
			'rotation_strategy' => $placement['rotation_strategy'],
			'created_at'        => $placement['created_at'],
			'updated_at'        => $placement['updated_at'],
		);

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
			'orderby'  => array(
				'description'       => __( 'Sort collection by placement attribute.', 'simple-add-banners' ),
				'type'              => 'string',
				'default'           => 'created_at',
				'enum'              => array( 'id', 'slug', 'name', 'rotation_strategy', 'created_at', 'updated_at' ),
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
	 * Retrieves the placement schema, conforming to JSON Schema.
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
			'title'      => 'placement',
			'type'       => 'object',
			'properties' => array(
				'id'                => array(
					'description' => __( 'Unique identifier for the placement.', 'simple-add-banners' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'slug'              => array(
					'description' => __( 'Unique slug identifier for the placement.', 'simple-add-banners' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'required'    => true,
					'minLength'   => 1,
					'maxLength'   => 100,
				),
				'name'              => array(
					'description' => __( 'Display name for the placement.', 'simple-add-banners' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'required'    => true,
				),
				'rotation_strategy' => array(
					'description' => __( 'Banner rotation strategy for this placement.', 'simple-add-banners' ),
					'type'        => 'string',
					'enum'        => array( 'random', 'weighted', 'ordered' ),
					'default'     => 'random',
					'context'     => array( 'view', 'edit' ),
				),
				'created_at'        => array(
					'description' => __( 'The date the placement was created.', 'simple-add-banners' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'updated_at'        => array(
					'description' => __( 'The date the placement was last updated.', 'simple-add-banners' ),
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
