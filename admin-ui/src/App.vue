<script setup lang="ts">
import { useRoute } from 'vue-router'
import Toast from 'primevue/toast'

const route = useRoute()

const menuItems = [
  { label: 'Banners', icon: 'pi pi-images', route: '/banners', tab: 0 },
  { label: 'Placements', icon: 'pi pi-th-large', route: '/placements', tab: 1 },
]

const isActive = (tab: number) => {
  return route.meta.tab === tab
}
</script>

<template>
  <div class="tw:p-4">
    <Toast />

    <nav class="tw:flex tw:gap-1 tw:mb-6 tw:border-b tw:border-gray-200 tw:pb-2">
      <router-link
        v-for="item in menuItems"
        :key="item.route"
        :to="item.route"
        class="tw:flex tw:items-center tw:gap-2 tw:px-4 tw:py-2 tw:rounded-md tw:text-sm tw:font-medium tw:transition-colors tw:no-underline"
        :class="[
          isActive(item.tab)
            ? 'tw:bg-primary-100 tw:text-primary-700'
            : 'tw:text-gray-600 tw:hover:bg-gray-100 tw:hover:text-gray-900',
        ]"
      >
        <i
          :class="item.icon"
          class="tw:text-base"
        />
        {{ item.label }}
      </router-link>
    </nav>

    <router-view />
  </div>
</template>
