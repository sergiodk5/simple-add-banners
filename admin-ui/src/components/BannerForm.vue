<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import Button from 'primevue/button'
import { useToast } from 'primevue/usetoast'
import { useBannerStore } from '@/stores/bannerStore'
import ImagePicker from '@/components/ImagePicker.vue'
import type { Banner, BannerPayload } from '@/types/banner'

const props = defineProps<{
  visible: boolean
  banner: Banner | null
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  saved: []
}>()

const toast = useToast()
const bannerStore = useBannerStore()
const { saving } = storeToRefs(bannerStore)

const dialogVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value),
})

const isEditing = computed(() => props.banner !== null)
const dialogTitle = computed(() => (isEditing.value ? 'Edit Banner' : 'Create Banner'))

const form = ref<BannerPayload>({
  title: '',
  desktop_url: '',
  mobile_url: null,
  desktop_image_id: null,
  mobile_image_id: null,
  start_date: null,
  end_date: null,
  status: 'active',
  weight: 1,
})

const startDate = ref<Date | null>(null)
const endDate = ref<Date | null>(null)
const desktopImageUrl = ref<string | null>(null)
const mobileImageUrl = ref<string | null>(null)

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Paused', value: 'paused' },
  { label: 'Scheduled', value: 'scheduled' },
]

const errors = ref<Record<string, string>>({})

const resetForm = () => {
  form.value = {
    title: '',
    desktop_url: '',
    mobile_url: null,
    desktop_image_id: null,
    mobile_image_id: null,
    start_date: null,
    end_date: null,
    status: 'active',
    weight: 1,
  }
  startDate.value = null
  endDate.value = null
  desktopImageUrl.value = null
  mobileImageUrl.value = null
  errors.value = {}
}

watch(
  () => props.banner,
  (banner) => {
    if (banner) {
      form.value = {
        title: banner.title,
        desktop_url: banner.desktop_url,
        mobile_url: banner.mobile_url,
        desktop_image_id: banner.desktop_image_id,
        mobile_image_id: banner.mobile_image_id,
        start_date: banner.start_date,
        end_date: banner.end_date,
        status: banner.status,
        weight: banner.weight,
      }
      startDate.value = banner.start_date ? new Date(banner.start_date) : null
      endDate.value = banner.end_date ? new Date(banner.end_date) : null
      desktopImageUrl.value = banner.desktop_image_url ?? null
      mobileImageUrl.value = banner.mobile_image_url ?? null
    } else {
      resetForm()
    }
  },
  { immediate: true }
)

const validate = (): boolean => {
  errors.value = {}

  if (!form.value.title?.trim()) {
    errors.value.title = 'Title is required'
  }

  if (!form.value.desktop_url?.trim()) {
    errors.value.desktop_url = 'Desktop URL is required'
  } else {
    try {
      new URL(form.value.desktop_url)
    } catch {
      errors.value.desktop_url = 'Please enter a valid URL'
    }
  }

  if (form.value.mobile_url) {
    try {
      new URL(form.value.mobile_url)
    } catch {
      errors.value.mobile_url = 'Please enter a valid URL'
    }
  }

  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validate()) return

  try {
    const payload: BannerPayload = {
      ...form.value,
      start_date: startDate.value ? startDate.value.toISOString() : null,
      end_date: endDate.value ? endDate.value.toISOString() : null,
    }

    if (isEditing.value && props.banner) {
      await bannerStore.updateBanner(props.banner.id, payload)
      toast.add({
        severity: 'success',
        summary: 'Updated',
        detail: 'Banner updated successfully',
        life: 3000,
      })
    } else {
      await bannerStore.createBanner(payload)
      toast.add({
        severity: 'success',
        summary: 'Created',
        detail: 'Banner created successfully',
        life: 3000,
      })
    }

    emit('saved')
    dialogVisible.value = false
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error instanceof Error ? error.message : 'Failed to save banner',
      life: 3000,
    })
  }
}

const handleCancel = () => {
  dialogVisible.value = false
}
</script>

