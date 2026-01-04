import * as placementApi from '@/services/placementApi'
import type { Placement } from '@/types/placement'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { usePlacementStore } from '../stores/placementStore'

vi.mock('@/services/placementApi')

const mockPlacement: Placement = {
	id: 1,
	slug: 'header-banner',
	name: 'Header Banner',
	rotation_strategy: 'random',
	created_at: '2024-01-01T00:00:00',
	updated_at: '2024-01-01T00:00:00',
}

const mockPlacement2: Placement = {
	id: 2,
	slug: 'sidebar-ad',
	name: 'Sidebar Ad',
	rotation_strategy: 'weighted',
	created_at: '2024-01-02T00:00:00',
	updated_at: '2024-01-02T00:00:00',
}

describe('placementStore', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
		vi.clearAllMocks()
	})

	describe('initial state', () => {
		it('has empty placements array', () => {
			const store = usePlacementStore()
			expect(store.placements).toEqual([])
		})

		it('has null currentPlacement', () => {
			const store = usePlacementStore()
			expect(store.currentPlacement).toBeNull()
		})

		it('has loading false', () => {
			const store = usePlacementStore()
			expect(store.loading).toBe(false)
		})

		it('has saving false', () => {
			const store = usePlacementStore()
			expect(store.saving).toBe(false)
		})

		it('has null error', () => {
			const store = usePlacementStore()
			expect(store.error).toBeNull()
		})

		it('has default pagination values', () => {
			const store = usePlacementStore()
			expect(store.total).toBe(0)
			expect(store.totalPages).toBe(0)
			expect(store.currentPage).toBe(1)
			expect(store.perPage).toBe(10)
		})
	})

	describe('computed getters', () => {
		it('hasPlacements returns true when placements exist', () => {
			const store = usePlacementStore()
			store.placements = [mockPlacement]
			expect(store.hasPlacements).toBe(true)
		})

		it('hasPlacements returns false when no placements', () => {
			const store = usePlacementStore()
			expect(store.hasPlacements).toBe(false)
		})

		it('placementBySlug finds placement by slug', () => {
			const store = usePlacementStore()
			store.placements = [mockPlacement, mockPlacement2]
			expect(store.placementBySlug('header-banner')).toEqual(mockPlacement)
			expect(store.placementBySlug('sidebar-ad')).toEqual(mockPlacement2)
		})

		it('placementBySlug returns undefined for non-existent slug', () => {
			const store = usePlacementStore()
			store.placements = [mockPlacement]
			expect(store.placementBySlug('non-existent')).toBeUndefined()
		})
	})

	describe('fetchPlacements', () => {
		it('fetches placements successfully', async () => {
			const store = usePlacementStore()
			vi.mocked(placementApi.getPlacements).mockResolvedValue({
				data: [mockPlacement],
				total: 1,
				totalPages: 1,
			})

			await store.fetchPlacements()

			expect(store.placements).toEqual([mockPlacement])
			expect(store.total).toBe(1)
			expect(store.totalPages).toBe(1)
			expect(store.loading).toBe(false)
		})

		it('handles fetch error', async () => {
			const store = usePlacementStore()
			vi.mocked(placementApi.getPlacements).mockRejectedValue(new Error('Network error'))

			await expect(store.fetchPlacements()).rejects.toThrow('Network error')
			expect(store.error).toBe('Network error')
			expect(store.loading).toBe(false)
		})

		it('handles non-Error rejection', async () => {
			const store = usePlacementStore()
			vi.mocked(placementApi.getPlacements).mockRejectedValue('String error')

			await expect(store.fetchPlacements()).rejects.toBe('String error')
			expect(store.error).toBe('Failed to fetch placements')
		})

		it('passes params to API', async () => {
			const store = usePlacementStore()
			vi.mocked(placementApi.getPlacements).mockResolvedValue({
				data: [],
				total: 0,
				totalPages: 0,
			})

			await store.fetchPlacements({ orderby: 'name', order: 'ASC' })

			expect(placementApi.getPlacements).toHaveBeenCalledWith({
				page: 1,
				per_page: 10,
				orderby: 'name',
				order: 'ASC',
			})
		})
	})

	describe('fetchPlacement', () => {
		it('fetches single placement successfully', async () => {
			const store = usePlacementStore()
			vi.mocked(placementApi.getPlacement).mockResolvedValue(mockPlacement)

			const result = await store.fetchPlacement(1)

			expect(result).toEqual(mockPlacement)
			expect(store.currentPlacement).toEqual(mockPlacement)
			expect(store.loading).toBe(false)
		})

		it('handles fetch error', async () => {
			const store = usePlacementStore()
			vi.mocked(placementApi.getPlacement).mockRejectedValue(new Error('Not found'))

			await expect(store.fetchPlacement(999)).rejects.toThrow('Not found')
			expect(store.error).toBe('Not found')
		})

		it('handles non-Error rejection', async () => {
			const store = usePlacementStore()
			vi.mocked(placementApi.getPlacement).mockRejectedValue('String error')

			await expect(store.fetchPlacement(1)).rejects.toBe('String error')
			expect(store.error).toBe('Failed to fetch placement')
		})
	})

	describe('createPlacement', () => {
		it('creates placement successfully', async () => {
			const store = usePlacementStore()
			vi.mocked(placementApi.createPlacement).mockResolvedValue(mockPlacement)

			const payload = {
				slug: 'new-placement',
				name: 'New Placement',
				rotation_strategy: 'random' as const,
			}

			const result = await store.createPlacement(payload)

			expect(result).toEqual(mockPlacement)
			expect(store.placements.length).toBe(1)
			expect(store.placements[0].id).toBe(mockPlacement.id)
			expect(store.total).toBe(1)
			expect(store.saving).toBe(false)
		})

		it('handles create error', async () => {
			const store = usePlacementStore()
			vi.mocked(placementApi.createPlacement).mockRejectedValue(new Error('Slug exists'))

			const payload = {
				slug: 'existing-slug',
				name: 'Test',
				rotation_strategy: 'random' as const,
			}

			await expect(store.createPlacement(payload)).rejects.toThrow('Slug exists')
			expect(store.error).toBe('Slug exists')
			expect(store.saving).toBe(false)
		})

		it('handles non-Error rejection', async () => {
			const store = usePlacementStore()
			vi.mocked(placementApi.createPlacement).mockRejectedValue('String error')

			const payload = {
				slug: 'test',
				name: 'Test',
				rotation_strategy: 'random' as const,
			}

			await expect(store.createPlacement(payload)).rejects.toBe('String error')
			expect(store.error).toBe('Failed to create placement')
		})
	})

	describe('updatePlacement', () => {
		it('updates placement successfully', async () => {
			const store = usePlacementStore()
			store.placements = [mockPlacement]
			store.currentPlacement = mockPlacement

			const updatedPlacement = { ...mockPlacement, name: 'Updated Name' }
			vi.mocked(placementApi.updatePlacement).mockResolvedValue(updatedPlacement)

			const result = await store.updatePlacement(1, { name: 'Updated Name' })

			expect(result).toEqual(updatedPlacement)
			expect(store.placements[0].name).toBe('Updated Name')
			expect(store.currentPlacement?.name).toBe('Updated Name')
			expect(store.saving).toBe(false)
		})

		it('handles update error', async () => {
			const store = usePlacementStore()
			vi.mocked(placementApi.updatePlacement).mockRejectedValue(new Error('Update failed'))

			await expect(store.updatePlacement(1, { name: 'Test' })).rejects.toThrow('Update failed')
			expect(store.error).toBe('Update failed')
			expect(store.saving).toBe(false)
		})

		it('handles non-Error rejection', async () => {
			const store = usePlacementStore()
			vi.mocked(placementApi.updatePlacement).mockRejectedValue('String error')

			await expect(store.updatePlacement(1, { name: 'Test' })).rejects.toBe('String error')
			expect(store.error).toBe('Failed to update placement')
		})

		it('updates placement not in current list', async () => {
			const store = usePlacementStore()
			store.placements = [mockPlacement2] // Different placement

			const updatedPlacement = { ...mockPlacement, name: 'Updated Name' }
			vi.mocked(placementApi.updatePlacement).mockResolvedValue(updatedPlacement)

			const result = await store.updatePlacement(1, { name: 'Updated Name' })

			expect(result).toEqual(updatedPlacement)
			// mockPlacement2 should be unchanged
			expect(store.placements[0]).toEqual(mockPlacement2)
		})
	})

	describe('deletePlacement', () => {
		it('deletes placement successfully', async () => {
			const store = usePlacementStore()
			store.placements = [mockPlacement, mockPlacement2]
			store.total = 2
			store.currentPlacement = mockPlacement

			vi.mocked(placementApi.deletePlacement).mockResolvedValue()

			await store.deletePlacement(1)

			expect(store.placements).toEqual([mockPlacement2])
			expect(store.total).toBe(1)
			expect(store.currentPlacement).toBeNull()
			expect(store.loading).toBe(false)
		})

		it('handles delete error', async () => {
			const store = usePlacementStore()
			vi.mocked(placementApi.deletePlacement).mockRejectedValue(new Error('Delete failed'))

			await expect(store.deletePlacement(1)).rejects.toThrow('Delete failed')
			expect(store.error).toBe('Delete failed')
			expect(store.loading).toBe(false)
		})

		it('handles non-Error rejection', async () => {
			const store = usePlacementStore()
			vi.mocked(placementApi.deletePlacement).mockRejectedValue('String error')

			await expect(store.deletePlacement(1)).rejects.toBe('String error')
			expect(store.error).toBe('Failed to delete placement')
		})

		it('does not clear currentPlacement if different id', async () => {
			const store = usePlacementStore()
			store.placements = [mockPlacement, mockPlacement2]
			store.currentPlacement = mockPlacement2

			vi.mocked(placementApi.deletePlacement).mockResolvedValue()

			await store.deletePlacement(1)

			expect(store.currentPlacement).toEqual(mockPlacement2)
		})
	})

	describe('reset', () => {
		it('resets all state to initial values', () => {
			const store = usePlacementStore()
			store.placements = [mockPlacement]
			store.currentPlacement = mockPlacement
			store.loading = true
			store.saving = true
			store.error = 'Some error'
			store.total = 10
			store.totalPages = 2
			store.currentPage = 3

			store.reset()

			expect(store.placements).toEqual([])
			expect(store.currentPlacement).toBeNull()
			expect(store.loading).toBe(false)
			expect(store.saving).toBe(false)
			expect(store.error).toBeNull()
			expect(store.total).toBe(0)
			expect(store.totalPages).toBe(0)
			expect(store.currentPage).toBe(1)
		})
	})
})
