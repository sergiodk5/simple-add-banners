import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createMemoryHistory } from 'vue-router'
import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'
import BannerFormView from '@/views/BannerFormView.vue'
import type { Banner } from '@/types/banner'

vi.stubGlobal('sabAdmin', {
  apiUrl: '/wp-json/sab/v1',
  nonce: 'test-nonce',
  adminUrl: '/wp-admin/',
})

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

const mockGetBanner = vi.fn()
const mockCreateBanner = vi.fn()
const mockUpdateBanner = vi.fn()

vi.mock('@/services/bannerApi', () => ({
  getBanners: vi.fn().mockResolvedValue({ data: [], total: 0, totalPages: 0 }),
  getBanner: (...args: unknown[]) => mockGetBanner(...args),
  createBanner: (...args: unknown[]) => mockCreateBanner(...args),
  updateBanner: (...args: unknown[]) => mockUpdateBanner(...args),
  deleteBanner: vi.fn(),
}))

const createTestRouter = () => {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/banners', name: 'banners', component: { template: '<div />' } },
      { path: '/banners/create', name: 'banner-create', component: { template: '<div />' } },
      { path: '/banners/:id', name: 'banner-edit', component: { template: '<div />' }, props: true },
    ],
  })
}

describe('BannerFormView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    mockGetBanner.mockResolvedValue(mockBanner)
    mockCreateBanner.mockResolvedValue(mockBanner)
    mockUpdateBanner.mockResolvedValue(mockBanner)
  })

  const mountView = async (route = '/banners/create', props = {}) => {
    const router = createTestRouter()
    router.push(route)
    await router.isReady()

    const wrapper = mount(BannerFormView, {
      props,
      global: {
        plugins: [createPinia(), router, PrimeVue, ToastService],
        stubs: {
          Card: {
            template: '<div class="card-stub"><slot name="title" /><slot name="content" /></div>',
          },
          ImagePicker: true,
          ProgressSpinner: true,
        },
      },
    })

    await flushPromises()
    return { wrapper, router }
  }

  describe('create mode', () => {
    it('renders create form with correct title', async () => {
      const { wrapper } = await mountView('/banners/create')
      expect(wrapper.text()).toContain('Create Banner')
    })

    it('shows empty form fields', async () => {
      const { wrapper } = await mountView('/banners/create')
      const titleInput = wrapper.find('#title')
      expect(titleInput.exists()).toBe(true)
    })

    it('navigates back on cancel', async () => {
      const { wrapper, router } = await mountView('/banners/create')
      const pushSpy = vi.spyOn(router, 'push')

      const backButton = wrapper.find('button[aria-label="Back to Banners"]')
      if (backButton.exists()) {
        await backButton.trigger('click')
      } else {
        // Find by text
        const buttons = wrapper.findAll('button')
        const cancelBtn = buttons.find((b) => b.text().includes('Cancel'))
        if (cancelBtn) await cancelBtn.trigger('click')
      }

      expect(pushSpy).toHaveBeenCalledWith({ name: 'banners' })
    })

    it('validates required fields', async () => {
      const { wrapper } = await mountView('/banners/create')

      const form = wrapper.find('form')
      await form.trigger('submit')
      await flushPromises()

      expect(wrapper.text()).toContain('Title is required')
      expect(wrapper.text()).toContain('Desktop URL is required')
    })

    it('creates banner on valid submit', async () => {
      const { wrapper, router } = await mountView('/banners/create')
      const pushSpy = vi.spyOn(router, 'push')

      await wrapper.find('#title').setValue('New Banner')
      await wrapper.find('#desktop_url').setValue('https://example.com')

      const form = wrapper.find('form')
      await form.trigger('submit')
      await flushPromises()

      expect(mockCreateBanner).toHaveBeenCalled()
      expect(pushSpy).toHaveBeenCalledWith({ name: 'banners' })
    })
  })

  describe('edit mode', () => {
    it('renders edit form with correct title', async () => {
      const { wrapper } = await mountView('/banners/1', { id: '1' })
      await flushPromises()
      expect(wrapper.text()).toContain('Edit Banner')
    })

    it('loads banner data on mount', async () => {
      await mountView('/banners/1', { id: '1' })
      await flushPromises()

      expect(mockGetBanner).toHaveBeenCalledWith(1)
    })

    it('updates banner on valid submit', async () => {
      const { wrapper, router } = await mountView('/banners/1', { id: '1' })
      await flushPromises()
      const pushSpy = vi.spyOn(router, 'push')

      await wrapper.find('#title').setValue('Updated Banner')

      const form = wrapper.find('form')
      await form.trigger('submit')
      await flushPromises()

      expect(mockUpdateBanner).toHaveBeenCalled()
      expect(pushSpy).toHaveBeenCalledWith({ name: 'banners' })
    })
  })

  describe('validation', () => {
    it('validates URL format', async () => {
      const { wrapper } = await mountView('/banners/create')

      await wrapper.find('#title').setValue('Test')
      await wrapper.find('#desktop_url').setValue('not-a-url')

      const form = wrapper.find('form')
      await form.trigger('submit')
      await flushPromises()

      expect(wrapper.text()).toContain('Please enter a valid URL')
    })

    it('validates mobile URL format when provided', async () => {
      const { wrapper } = await mountView('/banners/create')

      await wrapper.find('#title').setValue('Test')
      await wrapper.find('#desktop_url').setValue('https://example.com')
      await wrapper.find('#mobile_url').setValue('invalid-url')

      const form = wrapper.find('form')
      await form.trigger('submit')
      await flushPromises()

      expect(wrapper.text()).toContain('Please enter a valid URL')
    })
  })
})
