import type { Placement, PlacementPayload } from '@/types/placement'
import type { AxiosInstance } from 'axios'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { setApiClient } from '../services/apiClient'
import {
	createPlacement,
	deletePlacement,
	getPlacement,
	getPlacements,
	updatePlacement,
} from '../services/placementApi'

vi.stubGlobal('sabAdmin', {
	apiUrl: '/wp-json/sab/v1',
	nonce: 'test-nonce-123',
	adminUrl: '/wp-admin/',
})

const mockPlacement: Placement = {
	id: 1,
	slug: 'header-banner',
	name: 'Header Banner',
	rotation_strategy: 'random',
	created_at: '2024-01-01T00:00:00',
	updated_at: '2024-01-01T00:00:00',
}

describe('placementApi', () => {
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

	describe('getPlacements', () => {
		it('fetches placements successfully', async () => {
			vi.mocked(mockAxiosInstance.get).mockResolvedValue({
				data: [mockPlacement],
				headers: {
					'x-wp-total': '10',
					'x-wp-totalpages': '2',
				},
			})

			const result = await getPlacements()

			expect(mockAxiosInstance.get).toHaveBeenCalledWith('/placements', {
				params: {
					page: undefined,
					per_page: undefined,
					orderby: undefined,
					order: undefined,
				},
			})
			expect(result.data).toEqual([mockPlacement])
			expect(result.total).toBe(10)
			expect(result.totalPages).toBe(2)
		})

		it('passes parameters correctly', async () => {
			vi.mocked(mockAxiosInstance.get).mockResolvedValue({
				data: [],
				headers: {
					'x-wp-total': '0',
					'x-wp-totalpages': '0',
				},
			})

			await getPlacements({ page: 2, per_page: 20, orderby: 'name', order: 'ASC' })

			expect(mockAxiosInstance.get).toHaveBeenCalledWith('/placements', {
				params: {
					page: 2,
					per_page: 20,
					orderby: 'name',
					order: 'ASC',
				},
			})
		})

		it('handles missing headers with defaults', async () => {
			vi.mocked(mockAxiosInstance.get).mockResolvedValue({
				data: [],
				headers: {},
			})

			const result = await getPlacements()

			expect(result.total).toBe(0)
			expect(result.totalPages).toBe(0)
		})
	})

	describe('getPlacement', () => {
		it('fetches a single placement', async () => {
			vi.mocked(mockAxiosInstance.get).mockResolvedValue({
				data: mockPlacement,
			})

			const result = await getPlacement(1)

			expect(mockAxiosInstance.get).toHaveBeenCalledWith('/placements/1')
			expect(result).toEqual(mockPlacement)
		})
	})

	describe('createPlacement', () => {
		it('creates a placement successfully', async () => {
			const payload: PlacementPayload = {
				slug: 'new-placement',
				name: 'New Placement',
				rotation_strategy: 'weighted',
			}

			vi.mocked(mockAxiosInstance.post).mockResolvedValue({
				data: { ...mockPlacement, ...payload, id: 2 },
			})

			const result = await createPlacement(payload)

			expect(mockAxiosInstance.post).toHaveBeenCalledWith('/placements', payload)
			expect(result.slug).toBe('new-placement')
		})
	})

	describe('updatePlacement', () => {
		it('updates a placement successfully', async () => {
			const payload = { name: 'Updated Placement' }

			vi.mocked(mockAxiosInstance.put).mockResolvedValue({
				data: { ...mockPlacement, ...payload },
			})

			const result = await updatePlacement(1, payload)

			expect(mockAxiosInstance.put).toHaveBeenCalledWith('/placements/1', payload)
			expect(result.name).toBe('Updated Placement')
		})
	})

	describe('deletePlacement', () => {
		it('deletes a placement successfully', async () => {
			vi.mocked(mockAxiosInstance.delete).mockResolvedValue({})

			await deletePlacement(1)

			expect(mockAxiosInstance.delete).toHaveBeenCalledWith('/placements/1')
		})
	})
})
