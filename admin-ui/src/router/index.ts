import type { RouteRecordRaw } from 'vue-router'
import { createRouter, createWebHashHistory } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    redirect: '/banners',
  },
  {
    path: '/banners',
    name: 'banners',
    component: () => import('@/views/BannerListView.vue'),
    meta: { tab: 0 },
  },
  {
    path: '/banners/create',
    name: 'banner-create',
    component: () => import('@/views/BannerFormView.vue'),
    meta: { tab: 0 },
  },
  {
    path: '/banners/:id',
    name: 'banner-edit',
    component: () => import('@/views/BannerFormView.vue'),
    props: true,
    meta: { tab: 0 },
  },
  {
    path: '/placements',
    name: 'placements',
    component: () => import('@/views/PlacementListView.vue'),
    meta: { tab: 1 },
  },
  {
    path: '/placements/create',
    name: 'placement-create',
    component: () => import('@/views/PlacementFormView.vue'),
    meta: { tab: 1 },
  },
  {
    path: '/placements/:id',
    name: 'placement-edit',
    component: () => import('@/views/PlacementFormView.vue'),
    props: true,
    meta: { tab: 1 },
  },
  {
    path: '/placements/:id/banners',
    name: 'placement-banners',
    component: () => import('@/views/PlacementBannersView.vue'),
    props: true,
    meta: { tab: 1 },
  },
]

const router = createRouter({
  history: createWebHashHistory(),
  routes,
})

export default router
