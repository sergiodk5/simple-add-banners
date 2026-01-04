import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createMemoryHistory } from 'vue-router'
import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'
import PlacementBannersView from '@/views/PlacementBannersView.vue'
import type { Placement } from '@/types/placement'
import type { Banner } from '@/types/banner'

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

const mockBanners: Banner[] = [
  {
    id: 1,
    title: 'Banner 1',
    desktop_image_id: null,
    mobile_image_id: null,
    desktop_url: 'https://example.com/1',
    mobile_url: null,
    start_date: null,
    end_date: null,
    status: 'active',
    weight: 1,
    created_at: '2024-01-01T00:00:00',
    updated_at: '2024-01-01T00:00:00',
  },
  {
    id: 2,
    title: 'Banner 2',
    desktop_image_id: null,
    mobile_image_id: null,
    desktop_url: 'https://example.com/2',
    mobile_url: null,
    start_date: null,
    end_date: null,
    status: 'paused',
    weight: 2,
    created_at: '2024-01-01T00:00:00',
    updated_at: '2024-01-01T00:00:00',
  },
]

const mockGetPlacement = vi.fn()
const mockGetBanners = vi.fn()
const mockGetBannersForPlacement = vi.fn()
const mockSyncBannersForPlacement = vi.fn()

vi.mock('@/services/placementApi', () => ({
  getPlacements: vi.fn().mockResolvedValue({ data: [], total: 0, totalPages: 0 }),
  getPlacement: (...args: unknown[]) => mockGetPlacement(...args),
}))

vi.mock('@/services/bannerApi', () => ({
  getBanners: (...args: unknown[]) => mockGetBanners(...args),
}))

vi.mock('@/services/bannerPlacementApi', () => ({
  getBannersForPlacement: (...args: unknown[]) => mockGetBannersForPlacement(...args),
  syncBannersForPlacement: (...args: unknown[]) => mockSyncBannersForPlacement(...args),
}))

const createTestRouter = () => {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/placements', name: 'placements', component: { template: '<div />' } },
      {
        path: '/placements/:id/banners',
        name: 'placement-banners',
        component: { template: '<div />' },
        props: true,
      },
    ],
  })
}

describe('PlacementBannersView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    mockGetPlacement.mockResolvedValue(mockPlacement)
    mockGetBanners.mockResolvedValue({ data: mockBanners, total: 2, totalPages: 1 })
    mockGetBannersForPlacement.mockResolvedValue([mockBanners[0]])
    mockSyncBannersForPlacement.mockResolvedValue([])
  })

  const mountView = async (props = { id: '1' }) => {
    const router = createTestRouter()
    router.push('/placements/1/banners')
    await router.isReady()

    const wrapper = mount(PlacementBannersView, {
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

  it('renders with placement name in title', async () => {
    const { wrapper } = await mountView()
    expect(wrapper.text()).toContain('Manage Banners')
    expect(wrapper.text()).toContain('Header Banner')
  })

  it('loads placement and banners on mount', async () => {
    await mountView()

    expect(mockGetPlacement).toHaveBeenCalledWith(1)
    expect(mockGetBanners).toHaveBeenCalled()
    expect(mockGetBannersForPlacement).toHaveBeenCalledWith(1)
  })

  it('shows all available banners', async () => {
    const { wrapper } = await mountView()

    expect(wrapper.text()).toContain('Banner 1')
    expect(wrapper.text()).toContain('Banner 2')
  })

  it('shows assigned banner as selected', async () => {
    const { wrapper } = await mountView()

    // Banner 1 should be selected (it was returned by getBannersForPlacement)
    const checkboxes = wrapper.findAllComponents({ name: 'Checkbox' })
    expect(checkboxes.length).toBeGreaterThan(0)
  })

  it('shows selected count', async () => {
    const { wrapper } = await mountView()
    expect(wrapper.text()).toContain('1 banner(s) selected')
  })

  it('toggles banner selection on click', async () => {
    const { wrapper } = await mountView()

    // Find the banner row for Banner 2 and click it
    const bannerRows = wrapper.findAll('.tw\\:cursor-pointer')
    expect(bannerRows.length).toBeGreaterThan(0)

    await bannerRows[1].trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('2 banner(s) selected')
  })

  it('saves banner assignments', async () => {
    const { wrapper, router } = await mountView()
    const pushSpy = vi.spyOn(router, 'push')

    const saveButton = wrapper.findAll('button').find((b) => b.text().includes('Save'))
    expect(saveButton).toBeDefined()

    await saveButton!.trigger('click')
    await flushPromises()

    expect(mockSyncBannersForPlacement).toHaveBeenCalledWith(1, [1])
    expect(pushSpy).toHaveBeenCalledWith({ name: 'placements' })
  })

  it('navigates back on cancel', async () => {
    const { wrapper, router } = await mountView()
    const pushSpy = vi.spyOn(router, 'push')

    const cancelButton = wrapper.findAll('button').find((b) => b.text().includes('Cancel'))
    await cancelButton!.trigger('click')

    expect(pushSpy).toHaveBeenCalledWith({ name: 'placements' })
  })

  it('shows empty state when no banners available', async () => {
    mockGetBanners.mockResolvedValue({ data: [], total: 0, totalPages: 0 })

    const { wrapper } = await mountView()

    expect(wrapper.text()).toContain('No banners available')
  })

  it('displays banner status with correct styling', async () => {
    const { wrapper } = await mountView()

    expect(wrapper.text()).toContain('active')
    expect(wrapper.text()).toContain('paused')
  })

  it('displays banner weight', async () => {
    const { wrapper } = await mountView()

    expect(wrapper.text()).toContain('Weight: 1')
    expect(wrapper.text()).toContain('Weight: 2')
  })
})
