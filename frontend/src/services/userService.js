import api from './api';

export const userService = {
    getUsers(page = 1) {
        return api.get(`/users?page=${page}`);
    },
    createUser(data) {
        return api.post('/users', data);
    },
    updateUser(id, data) {
        return api.put(`/users/${id}`, data);
    },
    deleteUser(id) {
        return api.delete(`/users/${id}`);
    }
};
