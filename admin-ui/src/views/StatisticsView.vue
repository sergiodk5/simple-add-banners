<script setup lang="ts">
import { useRouter } from 'vue-router'
import StatisticsTable from '@/components/StatisticsTable.vue'
import type { BannerStatisticsSummary } from '@/types/statistics'

const router = useRouter()

const handleViewDetails = (banner: BannerStatisticsSummary) => {
  router.push({ name: 'banner-statistics', params: { id: banner.banner_id } })
}
</script>

<template>
  <StatisticsTable @view-details="handleViewDetails" />
</template>
