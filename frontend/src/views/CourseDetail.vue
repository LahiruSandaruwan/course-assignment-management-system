<template>
  <div class="course-detail-container">
    <button class="back-btn" @click="$router.push('/courses')">&larr; Back to Courses</button>

    <LoadingState v-if="isLoading" message="Loading course details..." />
    
    <ErrorState v-else-if="error" :message="error" :retry="fetchCourseDetails" />
    
    <div v-else-if="course">
      <div class="course-header">
        <div class="header-content">
          <h1>{{ course.name }}</h1>
          <span class="status-badge" :class="course.status">{{ course.status }}</span>
        </div>
        
        <!-- Management Actions -->
        <div class="management-actions" v-if="canManage">
          <button class="edit-btn" :disabled="isEditing" @click="startEdit">Edit Course</button>
          <button
            class="archive-btn"
            v-if="course.status === 'active'"
            @click="archiveCourse"
          >
            Archive
          </button>
        </div>
      </div>

      <div v-if="archiveError" class="error-alert">{{ archiveError }}</div>

      <form v-if="isEditing" class="edit-form" @submit.prevent="saveEdit">
        <div v-if="editError" class="error-alert">{{ editError }}</div>

        <div class="form-row">
          <label for="edit-name">Name</label>
          <input id="edit-name" v-model="editForm.name" required maxlength="255" :disabled="isSavingEdit" />
        </div>

        <div class="form-row">
          <label for="edit-description">Description</label>
          <textarea id="edit-description" v-model="editForm.description" :disabled="isSavingEdit"></textarea>
        </div>

        <div class="form-row form-row-inline">
          <div>
            <label for="edit-start">Start Date</label>
            <input id="edit-start" type="date" v-model="editForm.start_date" required :disabled="isSavingEdit" />
          </div>
          <div>
            <label for="edit-end">End Date</label>
            <input id="edit-end" type="date" v-model="editForm.end_date" required :disabled="isSavingEdit" />
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" :disabled="isSavingEdit">{{ isSavingEdit ? 'Saving...' : 'Save Changes' }}</button>
          <button type="button" class="cancel-btn" :disabled="isSavingEdit" @click="cancelEdit">Cancel</button>
        </div>
      </form>

      <div class="course-meta">
        <p><strong>Instructor:</strong> {{ course.instructor_name || 'N/A' }}</p>
        <p><strong>Dates:</strong> {{ course.start_date }} to {{ course.end_date }}</p>
      </div>

      <div class="course-description" v-if="course.description">
        <h3>Description</h3>
        <p>{{ course.description }}</p>
      </div>

      <div class="course-tabs">
        <div class="tab-contents">
          <!-- Assignments Section -->
          <div class="section">
            <div class="section-header">
              <h3>Assignments ({{ course.assignments_count || 0 }})</h3>
              <button v-if="canManage" class="enroll-toggle-btn" @click="showCreateAssignmentModal = true">
                + Create Assignment
              </button>
            </div>

            <CreateAssignmentModal
              v-if="showCreateAssignmentModal"
              :course-id="courseId"
              @close="showCreateAssignmentModal = false"
              @created="handleAssignmentCreated"
            />

            <LoadingState v-if="isLoadingAssignments" message="Loading assignments..." />
            <ErrorState v-else-if="assignmentsError" :message="assignmentsError" :retry="fetchAssignments" />
            <div v-else-if="assignments.length > 0" class="list-group">
              <div 
                v-for="assignment in assignments" 
                :key="assignment.id" 
                class="list-item clickable"
                @click="$router.push(`/assignments/${assignment.id}`)"
              >
                <div class="item-title">{{ assignment.title }}</div>
                <div class="item-meta">Due: {{ new Date(assignment.due_date).toLocaleString() }}</div>
              </div>
              
              <!-- Pagination for assignments -->
              <div class="pagination" v-if="assignmentsTotalPages > 1">
                <button :disabled="assignmentsPage === 1" @click="fetchAssignments(assignmentsPage - 1)">Prev</button>
                <span>{{ assignmentsPage }} / {{ assignmentsTotalPages }}</span>
                <button :disabled="assignmentsPage === assignmentsTotalPages" @click="fetchAssignments(assignmentsPage + 1)">Next</button>
              </div>
            </div>
            <EmptyState v-else message="No assignments yet." />
          </div>

          <!-- Students Section -->
          <div class="section">
            <div class="section-header">
              <h3>Enrolled Students ({{ course.students_count || 0 }})</h3>
              <button v-if="canManage" class="enroll-toggle-btn" @click="toggleEnrollForm">
                {{ showEnrollForm ? 'Cancel' : '+ Enroll Student' }}
              </button>
            </div>

            <form v-if="canManage && showEnrollForm" class="enroll-form" @submit.prevent="handleEnroll">
              <div v-if="enrollError" class="error-alert">{{ enrollError }}</div>
              <label for="enroll-search">Student (name, email, or ID)</label>
              <div class="enroll-search-wrapper">
                <input
                  id="enroll-search"
                  type="text"
                  v-model="studentQuery"
                  placeholder="Start typing a name, email, or ID..."
                  autocomplete="off"
                  :disabled="isEnrolling"
                  @input="onStudentQueryInput"
                />

                <ul v-if="studentQuery && !selectedStudent" class="student-search-results">
                  <li v-if="isSearching" class="search-hint">Searching...</li>
                  <li v-else-if="searchError" class="search-hint search-hint-error">{{ searchError }}</li>
                  <li v-else-if="searchResults.length === 0" class="search-hint">No matching students found.</li>
                  <li
                    v-for="result in searchResults"
                    :key="result.id"
                    class="search-result-item"
                    @click="selectStudent(result)"
                  >
                    [{{ result.id }}] {{ result.name }} ({{ result.email }})
                  </li>
                </ul>
              </div>

              <div class="enroll-form-row">
                <button type="submit" :disabled="isEnrolling || !selectedStudent">
                  {{ isEnrolling ? 'Enrolling...' : 'Enroll' }}
                </button>
              </div>
            </form>

            <LoadingState v-if="isLoadingStudents" message="Loading students..." />
            <ErrorState v-else-if="studentsError" :message="studentsError" :retry="fetchStudents" />
            <div v-else-if="students.length > 0" class="list-group">
              <div v-for="student in students" :key="student.id" class="list-item student-item">
                <div>
                  <div class="item-title">{{ student.name }}</div>
                  <div class="item-meta">{{ student.email }}</div>
                </div>
                <button
                  v-if="canManage"
                  class="remove-student-btn"
                  @click="handleRemoveStudent(student)"
                >
                  Remove
                </button>
              </div>

              <!-- Pagination for students -->
              <div class="pagination" v-if="studentsTotalPages > 1">
                <button :disabled="studentsPage === 1" @click="fetchStudents(studentsPage - 1)">Prev</button>
                <span>{{ studentsPage }} / {{ studentsTotalPages }}</span>
                <button :disabled="studentsPage === studentsTotalPages" @click="fetchStudents(studentsPage + 1)">Next</button>
              </div>
            </div>
            <EmptyState v-else message="No students enrolled yet." />
          </div>
        </div>
      </div>
    </div>

    <ConfirmModal
      :show="confirmModal.show"
      :title="confirmModal.title"
      :message="confirmModal.message"
      :confirm-text="confirmModal.confirmText"
      :is-destructive="confirmModal.isDestructive"
      :is-loading="confirmModal.isLoading"
      @confirm="handleConfirmAccept"
      @cancel="closeConfirm"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { courseService } from '../services/courseService';
