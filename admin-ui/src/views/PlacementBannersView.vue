<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import Card from 'primevue/card'
import Checkbox from 'primevue/checkbox'
import Button from 'primevue/button'
import ProgressSpinner from 'primevue/progressspinner'
import { useToast } from 'primevue/usetoast'
import { usePlacementStore } from '@/stores/placementStore'
import { getBanners } from '@/services/bannerApi'
import { getBannersForPlacement, syncBannersForPlacement } from '@/services/bannerPlacementApi'
import type { Banner } from '@/types/banner'

const props = defineProps<{
  id?: string
}>()

const router = useRouter()
const route = useRoute()
const toast = useToast()
const placementStore = usePlacementStore()

const placementId = computed(() => {
  const id = props.id || route.params.id
  return id ? parseInt(id as string, 10) : null
})

const loading = ref(false)
const saving = ref(false)
const placementName = ref('')
const allBanners = ref<Banner[]>([])
const selectedBannerIds = ref<number[]>([])

const pageTitle = computed(() =>
  placementName.value ? `Manage Banners - ${placementName.value}` : 'Manage Banners',
)

const loadData = async () => {
  if (!placementId.value) {
    router.push({ name: 'placements' })
    return
  }

  loading.value = true
  try {
    // Load placement info
    const placement = await placementStore.fetchPlacement(placementId.value)
    if (placement) {
      placementName.value = placement.name
    }

    // Load all banners and assigned banners in parallel
    const [bannersResponse, assignedBanners] = await Promise.all([
      getBanners({ per_page: 100 }),
      getBannersForPlacement(placementId.value),
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
    router.push({ name: 'placements' })
  } finally {
    loading.value = false
  }
}

const handleSave = async () => {
  if (!placementId.value) return

  saving.value = true
  try {
    await syncBannersForPlacement(placementId.value, selectedBannerIds.value)
    toast.add({
      severity: 'success',
      summary: 'Saved',
      detail: 'Banner assignments updated successfully',
      life: 3000,
    })
    router.push({ name: 'placements' })
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
  router.push({ name: 'placements' })
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

onMounted(() => {
  loadData()
})
</script>

<template>
  <div>
    <div class="tw:mb-4">
      <Button
        label="Back to Placements"
        icon="pi pi-arrow-left"
        severity="secondary"
        text
        @click="handleCancel"
      />
    </div>

    <Card>
      <template #title>
        {{ pageTitle }}
      </template>

      <template #content>
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
          class="tw:flex tw:flex-col tw:gap-4"
        >
          <p class="tw:text-sm tw:text-gray-600">
            Select banners to display in this placement:
          </p>

          <div class="tw:max-h-96 tw:overflow-y-auto tw:border tw:rounded tw:p-2">
            <div
              v-for="banner in allBanners"
              :key="banner.id"
              class="tw:flex tw:items-center tw:gap-3 tw:p-3 tw:hover:bg-gray-50 tw:rounded tw:cursor-pointer tw:border-b tw:last:border-b-0"
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

          <p class="tw:text-sm tw:text-gray-500">
            {{ selectedBannerIds.length }} banner(s) selected
          </p>

          <div class="tw:flex tw:justify-end tw:gap-2 tw:mt-4">
            <Button
              type="button"
              label="Cancel"
              severity="secondary"
              outlined
              :disabled="saving"
              @click="handleCancel"
            />
            <Button
              label="Save"
              :loading="saving"
              @click="handleSave"
            />
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>
