import api from './api';

export const submissionService = {
    getMySubmission(assignmentId) {
        return api.get(`/assignments/${assignmentId}/submissions/mine`);
    },
    submitAssignment(assignmentId, text) {
        return api.post(`/assignments/${assignmentId}/submissions`, { submission_text: text });
    }
};