import { studentService } from '../services/studentService';
import LoadingState from '../components/LoadingState.vue';
import ErrorState from '../components/ErrorState.vue';
import EmptyState from '../components/EmptyState.vue';
import CreateAssignmentModal from '../components/CreateAssignmentModal.vue';
import ConfirmModal from '../components/ConfirmModal.vue';

const route = useRoute();
const authStore = useAuthStore();
const courseId = route.params.id;

// Course state
const course = ref(null);
const isLoading = ref(true);
const error = ref(null);

// Assignments state
const assignments = ref([]);
const isLoadingAssignments = ref(false);
const assignmentsError = ref(null);
const assignmentsPage = ref(1);
const assignmentsTotalPages = ref(1);

// Students state
const students = ref([]);
const isLoadingStudents = ref(false);
const studentsError = ref(null);
const studentsPage = ref(1);
const studentsTotalPages = ref(1);

// Management permission
const canManage = computed(() => {
  if (!course.value || !authStore.user) return false;
  return authStore.user.role === 'admin' || 
         (authStore.user.role === 'instructor' && course.value.instructor_id === authStore.user.id);
});

const fetchCourseDetails = async () => {
  isLoading.value = true;
  error.value = null;
  
  try {
    const response = await courseService.getCourse(courseId);
    course.value = response.data.data;
    
    // Fetch associated data once course is loaded
    fetchAssignments();
    fetchStudents();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load course details.';
  } finally {
    isLoading.value = false;
  }
};

