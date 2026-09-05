import { defineStore } from 'pinia';
import { authService } from '../services/authService';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('auth_token') || null,
    }),
    
    getters: {
        isAuthenticated: (state) => !!state.token,
    },
    
    actions: {
        setToken(token) {
            this.token = token;
            if (token) {
                localStorage.setItem('auth_token', token);
            } else {
                localStorage.removeItem('auth_token');
            }
        },
        
        clearAuth() {
            this.user = null;
            this.setToken(null);
        },
        
        async login(credentials) {
            try {
                const response = await authService.login(credentials);
                // The API wraps every payload as { message, data: {...} } —
                // login's data is { user, token }, not top-level fields.
                const { user, token } = response.data.data;

                this.setToken(token);
                this.user = user;

                return response.data.data;
            } catch (error) {
                this.clearAuth();
                throw error;
            }
        },
        
        async logout() {
            try {
                if (this.token) {
                    await authService.logout();
                }
            } catch (error) {
                console.error('Logout error:', error);
            } finally {
                this.clearAuth();
            }
        },
        
        async fetchCurrentUser() {
            if (!this.token) return null;

            try {
                const response = await authService.me();
                this.user = response.data.data.user;
                return this.user;
            } catch (error) {
                // Interceptor will handle 401
                this.user = null;
                throw error;
            }
        }
    }
});
