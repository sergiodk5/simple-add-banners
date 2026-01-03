import axios, { type AxiosInstance, type AxiosError } from 'axios'
import type { Banner, BannerPayload, BannerListParams } from '@/types/banner'

/**
 * WordPress admin configuration injected via wp_localize_script.
 */
declare global {
  interface Window {
    sabAdmin: {
      apiUrl: string
      nonce: string
      adminUrl: string
    }
  }
}

/**
 * API response with pagination headers.
 */
export interface PaginatedResponse<T> {
  data: T[]
  total: number
  totalPages: number
}

/**
 * Creates and configures the axios instance.
 */
export function createApiClient(): AxiosInstance {
  const client = axios.create({
    baseURL: window.sabAdmin?.apiUrl || '/wp-json/sab/v1',
    headers: {
      'Content-Type': 'application/json',
    },
  })

  // Add nonce to all requests
  client.interceptors.request.use((config) => {
    const nonce = window.sabAdmin?.nonce || ''
    if (nonce) {
      config.headers['X-WP-Nonce'] = nonce
    }
    return config
  })

  // Transform error responses
  client.interceptors.response.use(
    (response) => response,
    (error: AxiosError<{ message?: string }>) => {
      const message = error.response?.data?.message || error.message || 'An error occurred'
      return Promise.reject(new Error(message))
    },
  )

  return client
}

// Default API client instance
let apiClient: AxiosInstance | null = null

/**
 * Gets or creates the API client instance.
 */
export function getApiClient(): AxiosInstance {
  if (!apiClient) {
    apiClient = createApiClient()
  }
  return apiClient
}

/**
 * Sets a custom API client (useful for testing).
 */
export function setApiClient(client: AxiosInstance | null): void {
  apiClient = client
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
