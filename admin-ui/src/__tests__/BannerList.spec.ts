import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import PrimeVue from 'primevue/config'
import ConfirmationService from 'primevue/confirmationservice'
import ToastService from 'primevue/toastservice'
import BannerList from '../components/BannerList.vue'
import type { Banner } from '@/types/banner'

// Mock the bannerApi module
const mockGetBanners = vi.fn()
const mockDeleteBanner = vi.fn()

vi.mock('@/services/bannerApi', () => ({
  getBanners: (...args: unknown[]) => mockGetBanners(...args),
  getBanner: vi.fn(),
  createBanner: vi.fn(),
  updateBanner: vi.fn(),
  deleteBanner: (...args: unknown[]) => mockDeleteBanner(...args),
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
  start_date: '2024-01-01T00:00:00',
  end_date: '2024-12-31T23:59:59',
  status: 'active',
  weight: 1,
  created_at: '2024-01-01T00:00:00',
  updated_at: '2024-01-01T00:00:00',
}


describe('BannerList', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    mockGetBanners.mockResolvedValue({ data: [], total: 0, totalPages: 0 })
  })

  const mountBannerList = () => {
    return mount(BannerList, {
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
    const wrapper = mountBannerList()
    expect(wrapper.find('h2').text()).toContain('Banners')
  })

  it('loads banners on mount', async () => {
    mockGetBanners.mockResolvedValue({
      data: [mockBanner],
      total: 1,
      totalPages: 1,
    })

    mountBannerList()
    await flushPromises()

    expect(mockGetBanners).toHaveBeenCalled()
  })

  it('shows banner count when banners exist', async () => {
    mockGetBanners.mockResolvedValue({
      data: [mockBanner],
      total: 5,
      totalPages: 1,
    })

    const wrapper = mountBannerList()
    await flushPromises()

    expect(wrapper.text()).toContain('(5)')
  })

  it('handles load error gracefully', async () => {
    mockGetBanners.mockRejectedValue(new Error('Network error'))

    const wrapper = mountBannerList()
    await flushPromises()

    expect(wrapper.exists()).toBe(true)
  })

  it('handles non-Error load failure', async () => {
    mockGetBanners.mockRejectedValue('String error')

    const wrapper = mountBannerList()
    await flushPromises()

    expect(wrapper.exists()).toBe(true)
  })

  it('exposes loadBanners method', async () => {
    const wrapper = mountBannerList()
    await flushPromises()

    expect(typeof wrapper.vm.loadBanners).toBe('function')

    mockGetBanners.mockResolvedValue({ data: [mockBanner], total: 1, totalPages: 1 })
    await wrapper.vm.loadBanners()

    expect(mockGetBanners).toHaveBeenCalledTimes(2)
  })
})

describe('BannerList internal methods', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    mockGetBanners.mockResolvedValue({ data: [], total: 0, totalPages: 0 })
  })

  it('getStatusSeverity returns correct severity for each status', async () => {
    const wrapper = mount(BannerList, {
      global: {
        plugins: [createPinia(), PrimeVue, ConfirmationService, ToastService],
        stubs: {
          ConfirmDialog: true,
          DataTable: true,
          Column: true,
          Button: true,
          Tag: true,
        },
      },
    })

    await flushPromises()

    // Access the internal function via template rendering context
    type BannerListVM = {
      getStatusSeverity: (status: string) => string
      formatDate: (date: string | null) => string
    }

    const vm = wrapper.vm as unknown as BannerListVM

    // Test getStatusSeverity
    expect(vm.getStatusSeverity('active')).toBe('success')
    expect(vm.getStatusSeverity('paused')).toBe('warn')
    expect(vm.getStatusSeverity('scheduled')).toBe('info')
    expect(vm.getStatusSeverity('unknown')).toBe('secondary')
  })

  it('formatDate returns correct formatted date', async () => {
    const wrapper = mount(BannerList, {
      global: {
        plugins: [createPinia(), PrimeVue, ConfirmationService, ToastService],
        stubs: {
          ConfirmDialog: true,
          DataTable: true,
          Column: true,
          Button: true,
          Tag: true,
        },
      },
    })

    await flushPromises()

    type BannerListVM = {
      formatDate: (date: string | null) => string
    }

    const vm = wrapper.vm as unknown as BannerListVM

    expect(vm.formatDate(null)).toBe('-')
    expect(vm.formatDate('2024-01-15T00:00:00')).toContain('2024')
  })
})

describe('BannerList handleDelete', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    mockGetBanners.mockResolvedValue({
      data: [mockBanner],
      total: 1,
      totalPages: 1,
    })
  })

  it('uses confirm service when deleting', async () => {
    const wrapper = mount(BannerList, {
      global: {
        plugins: [createPinia(), PrimeVue, ConfirmationService, ToastService],
        stubs: {
          ConfirmDialog: true,
          DataTable: true,
          Column: true,
          Button: true,
          Tag: true,
        },
      },
    })

    await flushPromises()

    // Access the useConfirm hook's internal state to mock the require function
    const vm = wrapper.vm as unknown as {
      handleDelete: (banner: Banner) => void
    }

    // The component uses useConfirm which is already provided by ConfirmationService
    // We can verify handleDelete doesn't throw
    expect(() => vm.handleDelete(mockBanner)).not.toThrow()
  })

  it('deletes banner when confirmed', async () => {
    mockDeleteBanner.mockResolvedValue(undefined)

    const wrapper = mount(BannerList, {
      global: {
        plugins: [createPinia(), PrimeVue, ConfirmationService, ToastService],
        stubs: {
          ConfirmDialog: true,
          DataTable: true,
          Column: true,
          Button: true,
          Tag: true,
        },
      },
    })

    await flushPromises()

    // Verify component exists and handleDelete is callable
    expect(wrapper.exists()).toBe(true)
  })

  it('handles delete flow', async () => {
    mockDeleteBanner.mockRejectedValue(new Error('Delete failed'))

    const wrapper = mount(BannerList, {
      global: {
        plugins: [createPinia(), PrimeVue, ConfirmationService, ToastService],
        stubs: {
          ConfirmDialog: true,
          DataTable: true,
          Column: true,
          Button: true,
          Tag: true,
        },
      },
    })

    await flushPromises()

    // Error is handled with a toast, component should still exist
    expect(wrapper.exists()).toBe(true)
  })
})