<template>
  <Dialog
    v-model:visible="dialogVisible"
    :header="dialogTitle"
    modal
    :style="{ width: '600px' }"
    :closable="!saving"
    :close-on-escape="!saving"
    @hide="resetForm"
  >
    <form
      class="tw:flex tw:flex-col tw:gap-4"
      @submit.prevent="handleSubmit"
    >
      <div class="tw:flex tw:flex-col tw:gap-2">
        <label
          for="title"
          class="tw:font-medium"
        >Title *</label>
        <InputText
          id="title"
          v-model="form.title"
          :invalid="!!errors.title"
          placeholder="Enter banner title"
        />
        <small
          v-if="errors.title"
          class="tw:text-red-500"
        >{{ errors.title }}</small>
      </div>

      <div class="tw:flex tw:flex-col tw:gap-2">
        <label
          for="desktop_url"
          class="tw:font-medium"
        >Desktop URL *</label>
        <InputText
          id="desktop_url"
          v-model="form.desktop_url"
          :invalid="!!errors.desktop_url"
          placeholder="https://example.com"
        />
        <small
          v-if="errors.desktop_url"
          class="tw:text-red-500"
        >{{ errors.desktop_url }}</small>
      </div>

      <div class="tw:flex tw:flex-col tw:gap-2">
        <label
          for="mobile_url"
          class="tw:font-medium"
        >Mobile URL</label>
        <InputText
          id="mobile_url"
          v-model="form.mobile_url"
          :invalid="!!errors.mobile_url"
          placeholder="https://example.com (optional)"
        />
        <small
          v-if="errors.mobile_url"
          class="tw:text-red-500"
        >{{ errors.mobile_url }}</small>
        <small class="tw:text-gray-500">Leave empty to use Desktop URL</small>
      </div>

      <div class="tw:grid tw:grid-cols-2 tw:gap-4">
        <ImagePicker
          v-model="form.desktop_image_id"
          v-model:image-url="desktopImageUrl"
          label="Desktop Image"
        />
        <ImagePicker
          v-model="form.mobile_image_id"
          v-model:image-url="mobileImageUrl"
          label="Mobile Image"
        />
      </div>

      <div class="tw:grid tw:grid-cols-2 tw:gap-4">
        <div class="tw:flex tw:flex-col tw:gap-2">
          <label
            for="status"
            class="tw:font-medium"
          >Status</label>
          <Select
            id="status"
            v-model="form.status"
            :options="statusOptions"
            option-label="label"
            option-value="value"
            placeholder="Select status"
          />
        </div>

        <div class="tw:flex tw:flex-col tw:gap-2">
          <label
            for="weight"
            class="tw:font-medium"
          >Weight</label>
          <InputNumber
            id="weight"
            v-model="form.weight"
            :min="1"
            :max="100"
          />
          <small class="tw:text-gray-500">Higher weight = more frequent display</small>
        </div>
      </div>

      <div class="tw:grid tw:grid-cols-2 tw:gap-4">
        <div class="tw:flex tw:flex-col tw:gap-2">
          <label
            for="start_date"
            class="tw:font-medium"
          >Start Date</label>
          <DatePicker
            id="start_date"
            v-model="startDate"
            date-format="yy-mm-dd"
            show-time
            hour-format="24"
            placeholder="Select start date"
          />
        </div>

        <div class="tw:flex tw:flex-col tw:gap-2">
          <label
            for="end_date"
            class="tw:font-medium"
          >End Date</label>
          <DatePicker
            id="end_date"
            v-model="endDate"
            date-format="yy-mm-dd"
            show-time
            hour-format="24"
            placeholder="Select end date"
          />
        </div>
      </div>
    </form>

    <template #footer>
      <div class="tw:flex tw:justify-end tw:gap-2">
        <Button
          label="Cancel"
          severity="secondary"
          :disabled="saving"
          @click="handleCancel"
        />
        <Button
          :label="isEditing ? 'Update' : 'Create'"
          :loading="saving"
          @click="handleSubmit"
        />
      </div>
    </template>
  </Dialog>
</template>
