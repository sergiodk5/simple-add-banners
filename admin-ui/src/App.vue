<script setup lang="ts">
import { ref } from 'vue'
import Toast from 'primevue/toast'
import BannerList from '@/components/BannerList.vue'
import BannerForm from '@/components/BannerForm.vue'
import type { Banner } from '@/types/banner'

const bannerListRef = ref<InstanceType<typeof BannerList> | null>(null)

const formVisible = ref(false)
const editingBanner = ref<Banner | null>(null)

const handleCreate = () => {
  editingBanner.value = null
  formVisible.value = true
}

const handleEdit = (banner: Banner) => {
  editingBanner.value = banner
  formVisible.value = true
}

const handleSaved = () => {
  bannerListRef.value?.loadBanners()
}
</script>

<template>
  <div class="tw:p-4">
    <Toast />

    <BannerList
      ref="bannerListRef"
      @create="handleCreate"
      @edit="handleEdit"
    />

    <BannerForm
      v-model:visible="formVisible"
      :banner="editingBanner"
      @saved="handleSaved"
    />
  </div>
</template>
