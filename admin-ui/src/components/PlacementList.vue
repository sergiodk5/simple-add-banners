<script setup lang="ts">
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import { usePlacementStore } from '@/stores/placementStore'
import type { Placement } from '@/types/placement'

const emit = defineEmits<{
  edit: [placement: Placement]
  create: []
}>()

const confirm = useConfirm()
const toast = useToast()
const placementStore = usePlacementStore()

const { placements, loading, total } = storeToRefs(placementStore)

const loadPlacements = async () => {
  try {
    await placementStore.fetchPlacements({ per_page: 100 })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error instanceof Error ? error.message : 'Failed to load placements',
      life: 3000,
    })
  }
}

const handleDelete = (placement: Placement) => {
  confirm.require({
    message: `Are you sure you want to delete "${placement.name}"?`,
    header: 'Delete Placement',
    icon: 'pi pi-exclamation-triangle',
    rejectClass: 'p-button-secondary p-button-outlined',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await placementStore.deletePlacement(placement.id)
        toast.add({
          severity: 'success',
          summary: 'Deleted',
          detail: 'Placement deleted successfully',
          life: 3000,
        })
      } catch (error) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error instanceof Error ? error.message : 'Failed to delete placement',
          life: 3000,
        })
      }
    },
  })
}

const getStrategySeverity = (strategy: string) => {
  switch (strategy) {
    case 'random':
      return 'info'
    case 'weighted':
      return 'warn'
    case 'ordered':
      return 'success'
    default:
      return 'secondary'
  }
}

const getStrategyLabel = (strategy: string) => {
  switch (strategy) {
    case 'random':
      return 'Random'
    case 'weighted':
      return 'Weighted'
    case 'ordered':
      return 'Ordered'
    default:
      return strategy
  }
}

const formatDate = (date: string | null) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString()
}

onMounted(() => {
  loadPlacements()
})

defineExpose({ loadPlacements })
</script>

<template>
  <div>
    <ConfirmDialog />

    <div class="tw:flex tw:justify-between tw:items-center tw:mb-4">
      <h2 class="tw:text-xl tw:font-semibold tw:m-0">
        Placements
        <span
          v-if="total > 0"
          class="tw:text-gray-500 tw:font-normal tw:text-base"
        >({{ total }})</span>
      </h2>
      <Button
        label="Add Placement"
        icon="pi pi-plus"
        @click="emit('create')"
      />
    </div>

    <DataTable
      :value="placements"
      :loading="loading"
      striped-rows
      responsive-layout="scroll"
      class="tw:w-full"
    >
      <template #empty>
        <div class="tw:text-center tw:py-8 tw:text-gray-500">
          No placements found. Click "Add Placement" to create one.
        </div>
      </template>

      <Column
        field="id"
        header="ID"
        style="width: 60px"
      />

      <Column
        field="name"
        header="Name"
        style="min-width: 200px"
      />

      <Column
        field="slug"
        header="Slug"
        style="min-width: 150px"
      >
        <template #body="{ data }">
          <code class="tw:bg-gray-100 tw:px-2 tw:py-1 tw:rounded tw:text-sm">{{ data.slug }}</code>
        </template>
      </Column>

      <Column
        field="rotation_strategy"
        header="Rotation"
        style="width: 120px"
      >
        <template #body="{ data }">
          <Tag
            :value="getStrategyLabel(data.rotation_strategy)"
            :severity="getStrategySeverity(data.rotation_strategy)"
          />
        </template>
      </Column>

      <Column
        header="Created"
        style="width: 120px"
      >
        <template #body="{ data }">
          {{ formatDate(data.created_at) }}
        </template>
      </Column>

      <Column
        header="Actions"
        style="width: 150px"
      >
        <template #body="{ data }">
          <div class="tw:flex tw:gap-2">
            <Button
              icon="pi pi-pencil"
              severity="info"
              size="small"
              outlined
              aria-label="Edit"
              @click="emit('edit', data)"
            />
            <Button
              icon="pi pi-trash"
              severity="danger"
              size="small"
              outlined
              aria-label="Delete"
              @click="handleDelete(data)"
            />
          </div>
        </template>
      </Column>
    </DataTable>
  </div>
</template>
