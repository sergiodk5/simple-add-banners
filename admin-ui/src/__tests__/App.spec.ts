import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createMemoryHistory } from 'vue-router'
import PrimeVue from 'primevue/config'
import ConfirmationService from 'primevue/confirmationservice'
import ToastService from 'primevue/toastservice'
import App from '../App.vue'

// Mock window.sabAdmin
vi.stubGlobal('sabAdmin', {
  apiUrl: '/wp-json/sab/v1',
  nonce: 'test-nonce',
  adminUrl: '/wp-admin/',
})

// Mock API calls
vi.mock('@/services/bannerApi', () => ({
  getBanners: vi.fn().mockResolvedValue({ data: [], total: 0, totalPages: 0 }),
  getBanner: vi.fn(),
  createBanner: vi.fn(),
  updateBanner: vi.fn(),
  deleteBanner: vi.fn(),
}))

vi.mock('@/services/placementApi', () => ({
  getPlacements: vi.fn().mockResolvedValue({ data: [], total: 0, totalPages: 0 }),
  getPlacement: vi.fn(),
  createPlacement: vi.fn(),
  updatePlacement: vi.fn(),
  deletePlacement: vi.fn(),
}))

const createTestRouter = () => {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/', redirect: '/banners' },
      {
        path: '/banners',
        name: 'banners',
        component: { template: '<div>Banner List</div>' },
        meta: { tab: 0 },
      },
      {
        path: '/banners/create',
        name: 'banner-create',
        component: { template: '<div>Banner Create</div>' },
        meta: { tab: 0 },
      },
      {
        path: '/banners/:id',
        name: 'banner-edit',
        component: { template: '<div>Banner Edit</div>' },
        meta: { tab: 0 },
      },
      {
        path: '/placements',
        name: 'placements',
        component: { template: '<div>Placement List</div>' },
        meta: { tab: 1 },
      },
      {
        path: '/placements/create',
        name: 'placement-create',
        component: { template: '<div>Placement Create</div>' },
        meta: { tab: 1 },
      },
      {
        path: '/placements/:id',
        name: 'placement-edit',
        component: { template: '<div>Placement Edit</div>' },
        meta: { tab: 1 },
      },
      {
        path: '/placements/:id/banners',
        name: 'placement-banners',
        component: { template: '<div>Placement Banners</div>' },
        meta: { tab: 1 },
      },
      {
        path: '/statistics',
        name: 'statistics',
        component: { template: '<div>Statistics</div>' },
        meta: { tab: 2 },
      },
      {
        path: '/statistics/banners/:id',
        name: 'banner-statistics',
        component: { template: '<div>Banner Statistics</div>' },
        meta: { tab: 2 },
      },
    ],
  })
}

describe('App', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  const mountApp = async (initialRoute = '/banners') => {
    const router = createTestRouter()
    router.push(initialRoute)
    await router.isReady()

    const wrapper = mount(App, {
      global: {
        plugins: [createPinia(), router, PrimeVue, ConfirmationService, ToastService],
        stubs: {
          Toast: true,
        },
      },
    })

    await flushPromises()
    return { wrapper, router }
  }

  it('renders the app container', async () => {
    const { wrapper } = await mountApp()
    expect(wrapper.find('.tw\\:p-4').exists()).toBe(true)
  })

  it('renders the navigation menu', async () => {
    const { wrapper } = await mountApp()
    expect(wrapper.find('nav').exists()).toBe(true)
  })

  it('has Banners, Placements, and Statistics menu items', async () => {
    const { wrapper } = await mountApp()
    const links = wrapper.findAll('nav a')
    expect(links.length).toBe(3)
    expect(wrapper.text()).toContain('Banners')
    expect(wrapper.text()).toContain('Placements')
    expect(wrapper.text()).toContain('Statistics')
  })

  it('shows Banners link as active on banners route', async () => {
    const { wrapper } = await mountApp('/banners')
    const bannersLink = wrapper.find('nav a[href="/banners"]')
    expect(bannersLink.classes()).toContain('tw:bg-primary-100')
  })

  it('shows Placements link as active on placements route', async () => {
    const { wrapper } = await mountApp('/placements')
    const placementsLink = wrapper.find('nav a[href="/placements"]')
    expect(placementsLink.classes()).toContain('tw:bg-primary-100')
  })

  it('renders router-view content', async () => {
    const { wrapper } = await mountApp()
    expect(wrapper.text()).toContain('Banner List')
  })
})

describe('App routing', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  const mountApp = async (initialRoute = '/banners') => {
    const router = createTestRouter()
    router.push(initialRoute)
    await router.isReady()

    const wrapper = mount(App, {
      global: {
        plugins: [createPinia(), router, PrimeVue, ConfirmationService, ToastService],
        stubs: {
          Toast: true,
        },
      },
    })

    await flushPromises()
    return { wrapper, router }
  }

  it('navigates to banner list on initial load', async () => {
    const { wrapper } = await mountApp('/')
    await flushPromises()
    expect(wrapper.text()).toContain('Banner List')
  })

  it('navigates to placement list route', async () => {
    const { wrapper, router } = await mountApp()
    await router.push('/placements')
    await flushPromises()
    expect(wrapper.text()).toContain('Placement List')
  })

  it('navigates to banner create route', async () => {
    const { wrapper, router } = await mountApp()
    await router.push('/banners/create')
    await flushPromises()
    expect(wrapper.text()).toContain('Banner Create')
  })

  it('navigates to placement banners route', async () => {
    const { wrapper, router } = await mountApp()
    await router.push('/placements/1/banners')
    await flushPromises()
    expect(wrapper.text()).toContain('Placement Banners')
  })

  it('updates active link when navigating between sections', async () => {
    const { wrapper, router } = await mountApp('/banners')

    let bannersLink = wrapper.find('nav a[href="/banners"]')
    expect(bannersLink.classes()).toContain('tw:bg-primary-100')

    await router.push('/placements')
    await flushPromises()

    const placementsLink = wrapper.find('nav a[href="/placements"]')
    expect(placementsLink.classes()).toContain('tw:bg-primary-100')

    bannersLink = wrapper.find('nav a[href="/banners"]')
    expect(bannersLink.classes()).not.toContain('tw:bg-primary-100')
  })
})
