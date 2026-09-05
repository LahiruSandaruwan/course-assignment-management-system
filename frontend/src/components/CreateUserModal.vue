<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-card">
      <h2>{{ isEditing ? 'Edit User' : 'Create User' }}</h2>

      <div v-if="generalError" class="error-alert">{{ generalError }}</div>

      <form @submit.prevent="submit">
        <div class="form-group">
          <label for="user-name">Name</label>
          <input
            id="user-name"
            v-model="form.name"
            required
            maxlength="255"
            :disabled="isSubmitting"
            placeholder="e.g. Jane Doe"
          />
          <span v-if="errors.name" class="error-text">{{ errors.name[0] }}</span>
        </div>

        <div class="form-group">
          <label for="user-email">Email</label>
          <input
            id="user-email"
            type="email"
            v-model="form.email"
            required
            maxlength="255"
            :disabled="isSubmitting"
            placeholder="jane@example.com"
          />
          <span v-if="errors.email" class="error-text">{{ errors.email[0] }}</span>
        </div>

        <div class="form-group">
          <label for="user-password">{{ isEditing ? 'Password (leave blank to keep unchanged)' : 'Password' }}</label>
          <input
            id="user-password"
            type="password"
            v-model="form.password"
            :required="!isEditing"
            minlength="8"
            :disabled="isSubmitting"
            placeholder="At least 8 characters"
          />
          <span v-if="errors.password" class="error-text">{{ errors.password[0] }}</span>
        </div>

        <div class="form-group">
          <label for="user-role">Role</label>
          <select id="user-role" v-model="form.role" required :disabled="isSubmitting">
            <option value="admin">Admin</option>
            <option value="instructor">Instructor</option>
            <option value="student">Student</option>
          </select>
          <span v-if="errors.role" class="error-text">{{ errors.role[0] }}</span>
        </div>

        <div class="modal-actions">
          <button type="button" class="cancel-btn" :disabled="isSubmitting" @click="$emit('close')">
            Cancel
          </button>
          <button type="submit" class="submit-btn" :disabled="isSubmitting">
            <span v-if="isSubmitting" class="spinner" aria-hidden="true"></span>
            {{ isSubmitting ? 'Saving...' : (isEditing ? 'Save Changes' : 'Create User') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { userService } from '../services/userService';

const props = defineProps({
  user: { type: Object, default: null },
});

const emit = defineEmits(['close', 'saved']);

const isEditing = computed(() => !!props.user);

const form = reactive({
  name: props.user?.name ?? '',
  email: props.user?.email ?? '',
  password: '',
  role: props.user?.role ?? 'student',
});

const isSubmitting = ref(false);
const errors = ref({});
const generalError = ref(null);

const submit = async () => {
  if (isSubmitting.value) return;

  isSubmitting.value = true;
  errors.value = {};
  generalError.value = null;

  const payload = { name: form.name, email: form.email, role: form.role };
  if (form.password) {
    payload.password = form.password;
  }

  try {
    const response = isEditing.value
      ? await userService.updateUser(props.user.id, payload)
      : await userService.createUser(payload);

    emit('saved', response.data.data);
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {};
      generalError.value = err.response.data.message || 'Please fix the errors below.';
    } else {
      generalError.value = err.response?.data?.message || 'Failed to save user.';
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

label {
  display: block;
  margin-bottom: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}

input,
select {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-family: inherit;
  font-size: 1rem;
  box-sizing: border-box;
  background-color: white;
}

input:focus,
select:focus {
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
