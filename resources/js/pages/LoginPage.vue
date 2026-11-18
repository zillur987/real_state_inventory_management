
<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useToast } from 'vue-toastification';

const auth = useAuthStore();
const router = useRouter();
const toast = useToast();

const form = reactive({
    email: '',
    password: '',
    remember: false,
});

const errors = ref({});
const loading = ref(false);

async function submit() {
    loading.value = true;
    errors.value = {};

    try {
        await auth.login(form);

        toast.success('Login successful! Welcome back.');

        router.push({ name: 'dashboard' });

    } catch (e) {
        errors.value = e.response?.data?.errors ?? {
            email: ['Something went wrong. Please try again.']
        };

        toast.error('Login failed. Please check your credentials.');

    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center px-4">
        <form
            class="w-full max-w-sm space-y-4 rounded-xl bg-white p-6 shadow"
            @submit.prevent="submit"
            novalidate
        >
            <div class="text-center">
                <h1 class="text-lg font-semibold text-brand-900">
                    Property Inventory
                </h1>

                <p class="text-sm text-slate-500">
                    Sign in to continue
                </p>
            </div>

            <!-- Email -->
            <div>
                <label
                    class="mb-1 block text-sm font-medium"
                    for="email"
                >
                    Email
                </label>

                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="username"
                    placeholder="Enter your email address"
                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-brand-700 focus:outline-none"
                >

                <p
                    v-if="errors.email"
                    class="mt-1 text-xs text-red-600"
                >
                    {{ errors.email[0] }}
                </p>
            </div>

            <!-- Password -->
            <div>
                <label
                    class="mb-1 block text-sm font-medium"
                    for="password"
                >
                    Password
                </label>

                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-brand-700 focus:outline-none"
                >

                <p
                    v-if="errors.password"
                    class="mt-1 text-xs text-red-600"
                >
                    {{ errors.password[0] }}
                </p>
            </div>

            <!-- Remember Me -->
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input
                    v-model="form.remember"
                    type="checkbox"
                    class="rounded"
                >
                Remember me
            </label>

            <!-- Submit Button -->
            <button
                type="submit"
                :disabled="loading"
                class="w-full rounded-md bg-brand-900 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
            >
                {{ loading ? 'Signing in...' : 'Sign in' }}
            </button>
        </form>
    </div>
</template>
