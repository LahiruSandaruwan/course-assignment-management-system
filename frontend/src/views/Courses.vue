<template>
  <div class="courses-container">
    <div class="header-actions">
      <h1>Courses</h1>
      <button class="logout-btn" @click="handleLogout">Logout</button>
    </div>

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
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { courseService } from '../services/courseService';
import CourseCard from '../components/CourseCard.vue';
import LoadingState from '../components/LoadingState.vue';
import ErrorState from '../components/ErrorState.vue';
import EmptyState from '../components/EmptyState.vue';

const router = useRouter();
const authStore = useAuthStore();

const courses = ref([]);
const isLoading = ref(true);
const error = ref(null);

const currentPage = ref(1);
const totalPages = ref(1);

const fetchCourses = async (page = 1) => {
  isLoading.value = true;
  error.value = null;
  
  try {
    const response = await courseService.getCourses(page);
    courses.value = response.data.data;
    
    // Setup pagination data
    const meta = response.data.meta;
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

const handleLogout = async () => {
  await authStore.logout();
  router.push('/login');
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

.header-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.header-actions h1 {
  margin: 0;
  color: #111827;
}

.logout-btn {
  background-color: #f3f4f6;
  color: #4b5563;
  border: 1px solid #d1d5db;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
}

.logout-btn:hover {
  background-color: #e5e7eb;
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
