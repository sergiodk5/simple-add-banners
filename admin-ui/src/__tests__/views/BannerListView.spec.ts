import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createMemoryHistory } from 'vue-router'
import PrimeVue from 'primevue/config'
import ConfirmationService from 'primevue/confirmationservice'
import ToastService from 'primevue/toastservice'
import BannerListView from '@/views/BannerListView.vue'

vi.stubGlobal('sabAdmin', {
  apiUrl: '/wp-json/sab/v1',
  nonce: 'test-nonce',
  adminUrl: '/wp-admin/',
})

vi.mock('@/services/bannerApi', () => ({
  getBanners: vi.fn().mockResolvedValue({ data: [], total: 0, totalPages: 0 }),
  deleteBanner: vi.fn(),
}))

const createTestRouter = () => {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/banners', name: 'banners', component: { template: '<div />' } },
      { path: '/banners/create', name: 'banner-create', component: { template: '<div />' } },
      { path: '/banners/:id', name: 'banner-edit', component: { template: '<div />' } },
    ],
  })
}

describe('BannerListView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  const mountView = async () => {
    const router = createTestRouter()
    router.push('/banners')
    await router.isReady()

    const wrapper = mount(BannerListView, {
      global: {
        plugins: [createPinia(), router, PrimeVue, ConfirmationService, ToastService],
        stubs: {
          ConfirmDialog: true,
          DataTable: {
            template: '<div class="datatable-stub"><slot name="empty" /></div>',
          },
          Column: true,
        },
      },
    })

    await flushPromises()
    return { wrapper, router }
  }

  it('renders BannerList component', async () => {
    const { wrapper } = await mountView()
    expect(wrapper.findComponent({ name: 'BannerList' }).exists()).toBe(true)
  })

  it('navigates to create route on create event', async () => {
    const { wrapper, router } = await mountView()
    const pushSpy = vi.spyOn(router, 'push')

    const bannerList = wrapper.findComponent({ name: 'BannerList' })
    await bannerList.vm.$emit('create')

    expect(pushSpy).toHaveBeenCalledWith({ name: 'banner-create' })
  })

  it('navigates to edit route on edit event', async () => {
    const { wrapper, router } = await mountView()
    const pushSpy = vi.spyOn(router, 'push')

    const bannerList = wrapper.findComponent({ name: 'BannerList' })
    await bannerList.vm.$emit('edit', { id: 123, title: 'Test Banner' })

    expect(pushSpy).toHaveBeenCalledWith({ name: 'banner-edit', params: { id: 123 } })
  })
})
