<template>
  <div class="submissions-container">
    <div class="header-actions">
      <button class="back-btn" @click="$router.back()">&larr; Back to Assignment</button>
    </div>

    <h1>Submissions</h1>

    <LoadingState v-if="isLoadingAssignment" message="Loading assignment..." />
    <ErrorState v-else-if="assignmentError" :message="assignmentError" :retry="fetchAssignmentAndSubmissions" />
    
    <div v-else-if="assignment">
      <div class="assignment-info">
        <h2>{{ assignment.title }}</h2>
        <p><strong>Max Score:</strong> {{ assignment.max_score }}</p>
      </div>

      <LoadingState v-if="isLoading" message="Loading submissions..." />
      <ErrorState v-else-if="error" :message="error" :retry="fetchSubmissions" />
      
      <div v-else>
        <div v-if="submissions.length === 0" class="empty-state">
          <p>No submissions found for this assignment.</p>
        </div>
        
        <div v-else class="submissions-list">
          <div 
            v-for="submission in submissions" 
            :key="submission.id" 
            class="submission-card"
            :class="{ 'is-graded': submission.status === 'graded' }"
          >
            <div class="submission-header">
              <div class="student-info">
                <h3>{{ submission.student?.name || 'Unknown Student' }}</h3>
                <span class="student-email">{{ submission.student?.email }}</span>
              </div>
              <div class="submission-meta">
                <span class="status-badge" :class="submission.status">{{ submission.status }}</span>
                <span class="submit-time">{{ new Date(submission.submitted_at).toLocaleString() }}</span>
              </div>
            </div>

            <div class="submission-content">
              <h4>Submission:</h4>
              <p class="readonly-text">{{ submission.submission_text }}</p>
            </div>

            <div class="grading-section">
              <div v-if="submission.status === 'graded' && !editing[submission.id]" class="graded-view">
                <div class="grade-score">
                  <span class="score-label">Score:</span> 
                  <span class="score-value">{{ submission.score }} / {{ assignment.max_score }}</span>
                </div>
                <div class="instructor-feedback" v-if="submission.instructor_feedback">
                  <h4>Feedback:</h4>
                  <p>{{ submission.instructor_feedback }}</p>
                </div>
                <button class="edit-grade-btn" @click="editGrade(submission)">Edit Grade</button>
              </div>

              <div v-else class="grading-form-container">
                <h4>{{ submission.status === 'graded' ? 'Edit Grade' : 'Grade Submission' }}</h4>
                
                <div v-if="errors[submission.id]" class="error-alert">
                  {{ errors[submission.id] }}
                </div>

                <form @submit.prevent="submitGrade(submission)" class="grading-form">
                  <div class="form-row">
                    <div class="form-group score-group">
                      <label :for="'score-' + submission.id">Score (0 - {{ assignment.max_score }})</label>
                      <input 
                        type="number" 
                        :id="'score-' + submission.id" 
                        v-model.number="forms[submission.id].score" 
                        min="0" 
                        :max="assignment.max_score" 
                        required
                        :disabled="isSaving[submission.id]"
                        @input="validateScore(submission.id)"
                      />
                    </div>
                  </div>

                  <div class="form-group">
                    <label :for="'feedback-' + submission.id">Feedback (Optional)</label>
                    <textarea 
                      :id="'feedback-' + submission.id" 
                      v-model="forms[submission.id].feedback" 
                      rows="3"
                      :disabled="isSaving[submission.id]"
                      maxlength="2000"
                    ></textarea>
                  </div>

                  <div class="form-actions">
                    <button 
                      type="button" 
                      class="cancel-btn" 
                      v-if="submission.status === 'graded'"
                      @click="cancelEdit(submission.id)"
                      :disabled="isSaving[submission.id]"
                    >
                      Cancel
                    </button>
                    <button 
                      type="submit" 
                      class="submit-btn"
                      :disabled="isSaving[submission.id] || hasValidationError(submission.id)"
                    >
                      {{ isSaving[submission.id] ? 'Saving...' : 'Save Grade' }}
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination && pagination.last_page > 1" class="pagination">
          <button 
            :disabled="currentPage === 1" 
            @click="changePage(currentPage - 1)"
          >
            Previous
          </button>
          <span>Page {{ currentPage }} of {{ pagination.last_page }}</span>
          <button 
            :disabled="currentPage === pagination.last_page" 
            @click="changePage(currentPage + 1)"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import { useRoute } from 'vue-router';
