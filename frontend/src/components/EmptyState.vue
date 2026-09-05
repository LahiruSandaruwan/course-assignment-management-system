<template>
  <div class="empty-state">
    <p>{{ message }}</p>
  </div>
</template>

<script setup>
defineProps({
  message: {
    type: String,
    default: 'No items found.'
  }
});
</script>

<style scoped>
.empty-state {
  text-align: center;
  padding: 3rem;
  color: #6b7280;
  background-color: #f9fafb;
  border-radius: 8px;
  border: 1px dashed #d1d5db;
}
</style>
