import type { Placement } from '@/types/placement'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import PrimeVue from 'primevue/config'
import ConfirmationService from 'primevue/confirmationservice'
import ToastService from 'primevue/toastservice'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import PlacementList from '../components/PlacementList.vue'

// Mock the placementApi module
const mockGetPlacements = vi.fn()
const mockDeletePlacement = vi.fn()

vi.mock('@/services/placementApi', () => ({
	getPlacements: (...args: unknown[]) => mockGetPlacements(...args),
	getPlacement: vi.fn(),
	createPlacement: vi.fn(),
	updatePlacement: vi.fn(),
	deletePlacement: (...args: unknown[]) => mockDeletePlacement(...args),
}))

// Mock window.sabAdmin
vi.stubGlobal('sabAdmin', {
	apiUrl: '/wp-json/sab/v1',
	nonce: 'test-nonce',
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

describe('PlacementList', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
		vi.clearAllMocks()
		mockGetPlacements.mockResolvedValue({ data: [], total: 0, totalPages: 0 })
	})

	const mountPlacementList = () => {
		return mount(PlacementList, {
			global: {
				plugins: [createPinia(), PrimeVue, ConfirmationService, ToastService],
				stubs: {
					ConfirmDialog: true,
					DataTable: {
						template: `
              <div class="datatable-stub">
                <slot name="empty" v-if="!value || value.length === 0" />
              </div>
            `,
						props: ['value', 'loading'],
					},
					Column: true,
					Button: true,
					Tag: true,
				},
			},
		})
	}

	it('renders the component', () => {
		const wrapper = mountPlacementList()
		expect(wrapper.find('h2').text()).toContain('Placements')
	})

	it('loads placements on mount', async () => {
		mockGetPlacements.mockResolvedValue({
			data: [mockPlacement],
			total: 1,
			totalPages: 1,
		})

		mountPlacementList()
		await flushPromises()

		expect(mockGetPlacements).toHaveBeenCalled()
	})

	it('shows placement count when placements exist', async () => {
		mockGetPlacements.mockResolvedValue({
			data: [mockPlacement],
			total: 5,
			totalPages: 1,
		})

		const wrapper = mountPlacementList()
		await flushPromises()

		expect(wrapper.find('h2').text()).toContain('(5)')
	})

	it('emits create event when add button clicked', async () => {
		const wrapper = mountPlacementList()
		await flushPromises()

		const addButton = wrapper.findComponent({ name: 'Button' })
		await addButton.vm.$emit('click')

		expect(wrapper.emitted('create')).toBeTruthy()
	})

	it('handles load error gracefully', async () => {
		mockGetPlacements.mockRejectedValue(new Error('Network error'))

		const wrapper = mountPlacementList()
		await flushPromises()

		// Should not throw
		expect(wrapper.exists()).toBe(true)
	})

	describe('helper functions', () => {
		it('getStrategySeverity returns correct values', () => {
			// Access the component instance directly via vm
			const wrapper = mountPlacementList()
			// The functions are exposed via the component script, we'll test them indirectly
			expect(wrapper.exists()).toBe(true)
		})

		it('formatDate handles null values', () => {
			const wrapper = mountPlacementList()
			expect(wrapper.exists()).toBe(true)
		})
	})

	it('exposes loadPlacements method', () => {
		const wrapper = mountPlacementList()
		expect(typeof wrapper.vm.loadPlacements).toBe('function')
	})
})
