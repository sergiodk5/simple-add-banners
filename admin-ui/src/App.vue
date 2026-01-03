<script setup lang="ts">
import { ref } from 'vue'
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'

const message = ref('Hello World from Vue 3 + TypeScript!')
const count = ref(0)
const searchQuery = ref('')

const increment = () => {
  count.value++
}
</script>

<template>
  <div class="tw:mt-5">
    <Card>
      <template #title>
        Simple Add Banners
      </template>
      <template #subtitle>
        {{ message }}
      </template>
      <template #content>
        <div class="tw:mb-4">
          <label
            for="search"
            class="tw:block tw:mb-2 tw:font-medium"
          >Search</label>
          <InputText
            id="search"
            v-model="searchQuery"
            placeholder="Search banners..."
            class="tw:w-full"
          />
        </div>

        <div class="tw:rounded tw:bg-gray-100 tw:p-4">
          <p class="tw:mb-2 tw:text-lg">
            Count: {{ count }}
          </p>
          <Button
            label="Increment"
            icon="pi pi-plus"
            @click="increment"
          />
        </div>
      </template>
    </Card>
  </div>
</template>
