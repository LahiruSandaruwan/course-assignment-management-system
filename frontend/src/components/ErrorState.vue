<template>
  <div class="error-state">
    <p class="error-message">{{ message }}</p>
    <button v-if="retry" @click="retry" class="retry-btn">Try Again</button>
  </div>
</template>

<script setup>
defineProps({
  message: {
    type: String,
    default: 'An error occurred while fetching data.'
  },
  retry: {
    type: Function,
    default: null
  }
});
</script>

<style scoped>
.error-state {
  text-align: center;
  padding: 2rem;
  background-color: #fef2f2;
  border: 1px solid #fee2e2;
  border-radius: 8px;
  color: #b91c1c;
}
.error-message {
  margin-bottom: 1rem;
}
.retry-btn {
  background-color: #ef4444;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
}
.retry-btn:hover {
  background-color: #dc2626;
}
</style>
