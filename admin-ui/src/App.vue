<script setup lang="ts">
import { ref } from 'vue'

const message = ref('Hello World from Vue 3 + TypeScript!')
const count = ref(0)

const increment = () => {
  count.value++
}
</script>

<template>
  <div class="sab-admin-wrapper">
    <h1>Simple Add Banners</h1>
    <p>{{ message }}</p>
    <div class="sab-counter">
      <p>Count: {{ count }}</p>
      <button
        type="button"
        class="button button-primary"
        @click="increment"
      >
        Increment
      </button>
    </div>
  </div>
</template>

<style scoped>
.sab-admin-wrapper {
  padding: 20px;
  background: #fff;
  border: 1px solid #ccd0d4;
  border-radius: 4px;
  margin-top: 20px;
}

.sab-admin-wrapper h1 {
  margin-top: 0;
  color: #1d2327;
}

.sab-counter {
  margin-top: 20px;
  padding: 15px;
  background: #f0f0f1;
  border-radius: 4px;
}

.sab-counter p {
  font-size: 18px;
  margin-bottom: 10px;
}
</style>