const fetchAssignments = async (page = 1) => {
  isLoadingAssignments.value = true;
  assignmentsError.value = null;
  
  try {
    const response = await courseService.getAssignments(courseId, page);
    // See Courses.vue's fetchCourses for why this is data.data.data.
    assignments.value = response.data.data.data;

    const meta = response.data.data.meta;
    if (meta) {
      assignmentsPage.value = meta.current_page;
      assignmentsTotalPages.value = meta.last_page;
    }
  } catch (err) {
    assignmentsError.value = err.response?.data?.message || 'Failed to load assignments.';
  } finally {
    isLoadingAssignments.value = false;
  }
};

const fetchStudents = async (page = 1) => {
  isLoadingStudents.value = true;
  studentsError.value = null;
  
  try {
    const response = await courseService.getEnrolledStudents(courseId, page);
    students.value = response.data.data.data;

    const meta = response.data.data.meta;
    if (meta) {
      studentsPage.value = meta.current_page;
      studentsTotalPages.value = meta.last_page;
    }
  } catch (err) {
    // 403 Forbidden is normal if user is a student trying to view other students
    if (err.response?.status === 403) {
      studentsError.value = 'You do not have permission to view the student list.';
    } else {
      studentsError.value = err.response?.data?.message || 'Failed to load students.';
    }
  } finally {
    isLoadingStudents.value = false;
  }
};

// Edit form state
const isEditing = ref(false);
const isSavingEdit = ref(false);
const editError = ref(null);
const editForm = reactive({ name: '', description: '', start_date: '', end_date: '' });

const startEdit = () => {
  if (!course.value) return;
  editForm.name = course.value.name;
  editForm.description = course.value.description || '';
  editForm.start_date = course.value.start_date;
  editForm.end_date = course.value.end_date;
  editError.value = null;
  isEditing.value = true;
};

const cancelEdit = () => {
  isEditing.value = false;
  editError.value = null;
};

const saveEdit = async () => {
  if (isSavingEdit.value) return;
  isSavingEdit.value = true;
  editError.value = null;

  try {
    const response = await courseService.updateCourse(courseId, {
      name: editForm.name,
      description: editForm.description,
      start_date: editForm.start_date,
      end_date: editForm.end_date,
    });
    course.value = response.data.data;
    isEditing.value = false;
  } catch (err) {
    const validationErrors = err.response?.data?.errors;
    editError.value = validationErrors
      ? Object.values(validationErrors).flat().join(' ')
      : err.response?.data?.message || 'Failed to update course.';
  } finally {
    isSavingEdit.value = false;
  }
};

// Shared confirmation modal, driven by whichever action opened it
const confirmModal = reactive({
  show: false,
  title: '',
  message: '',
  confirmText: 'Confirm',
  isDestructive: false,
  isLoading: false,
});
let pendingConfirmAction = null;

const openConfirm = (options, action) => {
  Object.assign(confirmModal, { ...options, isLoading: false, show: true });
  pendingConfirmAction = action;
};

const closeConfirm = () => {
  confirmModal.show = false;
  pendingConfirmAction = null;
};

const handleConfirmAccept = async () => {
  if (!pendingConfirmAction) return;
  confirmModal.isLoading = true;
  try {
    await pendingConfirmAction();
  } finally {
    confirmModal.isLoading = false;
    closeConfirm();
  }
};

// Archive action
const archiveError = ref(null);

const performArchive = async () => {
  archiveError.value = null;

  try {
    const response = await courseService.updateCourse(courseId, { status: 'archived' });
    course.value = response.data.data;
  } catch (err) {
    archiveError.value = err.response?.data?.message || 'Failed to archive course.';
  }
};

const archiveCourse = () => {
  openConfirm({
    title: 'Archive Course',
    message: 'Archive this course? Students will no longer be able to submit new work.',
    confirmText: 'Archive',
    isDestructive: true,
  }, performArchive);
};

// Assignment creation (Admin/Instructor only)
const showCreateAssignmentModal = ref(false);

const handleAssignmentCreated = async () => {
  showCreateAssignmentModal.value = false;
  await Promise.all([refreshCourse(), fetchAssignments(1)]);
};

