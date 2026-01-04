import { getApiClient } from './apiClient'
import type { Banner } from '@/types/banner'

/**
 * Banner with position in placement.
 */
export interface AssignedBanner extends Banner {
  position: number
}

/**
 * Fetches banners assigned to a placement.
 */
export async function getBannersForPlacement(placementId: number): Promise<AssignedBanner[]> {
  const client = getApiClient()
  const response = await client.get<AssignedBanner[]>(`/placements/${placementId}/banners`)
  return response.data
}

/**
 * Syncs banners for a placement (replaces all assignments).
 */
export async function syncBannersForPlacement(
  placementId: number,
  bannerIds: number[],
): Promise<AssignedBanner[]> {
  const client = getApiClient()
  const response = await client.put<AssignedBanner[]>(`/placements/${placementId}/banners`, {
    banner_ids: bannerIds,
  })
  return response.data
}

/**
 * Adds a single banner to a placement.
 */
export async function addBannerToPlacement(
  placementId: number,
  bannerId: number,
  position: number = 0,
): Promise<AssignedBanner[]> {
  const client = getApiClient()
  const response = await client.post<AssignedBanner[]>(`/placements/${placementId}/banners`, {
    banner_id: bannerId,
    position,
  })
  return response.data
}

/**
 * Removes a banner from a placement.
 */
export async function removeBannerFromPlacement(
  placementId: number,
  bannerId: number,
): Promise<void> {
  const client = getApiClient()
  await client.delete(`/placements/${placementId}/banners/${bannerId}`)
}
