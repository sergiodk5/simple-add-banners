import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import type { AxiosInstance } from 'axios'
import { setApiClient } from '../services/apiClient'
import {
  getBanners,
  getBanner,
  createBanner,
  updateBanner,
  deleteBanner,
} from '../services/bannerApi'
import type { Banner, BannerPayload } from '@/types/banner'

vi.stubGlobal('sabAdmin', {
  apiUrl: '/wp-json/sab/v1',
  nonce: 'test-nonce-123',
  adminUrl: '/wp-admin/',
})

const mockBanner: Banner = {
  id: 1,
  title: 'Test Banner',
  desktop_image_id: null,
  mobile_image_id: null,
  desktop_url: 'https://example.com',
  mobile_url: null,
  start_date: null,
  end_date: null,
  status: 'active',
  weight: 1,
  created_at: '2024-01-01T00:00:00',
  updated_at: '2024-01-01T00:00:00',
}

describe('bannerApi', () => {
  let mockAxiosInstance: AxiosInstance

  beforeEach(() => {
    mockAxiosInstance = {
      get: vi.fn(),
      post: vi.fn(),
      put: vi.fn(),
      delete: vi.fn(),
      interceptors: {
        request: { use: vi.fn() },
        response: { use: vi.fn() },
      },
    } as unknown as AxiosInstance

    setApiClient(mockAxiosInstance)
  })

  afterEach(() => {
    vi.clearAllMocks()
    setApiClient(null)
  })

  describe('getBanners', () => {
    it('fetches banners successfully', async () => {
      vi.mocked(mockAxiosInstance.get).mockResolvedValue({
        data: [mockBanner],
        headers: {
          'x-wp-total': '10',
          'x-wp-totalpages': '2',
        },
      })

      const result = await getBanners()

      expect(mockAxiosInstance.get).toHaveBeenCalledWith('/banners', {
        params: {
          page: undefined,
          per_page: undefined,
          status: undefined,
          orderby: undefined,
          order: undefined,
        },
      })
      expect(result.data).toEqual([mockBanner])
      expect(result.total).toBe(10)
      expect(result.totalPages).toBe(2)
    })

    it('passes query parameters correctly', async () => {
      vi.mocked(mockAxiosInstance.get).mockResolvedValue({
        data: [],
        headers: {
          'x-wp-total': '0',
          'x-wp-totalpages': '0',
        },
      })

      await getBanners({
        page: 2,
        per_page: 20,
        status: 'active',
        orderby: 'title',
        order: 'ASC',
      })

      expect(mockAxiosInstance.get).toHaveBeenCalledWith('/banners', {
        params: {
          page: 2,
          per_page: 20,
          status: 'active',
          orderby: 'title',
          order: 'ASC',
        },
      })
    })

    it('handles missing pagination headers', async () => {
      vi.mocked(mockAxiosInstance.get).mockResolvedValue({
        data: [mockBanner],
        headers: {},
      })

      const result = await getBanners()

      expect(result.total).toBe(0)
      expect(result.totalPages).toBe(0)
    })
  })

  describe('getBanner', () => {
    it('fetches a single banner', async () => {
      vi.mocked(mockAxiosInstance.get).mockResolvedValue({
        data: mockBanner,
      })

      const result = await getBanner(1)

      expect(mockAxiosInstance.get).toHaveBeenCalledWith('/banners/1')
      expect(result).toEqual(mockBanner)
    })
  })

  describe('createBanner', () => {
    it('creates a new banner', async () => {
      const payload: BannerPayload = {
        title: 'New Banner',
        desktop_url: 'https://example.com',
        mobile_url: null,
        desktop_image_id: null,
        mobile_image_id: null,
        start_date: null,
        end_date: null,
        status: 'active',
        weight: 1,
      }

      vi.mocked(mockAxiosInstance.post).mockResolvedValue({
        data: { ...mockBanner, ...payload },
      })

      const result = await createBanner(payload)

      expect(mockAxiosInstance.post).toHaveBeenCalledWith('/banners', payload)
      expect(result.title).toBe('New Banner')
    })
  })

  describe('updateBanner', () => {
    it('updates an existing banner', async () => {
      const payload = { title: 'Updated Banner' }

      vi.mocked(mockAxiosInstance.put).mockResolvedValue({
        data: { ...mockBanner, ...payload },
      })

      const result = await updateBanner(1, payload)

      expect(mockAxiosInstance.put).toHaveBeenCalledWith('/banners/1', payload)
      expect(result.title).toBe('Updated Banner')
    })
  })

  describe('deleteBanner', () => {
    it('deletes a banner', async () => {
      vi.mocked(mockAxiosInstance.delete).mockResolvedValue({})

      await deleteBanner(1)

      expect(mockAxiosInstance.delete).toHaveBeenCalledWith('/banners/1')
    })
  })
})
