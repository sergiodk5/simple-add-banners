/**
 * Banner status type.
 */
export type BannerStatus = 'active' | 'paused' | 'scheduled'

/**
 * Banner entity interface.
 */
export interface Banner {
  id: number
  title: string
  desktop_image_id: number | null
  mobile_image_id: number | null
  desktop_image_url?: string
  mobile_image_url?: string
  desktop_url: string
  mobile_url: string | null
  start_date: string | null
  end_date: string | null
  status: BannerStatus
  weight: number
  created_at: string
  updated_at: string
}

/**
 * Banner create/update payload.
 */
export interface BannerPayload {
  title: string
  desktop_image_id?: number | null
  mobile_image_id?: number | null
  desktop_url: string
  mobile_url?: string | null
  start_date?: string | null
  end_date?: string | null
  status?: BannerStatus
  weight?: number
}

/**
 * Banner list query parameters.
 */
export interface BannerListParams {
  page?: number
  per_page?: number
  status?: BannerStatus | ''
  orderby?: string
  order?: 'ASC' | 'DESC'
}
