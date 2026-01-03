<script setup lang="ts">
import { ref } from 'vue'

const message = ref('Hello World from Vue 3 + TypeScript!')
const count = ref(0)

const increment = () => {
  count.value++
}
</script>

<template>
  <div class="tw:mt-5 tw:rounded tw:border tw:border-gray-300 tw:bg-white tw:p-5">
    <h1 class="tw:mt-0 tw:text-2xl tw:font-bold tw:text-gray-800">
      Simple Add Banners
    </h1>
    <p class="tw:text-gray-600">
      {{ message }}
    </p>
    <div class="tw:mt-5 tw:rounded tw:bg-gray-100 tw:p-4">
      <p class="tw:mb-2 tw:text-lg">
        Count: {{ count }}
      </p>
      <button
        type="button"
        class="tw:rounded tw:bg-blue-600 tw:px-4 tw:py-2 tw:text-white tw:hover:bg-blue-700"
        @click="increment"
      >
        Increment
      </button>
    </div>
  </div>
</template>
