import * as placementApi from '@/services/placementApi'
import type { Placement, PlacementListParams, PlacementPayload } from '@/types/placement'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

export const usePlacementStore = defineStore('placement', () => {
  // State
  const placements = ref<Placement[]>([])
  const currentPlacement = ref<Placement | null>(null)
  const loading = ref(false)
  const saving = ref(false)
  const error = ref<string | null>(null)
  const total = ref(0)
  const totalPages = ref(0)
  const currentPage = ref(1)
  const perPage = ref(10)

  // Getters
  const hasPlacements = computed(() => placements.value.length > 0)

  const placementBySlug = computed(() => {
    return (slug: string) => placements.value.find((p) => p.slug === slug)
  })

  // Actions
  async function fetchPlacements(params: PlacementListParams = {}) {
    loading.value = true
    error.value = null

    try {
      const response = await placementApi.getPlacements({
        page: currentPage.value,
        per_page: perPage.value,
        ...params,
      })

      placements.value = response.data
      total.value = response.total
      totalPages.value = response.totalPages
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to fetch placements'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchPlacement(id: number) {
    loading.value = true
    error.value = null

    try {
      currentPlacement.value = await placementApi.getPlacement(id)
      return currentPlacement.value
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to fetch placement'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createPlacement(payload: PlacementPayload) {
    saving.value = true
    error.value = null

    try {
      const placement = await placementApi.createPlacement(payload)
      placements.value.unshift(placement)
      total.value++
      return placement
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to create placement'
      throw err
    } finally {
      saving.value = false
    }
  }

  async function updatePlacement(id: number, payload: Partial<PlacementPayload>) {
    saving.value = true
    error.value = null

    try {
      const updated = await placementApi.updatePlacement(id, payload)
      const index = placements.value.findIndex((p) => p.id === id)
      if (index !== -1) {
        placements.value[index] = updated
      }
      if (currentPlacement.value?.id === id) {
        currentPlacement.value = updated
      }
      return updated
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to update placement'
      throw err
    } finally {
      saving.value = false
    }
  }

  async function deletePlacement(id: number) {
    loading.value = true
    error.value = null

    try {
      await placementApi.deletePlacement(id)
      placements.value = placements.value.filter((p) => p.id !== id)
      total.value--
      if (currentPlacement.value?.id === id) {
        currentPlacement.value = null
      }
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to delete placement'
      throw err
    } finally {
      loading.value = false
    }
  }

  function reset() {
    placements.value = []
    currentPlacement.value = null
    loading.value = false
    saving.value = false
    error.value = null
    total.value = 0
    totalPages.value = 0
    currentPage.value = 1
  }

  return {
    // State
    placements,
    currentPlacement,
    loading,
    saving,
    error,
    total,
    totalPages,
    currentPage,
    perPage,
    // Getters
    hasPlacements,
    placementBySlug,
    // Actions
    fetchPlacements,
    fetchPlacement,
    createPlacement,
    updatePlacement,
    deletePlacement,
    reset,
  }
})
