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
          <button class="edit-btn">Edit Course</button>
          <button class="archive-btn" v-if="course.status === 'active'">Archive</button>
        </div>
      </div>

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
            <h3>Assignments ({{ course.assignments_count || 0 }})</h3>
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
            <h3>Enrolled Students ({{ course.students_count || 0 }})</h3>
            <LoadingState v-if="isLoadingStudents" message="Loading students..." />
            <ErrorState v-else-if="studentsError" :message="studentsError" :retry="fetchStudents" />
            <div v-else-if="students.length > 0" class="list-group">
              <div v-for="student in students" :key="student.id" class="list-item">
                <div class="item-title">{{ student.name }}</div>
                <div class="item-meta">{{ student.email }}</div>
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
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { courseService } from '../services/courseService';
import LoadingState from '../components/LoadingState.vue';
import ErrorState from '../components/ErrorState.vue';
import EmptyState from '../components/EmptyState.vue';

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
    assignments.value = response.data.data;
    
    const meta = response.data.meta;
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
    students.value = response.data.data;
    
    const meta = response.data.meta;
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

onMounted(() => {
  fetchCourseDetails();
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
