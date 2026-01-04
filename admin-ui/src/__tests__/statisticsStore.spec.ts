import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useStatisticsStore } from '../stores/statisticsStore'
import * as statisticsApi from '@/services/statisticsApi'
import type { BannerStatisticsSummary, BannerStatisticsDetail } from '@/types/statistics'

vi.mock('@/services/statisticsApi')

const mockBannerSummary: BannerStatisticsSummary = {
  banner_id: 1,
  banner_title: 'Test Banner',
  banner_status: 'active',
  impressions: 100,
  clicks: 5,
  ctr: 5.0,
}

const mockBannerSummary2: BannerStatisticsSummary = {
  banner_id: 2,
  banner_title: 'Paused Banner',
  banner_status: 'paused',
  impressions: 50,
  clicks: 2,
  ctr: 4.0,
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
  ],
  start_date: null,
  end_date: null,
}

describe('statisticsStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  describe('initial state', () => {
    it('has empty bannerSummaries array', () => {
      const store = useStatisticsStore()
      expect(store.bannerSummaries).toEqual([])
    })

    it('has null currentBannerStats', () => {
      const store = useStatisticsStore()
      expect(store.currentBannerStats).toBeNull()
    })

    it('has null currentPlacementStats', () => {
      const store = useStatisticsStore()
      expect(store.currentPlacementStats).toBeNull()
    })

    it('has loading false', () => {
      const store = useStatisticsStore()
      expect(store.loading).toBe(false)
    })

    it('has null error', () => {
      const store = useStatisticsStore()
      expect(store.error).toBeNull()
    })

    it('has empty dateFilter', () => {
      const store = useStatisticsStore()
      expect(store.dateFilter).toEqual({})
    })
  })

  describe('computed getters', () => {
    it('totalImpressions sums all impressions', () => {
      const store = useStatisticsStore()
      store.bannerSummaries = [mockBannerSummary, mockBannerSummary2]
      expect(store.totalImpressions).toBe(150)
    })

    it('totalClicks sums all clicks', () => {
      const store = useStatisticsStore()
      store.bannerSummaries = [mockBannerSummary, mockBannerSummary2]
      expect(store.totalClicks).toBe(7)
    })

    it('overallCtr calculates correct CTR', () => {
      const store = useStatisticsStore()
      store.bannerSummaries = [mockBannerSummary, mockBannerSummary2]
      // 7 clicks / 150 impressions = 4.67%
      expect(store.overallCtr).toBeCloseTo(4.67, 1)
    })

    it('overallCtr returns 0 when no impressions', () => {
      const store = useStatisticsStore()
      expect(store.overallCtr).toBe(0)
    })

    it('hasData returns true when summaries exist', () => {
      const store = useStatisticsStore()
      store.bannerSummaries = [mockBannerSummary]
      expect(store.hasData).toBe(true)
    })

    it('hasData returns false when no summaries', () => {
      const store = useStatisticsStore()
      expect(store.hasData).toBe(false)
    })

    it('activeBannerStats filters only active banners', () => {
      const store = useStatisticsStore()
      store.bannerSummaries = [mockBannerSummary, mockBannerSummary2]
      expect(store.activeBannerStats).toEqual([mockBannerSummary])
    })
  })

  describe('fetchAllBannerStats', () => {
    it('fetches all banner statistics successfully', async () => {
      const store = useStatisticsStore()
      vi.mocked(statisticsApi.getAllBannerStats).mockResolvedValue([mockBannerSummary])

      await store.fetchAllBannerStats()

      expect(store.bannerSummaries).toEqual([mockBannerSummary])
      expect(store.loading).toBe(false)
    })

    it('sets dateFilter when provided', async () => {
      const store = useStatisticsStore()
      vi.mocked(statisticsApi.getAllBannerStats).mockResolvedValue([])

      await store.fetchAllBannerStats({ start_date: '2025-01-01', end_date: '2025-01-31' })

      expect(store.dateFilter).toEqual({ start_date: '2025-01-01', end_date: '2025-01-31' })
    })

    it('handles fetch error', async () => {
      const store = useStatisticsStore()
      vi.mocked(statisticsApi.getAllBannerStats).mockRejectedValue(new Error('Network error'))

      await expect(store.fetchAllBannerStats()).rejects.toThrow('Network error')
      expect(store.error).toBe('Network error')
      expect(store.loading).toBe(false)
    })

    it('handles non-Error rejection', async () => {
      const store = useStatisticsStore()
      vi.mocked(statisticsApi.getAllBannerStats).mockRejectedValue('String error')

      await expect(store.fetchAllBannerStats()).rejects.toBe('String error')
      expect(store.error).toBe('Failed to fetch statistics')
    })
  })

  describe('fetchBannerStats', () => {
    it('fetches banner statistics successfully', async () => {
      const store = useStatisticsStore()
      vi.mocked(statisticsApi.getBannerStats).mockResolvedValue(mockBannerDetail)

      const result = await store.fetchBannerStats(1)

      expect(result).toEqual(mockBannerDetail)
      expect(store.currentBannerStats).toEqual(mockBannerDetail)
      expect(store.loading).toBe(false)
    })

    it('handles fetch error', async () => {
      const store = useStatisticsStore()
      vi.mocked(statisticsApi.getBannerStats).mockRejectedValue(new Error('Not found'))

      await expect(store.fetchBannerStats(999)).rejects.toThrow('Not found')
      expect(store.error).toBe('Not found')
    })

    it('handles non-Error rejection', async () => {
      const store = useStatisticsStore()
      vi.mocked(statisticsApi.getBannerStats).mockRejectedValue('String error')

      await expect(store.fetchBannerStats(1)).rejects.toBe('String error')
      expect(store.error).toBe('Failed to fetch banner statistics')
    })
  })

  describe('fetchPlacementStats', () => {
    it('fetches placement statistics successfully', async () => {
      const store = useStatisticsStore()
      const mockPlacementDetail = {
        placement_id: 1,
        totals: { impressions: 200, clicks: 10, ctr: 5.0 },
        daily: [],
        start_date: null,
        end_date: null,
      }
      vi.mocked(statisticsApi.getPlacementStats).mockResolvedValue(mockPlacementDetail)

      const result = await store.fetchPlacementStats(1)

      expect(result).toEqual(mockPlacementDetail)
      expect(store.currentPlacementStats).toEqual(mockPlacementDetail)
      expect(store.loading).toBe(false)
    })

    it('handles fetch error', async () => {
      const store = useStatisticsStore()
      vi.mocked(statisticsApi.getPlacementStats).mockRejectedValue(new Error('Not found'))

      await expect(store.fetchPlacementStats(999)).rejects.toThrow('Not found')
      expect(store.error).toBe('Not found')
    })

    it('handles non-Error rejection', async () => {
      const store = useStatisticsStore()
      vi.mocked(statisticsApi.getPlacementStats).mockRejectedValue('String error')

      await expect(store.fetchPlacementStats(1)).rejects.toBe('String error')
      expect(store.error).toBe('Failed to fetch placement statistics')
    })
  })

  describe('utility actions', () => {
    it('setDateFilter sets the date filter', () => {
      const store = useStatisticsStore()
      store.setDateFilter({ start_date: '2025-01-01' })
      expect(store.dateFilter).toEqual({ start_date: '2025-01-01' })
    })

    it('clearCurrentStats clears current stats', () => {
      const store = useStatisticsStore()
      store.currentBannerStats = mockBannerDetail
      store.currentPlacementStats = {
        placement_id: 1,
        totals: { impressions: 0, clicks: 0, ctr: 0 },
        daily: [],
        start_date: null,
        end_date: null,
      }

      store.clearCurrentStats()

      expect(store.currentBannerStats).toBeNull()
      expect(store.currentPlacementStats).toBeNull()
    })

    it('clearError clears the error', () => {
      const store = useStatisticsStore()
      store.error = 'Some error'
      store.clearError()
      expect(store.error).toBeNull()
    })
  })
})