import { assignmentService } from '../services/assignmentService';
import { submissionService } from '../services/submissionService';
import LoadingState from '../components/LoadingState.vue';
import ErrorState from '../components/ErrorState.vue';

const route = useRoute();
const assignmentId = route.params.assignmentId;

const assignment = ref(null);
const isLoadingAssignment = ref(true);
const assignmentError = ref(null);

const submissions = ref([]);
const isLoading = ref(true);
const error = ref(null);
const currentPage = ref(1);
const pagination = ref(null);

// Row-level states
const isSaving = reactive({});
const errors = reactive({});
const forms = reactive({});
const editing = reactive({});

const fetchAssignmentAndSubmissions = async () => {
  isLoadingAssignment.value = true;
  assignmentError.value = null;
  
  try {
    const res = await assignmentService.getAssignment(assignmentId);
    assignment.value = res.data.data;
    await fetchSubmissions(1);
  } catch (err) {
    assignmentError.value = 'Failed to load assignment information.';
  } finally {
    isLoadingAssignment.value = false;
  }
};

const fetchSubmissions = async (page = currentPage.value) => {
  isLoading.value = true;
  error.value = null;
  
  try {
    const response = await submissionService.listSubmissions(assignmentId, page);
    submissions.value = response.data.data.data;
    pagination.value = response.data.data.meta;
    currentPage.value = page;
    
    // Initialize forms for each submission
    submissions.value.forEach(sub => {
      initForm(sub);
    });
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load submissions.';
  } finally {
    isLoading.value = false;
  }
};

const changePage = (page) => {
  fetchSubmissions(page);
};

const initForm = (submission) => {
  forms[submission.id] = {
    score: submission.status === 'graded' ? submission.score : null,
    feedback: submission.status === 'graded' ? (submission.instructor_feedback || '') : ''
  };
  isSaving[submission.id] = false;
  errors[submission.id] = null;
  editing[submission.id] = false;
};

const editGrade = (submission) => {
  editing[submission.id] = true;
  initForm(submission);
};

const cancelEdit = (submissionId) => {
  editing[submissionId] = false;
  errors[submissionId] = null;
};

const validateScore = (submissionId) => {
  const score = forms[submissionId].score;
  const max = assignment.value.max_score;
  
  if (score === null || score === '') {
    errors[submissionId] = null;
    return;
  }
  
  if (score < 0 || score > max) {
    errors[submissionId] = `Score must be between 0 and ${max}.`;
  } else {
    errors[submissionId] = null;
  }
};

const hasValidationError = (submissionId) => {
  return errors[submissionId] !== null;
};

const submitGrade = async (submission) => {
  const subId = submission.id;
  
  // Client-side guard
  validateScore(subId);
  if (hasValidationError(subId)) return;
  
  const score = forms[subId].score;
  if (score === null || score === '') {
    errors[subId] = 'Score is required.';
    return;
  }
  
  // Prevent duplicate submissions
  if (isSaving[subId]) return;
  
  isSaving[subId] = true;
  errors[subId] = null;
  
  try {
    const response = await submissionService.gradeSubmission(subId, {
      score: forms[subId].score,
      instructor_feedback: forms[subId].feedback
    });
    
    // Update the submission object in place
    const updated = response.data.data;
    const index = submissions.value.findIndex(s => s.id === subId);
    if (index !== -1) {
      submissions.value[index] = updated;
    }
    
    editing[subId] = false;
  } catch (err) {
    if (err.response?.status === 422) {
      const msg = err.response.data.message || 'Validation failed.';
      const detailedError = err.response.data.errors?.score?.[0] || err.response.data.errors?.instructor_feedback?.[0];
      errors[subId] = detailedError || msg;
    } else {
      errors[subId] = 'Failed to save grade. Please try again.';
    }
  } finally {
    isSaving[subId] = false;
  }
};

onMounted(() => {
  fetchAssignmentAndSubmissions();
});
</script>

<style scoped>
.submissions-container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 2rem;
}

