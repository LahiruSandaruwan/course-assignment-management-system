import api from './api';

export const assignmentService = {
    getAssignment(id) {
        return api.get(`/assignments/${id}`);
    }
};
