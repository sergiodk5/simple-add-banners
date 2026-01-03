import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import PrimeVue from 'primevue/config'
import ConfirmationService from 'primevue/confirmationservice'
import ToastService from 'primevue/toastservice'
import App from '../App.vue'
import type { Banner } from '@/types/banner'

const mockGetBanners = vi.fn()

// Mock the bannerApi module
vi.mock('@/services/bannerApi', () => ({
  getBanners: (...args: unknown[]) => mockGetBanners(...args),
  getBanner: vi.fn(),
  createBanner: vi.fn(),
  updateBanner: vi.fn(),
  deleteBanner: vi.fn(),
}))

// Mock window.sabAdmin
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

describe('App', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    mockGetBanners.mockResolvedValue({ data: [], total: 0, totalPages: 0 })
  })

  const mountApp = () => {
    return mount(App, {
      global: {
        plugins: [createPinia(), PrimeVue, ConfirmationService, ToastService],
        stubs: {
          Toast: true,
          ConfirmDialog: true,
          DataTable: {
            template: '<div class="datatable-stub"><slot name="empty" /></div>',
          },
          Column: true,
          Dialog: true,
        },
      },
    })
  }

  it('renders the app container', () => {
    const wrapper = mountApp()
    expect(wrapper.find('.tw\\:p-4').exists()).toBe(true)
  })

  it('renders the BannerList component', () => {
    const wrapper = mountApp()
    expect(wrapper.findComponent({ name: 'BannerList' }).exists()).toBe(true)
  })

  it('renders the BannerForm component', () => {
    const wrapper = mountApp()
    expect(wrapper.findComponent({ name: 'BannerForm' }).exists()).toBe(true)
  })

  it('has the form dialog hidden by default', () => {
    const wrapper = mountApp()
    const bannerForm = wrapper.findComponent({ name: 'BannerForm' })
    expect(bannerForm.props('visible')).toBe(false)
  })

  it('shows Add Banner button', () => {
    const wrapper = mountApp()
    expect(wrapper.text()).toContain('Add Banner')
  })

  it('displays Banners heading', () => {
    const wrapper = mountApp()
    expect(wrapper.text()).toContain('Banners')
  })

  it('shows empty state message when no banners', () => {
    const wrapper = mountApp()
    expect(wrapper.text()).toContain('No banners found')
  })
})

describe('App event handlers', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    mockGetBanners.mockResolvedValue({ data: [], total: 0, totalPages: 0 })
  })

  it('opens create dialog when BannerList emits create', async () => {
    const wrapper = mount(App, {
      global: {
        plugins: [createPinia(), PrimeVue, ConfirmationService, ToastService],
        stubs: {
          Toast: true,
          ConfirmDialog: true,
          DataTable: {
            template: '<div class="datatable-stub"><slot name="empty" /></div>',
          },
          Column: true,
          Dialog: true,
        },
      },
    })

    await flushPromises()

    const bannerList = wrapper.findComponent({ name: 'BannerList' })
    await bannerList.vm.$emit('create')

    const bannerForm = wrapper.findComponent({ name: 'BannerForm' })
    expect(bannerForm.props('visible')).toBe(true)
    expect(bannerForm.props('banner')).toBeNull()
  })

  it('opens edit dialog when BannerList emits edit', async () => {
    const wrapper = mount(App, {
      global: {
        plugins: [createPinia(), PrimeVue, ConfirmationService, ToastService],
        stubs: {
          Toast: true,
          ConfirmDialog: true,
          DataTable: {
            template: '<div class="datatable-stub"><slot name="empty" /></div>',
          },
          Column: true,
          Dialog: true,
        },
      },
    })

    await flushPromises()

    const bannerList = wrapper.findComponent({ name: 'BannerList' })
    await bannerList.vm.$emit('edit', mockBanner)

    const bannerForm = wrapper.findComponent({ name: 'BannerForm' })
    expect(bannerForm.props('visible')).toBe(true)
    expect(bannerForm.props('banner')).toEqual(mockBanner)
  })

  it('reloads banners when BannerForm emits saved', async () => {
    mockGetBanners.mockResolvedValue({ data: [mockBanner], total: 1, totalPages: 1 })

    const wrapper = mount(App, {
      global: {
        plugins: [createPinia(), PrimeVue, ConfirmationService, ToastService],
        stubs: {
          Toast: true,
          ConfirmDialog: true,
          DataTable: {
            template: '<div class="datatable-stub"><slot name="empty" /></div>',
          },
          Column: true,
          Dialog: true,
        },
      },
    })

    await flushPromises()
    vi.clearAllMocks()
    mockGetBanners.mockResolvedValue({ data: [mockBanner], total: 1, totalPages: 1 })

    const bannerForm = wrapper.findComponent({ name: 'BannerForm' })
    await bannerForm.vm.$emit('saved')
    await flushPromises()

    expect(mockGetBanners).toHaveBeenCalled()
  })
})
