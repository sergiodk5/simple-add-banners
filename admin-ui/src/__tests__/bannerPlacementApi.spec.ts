import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import type { AxiosInstance } from 'axios'
import { setApiClient } from '../services/apiClient'
import {
  getBannersForPlacement,
  syncBannersForPlacement,
  addBannerToPlacement,
  removeBannerFromPlacement,
} from '../services/bannerPlacementApi'
import type { AssignedBanner } from '../services/bannerPlacementApi'

vi.stubGlobal('sabAdmin', {
  apiUrl: '/wp-json/sab/v1',
  nonce: 'test-nonce-123',
  adminUrl: '/wp-admin/',
})

const mockAssignedBanner: AssignedBanner = {
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
  position: 0,
  created_at: '2024-01-01T00:00:00',
  updated_at: '2024-01-01T00:00:00',
}

describe('bannerPlacementApi', () => {
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

  describe('getBannersForPlacement', () => {
    it('fetches banners assigned to a placement', async () => {
      vi.mocked(mockAxiosInstance.get).mockResolvedValue({
        data: [mockAssignedBanner],
      })

      const result = await getBannersForPlacement(1)

      expect(mockAxiosInstance.get).toHaveBeenCalledWith('/placements/1/banners')
      expect(result).toEqual([mockAssignedBanner])
    })

    it('returns empty array when no banners assigned', async () => {
      vi.mocked(mockAxiosInstance.get).mockResolvedValue({
        data: [],
      })

      const result = await getBannersForPlacement(1)

      expect(result).toEqual([])
    })
  })

  describe('syncBannersForPlacement', () => {
    it('syncs banners for a placement', async () => {
      const bannerIds = [1, 2, 3]

      vi.mocked(mockAxiosInstance.put).mockResolvedValue({
        data: [mockAssignedBanner],
      })

      const result = await syncBannersForPlacement(1, bannerIds)

      expect(mockAxiosInstance.put).toHaveBeenCalledWith('/placements/1/banners', {
        banner_ids: bannerIds,
      })
      expect(result).toEqual([mockAssignedBanner])
    })

    it('can sync with empty banner array', async () => {
      vi.mocked(mockAxiosInstance.put).mockResolvedValue({
        data: [],
      })

      const result = await syncBannersForPlacement(1, [])

      expect(mockAxiosInstance.put).toHaveBeenCalledWith('/placements/1/banners', {
        banner_ids: [],
      })
      expect(result).toEqual([])
    })
  })

  describe('addBannerToPlacement', () => {
    it('adds a banner to a placement', async () => {
      vi.mocked(mockAxiosInstance.post).mockResolvedValue({
        data: [mockAssignedBanner],
      })

      const result = await addBannerToPlacement(1, 2)

      expect(mockAxiosInstance.post).toHaveBeenCalledWith('/placements/1/banners', {
        banner_id: 2,
        position: 0,
      })
      expect(result).toEqual([mockAssignedBanner])
    })

    it('adds a banner with custom position', async () => {
      vi.mocked(mockAxiosInstance.post).mockResolvedValue({
        data: [mockAssignedBanner],
      })

      await addBannerToPlacement(1, 2, 5)

      expect(mockAxiosInstance.post).toHaveBeenCalledWith('/placements/1/banners', {
        banner_id: 2,
        position: 5,
      })
    })
  })

  describe('removeBannerFromPlacement', () => {
    it('removes a banner from a placement', async () => {
      vi.mocked(mockAxiosInstance.delete).mockResolvedValue({})

      await removeBannerFromPlacement(1, 2)

      expect(mockAxiosInstance.delete).toHaveBeenCalledWith('/placements/1/banners/2')
    })
  })
})
