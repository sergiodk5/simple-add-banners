import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import type { AxiosInstance } from 'axios'
import { setApiClient } from '../services/apiClient'
import {
  getAllBannerStats,
  getBannerStats,
  getPlacementStats,
} from '../services/statisticsApi'
import type {
  BannerStatisticsSummary,
  BannerStatisticsDetail,
  PlacementStatisticsDetail,
} from '@/types/statistics'

vi.stubGlobal('sabAdmin', {
  apiUrl: '/wp-json/sab/v1',
  nonce: 'test-nonce-123',
  adminUrl: '/wp-admin/',
})

const mockBannerSummary: BannerStatisticsSummary = {
  banner_id: 1,
  banner_title: 'Test Banner',
  banner_status: 'active',
  impressions: 100,
  clicks: 5,
  ctr: 5.0,
}

const mockBannerDetail: BannerStatisticsDetail = {
  banner_id: 1,
  totals: {
    impressions: 100,
    clicks: 5,
    ctr: 5.0,
  },
  daily: [
    {
      id: 1,
      banner_id: 1,
      placement_id: 1,
      stat_date: '2025-01-15',
      impressions: 50,
      clicks: 3,
      ctr: 6.0,
    },
    {
      id: 2,
      banner_id: 1,
      placement_id: 2,
      stat_date: '2025-01-15',
      impressions: 50,
      clicks: 2,
      ctr: 4.0,
    },
  ],
  start_date: null,
  end_date: null,
}

const mockPlacementDetail: PlacementStatisticsDetail = {
  placement_id: 1,
  totals: {
    impressions: 200,
    clicks: 10,
    ctr: 5.0,
  },
  daily: [
    {
      id: 1,
      banner_id: 1,
      placement_id: 1,
      stat_date: '2025-01-15',
      impressions: 100,
      clicks: 5,
      ctr: 5.0,
    },
    {
      id: 2,
      banner_id: 2,
      placement_id: 1,
      stat_date: '2025-01-15',
      impressions: 100,
      clicks: 5,
      ctr: 5.0,
    },
  ],
  start_date: null,
  end_date: null,
}

describe('statisticsApi', () => {
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

  describe('getAllBannerStats', () => {
    it('fetches all banner statistics successfully', async () => {
      vi.mocked(mockAxiosInstance.get).mockResolvedValue({
        data: [mockBannerSummary],
      })

      const result = await getAllBannerStats()

      expect(mockAxiosInstance.get).toHaveBeenCalledWith('/statistics', {
        params: {
          start_date: undefined,
          end_date: undefined,
        },
      })
      expect(result).toEqual([mockBannerSummary])
    })

    it('passes date filter parameters correctly', async () => {
      vi.mocked(mockAxiosInstance.get).mockResolvedValue({
        data: [],
      })

      await getAllBannerStats({
        start_date: '2025-01-01',
        end_date: '2025-01-31',
      })

      expect(mockAxiosInstance.get).toHaveBeenCalledWith('/statistics', {
        params: {
          start_date: '2025-01-01',
          end_date: '2025-01-31',
        },
      })
    })

    it('returns empty array when no stats exist', async () => {
      vi.mocked(mockAxiosInstance.get).mockResolvedValue({
        data: [],
      })

      const result = await getAllBannerStats()

      expect(result).toEqual([])
    })
  })

  describe('getBannerStats', () => {
    it('fetches statistics for a specific banner', async () => {
      vi.mocked(mockAxiosInstance.get).mockResolvedValue({
        data: mockBannerDetail,
      })

      const result = await getBannerStats(1)

      expect(mockAxiosInstance.get).toHaveBeenCalledWith('/statistics/banners/1', {
        params: {
          start_date: undefined,
          end_date: undefined,
        },
      })
      expect(result).toEqual(mockBannerDetail)
    })

    it('passes date filter parameters correctly', async () => {
      vi.mocked(mockAxiosInstance.get).mockResolvedValue({
        data: mockBannerDetail,
      })

      await getBannerStats(5, {
        start_date: '2025-01-01',
        end_date: '2025-01-15',
      })

      expect(mockAxiosInstance.get).toHaveBeenCalledWith('/statistics/banners/5', {
        params: {
          start_date: '2025-01-01',
          end_date: '2025-01-15',
        },
      })
    })
  })

  describe('getPlacementStats', () => {
    it('fetches statistics for a specific placement', async () => {
      vi.mocked(mockAxiosInstance.get).mockResolvedValue({
        data: mockPlacementDetail,
      })

      const result = await getPlacementStats(1)

      expect(mockAxiosInstance.get).toHaveBeenCalledWith('/statistics/placements/1', {
        params: {
          start_date: undefined,
          end_date: undefined,
        },
      })
      expect(result).toEqual(mockPlacementDetail)
    })

    it('passes date filter parameters correctly', async () => {
      vi.mocked(mockAxiosInstance.get).mockResolvedValue({
        data: mockPlacementDetail,
      })

      await getPlacementStats(3, {
        start_date: '2025-01-01',
        end_date: '2025-01-31',
      })

      expect(mockAxiosInstance.get).toHaveBeenCalledWith('/statistics/placements/3', {
        params: {
          start_date: '2025-01-01',
          end_date: '2025-01-31',
        },
      })
    })
  })
})
