import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createMemoryHistory } from 'vue-router'
import PrimeVue from 'primevue/config'
import ConfirmationService from 'primevue/confirmationservice'
import ToastService from 'primevue/toastservice'
import Tooltip from 'primevue/tooltip'
import PlacementListView from '@/views/PlacementListView.vue'

vi.stubGlobal('sabAdmin', {
  apiUrl: '/wp-json/sab/v1',
  nonce: 'test-nonce',
  adminUrl: '/wp-admin/',
})

vi.mock('@/services/placementApi', () => ({
  getPlacements: vi.fn().mockResolvedValue({ data: [], total: 0, totalPages: 0 }),
  deletePlacement: vi.fn(),
}))

const createTestRouter = () => {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/placements', name: 'placements', component: { template: '<div />' } },
      { path: '/placements/create', name: 'placement-create', component: { template: '<div />' } },
      { path: '/placements/:id', name: 'placement-edit', component: { template: '<div />' } },
      { path: '/placements/:id/banners', name: 'placement-banners', component: { template: '<div />' } },
    ],
  })
}

describe('PlacementListView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  const mountView = async () => {
    const router = createTestRouter()
    router.push('/placements')
    await router.isReady()

    const wrapper = mount(PlacementListView, {
      global: {
        plugins: [createPinia(), router, PrimeVue, ConfirmationService, ToastService],
        directives: {
          tooltip: Tooltip,
        },
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

  it('renders PlacementList component', async () => {
    const { wrapper } = await mountView()
    expect(wrapper.findComponent({ name: 'PlacementList' }).exists()).toBe(true)
  })

  it('navigates to create route on create event', async () => {
    const { wrapper, router } = await mountView()
    const pushSpy = vi.spyOn(router, 'push')

    const placementList = wrapper.findComponent({ name: 'PlacementList' })
    await placementList.vm.$emit('create')

    expect(pushSpy).toHaveBeenCalledWith({ name: 'placement-create' })
  })

  it('navigates to edit route on edit event', async () => {
    const { wrapper, router } = await mountView()
    const pushSpy = vi.spyOn(router, 'push')

    const placementList = wrapper.findComponent({ name: 'PlacementList' })
    await placementList.vm.$emit('edit', { id: 456, name: 'Test Placement' })

    expect(pushSpy).toHaveBeenCalledWith({ name: 'placement-edit', params: { id: 456 } })
  })

  it('navigates to banners route on manage-banners event', async () => {
    const { wrapper, router } = await mountView()
    const pushSpy = vi.spyOn(router, 'push')

    const placementList = wrapper.findComponent({ name: 'PlacementList' })
    await placementList.vm.$emit('manage-banners', { id: 789, name: 'Test Placement' })

    expect(pushSpy).toHaveBeenCalledWith({ name: 'placement-banners', params: { id: 789 } })
  })
})
