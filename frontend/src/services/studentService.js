import api from './api';

export const studentService = {
    search(query) {
        return api.get('/students/search', { params: { q: query } });
    }
};
