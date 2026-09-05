<template>
  <div class="assignment-detail-container">
    <div class="header-actions">
      <button class="back-btn" @click="$router.back()">&larr; Back</button>
    </div>

    <LoadingState v-if="isLoading" message="Loading assignment details..." />
    <ErrorState v-else-if="error" :message="error" :retry="fetchAssignmentDetails" />

    <div v-else-if="assignment">
      <!-- Assignment Header -->
      <div class="assignment-header">
        <div class="header-content">
          <h1>{{ assignment.title }}</h1>
          <span class="status-badge" :class="assignment.status">{{ assignment.status }}</span>
        </div>

        <div class="management-actions" v-if="isInstructorOrAdmin">
          <button class="view-submissions-btn" @click="goToSubmissions">View Submissions</button>
        </div>
      </div>

      <div class="assignment-meta">
        <p><strong>Course:</strong> 
          <router-link :to="`/courses/${assignment.course_id}`" class="course-link">
            View Course
          </router-link>
        </p>
        <p><strong>Due Date:</strong> {{ new Date(assignment.due_date).toLocaleString() }}</p>
        <p><strong>Max Score:</strong> {{ assignment.max_score }}</p>
      </div>
      
      <div class="assignment-description" v-if="assignment.description">
        <h3>Instructions</h3>
        <p>{{ assignment.description }}</p>
      </div>

      <!-- Student Submission Section -->
      <div class="submission-section" v-if="isStudent && assignment.status === 'published'">
        <h3>Your Submission</h3>
        
        <LoadingState v-if="isLoadingSubmission" message="Loading your submission..." />
        
        <div v-else>
          <!-- Submission Graded -->
          <div v-if="mySubmission?.status === 'graded'" class="graded-submission">
            <div class="grade-box">
              <div class="grade-score">
                <span class="score-label">Score:</span> 
                <span class="score-value">{{ mySubmission.score }} / {{ assignment.max_score }}</span>
              </div>
              <div class="instructor-feedback" v-if="mySubmission.instructor_feedback">
                <h4>Instructor Feedback:</h4>
                <p>{{ mySubmission.instructor_feedback }}</p>
              </div>
            </div>
            
            <div class="submitted-content">
              <h4>Your Answer:</h4>
              <p class="readonly-text">{{ mySubmission.submission_text }}</p>
            </div>
          </div>

          <!-- Submission Edit Form -->
          <div v-else class="submission-form-container">
            <div v-if="successMessage" class="success-alert">
              {{ successMessage }}
            </div>
            <div v-if="submissionError" class="error-alert">
              {{ submissionError }}
            </div>

            <form @submit.prevent="submitAssignment" class="submission-form">
              <div class="form-group">
                <label for="submissionText">Answer (Required)</label>
                <textarea 
                  id="submissionText" 
                  v-model="submissionText" 
                  rows="6" 
                  required 
                  maxlength="5000"
                  :disabled="isSubmitting"
                  placeholder="Enter your submission here..."
                ></textarea>
                <div class="character-count">
                  {{ submissionText.length }} / 5000
                </div>
              </div>

              <div class="form-actions">
                <button type="submit" :disabled="isSubmitting || !submissionText.trim()">
                  {{ isSubmitting ? 'Submitting...' : (mySubmission ? 'Update Submission' : 'Submit Assignment') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <!-- Fallbacks for unauthorized/draft -->
      <div v-else-if="isStudent && assignment.status === 'draft'" class="empty-state">
        <p>This assignment is not published yet. You cannot submit.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { assignmentService } from '../services/assignmentService';
import { submissionService } from '../services/submissionService';
import LoadingState from '../components/LoadingState.vue';
import ErrorState from '../components/ErrorState.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const assignmentId = route.params.id;

const assignment = ref(null);
const isLoading = ref(true);
const error = ref(null);

const mySubmission = ref(null);
const isLoadingSubmission = ref(false);

const submissionText = ref('');
const isSubmitting = ref(false);
const submissionError = ref(null);
const successMessage = ref(null);

const isStudent = computed(() => {
  return authStore.user && authStore.user.role === 'student';
});

const isInstructorOrAdmin = computed(() => {
  return authStore.user && ['admin', 'instructor'].includes(authStore.user.role);
});

const goToSubmissions = () => {
  if (!assignment.value) return;

  router.push({
    name: 'Submissions',
    params: { id: assignment.value.course_id, assignmentId: assignmentId },
  });
};

const fetchAssignmentDetails = async () => {
  isLoading.value = true;
  error.value = null;
  
  try {
    const response = await assignmentService.getAssignment(assignmentId);
    assignment.value = response.data.data;
    
    if (isStudent.value && assignment.value.status === 'published') {
      await fetchMySubmission();
    }
  } catch (err) {
    if (err.response?.status === 404) {
      // Defense in depth: the backend already sends a clean message for a
      // genuine 404, but never rely on that alone for user-facing text.
      error.value = 'This assignment could not be found. It may have been removed.';
    } else {
      // Handling 403 naturally if user tries to bypass
      error.value = err.response?.data?.message || 'Failed to load assignment.';
    }
  } finally {
    isLoading.value = false;
  }
};

const fetchMySubmission = async () => {
  isLoadingSubmission.value = true;
  try {
    const response = await submissionService.getMySubmission(assignmentId);
    mySubmission.value = response.data.data;
    if (mySubmission.value) {
      submissionText.value = mySubmission.value.submission_text;
    }
  } catch (err) {
    if (err.response?.status !== 404) {
      console.error('Failed to load submission status', err);
    }
    mySubmission.value = null;
  } finally {
    isLoadingSubmission.value = false;
  }
};

const submitAssignment = async () => {
  if (!submissionText.value.trim()) return;
  
  isSubmitting.value = true;
  submissionError.value = null;
  successMessage.value = null;
  
  try {
    const response = await submissionService.submitAssignment(assignmentId, submissionText.value);
    mySubmission.value = response.data.data;
    successMessage.value = 'Assignment submitted successfully!';
    
    // Clear success message after 3 seconds
    setTimeout(() => {
      successMessage.value = null;
    }, 3000);
  } catch (err) {
    if (err.response?.status === 422) {
      submissionError.value = err.response.data.message || 'Validation failed.';
      if (err.response.data.errors?.submission_text) {
        submissionError.value = err.response.data.errors.submission_text[0];
      }
    } else {
      submissionError.value = 'Failed to submit assignment. Please try again.';
    }
  } finally {
    isSubmitting.value = false;
  }
};

onMounted(() => {
  fetchAssignmentDetails();
});
</script>

<style scoped>
.assignment-detail-container {
  max-width: 900px;
  margin: 0 auto;
  padding: 2rem;
}

.back-btn {
  background: none;
  border: none;
  color: #3b82f6;
  cursor: pointer;
  padding: 0;
  margin-bottom: 2rem;
  font-size: 1rem;
}

.back-btn:hover {
  text-decoration: underline;
}

.assignment-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.view-submissions-btn {
  background-color: #3b82f6;
  border: 1px solid #3b82f6;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  font-weight: 500;
  font-size: 0.875rem;
  cursor: pointer;
  white-space: nowrap;
}

.view-submissions-btn:hover {
  background-color: #2563eb;
}

.header-content h1 {
  margin: 0 0 0.5rem 0;
  color: #111827;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: capitalize;
}

.status-badge.published { background-color: #dcfce7; color: #166534; }
.status-badge.draft { background-color: #f3f4f6; color: #4b5563; }

.assignment-meta {
  background-color: #f9fafb;
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 2rem;
}

.assignment-meta p {
  margin: 0.5rem 0;
  color: #4b5563;
}

.course-link {
  color: #3b82f6;
  text-decoration: none;
}

.course-link:hover {
  text-decoration: underline;
}

.assignment-description {
  margin-bottom: 3rem;
}

.assignment-description h3 {
  color: #111827;
  margin-bottom: 1rem;
}

.assignment-description p {
  line-height: 1.6;
  color: #374151;
  white-space: pre-wrap;
}

.submission-section {
  border-top: 2px solid #e5e7eb;
  padding-top: 2rem;
}

.submission-section h3 {
  margin-bottom: 1.5rem;
  color: #111827;
}

.graded-submission {
  background-color: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1.5rem;
}

.grade-box {
  background-color: white;
  border: 1px solid #dcfce7;
  border-radius: 6px;
  padding: 1rem;
  margin-bottom: 1.5rem;
  border-left: 4px solid #22c55e;
}

.grade-score {
  font-size: 1.25rem;
  font-weight: 600;
  color: #166534;
  margin-bottom: 0.5rem;
}

.score-label {
  color: #4b5563;
  font-size: 1rem;
  font-weight: normal;
}

.instructor-feedback {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px dashed #e2e8f0;
}

.instructor-feedback h4 {
  margin: 0 0 0.5rem 0;
  color: #475569;
  font-size: 0.875rem;
}

.instructor-feedback p {
  margin: 0;
  color: #334155;
  font-style: italic;
}

.submitted-content h4 {
  margin: 0 0 0.5rem 0;
  color: #475569;
}

.readonly-text {
  background-color: white;
  padding: 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  white-space: pre-wrap;
  color: #334155;
  line-height: 1.6;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #374151;
}

textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-family: inherit;
  font-size: 1rem;
  resize: vertical;
  box-sizing: border-box;
}

textarea:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 1px #3b82f6;
}

.character-count {
  text-align: right;
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

button[type="submit"] {
  background-color: #3b82f6;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 4px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

button[type="submit"]:hover:not(:disabled) {
  background-color: #2563eb;
}

button[type="submit"]:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.success-alert {
  background-color: #dcfce7;
  color: #166534;
  padding: 1rem;
  border-radius: 6px;
  margin-bottom: 1.5rem;
  border: 1px solid #bbf7d0;
}

.error-alert {
  background-color: #fee2e2;
  color: #b91c1c;
  padding: 1rem;
  border-radius: 6px;
  margin-bottom: 1.5rem;
  border: 1px solid #fecaca;
}

.empty-state {
  text-align: center;
  padding: 2rem;
  background-color: #f9fafb;
  border-radius: 8px;
  color: #6b7280;
}
</style>
