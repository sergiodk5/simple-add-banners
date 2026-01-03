<script setup lang="ts">
import { ref, watch, onUnmounted } from 'vue'
import Button from 'primevue/button'

const props = defineProps<{
  modelValue?: number | null
  imageUrl?: string | null
  label?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: number | null]
  'update:imageUrl': [value: string | null]
}>()

const previewUrl = ref<string | null>(props.imageUrl ?? null)
let mediaFrame: ReturnType<typeof window.wp.media> | null = null

watch(
  () => props.imageUrl,
  (newUrl) => {
    previewUrl.value = newUrl ?? null
  }
)

const openMediaLibrary = () => {
  if (!window.wp?.media) {
    console.error('WordPress Media Library not available')
    return
  }

  if (!mediaFrame) {
    mediaFrame = window.wp.media({
      title: props.label || 'Select Image',
      button: {
        text: 'Use this image',
      },
      library: {
        type: 'image',
      },
      multiple: false,
    })

    mediaFrame.on('select', () => {
      const attachment = mediaFrame!.state().get('selection').first().toJSON()
      previewUrl.value = attachment.url
      emit('update:modelValue', attachment.id)
      emit('update:imageUrl', attachment.url)
    })
  }

  mediaFrame.open()
}

const clearImage = () => {
  previewUrl.value = null
  emit('update:modelValue', null)
  emit('update:imageUrl', null)
}

onUnmounted(() => {
  if (mediaFrame) {
    mediaFrame.off('select')
    mediaFrame = null
  }
})
</script>

<template>
  <div class="tw:flex tw:flex-col tw:gap-2">
    <label
      v-if="label"
      class="tw:font-medium"
    >{{ label }}</label>

    <div
      v-if="previewUrl"
      class="tw:relative tw:inline-block tw:max-w-xs"
    >
      <img
        :src="previewUrl"
        :alt="label || 'Selected image'"
        class="tw:max-w-full tw:h-auto tw:rounded tw:border tw:border-gray-300"
      >
      <Button
        icon="pi pi-times"
        severity="danger"
        size="small"
        rounded
        class="tw:absolute tw:top-2 tw:right-2"
        aria-label="Remove image"
        @click="clearImage"
      />
    </div>

    <div class="tw:flex tw:gap-2">
      <Button
        :label="previewUrl ? 'Change Image' : 'Select Image'"
        icon="pi pi-image"
        severity="secondary"
        @click="openMediaLibrary"
      />
    </div>
  </div>
</template>
