import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useBannerStore } from '../stores/bannerStore'
import * as bannerApi from '@/services/bannerApi'
import type { Banner } from '@/types/banner'

vi.mock('@/services/bannerApi')

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

const mockBanner2: Banner = {
  id: 2,
  title: 'Paused Banner',
  desktop_image_id: null,
  mobile_image_id: null,
  desktop_url: 'https://example2.com',
  mobile_url: null,
  start_date: null,
  end_date: null,
  status: 'paused',
  weight: 1,
  created_at: '2024-01-02T00:00:00',
  updated_at: '2024-01-02T00:00:00',
}

describe('bannerStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  describe('initial state', () => {
    it('has empty banners array', () => {
      const store = useBannerStore()
      expect(store.banners).toEqual([])
    })

    it('has null currentBanner', () => {
      const store = useBannerStore()
      expect(store.currentBanner).toBeNull()
    })

    it('has loading false', () => {
      const store = useBannerStore()
      expect(store.loading).toBe(false)
    })

    it('has saving false', () => {
      const store = useBannerStore()
      expect(store.saving).toBe(false)
    })

    it('has null error', () => {
      const store = useBannerStore()
      expect(store.error).toBeNull()
    })
  })

  describe('computed getters', () => {
    it('activeBanners filters only active banners', () => {
      const store = useBannerStore()
      store.banners = [mockBanner, mockBanner2]
      expect(store.activeBanners).toEqual([mockBanner])
    })

    it('pausedBanners filters only paused banners', () => {
      const store = useBannerStore()
      store.banners = [mockBanner, mockBanner2]
      expect(store.pausedBanners).toEqual([mockBanner2])
    })

    it('hasBanners returns true when banners exist', () => {
      const store = useBannerStore()
      store.banners = [mockBanner]
      expect(store.hasBanners).toBe(true)
    })

    it('hasBanners returns false when no banners', () => {
      const store = useBannerStore()
      expect(store.hasBanners).toBe(false)
    })
  })

  describe('fetchBanners', () => {
    it('fetches banners successfully', async () => {
      const store = useBannerStore()
      vi.mocked(bannerApi.getBanners).mockResolvedValue({
        data: [mockBanner],
        total: 1,
        totalPages: 1,
      })

      await store.fetchBanners()

      expect(store.banners).toEqual([mockBanner])
      expect(store.total).toBe(1)
      expect(store.totalPages).toBe(1)
      expect(store.loading).toBe(false)
    })

    it('handles fetch error', async () => {
      const store = useBannerStore()
      vi.mocked(bannerApi.getBanners).mockRejectedValue(new Error('Network error'))

      await expect(store.fetchBanners()).rejects.toThrow('Network error')
      expect(store.error).toBe('Network error')
      expect(store.loading).toBe(false)
    })

    it('handles non-Error rejection', async () => {
      const store = useBannerStore()
      vi.mocked(bannerApi.getBanners).mockRejectedValue('String error')

      await expect(store.fetchBanners()).rejects.toBe('String error')
      expect(store.error).toBe('Failed to fetch banners')
    })
  })

  describe('fetchBanner', () => {
    it('fetches single banner successfully', async () => {
      const store = useBannerStore()
      vi.mocked(bannerApi.getBanner).mockResolvedValue(mockBanner)

      const result = await store.fetchBanner(1)

      expect(result).toEqual(mockBanner)
      expect(store.currentBanner).toEqual(mockBanner)
      expect(store.loading).toBe(false)
    })

    it('handles fetch error', async () => {
      const store = useBannerStore()
      vi.mocked(bannerApi.getBanner).mockRejectedValue(new Error('Not found'))

      await expect(store.fetchBanner(999)).rejects.toThrow('Not found')
      expect(store.error).toBe('Not found')
    })

    it('handles non-Error rejection', async () => {
      const store = useBannerStore()
      vi.mocked(bannerApi.getBanner).mockRejectedValue('String error')

      await expect(store.fetchBanner(1)).rejects.toBe('String error')
      expect(store.error).toBe('Failed to fetch banner')
    })
  })

  describe('createBanner', () => {
    it('creates banner successfully', async () => {
      const store = useBannerStore()
      vi.mocked(bannerApi.createBanner).mockResolvedValue(mockBanner)

      const payload = {
        title: 'New Banner',
        desktop_url: 'https://example.com',
        mobile_url: null,
        desktop_image_id: null,
        mobile_image_id: null,
        start_date: null,
        end_date: null,
        status: 'active' as const,
        weight: 1,
      }

      const result = await store.createBanner(payload)

      expect(result).toEqual(mockBanner)
      expect(store.banners[0]).toEqual(mockBanner)
      expect(store.total).toBe(1)
      expect(store.saving).toBe(false)
    })

    it('handles create error', async () => {
      const store = useBannerStore()
      vi.mocked(bannerApi.createBanner).mockRejectedValue(new Error('Create failed'))

      await expect(
        store.createBanner({
          title: 'Test',
          desktop_url: 'https://example.com',
          mobile_url: null,
          desktop_image_id: null,
          mobile_image_id: null,
          start_date: null,
          end_date: null,
          status: 'active',
          weight: 1,
        }),
      ).rejects.toThrow('Create failed')
      expect(store.error).toBe('Create failed')
      expect(store.saving).toBe(false)
    })

    it('handles non-Error rejection', async () => {
      const store = useBannerStore()
      vi.mocked(bannerApi.createBanner).mockRejectedValue('String error')

      await expect(
        store.createBanner({
          title: 'Test',
          desktop_url: 'https://example.com',
          mobile_url: null,
          desktop_image_id: null,
          mobile_image_id: null,
          start_date: null,
          end_date: null,
          status: 'active',
          weight: 1,
        }),
      ).rejects.toBe('String error')
      expect(store.error).toBe('Failed to create banner')
    })
  })

  describe('updateBanner', () => {
    it('updates banner successfully', async () => {
      const store = useBannerStore()
      store.banners = [mockBanner]
      const updatedBanner = { ...mockBanner, title: 'Updated Title' }
      vi.mocked(bannerApi.updateBanner).mockResolvedValue(updatedBanner)

      const result = await store.updateBanner(1, { title: 'Updated Title' })

      expect(result).toEqual(updatedBanner)
      expect(store.banners[0].title).toBe('Updated Title')
      expect(store.saving).toBe(false)
    })

    it('updates currentBanner if it matches', async () => {
      const store = useBannerStore()
      store.banners = [mockBanner]
      store.currentBanner = mockBanner
      const updatedBanner = { ...mockBanner, title: 'Updated Title' }
      vi.mocked(bannerApi.updateBanner).mockResolvedValue(updatedBanner)

      await store.updateBanner(1, { title: 'Updated Title' })

      expect(store.currentBanner?.title).toBe('Updated Title')
    })

    it('does not update banners list if banner not found', async () => {
      const store = useBannerStore()
      store.banners = [mockBanner]
      const updatedBanner = { ...mockBanner2, title: 'Updated Title' }
      vi.mocked(bannerApi.updateBanner).mockResolvedValue(updatedBanner)

      await store.updateBanner(2, { title: 'Updated Title' })

      expect(store.banners).toEqual([mockBanner])
    })

    it('handles update error', async () => {
      const store = useBannerStore()
      vi.mocked(bannerApi.updateBanner).mockRejectedValue(new Error('Update failed'))

      await expect(store.updateBanner(1, { title: 'Test' })).rejects.toThrow('Update failed')
      expect(store.error).toBe('Update failed')
      expect(store.saving).toBe(false)
    })

    it('handles non-Error rejection', async () => {
      const store = useBannerStore()
      vi.mocked(bannerApi.updateBanner).mockRejectedValue('String error')

      await expect(store.updateBanner(1, { title: 'Test' })).rejects.toBe('String error')
      expect(store.error).toBe('Failed to update banner')
    })
  })

  describe('deleteBanner', () => {
    it('deletes banner successfully', async () => {
      const store = useBannerStore()
      store.banners = [mockBanner, mockBanner2]
      store.total = 2
      vi.mocked(bannerApi.deleteBanner).mockResolvedValue(undefined)

      await store.deleteBanner(1)

      expect(store.banners).toEqual([mockBanner2])
      expect(store.total).toBe(1)
      expect(store.saving).toBe(false)
    })

    it('clears currentBanner if deleted', async () => {
      const store = useBannerStore()
      store.banners = [mockBanner]
      store.currentBanner = mockBanner
      vi.mocked(bannerApi.deleteBanner).mockResolvedValue(undefined)

      await store.deleteBanner(1)

      expect(store.currentBanner).toBeNull()
    })

    it('handles delete error', async () => {
      const store = useBannerStore()
      vi.mocked(bannerApi.deleteBanner).mockRejectedValue(new Error('Delete failed'))

      await expect(store.deleteBanner(1)).rejects.toThrow('Delete failed')
      expect(store.error).toBe('Delete failed')
      expect(store.saving).toBe(false)
    })

    it('handles non-Error rejection', async () => {
      const store = useBannerStore()
      vi.mocked(bannerApi.deleteBanner).mockRejectedValue('String error')

      await expect(store.deleteBanner(1)).rejects.toBe('String error')
      expect(store.error).toBe('Failed to delete banner')
    })
  })

  describe('utility actions', () => {
    it('setCurrentBanner sets the current banner', () => {
      const store = useBannerStore()
      store.setCurrentBanner(mockBanner)
      expect(store.currentBanner).toEqual(mockBanner)
    })

    it('setCurrentBanner can set to null', () => {
      const store = useBannerStore()
      store.currentBanner = mockBanner
      store.setCurrentBanner(null)
      expect(store.currentBanner).toBeNull()
    })

    it('setPage sets the current page', () => {
      const store = useBannerStore()
      store.setPage(5)
      expect(store.currentPage).toBe(5)
    })

    it('clearError clears the error', () => {
      const store = useBannerStore()
      store.error = 'Some error'
      store.clearError()
      expect(store.error).toBeNull()
    })
  })
})
