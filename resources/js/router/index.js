import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/login',
            name: 'login',
            component: () => import('@/pages/LoginPage.vue'),
            meta: { guestOnly: true },
        },
        {
            path: '/',
            name: 'dashboard',
            component: () => import('@/pages/HomePage.vue'),
            meta: { requiresAuth: true },
        },

        {
            path: '/properties',
            name: 'properties.index',
            component: () => import('@/pages/PropertyIndexPage.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/properties/create',
            name: 'properties.create',
            component: () => import('@/pages/PropertyCreatePage.vue'),
            meta: { requiresAuth: true },
        },

        {
            path: '/properties/:id',
            name: 'properties.show',
            component: () => import('@/pages/PropertyShowPage.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/properties/:id/edit',
            name: 'properties.edit',
            component: () => import('@/pages/PropertyEditPage.vue'),
            meta: { requiresAuth: true },
        },

        {
            path: '/property-workflow',
            name: 'properties.workflow',
            component: () => import('@/pages/PropertyWorkflowPage.vue'),
            meta: { requiresAuth: true },
        },

        {
            path: '/management-dashboard',
            name: 'management.dashboard',
            component: () => import('@/pages/ManagementDashboardPage.vue'),
            meta: { requiresAuth: true },
        },

        {
            path: '/clients/matching',
            name: 'clients.matching',
            component: () => import('@/pages/ClientMatchingPage.vue'),
            meta: { requiresAuth: true },
        },

        {
            path: '/automation/property-intelligence',
            name: 'automation.property-intelligence',
            component: () => import('@/pages/PropertyAutomationPage.vue'),
            meta: { requiresAuth: true, permission: 'automation.view' },
        },

        {
            path: '/properties/search',
            name: 'properties.search',
            component: () => import('@/pages/PropertySearchPage.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/properties/:property',
            name: 'properties.show',
            component: () => import('@/pages/PropertyDetailPage.vue'),
            meta: { requiresAuth: true },
        },
        { path: '/:pathMatch(.*)*', redirect: { name: 'dashboard' } },
    ],
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (!auth.initialized) {
        await auth.fetchUser();
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return { name: 'login' };
    }

    if (to.meta.guestOnly && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }
});

export default router;
