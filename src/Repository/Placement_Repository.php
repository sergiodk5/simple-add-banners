<?php
/**
 * Placement repository.
 *
 * Handles database operations for placements.
 *
 * @package SimpleAddBanners\Repository
 * @since   1.0.0
 */

declare(strict_types=1);

namespace SimpleAddBanners\Repository;

/**
 * Placement repository class.
 *
 * Provides CRUD operations for the placements table.
 *
 * @since 1.0.0
 */
class Placement_Repository {



	/**
	 * WordPress database instance.
	 *
	 * @since 1.0.0
	 * @var \wpdb
	 */
	private \wpdb $wpdb;

	/**
	 * Table name with prefix.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $table_name;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb       = $wpdb;
		$this->table_name = $wpdb->prefix . 'sab_placements';
	}

	/**
	 * Gets all placements.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Optional. Query arguments.
	 * @return array List of placement objects.
	 */
	public function get_all( array $args = array() ): array {
		$defaults = array(
			'order_by' => 'created_at',
			'order'    => 'DESC',
			'limit'    => 0,
			'offset'   => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$sql = "SELECT * FROM {$this->table_name}";

		$values = array();

		$allowed_order_by = array( 'id', 'slug', 'name', 'rotation_strategy', 'created_at', 'updated_at' );
		$order_by         = in_array( $args['order_by'], $allowed_order_by, true ) ? $args['order_by'] : 'created_at';
		$order            = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$sql .= " ORDER BY {$order_by} {$order}";

		if ( $args['limit'] > 0 ) {
			$sql     .= ' LIMIT %d';
			$values[] = $args['limit'];

			if ( $args['offset'] > 0 ) {
				$sql     .= ' OFFSET %d';
				$values[] = $args['offset'];
			}
		}

		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Dynamic SQL is safely constructed above.
			$sql = $this->wpdb->prepare( $sql, $values );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL is prepared above when values exist.
		$results = $this->wpdb->get_results( $sql, ARRAY_A );

		return array_map( array( $this, 'format_placement' ), $results ? $results : array() );
	}

	/**
	 * Gets a single placement by ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Placement ID.
	 * @return array|null Placement data or null if not found.
	 */
	public function get_by_id( int $id ): ?array {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name is set in constructor.
		$result = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE id = %d",
				$id
			),
			ARRAY_A
		);
		// phpcs:enable

		return $result ? $this->format_placement( $result ) : null;
	}

	/**
	 * Gets a single placement by slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Placement slug.
	 * @return array|null Placement data or null if not found.
	 */
	public function get_by_slug( string $slug ): ?array {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name is set in constructor.
		$result = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE slug = %s",
				$slug
			),
			ARRAY_A
		);
		// phpcs:enable

		return $result ? $this->format_placement( $result ) : null;
	}

	/**
	 * Creates a new placement.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Placement data.
	 * @return int|false Inserted ID or false on failure.
	 */
	public function create( array $data ): int|false {
		$defaults = array(
			'slug'              => '',
			'name'              => '',
			'rotation_strategy' => 'random',
		);

		$data = wp_parse_args( $data, $defaults );

		$insert_data = array(
			'slug'              => $this->sanitize_slug( $data['slug'] ),
			'name'              => sanitize_text_field( $data['name'] ),
			'rotation_strategy' => $this->sanitize_rotation_strategy( $data['rotation_strategy'] ),
		);

		$formats = array( '%s', '%s', '%s' );

		$result = $this->wpdb->insert( $this->table_name, $insert_data, $formats );

		return $result ? (int) $this->wpdb->insert_id : false;
	}

	/**
	 * Updates an existing placement.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $id   Placement ID.
	 * @param array $data Placement data to update.
	 * @return bool True on success, false on failure.
	 */
	public function update( int $id, array $data ): bool {
		$existing = $this->get_by_id( $id );

		if ( ! $existing ) {
			return false;
		}

		$update_data = array();
		$formats     = array();

		if ( isset( $data['slug'] ) ) {
			$update_data['slug'] = $this->sanitize_slug( $data['slug'] );
			$formats[]           = '%s';
		}

		if ( isset( $data['name'] ) ) {
			$update_data['name'] = sanitize_text_field( $data['name'] );
			$formats[]           = '%s';
		}

		if ( isset( $data['rotation_strategy'] ) ) {
			$update_data['rotation_strategy'] = $this->sanitize_rotation_strategy( $data['rotation_strategy'] );
			$formats[]                        = '%s';
		}

		if ( empty( $update_data ) ) {
			return true;
		}

		$result = $this->wpdb->update(
			$this->table_name,
			$update_data,
			array( 'id' => $id ),
			$formats,
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Deletes a placement.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Placement ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete( int $id ): bool {
		$result = $this->wpdb->delete(
			$this->table_name,
			array( 'id' => $id ),
			array( '%d' )
		);

		return false !== $result && $result > 0;
	}

	/**
	 * Counts total placements.
	 *
	 * @since 1.0.0
	 *
	 * @return int Total count.
	 */
	public function count(): int {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is set in constructor.
		return (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
	}

	/**
	 * Checks if a slug already exists.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $slug        Slug to check.
	 * @param int|null $exclude_id  Optional. Placement ID to exclude from check.
	 * @return bool True if slug exists, false otherwise.
	 */
	public function slug_exists( string $slug, ?int $exclude_id = null ): bool {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name is set in constructor.
		if ( $exclude_id ) {
			$count = $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table_name} WHERE slug = %s AND id != %d",
					$slug,
					$exclude_id
				)
			);
		} else {
			$count = $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table_name} WHERE slug = %s",
					$slug
				)
			);
		}
		// phpcs:enable

		return (int) $count > 0;
	}

	/**
	 * Formats a placement row for output.
	 *
	 * @since 1.0.0
	 *
	 * @param array $row Database row.
	 * @return array Formatted placement data.
	 */
	private function format_placement( array $row ): array {
		return array(
			'id'                => (int) $row['id'],
			'slug'              => $row['slug'],
			'name'              => $row['name'],
			'rotation_strategy' => $row['rotation_strategy'],
			'created_at'        => $row['created_at'],
			'updated_at'        => $row['updated_at'],
		);
	}

	/**
	 * Sanitizes a slug value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Slug value.
	 * @return string Sanitized slug.
	 */
	private function sanitize_slug( string $slug ): string {
		return sanitize_title( $slug );
	}

	/**
	 * Sanitizes rotation strategy.
	 *
	 * @since 1.0.0
	 *
	 * @param string $strategy Strategy value.
	 * @return string Sanitized strategy.
	 */
	private function sanitize_rotation_strategy( string $strategy ): string {
		$allowed = array( 'random', 'weighted', 'ordered' );

		return in_array( $strategy, $allowed, true ) ? $strategy : 'random';
	}
}
