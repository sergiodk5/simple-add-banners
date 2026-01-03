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
import { useBannerStore } from '@/stores/bannerStore'
import type { Banner } from '@/types/banner'

const emit = defineEmits<{
  edit: [banner: Banner]
  create: []
}>()

const confirm = useConfirm()
const toast = useToast()
const bannerStore = useBannerStore()

const { banners, loading, total } = storeToRefs(bannerStore)

const loadBanners = async () => {
  try {
    await bannerStore.fetchBanners({ per_page: 100 })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error instanceof Error ? error.message : 'Failed to load banners',
      life: 3000,
    })
  }
}

const handleDelete = (banner: Banner) => {
  confirm.require({
    message: `Are you sure you want to delete "${banner.title}"?`,
    header: 'Delete Banner',
    icon: 'pi pi-exclamation-triangle',
    rejectClass: 'p-button-secondary p-button-outlined',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await bannerStore.deleteBanner(banner.id)
        toast.add({
          severity: 'success',
          summary: 'Deleted',
          detail: 'Banner deleted successfully',
          life: 3000,
        })
      } catch (error) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error instanceof Error ? error.message : 'Failed to delete banner',
          life: 3000,
        })
      }
    },
  })
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

const formatDate = (date: string | null) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString()
}

onMounted(() => {
  loadBanners()
})

defineExpose({ loadBanners })
</script>

<template>
  <div>
    <ConfirmDialog />

    <div class="tw:flex tw:justify-between tw:items-center tw:mb-4">
      <h2 class="tw:text-xl tw:font-semibold tw:m-0">
        Banners
        <span
          v-if="total > 0"
          class="tw:text-gray-500 tw:font-normal tw:text-base"
        >({{ total }})</span>
      </h2>
      <Button
        label="Add Banner"
        icon="pi pi-plus"
        @click="emit('create')"
      />
    </div>

    <DataTable
      :value="banners"
      :loading="loading"
      striped-rows
      responsive-layout="scroll"
      class="tw:w-full"
    >
      <template #empty>
        <div class="tw:text-center tw:py-8 tw:text-gray-500">
          No banners found. Click "Add Banner" to create one.
        </div>
      </template>

      <Column
        field="id"
        header="ID"
        style="width: 60px"
      />

      <Column
        field="title"
        header="Title"
        style="min-width: 200px"
      />

      <Column
        field="status"
        header="Status"
        style="width: 100px"
      >
        <template #body="{ data }">
          <Tag
            :value="data.status"
            :severity="getStatusSeverity(data.status)"
          />
        </template>
      </Column>

      <Column
        field="weight"
        header="Weight"
        style="width: 80px"
      />

      <Column
        header="Schedule"
        style="width: 200px"
      >
        <template #body="{ data }">
          <span v-if="data.start_date || data.end_date">
            {{ formatDate(data.start_date) }} - {{ formatDate(data.end_date) }}
          </span>
          <span
            v-else
            class="tw:text-gray-400"
          >Always</span>
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
              severity="secondary"
              text
              rounded
              aria-label="Edit"
              @click="emit('edit', data)"
            />
            <Button
              icon="pi pi-trash"
              severity="danger"
              text
              rounded
              aria-label="Delete"
              @click="handleDelete(data)"
            />
          </div>
        </template>
      </Column>
    </DataTable>
  </div>
</template>
