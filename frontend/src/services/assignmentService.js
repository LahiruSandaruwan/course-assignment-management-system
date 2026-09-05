import api from './api';

export const assignmentService = {
    getAssignment(id) {
        return api.get(`/assignments/${id}`);
    },
    createAssignment(courseId, data) {
        return api.post(`/courses/${courseId}/assignments`, data);
    }
};
