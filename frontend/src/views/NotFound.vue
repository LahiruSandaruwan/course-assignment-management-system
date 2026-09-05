<template>
  <div class="not-found-container">
    <div class="not-found-card">
      <div class="status-code">404</div>
      <h1>Page not found</h1>
      <p>The page you're looking for doesn't exist or may have been moved.</p>
      <router-link to="/courses" class="back-link">&larr; Back to Courses</router-link>
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped>
.not-found-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 1.5rem;
  box-sizing: border-box;
}

.not-found-card {
  background: white;
  border-radius: 12px;
  padding: 3rem 2.5rem;
  width: 100%;
  max-width: 400px;
  text-align: center;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  box-sizing: border-box;
}

.status-code {
  font-size: 2.5rem;
  font-weight: 700;
  color: #d1d5db;
  margin-bottom: 0.5rem;
}

h1 {
  margin: 0 0 0.75rem 0;
  color: #111827;
  font-size: 1.25rem;
}

p {
  margin: 0 0 1.5rem 0;
  color: #6b7280;
  font-size: 0.9rem;
  line-height: 1.5;
}

.back-link {
  color: #3b82f6;
  text-decoration: none;
  font-weight: 500;
  font-size: 0.9rem;
}

.back-link:hover {
  text-decoration: underline;
}
</style>
