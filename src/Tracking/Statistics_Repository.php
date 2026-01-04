<?php
/**
 * Statistics Repository - Database operations for tracking statistics.
 *
 * @package SimpleAddBanners\Tracking
 */

declare(strict_types=1);

namespace SimpleAddBanners\Tracking;

/**
 * Class Statistics_Repository
 *
 * Handles database operations for the statistics table.
 * Uses atomic increments for impression and click counting.
 */
class Statistics_Repository {

	/**
	 * WordPress database instance.
	 *
	 * @var \wpdb
	 */
	private \wpdb $wpdb;

	/**
	 * Table name with prefix.
	 *
	 * @var string
	 */
	private string $table_name;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb       = $wpdb;
		$this->table_name = $wpdb->prefix . 'sab_statistics';
	}

	/**
	 * Increment impressions for a banner/placement combination.
	 *
	 * Uses INSERT ON DUPLICATE KEY UPDATE for atomic increment.
	 *
	 * @param int $banner_id    The banner ID.
	 * @param int $placement_id The placement ID.
	 * @return bool True on success, false on failure.
	 */
	public function increment_impressions( int $banner_id, int $placement_id ): bool {
		$date = $this->get_current_date();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is set in constructor.
		$sql = $this->wpdb->prepare(
			"INSERT INTO {$this->table_name} (banner_id, placement_id, stat_date, impressions, clicks)
			VALUES (%d, %d, %s, 1, 0)
			ON DUPLICATE KEY UPDATE impressions = impressions + 1",
			$banner_id,
			$placement_id,
			$date
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL is prepared above.
		$result = $this->wpdb->query( $sql );

		return false !== $result;
	}

	/**
	 * Increment clicks for a banner/placement combination.
	 *
	 * Uses INSERT ON DUPLICATE KEY UPDATE for atomic increment.
	 *
	 * @param int $banner_id    The banner ID.
	 * @param int $placement_id The placement ID.
	 * @return bool True on success, false on failure.
	 */
	public function increment_clicks( int $banner_id, int $placement_id ): bool {
		$date = $this->get_current_date();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is set in constructor.
		$sql = $this->wpdb->prepare(
			"INSERT INTO {$this->table_name} (banner_id, placement_id, stat_date, impressions, clicks)
			VALUES (%d, %d, %s, 0, 1)
			ON DUPLICATE KEY UPDATE clicks = clicks + 1",
			$banner_id,
			$placement_id,
			$date
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL is prepared above.
		$result = $this->wpdb->query( $sql );

		return false !== $result;
	}

	/**
	 * Get statistics for a specific banner.
	 *
	 * @param int         $banner_id  The banner ID.
	 * @param string|null $start_date Optional start date (Y-m-d).
	 * @param string|null $end_date   Optional end date (Y-m-d).
	 * @return array Array of statistics records.
	 */
	public function get_stats( int $banner_id, ?string $start_date = null, ?string $end_date = null ): array {
		$where_clauses = array( 'banner_id = %d' );
		$values        = array( $banner_id );

		if ( $start_date ) {
			$where_clauses[] = 'stat_date >= %s';
			$values[]        = $start_date;
		}

		if ( $end_date ) {
			$where_clauses[] = 'stat_date <= %s';
			$values[]        = $end_date;
		}

		$where_sql = implode( ' AND ', $where_clauses );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$sql = $this->wpdb->prepare(
			"SELECT * FROM {$this->table_name} WHERE {$where_sql} ORDER BY stat_date DESC",
			$values
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL is prepared above.
		$results = $this->wpdb->get_results( $sql, ARRAY_A );

		return array_map( array( $this, 'format_stat' ), $results ? $results : array() );
	}

	/**
	 * Get statistics for a specific placement.
	 *
	 * @param int         $placement_id The placement ID.
	 * @param string|null $start_date   Optional start date (Y-m-d).
	 * @param string|null $end_date     Optional end date (Y-m-d).
	 * @return array Array of statistics records.
	 */
	public function get_stats_by_placement( int $placement_id, ?string $start_date = null, ?string $end_date = null ): array {
		$where_clauses = array( 'placement_id = %d' );
		$values        = array( $placement_id );

		if ( $start_date ) {
			$where_clauses[] = 'stat_date >= %s';
			$values[]        = $start_date;
		}

		if ( $end_date ) {
			$where_clauses[] = 'stat_date <= %s';
			$values[]        = $end_date;
		}

		$where_sql = implode( ' AND ', $where_clauses );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$sql = $this->wpdb->prepare(
			"SELECT * FROM {$this->table_name} WHERE {$where_sql} ORDER BY stat_date DESC",
			$values
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL is prepared above.
		$results = $this->wpdb->get_results( $sql, ARRAY_A );

		return array_map( array( $this, 'format_stat' ), $results ? $results : array() );
	}

	/**
	 * Get aggregated statistics for a banner.
	 *
	 * @param int         $banner_id  The banner ID.
	 * @param string|null $start_date Optional start date (Y-m-d).
	 * @param string|null $end_date   Optional end date (Y-m-d).
	 * @return array Aggregated statistics with totals.
	 */
	public function get_aggregated_stats( int $banner_id, ?string $start_date = null, ?string $end_date = null ): array {
		$where_clauses = array( 'banner_id = %d' );
		$values        = array( $banner_id );

		if ( $start_date ) {
			$where_clauses[] = 'stat_date >= %s';
			$values[]        = $start_date;
		}

		if ( $end_date ) {
			$where_clauses[] = 'stat_date <= %s';
			$values[]        = $end_date;
		}

		$where_sql = implode( ' AND ', $where_clauses );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$sql = $this->wpdb->prepare(
			"SELECT SUM(impressions) as total_impressions, SUM(clicks) as total_clicks
			FROM {$this->table_name} WHERE {$where_sql}",
			$values
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL is prepared above.
		$result = $this->wpdb->get_row( $sql, ARRAY_A );

		$impressions = (int) ( $result['total_impressions'] ?? 0 );
		$clicks      = (int) ( $result['total_clicks'] ?? 0 );

		return array(
			'impressions' => $impressions,
			'clicks'      => $clicks,
			'ctr'         => $impressions > 0 ? round( ( $clicks / $impressions ) * 100, 2 ) : 0,
		);
	}

	/**
	 * Get statistics for a specific banner and placement.
	 *
	 * @param int    $banner_id    The banner ID.
	 * @param int    $placement_id The placement ID.
	 * @param string $date         The date (Y-m-d).
	 * @return array|null Statistics record or null if not found.
	 */
	public function get_by_banner_placement_date( int $banner_id, int $placement_id, string $date ): ?array {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is set in constructor.
		$sql = $this->wpdb->prepare(
			"SELECT * FROM {$this->table_name}
			WHERE banner_id = %d AND placement_id = %d AND stat_date = %s",
			$banner_id,
			$placement_id,
			$date
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL is prepared above.
		$result = $this->wpdb->get_row( $sql, ARRAY_A );

		return $result ? $this->format_stat( $result ) : null;
	}

	/**
	 * Format a statistics row for output.
	 *
	 * @param array $row Database row.
	 * @return array Formatted statistics data.
	 */
	private function format_stat( array $row ): array {
		$impressions = (int) $row['impressions'];
		$clicks      = (int) $row['clicks'];

		return array(
			'id'           => (int) $row['id'],
			'banner_id'    => (int) $row['banner_id'],
			'placement_id' => (int) $row['placement_id'],
			'stat_date'    => $row['stat_date'],
			'impressions'  => $impressions,
			'clicks'       => $clicks,
			'ctr'          => $impressions > 0 ? round( ( $clicks / $impressions ) * 100, 2 ) : 0,
		);
	}

	/**
	 * Get aggregated statistics for all banners.
	 *
	 * Returns totals grouped by banner with banner title.
	 *
	 * @param string|null $start_date Optional start date (Y-m-d).
	 * @param string|null $end_date   Optional end date (Y-m-d).
	 * @return array Array of aggregated statistics per banner.
	 */
	public function get_all_banner_stats( ?string $start_date = null, ?string $end_date = null ): array {
		$banners_table = $this->wpdb->prefix . 'sab_banners';
		$where_clauses = array();
		$values        = array();

		if ( $start_date ) {
			$where_clauses[] = 's.stat_date >= %s';
			$values[]        = $start_date;
		}

		if ( $end_date ) {
			$where_clauses[] = 's.stat_date <= %s';
			$values[]        = $end_date;
		}

		$where_sql = ! empty( $where_clauses ) ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.NotPrepared
		$sql = "SELECT
				b.id as banner_id,
				b.title as banner_title,
				b.status as banner_status,
				COALESCE(SUM(s.impressions), 0) as impressions,
				COALESCE(SUM(s.clicks), 0) as clicks
			FROM {$banners_table} b
			LEFT JOIN {$this->table_name} s ON b.id = s.banner_id
			{$where_sql}
			GROUP BY b.id, b.title, b.status
			ORDER BY impressions DESC";

		if ( ! empty( $values ) ) {
			$sql = $this->wpdb->prepare( $sql, $values );
		}

		$results = $this->wpdb->get_results( $sql, ARRAY_A );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.NotPrepared

		return array_map(
			function ( $row ) {
				$impressions = (int) $row['impressions'];
				$clicks      = (int) $row['clicks'];

				return array(
					'banner_id'     => (int) $row['banner_id'],
					'banner_title'  => $row['banner_title'],
					'banner_status' => $row['banner_status'],
					'impressions'   => $impressions,
					'clicks'        => $clicks,
					'ctr'           => $impressions > 0 ? round( ( $clicks / $impressions ) * 100, 2 ) : 0,
				);
			},
			$results ? $results : array()
		);
	}

	/**
	 * Get the current date in UTC.
	 *
	 * @return string The date in Y-m-d format.
	 */
	private function get_current_date(): string {
		return gmdate( 'Y-m-d' );
	}
}
