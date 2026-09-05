<template>
  <div>
    <AppHeader />

    <div class="courses-container">
      <div class="page-toolbar">
        <h1>Courses</h1>
        <button v-if="canCreateCourse" class="create-course-btn" @click="showCreateModal = true">
          + Create Course
        </button>
      </div>

      <CreateCourseModal
        v-if="showCreateModal"
        @close="showCreateModal = false"
        @created="handleCourseCreated"
      />

      <LoadingState v-if="isLoading" message="Loading courses..." />

      <ErrorState v-else-if="error" :message="error" :retry="fetchCourses" />

      <div v-else-if="courses.length > 0">
        <div class="course-grid">
          <CourseCard
            v-for="course in courses"
            :key="course.id"
            :course="course"
          />
        </div>

        <!-- Pagination -->
        <div class="pagination" v-if="totalPages > 1">
          <button
            :disabled="currentPage === 1"
            @click="fetchCourses(currentPage - 1)"
          >
            Previous
          </button>
          <span>Page {{ currentPage }} of {{ totalPages }}</span>
          <button
            :disabled="currentPage === totalPages"
            @click="fetchCourses(currentPage + 1)"
          >
            Next
          </button>
        </div>
      </div>

      <EmptyState v-else message="You don't have any courses yet." />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import { courseService } from '../services/courseService';
import AppHeader from '../components/AppHeader.vue';
import CreateCourseModal from '../components/CreateCourseModal.vue';
import CourseCard from '../components/CourseCard.vue';
import LoadingState from '../components/LoadingState.vue';
import ErrorState from '../components/ErrorState.vue';
import EmptyState from '../components/EmptyState.vue';

const authStore = useAuthStore();

const courses = ref([]);
const isLoading = ref(true);
const error = ref(null);

const currentPage = ref(1);
const totalPages = ref(1);

const showCreateModal = ref(false);
const canCreateCourse = computed(() => ['admin', 'instructor'].includes(authStore.user?.role));

const handleCourseCreated = () => {
  showCreateModal.value = false;
  fetchCourses(1);
};

const fetchCourses = async (page = 1) => {
  isLoading.value = true;
  error.value = null;
  
  try {
    const response = await courseService.getCourses(page);
    // The backend wraps every response as { message, data: {...} }, and a
    // paginated list's own shape ({ data, links, meta }) sits inside that
    // — so the actual rows are at response.data.data.data, not .data.data.
    courses.value = response.data.data.data;

    // Setup pagination data
    const meta = response.data.data.meta;
    if (meta) {
      currentPage.value = meta.current_page;
      totalPages.value = meta.last_page;
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to fetch courses.';
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  fetchCourses();
});
</script>

<style scoped>
.courses-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.page-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.page-toolbar h1 {
  margin: 0;
  color: #111827;
}

.create-course-btn {
  background-color: #3b82f6;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  font-weight: 500;
  cursor: pointer;
}

.create-course-btn:hover {
  background-color: #2563eb;
}

.course-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
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
  background-color: white;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  cursor: pointer;
}

.pagination button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