// Enrollment management (Admin/Instructor only)
const showEnrollForm = ref(false);
const isEnrolling = ref(false);
const enrollError = ref(null);

// Student search (name/email/ID) feeding the enroll form
const studentQuery = ref('');
const selectedStudent = ref(null);
const searchResults = ref([]);
const isSearching = ref(false);
const searchError = ref(null);
let searchDebounceTimer = null;

const runStudentSearch = async (query) => {
  isSearching.value = true;
  searchError.value = null;

  try {
    const response = await studentService.search(query);
    // Deliberately unpaginated on the backend, so this is a plain array —
    // not the response.data.data.data pattern paginated endpoints use.
    searchResults.value = response.data.data;
  } catch (err) {
    searchResults.value = [];
    searchError.value = err.response?.data?.message || 'Failed to search students.';
  } finally {
    isSearching.value = false;
  }
};

const onStudentQueryInput = () => {
  selectedStudent.value = null;
  clearTimeout(searchDebounceTimer);

  const query = studentQuery.value.trim();
  if (!query) {
    searchResults.value = [];
    isSearching.value = false;
    return;
  }

  searchDebounceTimer = setTimeout(() => runStudentSearch(query), 300);
};

const selectStudent = (student) => {
  selectedStudent.value = student;
  studentQuery.value = `${student.name} (${student.email})`;
  searchResults.value = [];
};

const resetEnrollForm = () => {
  studentQuery.value = '';
  selectedStudent.value = null;
  searchResults.value = [];
  enrollError.value = null;
  showEnrollForm.value = false;
};

const toggleEnrollForm = () => {
  if (showEnrollForm.value) {
    resetEnrollForm();
  } else {
    showEnrollForm.value = true;
  }
};

const refreshCourse = async () => {
  try {
    const response = await courseService.getCourse(courseId);
    course.value = response.data.data;
  } catch (err) {
    // Non-critical: the header counts may be briefly stale.
  }
};

const handleEnroll = async () => {
  if (isEnrolling.value || !selectedStudent.value) return;
  isEnrolling.value = true;
  enrollError.value = null;

  try {
    await courseService.enrollStudent(courseId, selectedStudent.value.id);
    resetEnrollForm();
    await Promise.all([refreshCourse(), fetchStudents(studentsPage.value)]);
  } catch (err) {
    const validationErrors = err.response?.data?.errors;
    enrollError.value = validationErrors?.user_id?.[0]
      || err.response?.data?.message
      || 'Failed to enroll student.';
  } finally {
    isEnrolling.value = false;
  }
};

const performRemoveStudent = async (student) => {
  try {
    await courseService.removeStudent(courseId, student.id);
    await Promise.all([refreshCourse(), fetchStudents(studentsPage.value)]);
  } catch (err) {
    studentsError.value = err.response?.data?.message || 'Failed to remove student.';
  }
};

const handleRemoveStudent = (student) => {
  openConfirm({
    title: 'Remove Student',
    message: `Remove ${student.name} from this course?`,
    confirmText: 'Remove',
    isDestructive: true,
  }, () => performRemoveStudent(student));
};

onMounted(() => {
  fetchCourseDetails();
});

onUnmounted(() => {
  clearTimeout(searchDebounceTimer);
});
</script>

<style scoped>
.course-detail-container {
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
  margin-bottom: 2rem;
  font-size: 1rem;
}

.back-btn:hover {
  text-decoration: underline;
}

.course-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e5e7eb;
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

