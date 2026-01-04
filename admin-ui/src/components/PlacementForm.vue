<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Button from 'primevue/button'
import { useToast } from 'primevue/usetoast'
import { usePlacementStore } from '@/stores/placementStore'
import type { Placement, PlacementPayload } from '@/types/placement'

const props = defineProps<{
  visible: boolean
  placement: Placement | null
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  saved: []
}>()

const toast = useToast()
const placementStore = usePlacementStore()
const { saving } = storeToRefs(placementStore)

const dialogVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value),
})

const isEditing = computed(() => props.placement !== null)
const dialogTitle = computed(() => (isEditing.value ? 'Edit Placement' : 'Create Placement'))

const form = ref<PlacementPayload>({
  slug: '',
  name: '',
  rotation_strategy: 'random',
})

const rotationOptions = [
  { label: 'Random', value: 'random' },
  { label: 'Weighted', value: 'weighted' },
  { label: 'Ordered', value: 'ordered' },
]

const errors = ref<Record<string, string>>({})

const resetForm = () => {
  form.value = {
    slug: '',
    name: '',
    rotation_strategy: 'random',
  }
  errors.value = {}
}

watch(
  () => props.placement,
  (placement) => {
    if (placement) {
      form.value = {
        slug: placement.slug,
        name: placement.name,
        rotation_strategy: placement.rotation_strategy,
      }
    } else {
      resetForm()
    }
  },
  { immediate: true }
)

/**
 * Auto-generates slug from name.
 */
const handleNameInput = () => {
  if (!isEditing.value && form.value.name) {
    form.value.slug = form.value.name
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-|-$/g, '')
  }
}

const validate = (): boolean => {
  errors.value = {}

  if (!form.value.name?.trim()) {
    errors.value.name = 'Name is required'
  }

  if (!form.value.slug?.trim()) {
    errors.value.slug = 'Slug is required'
  } else if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(form.value.slug)) {
    errors.value.slug = 'Slug must contain only lowercase letters, numbers, and hyphens'
  }

  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validate()) return

  try {
    const payload: PlacementPayload = {
      ...form.value,
    }

    if (isEditing.value && props.placement) {
      await placementStore.updatePlacement(props.placement.id, payload)
      toast.add({
        severity: 'success',
        summary: 'Updated',
        detail: 'Placement updated successfully',
        life: 3000,
      })
    } else {
      await placementStore.createPlacement(payload)
      toast.add({
        severity: 'success',
        summary: 'Created',
        detail: 'Placement created successfully',
        life: 3000,
      })
    }

    emit('saved')
    dialogVisible.value = false
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error instanceof Error ? error.message : 'Failed to save placement',
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
    :style="{ width: '500px' }"
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
          for="name"
          class="tw:font-medium"
        >Name *</label>
        <InputText
          id="name"
          v-model="form.name"
          :class="{ 'p-invalid': errors.name }"
          placeholder="e.g., Header Banner Slot"
          @input="handleNameInput"
        />
        <small
          v-if="errors.name"
          class="tw:text-red-500"
        >{{ errors.name }}</small>
      </div>

      <div class="tw:flex tw:flex-col tw:gap-2">
        <label
          for="slug"
          class="tw:font-medium"
        >Slug *</label>
        <InputText
          id="slug"
          v-model="form.slug"
          :class="{ 'p-invalid': errors.slug }"
          placeholder="e.g., header-banner"
        />
        <small class="tw:text-gray-500">
          Used in shortcodes: [sab_banner placement="{{ form.slug || 'slug' }}"]
        </small>
        <small
          v-if="errors.slug"
          class="tw:text-red-500"
        >{{ errors.slug }}</small>
      </div>

      <div class="tw:flex tw:flex-col tw:gap-2">
        <label
          for="rotation_strategy"
          class="tw:font-medium"
        >Rotation Strategy</label>
        <Select
          id="rotation_strategy"
          v-model="form.rotation_strategy"
          :options="rotationOptions"
          option-label="label"
          option-value="value"
          placeholder="Select rotation strategy"
        />
        <small class="tw:text-gray-500">
          <template v-if="form.rotation_strategy === 'random'">
            Banners are displayed randomly each time.
          </template>
          <template v-else-if="form.rotation_strategy === 'weighted'">
            Banners are displayed based on their weight/priority.
          </template>
          <template v-else-if="form.rotation_strategy === 'ordered'">
            Banners are displayed in a fixed order.
          </template>
        </small>
      </div>

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
          type="submit"
          :label="isEditing ? 'Update' : 'Create'"
          :loading="saving"
        />
      </div>
    </form>
  </Dialog>
</template>
