import api from './api';

export const courseService = {
    getCourses(page = 1) {
        return api.get(`/courses?page=${page}`);
    },
    getCourse(id) {
        return api.get(`/courses/${id}`);
    },
    createCourse(data) {
        return api.post('/courses', data);
    },
    updateCourse(id, data) {
        return api.put(`/courses/${id}`, data);
    },
    deleteCourse(id) {
        return api.delete(`/courses/${id}`);
    },
    getEnrolledStudents(courseId, page = 1) {
        return api.get(`/courses/${courseId}/students?page=${page}`);
    },
    getAssignments(courseId, page = 1) {
        return api.get(`/courses/${courseId}/assignments?page=${page}`);
    },
    enrollStudent(courseId, userId) {
        return api.post(`/courses/${courseId}/students`, { user_id: userId });
    },
    removeStudent(courseId, studentId) {
        return api.delete(`/courses/${courseId}/students/${studentId}`);
    }
};
