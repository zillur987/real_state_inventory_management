<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import ModuleCard from '@/components/ModuleCard.vue';
import { modules, managementModule } from '@/config/modules';
</script>

<template>
    <AppLayout>
        <section class="rounded-xl bg-brand-900 px-6 py-6 text-center text-white">
            <h1 class="text-lg font-semibold uppercase tracking-wide">Property Inventory Management System</h1>
            <p class="mt-1 text-sm text-brand-100">Centralized Property Database</p>
        </section>

        <section class="mt-4 grid gap-4 sm:grid-cols-2">
            <ModuleCard v-for="m in modules" :key="m.key" :module="m" />
        </section>

        <ModuleCard class="mt-4" :module="managementModule" variant="wide" />
    </AppLayout>
</template>
