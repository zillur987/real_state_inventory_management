<script setup>
import { useRouter } from 'vue-router';
import { LogOut } from 'lucide-vue-next';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const router = useRouter();

async function logout() {
    await auth.logout();
    router.push({ name: 'login' });
}
</script>

<template>
    <div class="min-h-screen">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
                <span class="text-sm font-semibold text-brand-900">Property Inventory</span>
                <div class="flex items-center gap-4 text-sm">
                    <span class="text-slate-600">
                        {{ auth.user?.name }}
                        <span class="text-slate-400">· {{ auth.user?.role_label }}</span>
                    </span>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-slate-600 hover:bg-slate-100"
                        @click="logout"
                    >
                        <LogOut class="size-4" /> Logout
                    </button>
                </div>
            </div>
        </header>
        <main class="mx-auto max-w-5xl px-4 py-6">
            <slot />
        </main>
    </div>
</template>
