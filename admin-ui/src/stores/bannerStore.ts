import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Banner, BannerPayload, BannerListParams } from '@/types/banner'
import * as bannerApi from '@/services/bannerApi'

export const useBannerStore = defineStore('banner', () => {
  // State
  const banners = ref<Banner[]>([])
  const currentBanner = ref<Banner | null>(null)
  const loading = ref(false)
  const saving = ref(false)
  const error = ref<string | null>(null)
  const total = ref(0)
  const totalPages = ref(0)
  const currentPage = ref(1)
  const perPage = ref(10)

  // Getters
  const activeBanners = computed(() =>
    banners.value.filter((b) => b.status === 'active')
  )

  const pausedBanners = computed(() =>
    banners.value.filter((b) => b.status === 'paused')
  )

  const hasBanners = computed(() => banners.value.length > 0)

  // Actions
  async function fetchBanners(params: BannerListParams = {}) {
    loading.value = true
    error.value = null

    try {
      const response = await bannerApi.getBanners({
        page: currentPage.value,
        per_page: perPage.value,
        ...params,
      })

      banners.value = response.data
      total.value = response.total
      totalPages.value = response.totalPages
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to fetch banners'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchBanner(id: number) {
    loading.value = true
    error.value = null

    try {
      currentBanner.value = await bannerApi.getBanner(id)
      return currentBanner.value
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to fetch banner'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createBanner(payload: BannerPayload) {
    saving.value = true
    error.value = null

    try {
      const banner = await bannerApi.createBanner(payload)
      banners.value.unshift(banner)
      total.value++
      return banner
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to create banner'
      throw err
    } finally {
      saving.value = false
    }
  }

  async function updateBanner(id: number, payload: Partial<BannerPayload>) {
    saving.value = true
    error.value = null

    try {
      const updated = await bannerApi.updateBanner(id, payload)
      const index = banners.value.findIndex((b) => b.id === id)
      if (index !== -1) {
        banners.value[index] = updated
      }
      if (currentBanner.value?.id === id) {
        currentBanner.value = updated
      }
      return updated
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to update banner'
      throw err
    } finally {
      saving.value = false
    }
  }

  async function deleteBanner(id: number) {
    saving.value = true
    error.value = null

    try {
      await bannerApi.deleteBanner(id)
      banners.value = banners.value.filter((b) => b.id !== id)
      total.value--
      if (currentBanner.value?.id === id) {
        currentBanner.value = null
      }
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to delete banner'
      throw err
    } finally {
      saving.value = false
    }
  }

  function setCurrentBanner(banner: Banner | null) {
    currentBanner.value = banner
  }

  function setPage(page: number) {
    currentPage.value = page
  }

  function clearError() {
    error.value = null
  }

  return {
    // State
    banners,
    currentBanner,
    loading,
    saving,
    error,
    total,
    totalPages,
    currentPage,
    perPage,

    // Getters
    activeBanners,
    pausedBanners,
    hasBanners,

    // Actions
    fetchBanners,
    fetchBanner,
    createBanner,
    updateBanner,
    deleteBanner,
    setCurrentBanner,
    setPage,
    clearError,
  }
})
