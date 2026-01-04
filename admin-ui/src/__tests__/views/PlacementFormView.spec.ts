import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createMemoryHistory } from 'vue-router'
import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'
import PlacementFormView from '@/views/PlacementFormView.vue'
import type { Placement } from '@/types/placement'

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

const mockGetPlacement = vi.fn()
const mockCreatePlacement = vi.fn()
const mockUpdatePlacement = vi.fn()

vi.mock('@/services/placementApi', () => ({
  getPlacements: vi.fn().mockResolvedValue({ data: [], total: 0, totalPages: 0 }),
  getPlacement: (...args: unknown[]) => mockGetPlacement(...args),
  createPlacement: (...args: unknown[]) => mockCreatePlacement(...args),
  updatePlacement: (...args: unknown[]) => mockUpdatePlacement(...args),
  deletePlacement: vi.fn(),
}))

const createTestRouter = () => {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/placements', name: 'placements', component: { template: '<div />' } },
      { path: '/placements/create', name: 'placement-create', component: { template: '<div />' } },
      { path: '/placements/:id', name: 'placement-edit', component: { template: '<div />' }, props: true },
    ],
  })
}

describe('PlacementFormView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    mockGetPlacement.mockResolvedValue(mockPlacement)
    mockCreatePlacement.mockResolvedValue(mockPlacement)
    mockUpdatePlacement.mockResolvedValue(mockPlacement)
  })

  const mountView = async (route = '/placements/create', props = {}) => {
    const router = createTestRouter()
    router.push(route)
    await router.isReady()

    const wrapper = mount(PlacementFormView, {
      props,
      global: {
        plugins: [createPinia(), router, PrimeVue, ToastService],
        stubs: {
          Card: {
            template: '<div class="card-stub"><slot name="title" /><slot name="content" /></div>',
          },
          ProgressSpinner: true,
        },
      },
    })

    await flushPromises()
    return { wrapper, router }
  }

  describe('create mode', () => {
    it('renders create form with correct title', async () => {
      const { wrapper } = await mountView('/placements/create')
      expect(wrapper.text()).toContain('Create Placement')
    })

    it('auto-generates slug from name', async () => {
      const { wrapper } = await mountView('/placements/create')

      const nameInput = wrapper.find('#name')
      await nameInput.setValue('Header Banner Slot')
      await nameInput.trigger('input')
      await flushPromises()

      const slugInput = wrapper.find('#slug')
      expect((slugInput.element as HTMLInputElement).value).toBe('header-banner-slot')
    })

    it('validates required fields', async () => {
      const { wrapper } = await mountView('/placements/create')

      const form = wrapper.find('form')
      await form.trigger('submit')
      await flushPromises()

      expect(wrapper.text()).toContain('Name is required')
      expect(wrapper.text()).toContain('Slug is required')
    })

    it('validates slug format', async () => {
      const { wrapper } = await mountView('/placements/create')

      await wrapper.find('#name').setValue('Test')
      await wrapper.find('#slug').setValue('Invalid Slug!')

      const form = wrapper.find('form')
      await form.trigger('submit')
      await flushPromises()

      expect(wrapper.text()).toContain('Slug must contain only lowercase letters, numbers, and hyphens')
    })

    it('creates placement on valid submit', async () => {
      const { wrapper, router } = await mountView('/placements/create')
      const pushSpy = vi.spyOn(router, 'push')

      await wrapper.find('#name').setValue('New Placement')
      await wrapper.find('#slug').setValue('new-placement')

      const form = wrapper.find('form')
      await form.trigger('submit')
      await flushPromises()

      expect(mockCreatePlacement).toHaveBeenCalled()
      expect(pushSpy).toHaveBeenCalledWith({ name: 'placements' })
    })

    it('navigates back on cancel', async () => {
      const { wrapper, router } = await mountView('/placements/create')
      const pushSpy = vi.spyOn(router, 'push')

      const buttons = wrapper.findAll('button')
      const cancelBtn = buttons.find((b) => b.text().includes('Cancel'))
      if (cancelBtn) await cancelBtn.trigger('click')

      expect(pushSpy).toHaveBeenCalledWith({ name: 'placements' })
    })
  })

  describe('edit mode', () => {
    it('renders edit form with correct title', async () => {
      const { wrapper } = await mountView('/placements/1', { id: '1' })
      await flushPromises()
      expect(wrapper.text()).toContain('Edit Placement')
    })

    it('loads placement data on mount', async () => {
      await mountView('/placements/1', { id: '1' })
      await flushPromises()

      expect(mockGetPlacement).toHaveBeenCalledWith(1)
    })

    it('does not auto-generate slug in edit mode', async () => {
      const { wrapper } = await mountView('/placements/1', { id: '1' })
      await flushPromises()

      const nameInput = wrapper.find('#name')
      await nameInput.setValue('Changed Name')
      await nameInput.trigger('input')
      await flushPromises()

      const slugInput = wrapper.find('#slug')
      // Slug should remain as loaded, not auto-generated
      expect((slugInput.element as HTMLInputElement).value).toBe('header-banner')
    })

    it('updates placement on valid submit', async () => {
      const { wrapper, router } = await mountView('/placements/1', { id: '1' })
      await flushPromises()
      const pushSpy = vi.spyOn(router, 'push')

      await wrapper.find('#name').setValue('Updated Placement')

      const form = wrapper.find('form')
      await form.trigger('submit')
      await flushPromises()

      expect(mockUpdatePlacement).toHaveBeenCalled()
      expect(pushSpy).toHaveBeenCalledWith({ name: 'placements' })
    })
  })
})
