<script setup lang="ts">
import { useRouter } from 'vue-router'
import BannerList from '@/components/BannerList.vue'
import type { Banner } from '@/types/banner'

const router = useRouter()

const handleCreate = () => {
  router.push({ name: 'banner-create' })
}

const handleEdit = (banner: Banner) => {
  router.push({ name: 'banner-edit', params: { id: banner.id } })
}
</script>

<template>
  <BannerList
    @create="handleCreate"
    @edit="handleEdit"
  />
</template>