.status-badge.active { background-color: #dcfce7; color: #166534; }
.status-badge.archived { background-color: #f3f4f6; color: #4b5563; }

.management-actions {
  display: flex;
  gap: 0.75rem;
}

.management-actions button {
  padding: 0.5rem 1rem;
  border-radius: 4px;
  font-weight: 500;
  cursor: pointer;
}

.edit-btn {
  background-color: #f3f4f6;
  border: 1px solid #d1d5db;
  color: #374151;
}
.edit-btn:hover { background-color: #e5e7eb; }

.archive-btn {
  background-color: #fee2e2;
  border: 1px solid #fca5a5;
  color: #b91c1c;
}
.archive-btn:hover { background-color: #fecaca; }

.error-alert {
  background-color: #fee2e2;
  color: #b91c1c;
  padding: 0.75rem;
  border-radius: 4px;
  margin-bottom: 1.5rem;
  font-size: 0.875rem;
}

.edit-form {
  background-color: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.edit-form .form-row {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.edit-form .form-row-inline {
  flex-direction: row;
  gap: 1.5rem;
}

.edit-form .form-row-inline > div {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.edit-form label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}

.edit-form input,
.edit-form textarea {
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 1rem;
  font-family: inherit;
}

.edit-form textarea {
  min-height: 5rem;
  resize: vertical;
}

.form-actions {
  display: flex;
  gap: 0.75rem;
}

.form-actions button {
  padding: 0.5rem 1rem;
  border-radius: 4px;
  font-weight: 500;
  cursor: pointer;
}

.form-actions button[type='submit'] {
  background-color: #3b82f6;
  border: 1px solid #3b82f6;
  color: white;
}

.form-actions button[type='submit']:disabled,
.management-actions button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.cancel-btn {
  background-color: white;
  border: 1px solid #d1d5db;
  color: #374151;
}

.course-meta {
  background-color: #f9fafb;
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 2rem;
}

.course-meta p {
  margin: 0.5rem 0;
  color: #4b5563;
}

.course-description {
  margin-bottom: 3rem;
}

.course-description h3 {
  color: #111827;
  margin-bottom: 1rem;
}

.course-description p {
  line-height: 1.6;
  color: #374151;
  white-space: pre-wrap;
}

.tab-contents {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}

@media (max-width: 768px) {
  .tab-contents {
    grid-template-columns: 1fr;
  }
}

.section h3 {
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #e5e7eb;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
}

.section-header h3 {
  margin: 0;
  border-bottom: none;
  padding-bottom: 0;
}

.enroll-toggle-btn {
  background-color: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1d4ed8;
  padding: 0.35rem 0.75rem;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: 500;
  cursor: pointer;
}

.enroll-toggle-btn:hover {
  background-color: #dbeafe;
}

.enroll-form {
  background-color: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.enroll-form label {
  display: block;
  font-size: 0.8rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.375rem;
}

.enroll-search-wrapper {
  position: relative;
  margin-bottom: 0.75rem;
}

.enroll-search-wrapper input {
  width: 100%;
  padding: 0.4rem 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 0.875rem;
  box-sizing: border-box;
}

.enroll-search-wrapper input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 1px #3b82f6;
}

.student-search-results {
  position: absolute;
  top: calc(100% + 0.25rem);
  left: 0;
  right: 0;
  z-index: 10;
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  max-height: 200px;
  overflow-y: auto;
  list-style: none;
  margin: 0;
  padding: 0.25rem 0;
}

.search-result-item {
  padding: 0.5rem 0.75rem;
  font-size: 0.85rem;
  cursor: pointer;
}

.search-result-item:hover {
  background-color: #eff6ff;
}

.search-hint {
  padding: 0.5rem 0.75rem;
  font-size: 0.8rem;
  color: #6b7280;
}

.search-hint-error {
  color: #b91c1c;
}

.enroll-form-row {
  display: flex;
  gap: 0.5rem;
}

.enroll-form-row button {
  background-color: #3b82f6;
  border: none;
  color: white;
  padding: 0.4rem 0.9rem;
  border-radius: 4px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  white-space: nowrap;
}

.enroll-form-row button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.student-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
}

.remove-student-btn {
  background-color: white;
  border: 1px solid #fca5a5;
  color: #b91c1c;
  padding: 0.3rem 0.7rem;
  border-radius: 4px;
  font-size: 0.8rem;
  cursor: pointer;
  white-space: nowrap;
}

.remove-student-btn:hover:not(:disabled) {
  background-color: #fee2e2;
}

.remove-student-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.list-group {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.list-item {
  padding: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  background-color: white;
}

.list-item.clickable {
  cursor: pointer;
  transition: border-color 0.2s, background-color 0.2s;
}

.list-item.clickable:hover {
  border-color: #3b82f6;
  background-color: #eff6ff;
}

.item-title {
  font-weight: 500;
  color: #111827;
  margin-bottom: 0.25rem;
}

.item-meta {
  font-size: 0.875rem;
  color: #6b7280;
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 1rem;
  font-size: 0.875rem;
}

.pagination button {
  padding: 0.25rem 0.5rem;
  border: 1px solid #d1d5db;
  background: white;
  border-radius: 4px;
  cursor: pointer;
}

.pagination button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