.back-btn {
  background: none;
  border: none;
  color: #3b82f6;
  cursor: pointer;
  padding: 0;
  margin-bottom: 1.5rem;
  font-size: 1rem;
}

.back-btn:hover {
  text-decoration: underline;
}

h1 {
  margin-top: 0;
  margin-bottom: 1rem;
  color: #111827;
}

.assignment-info {
  background-color: #f9fafb;
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 2rem;
  border-left: 4px solid #3b82f6;
}

.assignment-info h2 {
  margin: 0 0 0.5rem 0;
  color: #1f2937;
  font-size: 1.25rem;
}

.assignment-info p {
  margin: 0;
  color: #4b5563;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  background-color: #f9fafb;
  border-radius: 8px;
  color: #6b7280;
  border: 1px dashed #d1d5db;
}

.submissions-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.submission-card {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background-color: white;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.submission-card.is-graded {
  border-color: #dcfce7;
}

.submission-header {
  padding: 1.25rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  background-color: #f9fafb;
}

.student-info h3 {
  margin: 0 0 0.25rem 0;
  color: #111827;
  font-size: 1.125rem;
}

.student-email {
  color: #6b7280;
  font-size: 0.875rem;
}

.submission-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.5rem;
}

.submit-time {
  font-size: 0.875rem;
  color: #6b7280;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.6rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
}

.status-badge.graded { background-color: #dcfce7; color: #166534; }
.status-badge.submitted { background-color: #dbeafe; color: #1e40af; }

.submission-content {
  padding: 1.25rem;
  border-bottom: 1px solid #e5e7eb;
}

.submission-content h4 {
  margin: 0 0 0.75rem 0;
  color: #374151;
  font-size: 1rem;
}

.readonly-text {
  margin: 0;
  white-space: pre-wrap;
  color: #334155;
  line-height: 1.6;
  background-color: #f8fafc;
  padding: 1rem;
  border-radius: 4px;
  border: 1px solid #e2e8f0;
}

.grading-section {
  padding: 1.25rem;
  background-color: #fff;
}

.graded-view {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.grade-score {
  font-size: 1.25rem;
  font-weight: 600;
  color: #166534;
}

.score-label {
  color: #4b5563;
  font-size: 1rem;
  font-weight: normal;
}

.instructor-feedback h4 {
  margin: 0 0 0.25rem 0;
  color: #475569;
  font-size: 0.875rem;
}

.instructor-feedback p {
  margin: 0;
  color: #334155;
  font-style: italic;
  background-color: #f8fafc;
  padding: 0.75rem;
  border-radius: 4px;
  border-left: 3px solid #94a3b8;
}

.edit-grade-btn {
  align-self: flex-start;
  background-color: white;
  color: #3b82f6;
  border: 1px solid #3b82f6;
  padding: 0.375rem 0.75rem;
  border-radius: 4px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
}

.edit-grade-btn:hover {
  background-color: #eff6ff;
}

.grading-form-container h4 {
  margin: 0 0 1rem 0;
  color: #111827;
}

.error-alert {
  background-color: #fee2e2;
  color: #b91c1c;
  padding: 0.75rem;
  border-radius: 4px;
  margin-bottom: 1rem;
  font-size: 0.875rem;
  border: 1px solid #fecaca;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.375rem;
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.score-group {
  width: 150px;
}

input[type="number"], textarea {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-family: inherit;
  font-size: 0.875rem;
  box-sizing: border-box;
}

input[type="number"]:focus, textarea:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 1px #3b82f6;
}

.form-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 1.25rem;
}

.submit-btn {
  background-color: #10b981;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.submit-btn:hover:not(:disabled) {
  background-color: #059669;
}

.submit-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.cancel-btn {
  background-color: white;
  color: #4b5563;
  border: 1px solid #d1d5db;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  font-weight: 500;
  cursor: pointer;
}

.cancel-btn:hover:not(:disabled) {
  background-color: #f3f4f6;
}

.cancel-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-top: 2rem;
}

.pagination button {
  padding: 0.5rem 1rem;
  border: 1px solid #d1d5db;
  background-color: white;
  border-radius: 4px;
  cursor: pointer;
}

.pagination button:hover:not(:disabled) {
  background-color: #f3f4f6;
}

.pagination button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
