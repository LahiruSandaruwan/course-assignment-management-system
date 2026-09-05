<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-card">
      <h2>Create Course</h2>

      <div v-if="generalError" class="error-alert">{{ generalError }}</div>

      <form @submit.prevent="submit">
        <div class="form-group">
          <label for="course-name">Name</label>
          <input
            id="course-name"
            v-model="form.name"
            required
            maxlength="255"
            :disabled="isSubmitting"
            placeholder="e.g. Advanced Vue.js and Laravel"
          />
          <span v-if="errors.name" class="error-text">{{ errors.name[0] }}</span>
        </div>

        <div class="form-group">
          <label for="course-description">Description</label>
          <textarea
            id="course-description"
            v-model="form.description"
            rows="3"
            :disabled="isSubmitting"
            placeholder="What will students learn in this course?"
          ></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="course-start">Start Date</label>
            <input id="course-start" type="date" v-model="form.start_date" required :disabled="isSubmitting" />
            <span v-if="errors.start_date" class="error-text">{{ errors.start_date[0] }}</span>
          </div>
          <div class="form-group">
            <label for="course-end">End Date</label>
            <input id="course-end" type="date" v-model="form.end_date" required :disabled="isSubmitting" />
            <span v-if="errors.end_date" class="error-text">{{ errors.end_date[0] }}</span>
          </div>
        </div>

        <div class="modal-actions">
          <button type="button" class="cancel-btn" :disabled="isSubmitting" @click="$emit('close')">
            Cancel
          </button>
          <button type="submit" class="submit-btn" :disabled="isSubmitting">
            <span v-if="isSubmitting" class="spinner" aria-hidden="true"></span>
            {{ isSubmitting ? 'Creating...' : 'Create Course' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { courseService } from '../services/courseService';

const emit = defineEmits(['close', 'created']);

const form = reactive({ name: '', description: '', start_date: '', end_date: '' });
const isSubmitting = ref(false);
const errors = ref({});
const generalError = ref(null);

const submit = async () => {
  if (isSubmitting.value) return;

  isSubmitting.value = true;
  errors.value = {};
  generalError.value = null;

  try {
    const response = await courseService.createCourse({ ...form });
    emit('created', response.data.data);
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {};
      generalError.value = err.response.data.message || 'Please fix the errors below.';
    } else {
      generalError.value = err.response?.data?.message || 'Failed to create course.';
    }
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(17, 24, 39, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  z-index: 50;
}

.modal-card {
  background: white;
  border-radius: 10px;
  padding: 2rem;
  width: 100%;
  max-width: 480px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15);
  box-sizing: border-box;
}

.modal-card h2 {
  margin: 0 0 1.25rem 0;
  color: #111827;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-row {
  display: flex;
  gap: 1.25rem;
}

.form-row .form-group {
  flex: 1;
}

label {
  display: block;
  margin-bottom: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}

input,
textarea {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-family: inherit;
  font-size: 1rem;
  box-sizing: border-box;
}

textarea {
  resize: vertical;
}

input:focus,
textarea:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 1px #3b82f6;
}

.error-text {
  display: block;
  color: #ef4444;
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

.error-alert {
  background-color: #fee2e2;
  color: #b91c1c;
  padding: 0.75rem;
  border-radius: 4px;
  margin-bottom: 1.25rem;
  font-size: 0.875rem;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.5rem;
}

.modal-actions button {
  padding: 0.5rem 1.25rem;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
}

.cancel-btn {
  background-color: white;
  border: 1px solid #d1d5db;
  color: #374151;
}

.cancel-btn:hover:not(:disabled) {
  background-color: #f3f4f6;
}

.submit-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  background-color: #3b82f6;
  border: 1px solid #3b82f6;
  color: white;
}

.submit-btn:hover:not(:disabled) {
  background-color: #2563eb;
}

.modal-actions button:disabled {
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
