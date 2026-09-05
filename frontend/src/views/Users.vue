<template>
  <div>
    <AppHeader />

    <div class="users-container">
      <div class="page-toolbar">
        <h1>Users</h1>
        <button class="create-user-btn" @click="openCreateModal">
          + Create User
        </button>
      </div>

      <CreateUserModal
        v-if="showModal"
        :user="editingUser"
        @close="closeModal"
        @saved="handleSaved"
      />

      <ConfirmModal
        :show="!!deletingUser"
        title="Delete user?"
        :message="deletingUser ? `This will permanently delete ${deletingUser.name}'s account.` : ''"
        confirm-text="Delete"
        is-destructive
        :is-loading="isDeleting"
        @confirm="confirmDelete"
        @cancel="deletingUser = null"
      />

      <LoadingState v-if="isLoading" message="Loading users..." />

      <ErrorState v-else-if="error" :message="error" :retry="fetchUsers" />

      <div v-else-if="users.length > 0">
        <table class="users-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td>{{ user.name }}</td>
              <td>{{ user.email }}</td>
              <td><span class="role-badge" :class="user.role">{{ user.role }}</span></td>
              <td class="actions-cell">
                <button class="edit-btn" @click="openEditModal(user)">Edit</button>
                <button
                  class="delete-btn"
                  :disabled="user.id === authStore.user?.id"
                  :title="user.id === authStore.user?.id ? 'You cannot delete your own account' : ''"
                  @click="deletingUser = user"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="pagination" v-if="totalPages > 1">
          <button
            :disabled="currentPage === 1"
            @click="fetchUsers(currentPage - 1)"
          >
            Previous
          </button>
          <span>Page {{ currentPage }} of {{ totalPages }}</span>
          <button
            :disabled="currentPage === totalPages"
            @click="fetchUsers(currentPage + 1)"
          >
            Next
          </button>
        </div>
      </div>

      <EmptyState v-else message="No users found." />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import { userService } from '../services/userService';
import AppHeader from '../components/AppHeader.vue';
import CreateUserModal from '../components/CreateUserModal.vue';
import ConfirmModal from '../components/ConfirmModal.vue';
import LoadingState from '../components/LoadingState.vue';
import ErrorState from '../components/ErrorState.vue';
import EmptyState from '../components/EmptyState.vue';

const authStore = useAuthStore();

const users = ref([]);
const isLoading = ref(true);
const error = ref(null);

const currentPage = ref(1);
const totalPages = ref(1);

const showModal = ref(false);
const editingUser = ref(null);

const deletingUser = ref(null);
const isDeleting = ref(false);

const openCreateModal = () => {
  editingUser.value = null;
  showModal.value = true;
};

const openEditModal = (user) => {
  editingUser.value = user;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  editingUser.value = null;
};

const handleSaved = () => {
  closeModal();
  fetchUsers(currentPage.value);
};

const confirmDelete = async () => {
  if (!deletingUser.value) return;

  isDeleting.value = true;
  try {
    await userService.deleteUser(deletingUser.value.id);
    deletingUser.value = null;
    fetchUsers(currentPage.value);
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to delete user.';
    deletingUser.value = null;
  } finally {
    isDeleting.value = false;
  }
};

const fetchUsers = async (page = 1) => {
  isLoading.value = true;
  error.value = null;

  try {
    const response = await userService.getUsers(page);
    // The backend wraps every response as { message, data: {...} }, and a
    // paginated list's own shape ({ data, links, meta }) sits inside that
    // — so the actual rows are at response.data.data.data, not .data.data.
    users.value = response.data.data.data;

    const meta = response.data.data.meta;
    if (meta) {
      currentPage.value = meta.current_page;
      totalPages.value = meta.last_page;
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to fetch users.';
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  fetchUsers();
});
</script>

<style scoped>
.users-container {
  max-width: 1000px;
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

.create-user-btn {
  background-color: #3b82f6;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  font-weight: 500;
  cursor: pointer;
}

.create-user-btn:hover {
  background-color: #2563eb;
}

.users-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}

.users-table th,
.users-table td {
  text-align: left;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.users-table th {
  background-color: #f9fafb;
  font-size: 0.75rem;
  text-transform: uppercase;
  color: #6b7280;
  font-weight: 600;
}

.users-table tbody tr:last-child td {
  border-bottom: none;
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

.actions-cell {
  display: flex;
  gap: 0.5rem;
}

.edit-btn,
.delete-btn {
  padding: 0.35rem 0.75rem;
  border-radius: 4px;
  font-size: 0.8rem;
  cursor: pointer;
}

.edit-btn {
  background-color: white;
  border: 1px solid #d1d5db;
  color: #374151;
}

.edit-btn:hover {
  background-color: #f3f4f6;
}

.delete-btn {
  background-color: white;
  border: 1px solid #fecaca;
  color: #dc2626;
}

.delete-btn:hover:not(:disabled) {
  background-color: #fef2f2;
}

.delete-btn:disabled {
  opacity: 0.5;
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
