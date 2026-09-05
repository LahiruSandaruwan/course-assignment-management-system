import api from './api';

export const authService = {
    login(credentials) {
        return api.post('/login', credentials);
    },
    logout() {
        return api.post('/logout');
    },
    me() {
        return api.get('/me');
    }
};
