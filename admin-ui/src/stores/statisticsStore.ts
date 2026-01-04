import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type {
  BannerStatisticsSummary,
  BannerStatisticsDetail,
  PlacementStatisticsDetail,
  DateRangeFilter,
} from '@/types/statistics'
import * as statisticsApi from '@/services/statisticsApi'

export const useStatisticsStore = defineStore('statistics', () => {
  // State
  const bannerSummaries = ref<BannerStatisticsSummary[]>([])
  const currentBannerStats = ref<BannerStatisticsDetail | null>(null)
  const currentPlacementStats = ref<PlacementStatisticsDetail | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const dateFilter = ref<DateRangeFilter>({})

  // Getters
  const totalImpressions = computed(() =>
    bannerSummaries.value.reduce((sum, b) => sum + b.impressions, 0)
  )

  const totalClicks = computed(() => bannerSummaries.value.reduce((sum, b) => sum + b.clicks, 0))

  const overallCtr = computed(() => {
    if (totalImpressions.value === 0) return 0
    return Math.round((totalClicks.value / totalImpressions.value) * 10000) / 100
  })

  const hasData = computed(() => bannerSummaries.value.length > 0)

  const activeBannerStats = computed(() =>
    bannerSummaries.value.filter((b) => b.banner_status === 'active')
  )

  // Actions
  async function fetchAllBannerStats(filter: DateRangeFilter = {}) {
    loading.value = true
    error.value = null
    dateFilter.value = filter

    try {
      bannerSummaries.value = await statisticsApi.getAllBannerStats(filter)
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to fetch statistics'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchBannerStats(bannerId: number, filter: DateRangeFilter = {}) {
    loading.value = true
    error.value = null

    try {
      currentBannerStats.value = await statisticsApi.getBannerStats(bannerId, filter)
      return currentBannerStats.value
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to fetch banner statistics'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchPlacementStats(placementId: number, filter: DateRangeFilter = {}) {
    loading.value = true
    error.value = null

    try {
      currentPlacementStats.value = await statisticsApi.getPlacementStats(placementId, filter)
      return currentPlacementStats.value
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to fetch placement statistics'
      throw err
    } finally {
      loading.value = false
    }
  }

  function setDateFilter(filter: DateRangeFilter) {
    dateFilter.value = filter
  }

  function clearCurrentStats() {
    currentBannerStats.value = null
    currentPlacementStats.value = null
  }

  function clearError() {
    error.value = null
  }

  return {
    // State
    bannerSummaries,
    currentBannerStats,
    currentPlacementStats,
    loading,
    error,
    dateFilter,

    // Getters
    totalImpressions,
    totalClicks,
    overallCtr,
    hasData,
    activeBannerStats,

    // Actions
    fetchAllBannerStats,
    fetchBannerStats,
    fetchPlacementStats,
    setDateFilter,
    clearCurrentStats,
    clearError,
  }
})
