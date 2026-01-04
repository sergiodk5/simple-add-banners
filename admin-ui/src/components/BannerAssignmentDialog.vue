<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import ProgressSpinner from 'primevue/progressspinner'
import { useToast } from 'primevue/usetoast'
import { getBanners } from '@/services/bannerApi'
import { getBannersForPlacement, syncBannersForPlacement } from '@/services/bannerPlacementApi'
import type { Placement } from '@/types/placement'
import type { Banner } from '@/types/banner'

const props = defineProps<{
  visible: boolean
  placement: Placement | null
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  saved: []
}>()

const toast = useToast()

const dialogVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value),
})

const dialogTitle = computed(() =>
  props.placement ? `Manage Banners - ${props.placement.name}` : 'Manage Banners',
)

const loading = ref(false)
const saving = ref(false)
const allBanners = ref<Banner[]>([])
const selectedBannerIds = ref<number[]>([])

const loadData = async () => {
  if (!props.placement) return

  loading.value = true
  try {
    const [bannersResponse, assignedBanners] = await Promise.all([
      getBanners({ per_page: 100 }),
      getBannersForPlacement(props.placement.id),
    ])

    allBanners.value = bannersResponse.data
    selectedBannerIds.value = assignedBanners.map((b) => b.id)
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error instanceof Error ? error.message : 'Failed to load data',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

watch(
  () => props.visible,
  (visible) => {
    if (visible && props.placement) {
      loadData()
    }
  },
)

const handleSave = async () => {
  if (!props.placement) return

  saving.value = true
  try {
    await syncBannersForPlacement(props.placement.id, selectedBannerIds.value)
    toast.add({
      severity: 'success',
      summary: 'Saved',
      detail: 'Banner assignments updated successfully',
      life: 3000,
    })
    emit('saved')
    dialogVisible.value = false
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error instanceof Error ? error.message : 'Failed to save assignments',
      life: 3000,
    })
  } finally {
    saving.value = false
  }
}

const handleCancel = () => {
  dialogVisible.value = false
}

const toggleBanner = (bannerId: number) => {
  const index = selectedBannerIds.value.indexOf(bannerId)
  if (index === -1) {
    selectedBannerIds.value.push(bannerId)
  } else {
    selectedBannerIds.value.splice(index, 1)
  }
}

const isBannerSelected = (bannerId: number) => {
  return selectedBannerIds.value.includes(bannerId)
}

const getStatusClass = (status: string) => {
  switch (status) {
    case 'active':
      return 'tw:text-green-600'
    case 'paused':
      return 'tw:text-yellow-600'
    case 'scheduled':
      return 'tw:text-blue-600'
    default:
      return 'tw:text-gray-600'
  }
}
</script>

<template>
  <Dialog
    v-model:visible="dialogVisible"
    :header="dialogTitle"
    modal
    :style="{ width: '500px' }"
    :closable="!saving"
    :close-on-escape="!saving"
  >
    <div
      v-if="loading"
      class="tw:flex tw:justify-center tw:py-8"
    >
      <ProgressSpinner />
    </div>

    <div
      v-else-if="allBanners.length === 0"
      class="tw:text-center tw:py-8 tw:text-gray-500"
    >
      No banners available. Create banners first before assigning them to placements.
    </div>

    <div
      v-else
      class="tw:flex tw:flex-col tw:gap-2"
    >
      <p class="tw:text-sm tw:text-gray-600 tw:mb-2">
        Select banners to display in this placement:
      </p>
      <div class="tw:max-h-80 tw:overflow-y-auto tw:border tw:rounded tw:p-2">
        <div
          v-for="banner in allBanners"
          :key="banner.id"
          class="tw:flex tw:items-center tw:gap-3 tw:p-2 tw:hover:bg-gray-50 tw:rounded tw:cursor-pointer"
          @click="toggleBanner(banner.id)"
        >
          <Checkbox
            :model-value="isBannerSelected(banner.id)"
            :binary="true"
            :input-id="`banner-${banner.id}`"
            @click.stop
            @update:model-value="toggleBanner(banner.id)"
          />
          <div class="tw:flex-1">
            <div class="tw:font-medium">
              {{ banner.title }}
            </div>
            <div class="tw:text-sm tw:text-gray-500">
              <span :class="getStatusClass(banner.status)">{{ banner.status }}</span>
              <span class="tw:mx-1">|</span>
              <span>Weight: {{ banner.weight }}</span>
            </div>
          </div>
        </div>
      </div>
      <p class="tw:text-sm tw:text-gray-500 tw:mt-2">
        {{ selectedBannerIds.length }} banner(s) selected
      </p>
    </div>

    <template #footer>
      <div class="tw:flex tw:justify-end tw:gap-2">
        <Button
          label="Cancel"
          severity="secondary"
          :disabled="saving"
          @click="handleCancel"
        />
        <Button
          label="Save"
          :loading="saving"
          :disabled="loading"
          @click="handleSave"
        />
      </div>
    </template>
  </Dialog>
</template>
