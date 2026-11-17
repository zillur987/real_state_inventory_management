import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import { authApi } from '@/api/authApi';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const initialized = ref(false);

    const isAuthenticated = computed(() => user.value !== null);

    async function fetchUser() {
        try {
            user.value = await authApi.me();
        } catch {
            user.value = null;
        } finally {
            initialized.value = true;
        }
    }

    async function login(credentials) {
        user.value = await authApi.login(credentials);
    }

    async function logout() {
        await authApi.logout();
        user.value = null;
    }

    return { user, initialized, isAuthenticated, fetchUser, login, logout };
});
