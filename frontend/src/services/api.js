import axios from 'axios';
import { useAuthStore } from '../stores/auth';

const api = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    }
});

// Request interceptor to attach Bearer token
api.interceptors.request.use((config) => {
    const authStore = useAuthStore();
    if (authStore.token) {
        config.headers.Authorization = `Bearer ${authStore.token}`;
    }
    return config;
}, (error) => {
    return Promise.reject(error);
});

// Response interceptor for global error handling
api.interceptors.response.use((response) => {
    return response;
}, (error) => {
    if (!error.response) {
        // Network error
        return Promise.reject(new Error('Network error. Please try again later.'));
    }

    const authStore = useAuthStore();
    const status = error.response.status;

    if (status === 401) {
        authStore.clearAuth();
        // Redirect to login using window.location or router
        window.location.href = '/login';
    } else if (status === 403) {
        console.error('Forbidden access:', error.response.data.message || 'Access denied');
        // Let the component handle it or show a global toast
    } else if (status === 422) {
        // Validation error, let the form handle it
        return Promise.reject(error);
    } else {
        console.error('API Error:', error.response.data.message || 'Something went wrong');
    }

    return Promise.reject(error);
});

export default api;
