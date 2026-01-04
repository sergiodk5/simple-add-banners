<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import { useToast } from 'primevue/usetoast'
import { useStatisticsStore } from '@/stores/statisticsStore'
import { useBannerStore } from '@/stores/bannerStore'

const props = defineProps<{
  id: string | number
}>()

const router = useRouter()
const toast = useToast()
const statisticsStore = useStatisticsStore()
const bannerStore = useBannerStore()

const { currentBannerStats, loading } = storeToRefs(statisticsStore)
const bannerTitle = ref('')

const bannerId = computed(() => {
  return typeof props.id === 'string' ? parseInt(props.id, 10) : props.id
})

const loadData = async () => {
  try {
    const [, banner] = await Promise.all([
      statisticsStore.fetchBannerStats(bannerId.value),
      bannerStore.fetchBanner(bannerId.value),
    ])
    bannerTitle.value = banner.title
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error instanceof Error ? error.message : 'Failed to load statistics',
      life: 3000,
    })
  }
}

const formatNumber = (value: number) => {
  return value.toLocaleString()
}

const formatCtr = (value: number) => {
  return `${value.toFixed(2)}%`
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString()
}

const goBack = () => {
  router.push({ name: 'statistics' })
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <div>
    <div class="tw:flex tw:items-center tw:gap-4 tw:mb-6">
      <Button
        icon="pi pi-arrow-left"
        severity="secondary"
        text
        rounded
        aria-label="Back"
        @click="goBack"
      />
      <h2 class="tw:text-xl tw:font-semibold tw:m-0">
        Statistics: {{ bannerTitle || `Banner #${bannerId}` }}
      </h2>
    </div>

    <template v-if="currentBannerStats">
      <div class="tw:grid tw:grid-cols-3 tw:gap-4 tw:mb-6">
        <div class="tw:bg-blue-50 tw:rounded-lg tw:p-4 tw:text-center">
          <div class="tw:text-2xl tw:font-bold tw:text-blue-700">
            {{ formatNumber(currentBannerStats.totals.impressions) }}
          </div>
          <div class="tw:text-sm tw:text-blue-600">
            Total Impressions
          </div>
        </div>
        <div class="tw:bg-green-50 tw:rounded-lg tw:p-4 tw:text-center">
          <div class="tw:text-2xl tw:font-bold tw:text-green-700">
            {{ formatNumber(currentBannerStats.totals.clicks) }}
          </div>
          <div class="tw:text-sm tw:text-green-600">
            Total Clicks
          </div>
        </div>
        <div class="tw:bg-purple-50 tw:rounded-lg tw:p-4 tw:text-center">
          <div class="tw:text-2xl tw:font-bold tw:text-purple-700">
            {{ formatCtr(currentBannerStats.totals.ctr) }}
          </div>
          <div class="tw:text-sm tw:text-purple-600">
            Click-Through Rate
          </div>
        </div>
      </div>

      <h3 class="tw:text-lg tw:font-medium tw:mb-4">
        Daily Breakdown
      </h3>

      <DataTable
        :value="currentBannerStats.daily"
        :loading="loading"
        striped-rows
        responsive-layout="scroll"
        class="tw:w-full"
        sort-field="stat_date"
        :sort-order="-1"
      >
        <template #empty>
          <div class="tw:text-center tw:py-8 tw:text-gray-500">
            No daily statistics recorded for this banner yet.
          </div>
        </template>

        <Column
          field="stat_date"
          header="Date"
          sortable
          style="width: 150px"
        >
          <template #body="{ data }">
            {{ formatDate(data.stat_date) }}
          </template>
        </Column>

        <Column
          field="placement_id"
          header="Placement ID"
          sortable
          style="width: 120px"
        />

        <Column
          field="impressions"
          header="Impressions"
          sortable
          style="width: 120px"
        >
          <template #body="{ data }">
            {{ formatNumber(data.impressions) }}
          </template>
        </Column>

        <Column
          field="clicks"
          header="Clicks"
          sortable
          style="width: 100px"
        >
          <template #body="{ data }">
            {{ formatNumber(data.clicks) }}
          </template>
        </Column>

        <Column
          field="ctr"
          header="CTR"
          sortable
          style="width: 100px"
        >
          <template #body="{ data }">
            {{ formatCtr(data.ctr) }}
          </template>
        </Column>
      </DataTable>
    </template>

    <div
      v-else-if="loading"
      class="tw:text-center tw:py-8 tw:text-gray-500"
    >
      Loading statistics...
    </div>
  </div>
</template>
