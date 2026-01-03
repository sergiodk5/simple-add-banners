import { getApiClient } from './apiClient'
import type { Banner, BannerPayload, BannerListParams } from '@/types/banner'

export interface PaginatedResponse<T> {
  data: T[]
  total: number
  totalPages: number
}

/**
 * Fetches a paginated list of banners.
 */
export async function getBanners(params: BannerListParams = {}): Promise<PaginatedResponse<Banner>> {
  const client = getApiClient()

  const response = await client.get<Banner[]>('/banners', {
    params: {
      page: params.page,
      per_page: params.per_page,
      status: params.status,
      orderby: params.orderby,
      order: params.order,
    },
  })

  const total = parseInt(response.headers['x-wp-total'] || '0', 10)
  const totalPages = parseInt(response.headers['x-wp-totalpages'] || '0', 10)

  return {
    data: response.data,
    total,
    totalPages,
  }
}

/**
 * Fetches a single banner by ID.
 */
export async function getBanner(id: number): Promise<Banner> {
  const client = getApiClient()
  const response = await client.get<Banner>(`/banners/${id}`)
  return response.data
}

/**
 * Creates a new banner.
 */
export async function createBanner(payload: BannerPayload): Promise<Banner> {
  const client = getApiClient()
  const response = await client.post<Banner>('/banners', payload)
  return response.data
}

/**
 * Updates an existing banner.
 */
export async function updateBanner(id: number, payload: Partial<BannerPayload>): Promise<Banner> {
  const client = getApiClient()
  const response = await client.put<Banner>(`/banners/${id}`, payload)
  return response.data
}

/**
 * Deletes a banner.
 */
export async function deleteBanner(id: number): Promise<void> {
  const client = getApiClient()
  await client.delete(`/banners/${id}`)
}
