<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const email = ref('');
const password = ref('');
const isLoading = ref(false);
const errorMessage = ref('');
const errors = ref({});

const handleLogin = async () => {
    isLoading.value = true;
    errorMessage.value = '';
    errors.value = {};

    try {
        await authStore.login({ email: email.value, password: password.value });
        router.push('/courses');
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
            errorMessage.value = error.response.data.message || 'Please check your inputs.';
        } else if (error.response?.status === 401) {
            errorMessage.value = 'Invalid credentials.';
        } else {
            errorMessage.value = error.message || 'An error occurred during login.';
        }
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <div class="login-container">
        <form @submit.prevent="handleLogin" class="login-form">
            <h2>Sign In</h2>
            
            <div v-if="errorMessage" class="error-alert">
                {{ errorMessage }}
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input 
                    id="email" 
                    v-model="email" 
                    type="email" 
                    required 
                    :disabled="isLoading"
                    :class="{ 'is-invalid': errors.email }"
                />
                <span v-if="errors.email" class="error-text">{{ errors.email[0] }}</span>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    id="password" 
                    v-model="password" 
                    type="password" 
                    required 
                    :disabled="isLoading"
                    :class="{ 'is-invalid': errors.password }"
                />
                <span v-if="errors.password" class="error-text">{{ errors.password[0] }}</span>
            </div>

            <button type="submit" :disabled="isLoading">
                {{ isLoading ? 'Signing in...' : 'Sign In' }}
            </button>
        </form>
    </div>
</template>

<style scoped>
.login-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background-color: #f3f4f6;
}

.login-form {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 400px;
}

h2 {
    margin-top: 0;
    margin-bottom: 1.5rem;
    text-align: center;
    color: #111827;
}

.form-group {
    margin-bottom: 1rem;
}

label {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
}

input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    font-size: 1rem;
    box-sizing: border-box;
}

input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 1px #3b82f6;
}

input.is-invalid {
    border-color: #ef4444;
}

.error-text {
    color: #ef4444;
    font-size: 0.75rem;
    margin-top: 0.25rem;
    display: block;
}

.error-alert {
    background-color: #fee2e2;
    color: #b91c1c;
    padding: 0.75rem;
    border-radius: 4px;
    margin-bottom: 1rem;
    font-size: 0.875rem;
}

button {
    width: 100%;
    padding: 0.75rem;
    background-color: #3b82f6;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;
}

button:hover:not(:disabled) {
    background-color: #2563eb;
}

button:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}
</style>
