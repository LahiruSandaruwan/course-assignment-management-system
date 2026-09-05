<template>
  <header class="app-header">
    <span class="app-title">Course Assignment Management</span>

    <div class="user-info" v-if="authStore.user">
      <nav class="main-nav">
        <router-link to="/courses" class="nav-link">Courses</router-link>
        <router-link v-if="authStore.user.role === 'admin'" to="/users" class="nav-link">Users</router-link>
      </nav>
      <span class="user-name">{{ authStore.user.name }}</span>
      <span class="role-badge" :class="authStore.user.role">{{ roleLabel }}</span>
      <button class="logout-btn" @click="handleLogout">Logout</button>
    </div>
  </header>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const roleLabel = computed(() => {
  const role = authStore.user?.role;
  return role ? role.charAt(0).toUpperCase() + role.slice(1) : '';
});

const handleLogout = async () => {
  await authStore.logout();
  router.push('/login');
};

onMounted(() => {
  // On a hard refresh only the token survives in localStorage, not the
  // user object — fetch it so the header has a name/role to show.
  if (authStore.isAuthenticated && !authStore.user) {
    authStore.fetchCurrentUser();
  }
});
</script>

<style scoped>
.app-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 2rem;
  background-color: white;
  border-bottom: 1px solid #e5e7eb;
}

.app-title {
  font-weight: 600;
  color: #111827;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.user-name {
  color: #374151;
  font-size: 0.875rem;
}

.main-nav {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-right: 0.5rem;
}

.nav-link {
  color: #3b82f6;
  font-size: 0.875rem;
  text-decoration: none;
  font-weight: 500;
}

.nav-link:hover {
  text-decoration: underline;
}

.role-badge {
  padding: 0.25rem 0.6rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
}

.role-badge.admin { background-color: #ede9fe; color: #6d28d9; }
.role-badge.instructor { background-color: #dbeafe; color: #1e40af; }
.role-badge.student { background-color: #dcfce7; color: #166534; }

.logout-btn {
  background-color: #f3f4f6;
  color: #4b5563;
  border: 1px solid #d1d5db;
  padding: 0.4rem 0.85rem;
  border-radius: 4px;
  font-size: 0.875rem;
  cursor: pointer;
}

.logout-btn:hover {
  background-color: #e5e7eb;
}
</style>
