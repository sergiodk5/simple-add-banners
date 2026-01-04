<?php
/**
 * Banner placement repository.
 *
 * Handles database operations for banner-placement assignments.
 *
 * @package SimpleAddBanners\Repository
 * @since   1.0.0
 */

declare(strict_types=1);

namespace SimpleAddBanners\Repository;

/**
 * Banner placement repository class.
 *
 * Provides operations for the banner_placement pivot table.
 *
 * @since 1.0.0
 */
class Banner_Placement_Repository {

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
	 * Banners table name with prefix.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $banners_table;

	/**
	 * Placements table name with prefix.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $placements_table;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb             = $wpdb;
		$this->table_name       = $wpdb->prefix . 'sab_banner_placement';
		$this->banners_table    = $wpdb->prefix . 'sab_banners';
		$this->placements_table = $wpdb->prefix . 'sab_placements';
	}

	/**
	 * Gets all banners assigned to a placement.
	 *
	 * @since 1.0.0
	 *
	 * @param int $placement_id Placement ID.
	 * @return array List of banners with assignment data.
	 */
	public function get_banners_for_placement( int $placement_id ): array {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table names are set in constructor.
		$results = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT b.*, bp.position
				FROM {$this->banners_table} b
				INNER JOIN {$this->table_name} bp ON b.id = bp.banner_id
				WHERE bp.placement_id = %d
				ORDER BY bp.position ASC, b.id ASC",
				$placement_id
			),
			ARRAY_A
		);
		// phpcs:enable

		return array_map( array( $this, 'format_banner_with_position' ), $results ? $results : array() );
	}

	/**
	 * Gets all placements assigned to a banner.
	 *
	 * @since 1.0.0
	 *
	 * @param int $banner_id Banner ID.
	 * @return array List of placements with assignment data.
	 */
	public function get_placements_for_banner( int $banner_id ): array {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table names are set in constructor.
		$results = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT p.*, bp.position
				FROM {$this->placements_table} p
				INNER JOIN {$this->table_name} bp ON p.id = bp.placement_id
				WHERE bp.banner_id = %d
				ORDER BY p.name ASC",
				$banner_id
			),
			ARRAY_A
		);
		// phpcs:enable

		return array_map( array( $this, 'format_placement_with_position' ), $results ? $results : array() );
	}

	/**
	 * Gets banner IDs assigned to a placement.
	 *
	 * @since 1.0.0
	 *
	 * @param int $placement_id Placement ID.
	 * @return array List of banner IDs.
	 */
	public function get_banner_ids_for_placement( int $placement_id ): array {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name is set in constructor.
		$results = $this->wpdb->get_col(
			$this->wpdb->prepare(
				"SELECT banner_id FROM {$this->table_name} WHERE placement_id = %d ORDER BY position ASC",
				$placement_id
			)
		);
		// phpcs:enable

		return array_map( 'intval', $results ? $results : array() );
	}

	/**
	 * Syncs banners for a placement (replaces all assignments).
	 *
	 * @since 1.0.0
	 *
	 * @param int   $placement_id Placement ID.
	 * @param array $banner_ids   List of banner IDs to assign.
	 * @return bool True on success, false on failure.
	 */
	public function sync_banners( int $placement_id, array $banner_ids ): bool {
		// Delete existing assignments.
		$this->wpdb->delete(
			$this->table_name,
			array( 'placement_id' => $placement_id ),
			array( '%d' )
		);

		// Insert new assignments.
		$position = 0;
		foreach ( $banner_ids as $banner_id ) {
			$banner_id = absint( $banner_id );
			if ( $banner_id > 0 ) {
				$result = $this->attach( $placement_id, $banner_id, $position );
				if ( false === $result ) {
					return false;
				}
				++$position;
			}
		}

		return true;
	}

	/**
	 * Attaches a banner to a placement.
	 *
	 * @since 1.0.0
	 *
	 * @param int $placement_id Placement ID.
	 * @param int $banner_id    Banner ID.
	 * @param int $position     Position in rotation.
	 * @return int|false Insert ID or false on failure.
	 */
	public function attach( int $placement_id, int $banner_id, int $position = 0 ): int|false {
		$insert_data = array(
			'placement_id' => $placement_id,
			'banner_id'    => $banner_id,
			'position'     => max( 0, $position ),
		);

		$formats = array( '%d', '%d', '%d' );

		$result = $this->wpdb->insert( $this->table_name, $insert_data, $formats );

		return $result ? (int) $this->wpdb->insert_id : false;
	}

	/**
	 * Detaches a banner from a placement.
	 *
	 * @since 1.0.0
	 *
	 * @param int $placement_id Placement ID.
	 * @param int $banner_id    Banner ID.
	 * @return bool True on success, false on failure.
	 */
	public function detach( int $placement_id, int $banner_id ): bool {
		$result = $this->wpdb->delete(
			$this->table_name,
			array(
				'placement_id' => $placement_id,
				'banner_id'    => $banner_id,
			),
			array( '%d', '%d' )
		);

		return false !== $result && $result > 0;
	}

	/**
	 * Updates the position of a banner in a placement.
	 *
	 * @since 1.0.0
	 *
	 * @param int $placement_id Placement ID.
	 * @param int $banner_id    Banner ID.
	 * @param int $position     New position.
	 * @return bool True on success, false on failure.
	 */
	public function update_position( int $placement_id, int $banner_id, int $position ): bool {
		$result = $this->wpdb->update(
			$this->table_name,
			array( 'position' => max( 0, $position ) ),
			array(
				'placement_id' => $placement_id,
				'banner_id'    => $banner_id,
			),
			array( '%d' ),
			array( '%d', '%d' )
		);

		return false !== $result;
	}

	/**
	 * Checks if a banner is assigned to a placement.
	 *
	 * @since 1.0.0
	 *
	 * @param int $placement_id Placement ID.
	 * @param int $banner_id    Banner ID.
	 * @return bool True if assigned, false otherwise.
	 */
	public function is_attached( int $placement_id, int $banner_id ): bool {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name is set in constructor.
		$count = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_name} WHERE placement_id = %d AND banner_id = %d",
				$placement_id,
				$banner_id
			)
		);
		// phpcs:enable

		return (int) $count > 0;
	}

	/**
	 * Counts banners assigned to a placement.
	 *
	 * @since 1.0.0
	 *
	 * @param int $placement_id Placement ID.
	 * @return int Number of assigned banners.
	 */
	public function count_banners( int $placement_id ): int {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name is set in constructor.
		$count = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_name} WHERE placement_id = %d",
				$placement_id
			)
		);
		// phpcs:enable

		return (int) $count;
	}

	/**
	 * Deletes all assignments for a placement.
	 *
	 * @since 1.0.0
	 *
	 * @param int $placement_id Placement ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_by_placement( int $placement_id ): bool {
		$result = $this->wpdb->delete(
			$this->table_name,
			array( 'placement_id' => $placement_id ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Deletes all assignments for a banner.
	 *
	 * @since 1.0.0
	 *
	 * @param int $banner_id Banner ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_by_banner( int $banner_id ): bool {
		$result = $this->wpdb->delete(
			$this->table_name,
			array( 'banner_id' => $banner_id ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Formats a banner row with position data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $row Database row.
	 * @return array Formatted banner data.
	 */
	private function format_banner_with_position( array $row ): array {
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
			'position'         => (int) $row['position'],
			'created_at'       => $row['created_at'],
			'updated_at'       => $row['updated_at'],
		);
	}

	/**
	 * Formats a placement row with position data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $row Database row.
	 * @return array Formatted placement data.
	 */
	private function format_placement_with_position( array $row ): array {
		return array(
			'id'                => (int) $row['id'],
			'slug'              => $row['slug'],
			'name'              => $row['name'],
			'rotation_strategy' => $row['rotation_strategy'],
			'position'          => (int) $row['position'],
			'created_at'        => $row['created_at'],
			'updated_at'        => $row['updated_at'],
		);
	}
}
