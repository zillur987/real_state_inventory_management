<script setup>
import { computed, onMounted, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useManagementDashboardStore } from '@/stores/managementDashboard';

const store = useManagementDashboardStore();
const from = ref('');
const to = ref('');
const pageError = ref('');

const summaryCards = computed(() => {
    const data = store.summary;
    if (!data) return [];
    return [
        { label: 'Total properties', value: data.total_properties },
        { label: 'Available', value: data.available },
        { label: 'Reserved', value: data.reserved },
        { label: 'Sold', value: data.sold },
        { label: 'Primary', value: data.primary },
        { label: 'Secondary', value: data.secondary },
    ];
});

async function load() {
    pageError.value = '';
    try {
        await store.fetchSummary();
        await store.fetchReports({ from: from.value || undefined, to: to.value || undefined });
    } catch (error) {
        pageError.value = error.response?.data?.message ?? 'Unable to load management dashboard.';
    }
}

function number(value) {
    return new Intl.NumberFormat().format(Number(value ?? 0));
}

function money(value) {
    return new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT', maximumFractionDigits: 0 }).format(Number(value ?? 0));
}

onMounted(load);
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <header class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">Management Dashboard</h1>
                    <p class="mt-1 text-sm text-slate-500">Real-time property inventory and verification overview</p>
                </div>
                <button class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" :disabled="store.loading || store.reportsLoading" @click="load">
                    Refresh dashboard
                </button>
            </header>

            <div v-if="pageError" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ pageError }}</div>
            <div v-if="store.loading && !store.summary" class="rounded-xl border bg-white p-6 text-sm text-slate-500">Loading dashboard…</div>

            <section v-if="store.summary" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <article v-for="card in summaryCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">{{ card.label }}</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ number(card.value) }}</p>
                </article>
            </section>

            <section v-if="store.summary" class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <article class="rounded-xl border border-slate-200 bg-white p-5">
                    <h2 class="font-semibold text-slate-900">Verification status</h2>
                    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <div v-for="(value, key) in store.summary.verification" :key="key" class="rounded-lg bg-slate-50 p-3">
                            <p class="text-xs capitalize text-slate-500">{{ key.replaceAll('_', ' ') }}</p>
                            <p class="mt-1 text-xl font-semibold text-slate-900">{{ number(value) }}</p>
                        </div>
                    </div>
                </article>
                <article class="rounded-xl border border-slate-200 bg-white p-5">
                    <h2 class="font-semibold text-slate-900">Approval status</h2>
                    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <div v-for="(value, key) in store.summary.approval" :key="key" class="rounded-lg bg-slate-50 p-3">
                            <p class="text-xs capitalize text-slate-500">{{ key.replaceAll('_', ' ') }}</p>
                            <p class="mt-1 text-xl font-semibold text-slate-900">{{ number(value) }}</p>
                        </div>
                    </div>
                </article>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 class="font-semibold text-slate-900">Inventory reports</h2>
                        <p class="mt-1 text-sm text-slate-500">Filter report data by property creation date</p>
                    </div>
                    <div class="flex flex-wrap items-end gap-2">
                        <label class="text-xs text-slate-600">From<input v-model="from" type="date" class="mt-1 block rounded-md border-slate-300 text-sm" /></label>
                        <label class="text-xs text-slate-600">To<input v-model="to" type="date" :min="from || undefined" class="mt-1 block rounded-md border-slate-300 text-sm" /></label>
                        <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-50" :disabled="store.reportsLoading" @click="store.fetchReports({ from: from || undefined, to: to || undefined }).catch((e) => pageError = e.response?.data?.message ?? 'Unable to load reports.')">Apply</button>
                    </div>
                </div>
                <p v-if="store.reportsLoading" class="mt-4 text-sm text-slate-500">Loading reports…</p>
                <div v-if="store.reports" class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
                    <div>
                        <h3 class="mb-3 text-sm font-semibold text-slate-800">Inventory by area</h3>
                        <div v-if="!store.reports.inventory_by_area.length" class="text-sm text-slate-500">No report data.</div>
                        <div v-for="row in store.reports.inventory_by_area" :key="row.label" class="mb-3">
                            <div class="mb-1 flex justify-between gap-3 text-sm"><span class="truncate text-slate-600">{{ row.label }}</span><strong>{{ number(row.total) }}</strong></div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-slate-700" :style="{ width: `${Math.max(2, row.total / Math.max(1, store.reports.inventory_by_area[0].total) * 100)}%` }"></div></div>
                        </div>
                    </div>
                    <div>
                        <h3 class="mb-3 text-sm font-semibold text-slate-800">Inventory by developer</h3>
                        <div v-if="!store.reports.inventory_by_developer.length" class="text-sm text-slate-500">No report data.</div>
                        <div v-for="row in store.reports.inventory_by_developer" :key="row.label" class="mb-3 flex justify-between gap-3 border-b border-slate-100 pb-2 text-sm"><span class="truncate text-slate-600">{{ row.label }}</span><strong>{{ number(row.total) }}</strong></div>
                    </div>
                    <div>
                        <h3 class="mb-3 text-sm font-semibold text-slate-800">Average asking price by area</h3>
                        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="text-xs uppercase text-slate-500"><tr><th class="py-2 pr-3">Area</th><th class="py-2 pr-3">Properties</th><th class="py-2">Average price</th></tr></thead><tbody><tr v-for="row in store.reports.average_asking_price_by_area" :key="row.label" class="border-t border-slate-100"><td class="py-2 pr-3">{{ row.label }}</td><td class="py-2 pr-3">{{ number(row.property_count) }}</td><td class="py-2">{{ money(row.average_asking_price) }}</td></tr></tbody></table></div>
                    </div>
                    <div>
                        <h3 class="mb-3 text-sm font-semibold text-slate-800">Inventory aging</h3>
                        <div v-for="row in store.reports.inventory_aging" :key="row.label" class="flex justify-between border-b border-slate-100 py-2 text-sm"><span class="text-slate-600">{{ row.label }}</span><strong>{{ number(row.total) }}</strong></div>
                    </div>
                    <div>
                        <h3 class="mb-3 text-sm font-semibold text-slate-800">Market mix</h3>
                        <div v-for="row in store.reports.market_mix" :key="row.label" class="flex justify-between border-b border-slate-100 py-2 text-sm"><span class="capitalize text-slate-600">{{ row.label }}</span><strong>{{ number(row.total) }}</strong></div>
                    </div>
                    <div>
                        <h3 class="mb-3 text-sm font-semibold text-slate-800">Inventory status</h3>
                        <div v-for="row in store.reports.inventory_status" :key="row.label" class="flex justify-between border-b border-slate-100 py-2 text-sm"><span class="capitalize text-slate-600">{{ row.label }}</span><strong>{{ number(row.total) }}</strong></div>
                    </div>
                </div>
            </section>

            <section v-if="store.reports?.not_available_yet?.length" class="rounded-xl border border-amber-200 bg-amber-50/60 p-5">
                <h2 class="font-semibold text-slate-900">Reports requiring future modules</h2>
                <p class="mt-1 text-sm text-slate-600">These are listed in the PIMS specification but need additional source data before accurate reporting is possible.</p>
                <ul class="mt-3 space-y-2 text-sm">
                    <li v-for="item in store.reports.not_available_yet" :key="item.key"><strong class="capitalize">{{ item.key.replaceAll('_', ' ') }}:</strong> <span class="text-slate-600">{{ item.reason }}</span></li>
                </ul>
            </section>
        </div>
    </AppLayout>
</template>
