import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import Login from '../views/Login.vue';

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: Login,
        meta: { requiresGuest: true }
    },
    {
        path: '/',
        redirect: '/courses'
    },
    {
        path: '/courses',
        name: 'Courses',
        component: () => import('../views/Courses.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/courses/:id',
        name: 'CourseDetail',
        component: () => import('../views/CourseDetail.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/assignments/:id',
        name: 'AssignmentDetail',
        component: () => import('../views/AssignmentDetail.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/courses/:id/assignments/:assignmentId/submissions',
        name: 'Submissions',
        component: () => import('../views/Submissions.vue'),
        meta: { requiresAuth: true }
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

router.beforeEach((to, from, next) => {
    const authStore = useAuthStore();
    const isAuthenticated = authStore.isAuthenticated;

    // NOTE: This navigation guard is a UX convenience only — 
    // the backend API remains the actual authorization boundary.
    if (to.meta.requiresAuth && !isAuthenticated) {
        next('/login');
    } else if (to.meta.requiresGuest && isAuthenticated) {
        next('/courses');
    } else {
        next();
    }
});

export default router;
