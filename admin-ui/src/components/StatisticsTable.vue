<script setup lang="ts">
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import { useToast } from 'primevue/usetoast'
import { useStatisticsStore } from '@/stores/statisticsStore'
import type { BannerStatisticsSummary } from '@/types/statistics'

const emit = defineEmits<{
  viewDetails: [banner: BannerStatisticsSummary]
}>()

const toast = useToast()
const statisticsStore = useStatisticsStore()

const { bannerSummaries, loading, totalImpressions, totalClicks, overallCtr } =
  storeToRefs(statisticsStore)

const loadStatistics = async () => {
  try {
    await statisticsStore.fetchAllBannerStats()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error instanceof Error ? error.message : 'Failed to load statistics',
      life: 3000,
    })
  }
}

const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'active':
      return 'success'
    case 'paused':
      return 'warn'
    case 'scheduled':
      return 'info'
    default:
      return 'secondary'
  }
}

const formatNumber = (value: number) => {
  return value.toLocaleString()
}

const formatCtr = (value: number) => {
  return `${value.toFixed(2)}%`
}

onMounted(() => {
  loadStatistics()
})

defineExpose({ loadStatistics })
</script>

<template>
  <div>
    <div class="tw:flex tw:justify-between tw:items-center tw:mb-4">
      <h2 class="tw:text-xl tw:font-semibold tw:m-0">
        Statistics Overview
      </h2>
      <Button
        icon="pi pi-refresh"
        label="Refresh"
        severity="secondary"
        outlined
        @click="loadStatistics"
      />
    </div>

    <div class="tw:grid tw:grid-cols-3 tw:gap-4 tw:mb-6">
      <div class="tw:bg-blue-50 tw:rounded-lg tw:p-4 tw:text-center">
        <div class="tw:text-2xl tw:font-bold tw:text-blue-700">
          {{ formatNumber(totalImpressions) }}
        </div>
        <div class="tw:text-sm tw:text-blue-600">
          Total Impressions
        </div>
      </div>
      <div class="tw:bg-green-50 tw:rounded-lg tw:p-4 tw:text-center">
        <div class="tw:text-2xl tw:font-bold tw:text-green-700">
          {{ formatNumber(totalClicks) }}
        </div>
        <div class="tw:text-sm tw:text-green-600">
          Total Clicks
        </div>
      </div>
      <div class="tw:bg-purple-50 tw:rounded-lg tw:p-4 tw:text-center">
        <div class="tw:text-2xl tw:font-bold tw:text-purple-700">
          {{ formatCtr(overallCtr) }}
        </div>
        <div class="tw:text-sm tw:text-purple-600">
          Overall CTR
        </div>
      </div>
    </div>

    <DataTable
      :value="bannerSummaries"
      :loading="loading"
      striped-rows
      responsive-layout="scroll"
      class="tw:w-full"
      sort-field="impressions"
      :sort-order="-1"
    >
      <template #empty>
        <div class="tw:text-center tw:py-8 tw:text-gray-500">
          No statistics recorded yet. Impressions will appear once banners are viewed.
        </div>
      </template>

      <Column
        field="banner_id"
        header="ID"
        sortable
        style="width: 60px"
      />

      <Column
        field="banner_title"
        header="Banner"
        sortable
        style="min-width: 200px"
      />

      <Column
        field="banner_status"
        header="Status"
        sortable
        style="width: 100px"
      >
        <template #body="{ data }">
          <Tag
            :value="data.banner_status"
            :severity="getStatusSeverity(data.banner_status)"
          />
        </template>
      </Column>

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

      <Column
        header="Actions"
        style="width: 120px"
      >
        <template #body="{ data }">
          <Button
            icon="pi pi-chart-bar"
            label="Details"
            severity="info"
            size="small"
            outlined
            @click="emit('viewDetails', data)"
          />
        </template>
      </Column>
    </DataTable>
  </div>
</template>
