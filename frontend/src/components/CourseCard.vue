<template>
  <div class="course-card" @click="$router.push(`/courses/${course.id}`)">
    <div class="card-header">
      <h3 class="course-title">{{ course.name }}</h3>
      <span class="status-badge" :class="course.status">{{ course.status }}</span>
    </div>
    
    <div class="card-body">
      <p class="instructor">Instructor: {{ course.instructor_name || 'N/A' }}</p>
      
      <div class="stats">
        <div class="stat">
          <span class="stat-value">{{ course.students_count || 0 }}</span>
          <span class="stat-label">Students</span>
        </div>
        <div class="stat">
          <span class="stat-value">{{ course.assignments_count || 0 }}</span>
          <span class="stat-label">Assignments</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  course: {
    type: Object,
    required: true
  }
});
</script>

<style scoped>
.course-card {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  transition: transform 0.2s, box-shadow 0.2s;
  cursor: pointer;
  border: 1px solid #e5e7eb;
}

.course-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.course-title {
  margin: 0;
  font-size: 1.25rem;
  color: #111827;
}

.status-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: capitalize;
}

.status-badge.active { background-color: #dcfce7; color: #166534; }
.status-badge.archived { background-color: #f3f4f6; color: #4b5563; }

.instructor {
  color: #4b5563;
  margin-bottom: 1.5rem;
  font-size: 0.875rem;
}

.stats {
  display: flex;
  gap: 1.5rem;
  border-top: 1px solid #f3f4f6;
  padding-top: 1rem;
}

.stat {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: 1.125rem;
  font-weight: 600;
  color: #111827;
}

.stat-label {
  font-size: 0.75rem;
  color: #6b7280;
  text-transform: uppercase;
}
</style>
