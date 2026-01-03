<?php
/**
 * Banner repository.
 *
 * Handles database operations for banners.
 *
 * @package SimpleAddBanners\Repository
 * @since   1.0.0
 */

declare(strict_types=1);

namespace SimpleAddBanners\Repository;

/**
 * Banner repository class.
 *
 * Provides CRUD operations for the banners table.
 *
 * @since 1.0.0
 */
class Banner_Repository {

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
		$this->table_name = $wpdb->prefix . 'sab_banners';
	}

	/**
	 * Gets all banners.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Optional. Query arguments.
	 * @return array List of banner objects.
	 */
	public function get_all( array $args = array() ): array {
		$defaults = array(
			'status'   => '',
			'order_by' => 'created_at',
			'order'    => 'DESC',
			'limit'    => 0,
			'offset'   => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$sql = "SELECT * FROM {$this->table_name}";

		$where_clauses = array();
		$values        = array();

		if ( ! empty( $args['status'] ) ) {
			$where_clauses[] = 'status = %s';
			$values[]        = $args['status'];
		}

		if ( ! empty( $where_clauses ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where_clauses );
		}

		$allowed_order_by = array( 'id', 'title', 'status', 'weight', 'start_date', 'end_date', 'created_at', 'updated_at' );
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

		return array_map( array( $this, 'format_banner' ), $results ? $results : array() );
	}

	/**
	 * Gets a single banner by ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Banner ID.
	 * @return array|null Banner data or null if not found.
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

		return $result ? $this->format_banner( $result ) : null;
	}

	/**
	 * Creates a new banner.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Banner data.
	 * @return int|false Inserted ID or false on failure.
	 */
	public function create( array $data ): int|false {
		$defaults = array(
			'title'            => '',
			'desktop_image_id' => null,
			'mobile_image_id'  => null,
			'desktop_url'      => '',
			'mobile_url'       => null,
			'start_date'       => null,
			'end_date'         => null,
			'status'           => 'active',
			'weight'           => 1,
		);

		$data = wp_parse_args( $data, $defaults );

		$insert_data = array(
			'title'            => sanitize_text_field( $data['title'] ),
			'desktop_image_id' => $data['desktop_image_id'] ? absint( $data['desktop_image_id'] ) : null,
			'mobile_image_id'  => $data['mobile_image_id'] ? absint( $data['mobile_image_id'] ) : null,
			'desktop_url'      => esc_url_raw( $data['desktop_url'] ),
			'mobile_url'       => $data['mobile_url'] ? esc_url_raw( $data['mobile_url'] ) : null,
			'start_date'       => $this->sanitize_datetime( $data['start_date'] ),
			'end_date'         => $this->sanitize_datetime( $data['end_date'] ),
			'status'           => $this->sanitize_status( $data['status'] ),
			'weight'           => max( 1, absint( $data['weight'] ) ),
		);

		$formats = array( '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d' );

		// Handle null values for format.
		foreach ( $insert_data as $key => $value ) {
			if ( null === $value ) {
				$insert_data[ $key ] = null;
			}
		}

		$result = $this->wpdb->insert( $this->table_name, $insert_data, $formats );

		return $result ? (int) $this->wpdb->insert_id : false;
	}

	/**
	 * Updates an existing banner.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $id   Banner ID.
	 * @param array $data Banner data to update.
	 * @return bool True on success, false on failure.
	 */
	public function update( int $id, array $data ): bool {
		$existing = $this->get_by_id( $id );

		if ( ! $existing ) {
			return false;
		}

		$update_data = array();
		$formats     = array();

		if ( isset( $data['title'] ) ) {
			$update_data['title'] = sanitize_text_field( $data['title'] );
			$formats[]            = '%s';
		}

		if ( array_key_exists( 'desktop_image_id', $data ) ) {
			$update_data['desktop_image_id'] = $data['desktop_image_id'] ? absint( $data['desktop_image_id'] ) : null;
			$formats[]                       = '%d';
		}

		if ( array_key_exists( 'mobile_image_id', $data ) ) {
			$update_data['mobile_image_id'] = $data['mobile_image_id'] ? absint( $data['mobile_image_id'] ) : null;
			$formats[]                      = '%d';
		}

		if ( isset( $data['desktop_url'] ) ) {
			$update_data['desktop_url'] = esc_url_raw( $data['desktop_url'] );
			$formats[]                  = '%s';
		}

		if ( array_key_exists( 'mobile_url', $data ) ) {
			$update_data['mobile_url'] = $data['mobile_url'] ? esc_url_raw( $data['mobile_url'] ) : null;
			$formats[]                 = '%s';
		}

		if ( array_key_exists( 'start_date', $data ) ) {
			$update_data['start_date'] = $this->sanitize_datetime( $data['start_date'] );
			$formats[]                 = '%s';
		}

		if ( array_key_exists( 'end_date', $data ) ) {
			$update_data['end_date'] = $this->sanitize_datetime( $data['end_date'] );
			$formats[]               = '%s';
		}

		if ( isset( $data['status'] ) ) {
			$update_data['status'] = $this->sanitize_status( $data['status'] );
			$formats[]             = '%s';
		}

		if ( isset( $data['weight'] ) ) {
			$update_data['weight'] = max( 1, absint( $data['weight'] ) );
			$formats[]             = '%d';
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
	 * Deletes a banner.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Banner ID.
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
	 * Counts total banners.
	 *
	 * @since 1.0.0
	 *
	 * @param string $status Optional. Filter by status.
	 * @return int Total count.
	 */
	public function count( string $status = '' ): int {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name is set in constructor.
		$sql = "SELECT COUNT(*) FROM {$this->table_name}";

		if ( ! empty( $status ) ) {
			$sql = $this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_name} WHERE status = %s",
				$status
			);
		}

		return (int) $this->wpdb->get_var( $sql );
		// phpcs:enable
	}

	/**
	 * Formats a banner row for output.
	 *
	 * @since 1.0.0
	 *
	 * @param array $row Database row.
	 * @return array Formatted banner data.
	 */
	private function format_banner( array $row ): array {
		return array(
			'id'               => (int) $row['id'],
			'title'            => $row['title'],
			'desktop_image_id' => $row['desktop_image_id'] ? (int) $row['desktop_image_id'] : null,
			'mobile_image_id'  => $row['mobile_image_id'] ? (int) $row['mobile_image_id'] : null,
			'desktop_url'      => $row['desktop_url'],
			'mobile_url'       => $row['mobile_url'],
			'start_date'       => $row['start_date'],
			'end_date'         => $row['end_date'],
			'status'           => $row['status'],
			'weight'           => (int) $row['weight'],
			'created_at'       => $row['created_at'],
			'updated_at'       => $row['updated_at'],
		);
	}

	/**
	 * Sanitizes a datetime value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Datetime value.
	 * @return string|null Sanitized datetime or null.
	 */
	private function sanitize_datetime( mixed $value ): ?string {
		if ( empty( $value ) ) {
			return null;
		}

		$timestamp = strtotime( $value );

		if ( false === $timestamp ) {
			return null;
		}

		return gmdate( 'Y-m-d H:i:s', $timestamp );
	}

	/**
	 * Sanitizes banner status.
	 *
	 * @since 1.0.0
	 *
	 * @param string $status Status value.
	 * @return string Sanitized status.
	 */
	private function sanitize_status( string $status ): string {
		$allowed = array( 'active', 'paused', 'scheduled' );

		return in_array( $status, $allowed, true ) ? $status : 'active';
	}
}
