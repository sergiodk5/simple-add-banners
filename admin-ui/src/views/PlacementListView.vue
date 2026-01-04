<script setup lang="ts">
import { useRouter } from 'vue-router'
import PlacementList from '@/components/PlacementList.vue'
import type { Placement } from '@/types/placement'

const router = useRouter()

const handleCreate = () => {
  router.push({ name: 'placement-create' })
}

const handleEdit = (placement: Placement) => {
  router.push({ name: 'placement-edit', params: { id: placement.id } })
}

const handleManageBanners = (placement: Placement) => {
  router.push({ name: 'placement-banners', params: { id: placement.id } })
}
</script>

<template>
  <PlacementList
    @create="handleCreate"
    @edit="handleEdit"
    @manage-banners="handleManageBanners"
  />
</template>
