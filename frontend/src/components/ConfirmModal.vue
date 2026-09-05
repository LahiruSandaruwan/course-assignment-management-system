<template>
  <div v-if="show" class="confirm-overlay" @click.self="onCancel">
    <div class="confirm-card" role="alertdialog" aria-modal="true" :aria-label="title">
      <div class="confirm-icon" :class="{ destructive: isDestructive }">
        <svg v-if="isDestructive" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        <svg v-else viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" />
          <path d="M12 16v-4m0-4h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </div>

      <h3 class="confirm-title">{{ title }}</h3>
      <p class="confirm-message">{{ message }}</p>

      <div class="confirm-actions">
        <button type="button" class="cancel-btn" :disabled="isLoading" @click="onCancel">
          {{ cancelText }}
        </button>
        <button
          type="button"
          class="confirm-btn"
          :class="{ destructive: isDestructive }"
          :disabled="isLoading"
          @click="onConfirm"
        >
          <span v-if="isLoading" class="spinner" aria-hidden="true"></span>
          {{ isLoading ? 'Working...' : confirmText }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { watch, onUnmounted } from 'vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, default: '' },
  message: { type: String, default: '' },
  confirmText: { type: String, default: 'Confirm' },
  cancelText: { type: String, default: 'Cancel' },
  isDestructive: { type: Boolean, default: false },
  isLoading: { type: Boolean, default: false },
});

const emit = defineEmits(['confirm', 'cancel']);

const onConfirm = () => {
  if (props.isLoading) return;
  emit('confirm');
};

const onCancel = () => {
  if (props.isLoading) return;
  emit('cancel');
};

const onKeydown = (event) => {
  if (event.key === 'Escape') onCancel();
};

watch(() => props.show, (visible) => {
  if (visible) {
    window.addEventListener('keydown', onKeydown);
  } else {
    window.removeEventListener('keydown', onKeydown);
  }
}, { immediate: true });

onUnmounted(() => {
  window.removeEventListener('keydown', onKeydown);
});
</script>

<style scoped>
.confirm-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(17, 24, 39, 0.55);
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  z-index: 60;
}

.confirm-card {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  width: 100%;
  max-width: 400px;
  text-align: center;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
  box-sizing: border-box;
}

.confirm-icon {
  width: 48px;
  height: 48px;
  margin: 0 auto 1rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #eff6ff;
  color: #2563eb;
}

.confirm-icon svg {
  width: 24px;
  height: 24px;
}

.confirm-icon.destructive {
  background-color: #fef2f2;
  color: #dc2626;
}

.confirm-title {
  margin: 0 0 0.5rem 0;
  color: #111827;
  font-size: 1.125rem;
}

.confirm-message {
  margin: 0 0 1.5rem 0;
  color: #4b5563;
  font-size: 0.9rem;
  line-height: 1.5;
}

.confirm-actions {
  display: flex;
  justify-content: center;
  gap: 0.75rem;
}

.confirm-actions button {
  padding: 0.55rem 1.25rem;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.9rem;
  cursor: pointer;
  min-width: 96px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.cancel-btn {
  background-color: white;
  border: 1px solid #d1d5db;
  color: #374151;
}

.cancel-btn:hover:not(:disabled) {
  background-color: #f3f4f6;
}

.confirm-btn {
  background-color: #3b82f6;
  border: 1px solid #3b82f6;
  color: white;
}

.confirm-btn:hover:not(:disabled) {
  background-color: #2563eb;
}

.confirm-btn.destructive {
  background-color: #dc2626;
  border-color: #dc2626;
}

.confirm-btn.destructive:hover:not(:disabled) {
  background-color: #b91c1c;
}

.confirm-actions button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.spinner {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.4);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
