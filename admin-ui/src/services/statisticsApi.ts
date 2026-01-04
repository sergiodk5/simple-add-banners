import { getApiClient } from './apiClient'
import type {
  BannerStatisticsSummary,
  BannerStatisticsDetail,
  PlacementStatisticsDetail,
  DateRangeFilter,
} from '@/types/statistics'

/**
 * Fetches aggregated statistics for all banners.
 */
export async function getAllBannerStats(
  filter: DateRangeFilter = {}
): Promise<BannerStatisticsSummary[]> {
  const client = getApiClient()
  const response = await client.get<BannerStatisticsSummary[]>('/statistics', {
    params: {
      start_date: filter.start_date,
      end_date: filter.end_date,
    },
  })
  return response.data
}

/**
 * Fetches detailed statistics for a specific banner.
 */
export async function getBannerStats(
  bannerId: number,
  filter: DateRangeFilter = {}
): Promise<BannerStatisticsDetail> {
  const client = getApiClient()
  const response = await client.get<BannerStatisticsDetail>(`/statistics/banners/${bannerId}`, {
    params: {
      start_date: filter.start_date,
      end_date: filter.end_date,
    },
  })
  return response.data
}

/**
 * Fetches detailed statistics for a specific placement.
 */
export async function getPlacementStats(
  placementId: number,
  filter: DateRangeFilter = {}
): Promise<PlacementStatisticsDetail> {
  const client = getApiClient()
  const response = await client.get<PlacementStatisticsDetail>(
    `/statistics/placements/${placementId}`,
    {
      params: {
        start_date: filter.start_date,
        end_date: filter.end_date,
      },
    }
  )
  return response.data
}
