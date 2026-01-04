<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Button from 'primevue/button'
import ProgressSpinner from 'primevue/progressspinner'
import { useToast } from 'primevue/usetoast'
import { usePlacementStore } from '@/stores/placementStore'
import type { PlacementPayload } from '@/types/placement'

const props = defineProps<{
  id?: string
}>()

const router = useRouter()
const route = useRoute()
const toast = useToast()
const placementStore = usePlacementStore()
const { loading, saving } = storeToRefs(placementStore)

const placementId = computed(() => {
  const id = props.id || route.params.id
  return id ? parseInt(id as string, 10) : null
})

const isEditing = computed(() => placementId.value !== null)
const pageTitle = computed(() => (isEditing.value ? 'Edit Placement' : 'Create Placement'))

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

const loadPlacement = async () => {
  if (!placementId.value) return

  try {
    const placement = await placementStore.fetchPlacement(placementId.value)
    if (placement) {
      form.value = {
        slug: placement.slug,
        name: placement.name,
        rotation_strategy: placement.rotation_strategy,
      }
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error instanceof Error ? error.message : 'Failed to load placement',
      life: 3000,
    })
    router.push({ name: 'placements' })
  }
}

/**
 * Auto-generates slug from name (only when creating).
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

    if (isEditing.value && placementId.value) {
      await placementStore.updatePlacement(placementId.value, payload)
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

    router.push({ name: 'placements' })
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
  router.push({ name: 'placements' })
}

onMounted(() => {
  if (isEditing.value) {
    loadPlacement()
  } else {
    resetForm()
  }
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
          v-if="loading && isEditing"
          class="tw:flex tw:justify-center tw:py-8"
        >
          <ProgressSpinner />
        </div>

        <form
          v-else
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
      </template>
    </Card>
  </div>
</template>
