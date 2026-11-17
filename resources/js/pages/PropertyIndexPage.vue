<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePropertiesStore } from '@/stores/properties';

const properties = usePropertiesStore();
const router = useRouter();

onMounted(() => properties.fetchPage().catch(() => {}));
</script>

<template>
    <AppLayout>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h1 class="text-xl font-semibold text-slate-900">Property Inventory</h1><p class="mt-1 text-sm text-slate-500">Registered properties in your inventory</p></div>
            <button type="button" class="rounded-md bg-brand-900 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700" @click="router.push({ name: 'properties.create' })">Add Property</button>
        </div>
        <p v-if="properties.errors.general" class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{{ properties.errors.general[0] }}</p>
        <div class="mt-5 overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div v-if="properties.loading" class="p-6 text-sm text-slate-500">Loading properties…</div>
            <div v-else-if="!properties.items.length" class="p-8 text-center"><p class="font-medium text-slate-800">No properties registered yet</p><p class="mt-1 text-sm text-slate-500">Add the first property to start building your inventory.</p></div>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Property ID</th><th class="px-4 py-3">Property</th><th class="px-4 py-3">Area</th><th class="px-4 py-3">Category</th><th class="px-4 py-3">Asking price</th><th class="px-4 py-3">Availability</th><th class="px-4 py-3">Actions</th></tr></thead>
                    <tbody class="divide-y divide-slate-100"><tr v-for="property in properties.items" :key="property.id"><td class="px-4 py-3 font-medium text-brand-900">{{ property.property_code }}</td><td class="px-4 py-3">{{ property.name }}</td><td class="px-4 py-3">{{ property.area }}</td><td class="px-4 py-3 capitalize">{{ property.category }}</td><td class="px-4 py-3">{{ property.asking_price ?? '—' }}</td><td class="px-4 py-3 capitalize">{{ property.inventory_status }}</td><td class="whitespace-nowrap px-4 py-3"><button class="text-brand-800 hover:underline" @click="router.push({ name: 'properties.show', params: { id: property.id } })">Details</button><span class="px-2 text-slate-300">|</span><button class="text-brand-800 hover:underline" @click="router.push({ name: 'properties.edit', params: { id: property.id } })">Edit</button></td></tr></tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
