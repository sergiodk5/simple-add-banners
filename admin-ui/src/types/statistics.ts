/**
 * Statistics TypeScript types.
 *
 * @package SimpleAddBanners
 */

/**
 * Daily statistics record.
 */
export interface DailyStatistics {
  id: number;
  banner_id: number;
  placement_id: number;
  stat_date: string;
  impressions: number;
  clicks: number;
  ctr: number;
}

/**
 * Aggregated totals for statistics.
 */
export interface StatisticsTotals {
  impressions: number;
  clicks: number;
  ctr: number;
}

/**
 * Banner statistics summary (for dashboard overview).
 */
export interface BannerStatisticsSummary {
  banner_id: number;
  banner_title: string;
  banner_status: string;
  impressions: number;
  clicks: number;
  ctr: number;
}

/**
 * Detailed statistics for a specific banner.
 */
export interface BannerStatisticsDetail {
  banner_id: number;
  totals: StatisticsTotals;
  daily: DailyStatistics[];
  start_date: string | null;
  end_date: string | null;
}

/**
 * Detailed statistics for a specific placement.
 */
export interface PlacementStatisticsDetail {
  placement_id: number;
  totals: StatisticsTotals;
  daily: DailyStatistics[];
  start_date: string | null;
  end_date: string | null;
}

/**
 * Date range filter parameters.
 */
export interface DateRangeFilter {
  start_date?: string;
  end_date?: string;
}
