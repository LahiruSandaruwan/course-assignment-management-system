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
        meta: { requiresAuth: true, roles: ['admin', 'instructor'] }
    },
    // Must stay last: this wildcard matches everything, including
    // /assignments/ (no id) and any other unmatched URL.
    {
        path: '/:pathMatch(.*)*',
        name: 'NotFound',
        component: () => import('../views/NotFound.vue'),
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();
    const isAuthenticated = authStore.isAuthenticated;

    // NOTE: This navigation guard (including the role check below) is a UX
    // convenience only — the backend API remains the actual authorization
    // boundary and re-checks every request via Policies.
    if (to.meta.requiresAuth && !isAuthenticated) {
        return next('/login');
    }

    if (to.meta.requiresGuest && isAuthenticated) {
        return next('/courses');
    }

    if (to.meta.roles && isAuthenticated) {
        // `user` isn't persisted across a page reload (only the token is),
        // so fetch it once before deciding whether the role is allowed.
        if (!authStore.user) {
            try {
                await authStore.fetchCurrentUser();
            } catch (e) {
                return next('/login');
            }
        }

        if (authStore.user && !to.meta.roles.includes(authStore.user.role)) {
            return next('/courses');
        }
    }

    next();
});

export default router;
