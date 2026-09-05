import api from './api';

export const submissionService = {
    getMySubmission(assignmentId) {
        return api.get(`/assignments/${assignmentId}/submissions/mine`);
    },
    submitAssignment(assignmentId, text) {
        return api.post(`/assignments/${assignmentId}/submissions`, { submission_text: text });
    },
    listSubmissions(assignmentId, page = 1) {
        return api.get(`/assignments/${assignmentId}/submissions?page=${page}`);
    },
    gradeSubmission(submissionId, data) {
        return api.put(`/submissions/${submissionId}/grade`, data);
    }
};
