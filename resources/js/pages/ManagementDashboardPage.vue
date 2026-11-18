<script setup>
import { computed, onMounted, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useManagementDashboardStore } from '@/stores/managementDashboard';

const store = useManagementDashboardStore();
const from = ref('');
const to = ref('');
const pageError = ref('');
const lastUpdated = ref(null);

const palette = ['bg-slate-800', 'bg-sky-500', 'bg-emerald-500', 'bg-amber-500', 'bg-violet-500', 'bg-rose-500'];

const summaryCards = computed(() => {
    const data = store.summary;
    if (!data) return [];
    const total = Number(data.total_properties ?? 0);
    const share = (value) => (total > 0 ? Math.round((Number(value ?? 0) / total) * 100) : 0);
    return [
        { label: 'Total properties', value: data.total_properties, icon: 'building', tone: 'bg-slate-900 text-white', bar: 'bg-slate-900', share: null },
        { label: 'Available', value: data.available, icon: 'check', tone: 'bg-emerald-50 text-emerald-600', bar: 'bg-emerald-500', share: share(data.available) },
        { label: 'Reserved', value: data.reserved, icon: 'clock', tone: 'bg-amber-50 text-amber-600', bar: 'bg-amber-500', share: share(data.reserved) },
        { label: 'Sold', value: data.sold, icon: 'tag', tone: 'bg-sky-50 text-sky-600', bar: 'bg-sky-500', share: share(data.sold) },
        { label: 'Primary market', value: data.primary, icon: 'star', tone: 'bg-violet-50 text-violet-600', bar: 'bg-violet-500', share: share(data.primary) },
        { label: 'Secondary market', value: data.secondary, icon: 'repeat', tone: 'bg-rose-50 text-rose-600', bar: 'bg-rose-500', share: share(data.secondary) },
    ];
});

const iconPaths = {
    building: 'M4 21V5a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v16M14 9h5a1 1 0 0 1 1 1v11M3 21h18M8 8h2M8 12h2M8 16h2',
    check: 'M20 6 9 17l-5-5',
    clock: 'M12 7v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    tag: 'M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8ZM7.5 7.5h.01',
    star: 'm12 3 2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9L12 3Z',
    repeat: 'M17 2l4 4-4 4M3 11V9a3 3 0 0 1 3-3h15M7 22l-4-4 4-4M21 13v2a3 3 0 0 1-3 3H3',
};

const statusBreakdown = computed(() => {
    const data = store.summary;
    if (!data) return [];
    const parts = [
        { label: 'Available', value: Number(data.available ?? 0), color: 'bg-emerald-500' },
        { label: 'Reserved', value: Number(data.reserved ?? 0), color: 'bg-amber-500' },
        { label: 'Sold', value: Number(data.sold ?? 0), color: 'bg-sky-500' },
    ];
    const total = parts.reduce((sum, part) => sum + part.value, 0);
    return parts.map((part) => ({ ...part, percent: total > 0 ? (part.value / total) * 100 : 0 }));
});

const verificationDot = {
    not_submitted: 'bg-slate-400',
    submitted: 'bg-amber-500',
    verified: 'bg-sky-500',
    changes_requested: 'bg-orange-500',
    rejected: 'bg-red-500',
    approved: 'bg-emerald-500',
    pending: 'bg-amber-500',
};

const areaMax = computed(() => Math.max(1, ...(store.reports?.inventory_by_area ?? []).map((row) => Number(row.total ?? 0))));
const developerMax = computed(() => Math.max(1, ...(store.reports?.inventory_by_developer ?? []).map((row) => Number(row.total ?? 0))));
const agingMax = computed(() => Math.max(1, ...(store.reports?.inventory_aging ?? []).map((row) => Number(row.total ?? 0))));

function stacked(rows) {
    const list = rows ?? [];
    const total = list.reduce((sum, row) => sum + Number(row.total ?? 0), 0);
    return list.map((row, index) => ({
        ...row,
        color: palette[index % palette.length],
        percent: total > 0 ? (Number(row.total ?? 0) / total) * 100 : 0,
    }));
}

const marketMix = computed(() => stacked(store.reports?.market_mix));
const inventoryStatus = computed(() => stacked(store.reports?.inventory_status));

const hasDateFilter = computed(() => Boolean(from.value || to.value));
const initialLoading = computed(() => store.loading && !store.summary);

function toIso(date) {
    const offset = date.getTimezoneOffset() * 60000;
    return new Date(date.getTime() - offset).toISOString().slice(0, 10);
}

function applyPreset(days) {
    const end = new Date();
    const start = new Date();
    if (days === 'year') start.setMonth(0, 1);
    else start.setDate(end.getDate() - days);
    from.value = toIso(start);
    to.value = toIso(end);
    applyReports();
}

function clearDates() {
    from.value = '';
    to.value = '';
    applyReports();
}

async function applyReports() {
    pageError.value = '';
    try {
        await store.fetchReports({ from: from.value || undefined, to: to.value || undefined });
        lastUpdated.value = new Date();
    } catch (error) {
        pageError.value = error.response?.data?.message ?? 'Unable to load reports.';
    }
}

async function load() {
    pageError.value = '';
    try {
        await store.fetchSummary();
        await store.fetchReports({ from: from.value || undefined, to: to.value || undefined });
        lastUpdated.value = new Date();
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

function label(value) {
    return String(value ?? '').replaceAll('_', ' ');
}

function formatTime(value) {
    return value ? value.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' }) : '';
}

onMounted(load);
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <header class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Management Dashboard</h1>
                    <p class="mt-1 text-sm text-slate-500">Property inventory, verification and approval overview.</p>
                </div>
                <div class="flex items-center gap-3">
                    <span v-if="lastUpdated" class="hidden items-center gap-1.5 text-xs text-slate-500 sm:inline-flex">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Updated {{ formatTime(lastUpdated) }}
                    </span>
                    <button
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400/30 disabled:opacity-50"
                        :disabled="store.loading || store.reportsLoading"
                        @click="load"
                    >
                        <svg class="h-4 w-4" :class="store.loading || store.reportsLoading ? 'animate-spin' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7" /><path d="M21 3v6h-6" /></svg>
                        Refresh
                    </button>
                </div>
            </header>

            <!-- Error -->
            <div v-if="pageError" class="flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-3.5 text-sm text-red-800" role="alert">
                <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM9 6h2v5H9V6Zm0 6h2v2H9v-2Z" clip-rule="evenodd" /></svg>
                <p class="flex-1">{{ pageError }}</p>
                <button class="text-red-500 hover:text-red-700" aria-label="Dismiss" @click="pageError = ''">✕</button>
            </div>

            <!-- Loading skeleton -->
            <div v-if="initialLoading" class="space-y-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <div v-for="n in 6" :key="n" class="h-32 animate-pulse rounded-xl border border-slate-200 bg-white p-5">
                        <div class="h-3 w-24 rounded bg-slate-200"></div>
                        <div class="mt-4 h-8 w-20 rounded bg-slate-200"></div>
                        <div class="mt-4 h-1.5 w-full rounded bg-slate-100"></div>
                    </div>
                </div>
            </div>

            <!-- KPI cards -->
            <section v-if="store.summary" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <article v-for="card in summaryCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                    <div class="flex items-start justify-between">
                        <p class="text-sm font-medium text-slate-500">{{ card.label }}</p>
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg" :class="card.tone">
                            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path :d="iconPaths[card.icon]" /></svg>
                        </span>
                    </div>
                    <p class="mt-3 text-3xl font-semibold tabular-nums tracking-tight text-slate-900">{{ number(card.value) }}</p>
                    <div v-if="card.share !== null" class="mt-4">
                        <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full transition-all duration-500" :class="card.bar" :style="{ width: card.share + '%' }"></div>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-500">{{ card.share }}% of total inventory</p>
                    </div>
                    <p v-else class="mt-4 text-xs text-slate-500">All properties in the system</p>
                </article>
            </section>

            <!-- Status distribution -->
            <section v-if="store.summary" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-base font-semibold text-slate-900">Inventory distribution</h2>
                    <p class="text-xs text-slate-500">Available vs reserved vs sold</p>
                </div>
                <div class="mt-4 flex h-3 overflow-hidden rounded-full bg-slate-100">
                    <div v-for="part in statusBreakdown" :key="part.label" class="h-full transition-all duration-500" :class="part.color" :style="{ width: part.percent + '%' }" :title="`${part.label}: ${number(part.value)}`"></div>
                </div>
                <div class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                    <div v-for="part in statusBreakdown" :key="part.label" class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full" :class="part.color"></span>
                        <span class="text-slate-600">{{ part.label }}</span>
                        <span class="font-semibold tabular-nums text-slate-900">{{ number(part.value) }}</span>
                        <span class="text-xs text-slate-400">{{ part.percent.toFixed(0) }}%</span>
                    </div>
                </div>
            </section>

            <!-- Verification & approval -->
            <section v-if="store.summary" class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <article v-for="block in [
                    { title: 'Verification status', subtitle: 'Where properties sit in the review pipeline', data: store.summary.verification },
                    { title: 'Approval status', subtitle: 'Manager decisions on verified inventory', data: store.summary.approval },
                ]" :key="block.title" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">{{ block.title }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500">{{ block.subtitle }}</p>
                    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <div v-for="(value, key) in block.data" :key="key" class="rounded-lg border border-slate-100 bg-slate-50/70 p-3">
                            <p class="flex items-center gap-1.5 text-xs capitalize text-slate-500">
                                <span class="h-1.5 w-1.5 rounded-full" :class="verificationDot[key] ?? 'bg-slate-400'"></span>{{ label(key) }}
                            </p>
                            <p class="mt-1 text-xl font-semibold tabular-nums text-slate-900">{{ number(value) }}</p>
                        </div>
                    </div>
                </article>
            </section>

            <!-- Reports -->
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-100 p-5">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Inventory reports</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Filtered by property creation date.</p>
                    </div>
                    <div class="flex flex-wrap items-end gap-3">
                        <div class="flex flex-wrap gap-1.5">
                            <button v-for="preset in [{ l: '30 days', v: 30 }, { l: '90 days', v: 90 }, { l: 'This year', v: 'year' }]" :key="preset.l" class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-200" :disabled="store.reportsLoading" @click="applyPreset(preset.v)">{{ preset.l }}</button>
                        </div>
                        <label class="text-xs font-medium text-slate-600">From
                            <input v-model="from" type="date" :max="to || undefined" class="mt-1 block rounded-lg border-slate-200 text-sm focus:border-slate-500 focus:ring-slate-500/20" />
                        </label>
                        <label class="text-xs font-medium text-slate-600">To
                            <input v-model="to" type="date" :min="from || undefined" class="mt-1 block rounded-lg border-slate-200 text-sm focus:border-slate-500 focus:ring-slate-500/20" />
                        </label>
                        <div class="flex gap-2">
                            <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-slate-800 disabled:opacity-50" :disabled="store.reportsLoading" @click="applyReports">{{ store.reportsLoading ? 'Loading…' : 'Apply' }}</button>
                            <button v-if="hasDateFilter" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50" :disabled="store.reportsLoading" @click="clearDates">Clear</button>
                        </div>
                    </div>
                </div>

                <div v-if="store.reportsLoading && !store.reports" class="grid grid-cols-1 gap-6 p-5 xl:grid-cols-2">
                    <div v-for="n in 4" :key="n" class="h-44 animate-pulse rounded-lg bg-slate-100"></div>
                </div>

                <div v-if="store.reports" class="grid grid-cols-1 gap-px bg-slate-100 transition-opacity xl:grid-cols-2" :class="store.reportsLoading ? 'opacity-60' : ''">
                    <!-- By area -->
                    <div class="bg-white p-5">
                        <h3 class="text-sm font-semibold text-slate-800">Inventory by area</h3>
                        <p v-if="!store.reports.inventory_by_area?.length" class="mt-4 text-sm text-slate-500">No report data.</p>
                        <ul v-else class="mt-4 space-y-3.5">
                            <li v-for="row in store.reports.inventory_by_area" :key="row.label">
                                <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                                    <span class="truncate text-slate-600">{{ row.label }}</span>
                                    <span class="font-semibold tabular-nums text-slate-900">{{ number(row.total) }}</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-slate-800 transition-all duration-500" :style="{ width: `${Math.max(2, (Number(row.total) / areaMax) * 100)}%` }"></div>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- By developer -->
                    <div class="bg-white p-5">
                        <h3 class="text-sm font-semibold text-slate-800">Inventory by developer</h3>
                        <p v-if="!store.reports.inventory_by_developer?.length" class="mt-4 text-sm text-slate-500">No report data.</p>
                        <ul v-else class="mt-4 space-y-3.5">
                            <li v-for="row in store.reports.inventory_by_developer" :key="row.label">
                                <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                                    <span class="truncate text-slate-600">{{ row.label }}</span>
                                    <span class="font-semibold tabular-nums text-slate-900">{{ number(row.total) }}</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-sky-500 transition-all duration-500" :style="{ width: `${Math.max(2, (Number(row.total) / developerMax) * 100)}%` }"></div>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Average price -->
                    <div class="bg-white p-5">
                        <h3 class="text-sm font-semibold text-slate-800">Average asking price by area</h3>
                        <p v-if="!store.reports.average_asking_price_by_area?.length" class="mt-4 text-sm text-slate-500">No report data.</p>
                        <div v-else class="-mx-2 mt-3 overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead>
                                    <tr class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                        <th class="px-2 py-2">Area</th>
                                        <th class="px-2 py-2 text-right">Properties</th>
                                        <th class="px-2 py-2 text-right">Average price</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="row in store.reports.average_asking_price_by_area" :key="row.label" class="transition hover:bg-slate-50">
                                        <td class="px-2 py-2.5 text-slate-700">{{ row.label }}</td>
                                        <td class="px-2 py-2.5 text-right tabular-nums text-slate-600">{{ number(row.property_count) }}</td>
                                        <td class="px-2 py-2.5 text-right font-semibold tabular-nums text-slate-900">{{ money(row.average_asking_price) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Aging -->
                    <div class="bg-white p-5">
                        <h3 class="text-sm font-semibold text-slate-800">Inventory aging</h3>
                        <p v-if="!store.reports.inventory_aging?.length" class="mt-4 text-sm text-slate-500">No report data.</p>
                        <ul v-else class="mt-4 space-y-3.5">
                            <li v-for="row in store.reports.inventory_aging" :key="row.label">
                                <div class="mb-1.5 flex items-center justify-between text-sm">
                                    <span class="text-slate-600">{{ row.label }}</span>
                                    <span class="font-semibold tabular-nums text-slate-900">{{ number(row.total) }}</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-amber-500 transition-all duration-500" :style="{ width: `${Math.max(2, (Number(row.total) / agingMax) * 100)}%` }"></div>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Market mix -->
                    <div class="bg-white p-5">
                        <h3 class="text-sm font-semibold text-slate-800">Market mix</h3>
                        <p v-if="!marketMix.length" class="mt-4 text-sm text-slate-500">No report data.</p>
                        <template v-else>
                            <div class="mt-4 flex h-3 overflow-hidden rounded-full bg-slate-100">
                                <div v-for="row in marketMix" :key="row.label" class="h-full transition-all duration-500" :class="row.color" :style="{ width: row.percent + '%' }"></div>
                            </div>
                            <ul class="mt-4 divide-y divide-slate-100">
                                <li v-for="row in marketMix" :key="row.label" class="flex items-center justify-between py-2.5 text-sm">
                                    <span class="flex items-center gap-2 capitalize text-slate-600"><span class="h-2.5 w-2.5 rounded-full" :class="row.color"></span>{{ label(row.label) }}</span>
                                    <span><span class="font-semibold tabular-nums text-slate-900">{{ number(row.total) }}</span><span class="ml-2 text-xs text-slate-400">{{ row.percent.toFixed(0) }}%</span></span>
                                </li>
                            </ul>
                        </template>
                    </div>

                    <!-- Inventory status -->
                    <div class="bg-white p-5">
                        <h3 class="text-sm font-semibold text-slate-800">Inventory status</h3>
                        <p v-if="!inventoryStatus.length" class="mt-4 text-sm text-slate-500">No report data.</p>
                        <template v-else>
                            <div class="mt-4 flex h-3 overflow-hidden rounded-full bg-slate-100">
                                <div v-for="row in inventoryStatus" :key="row.label" class="h-full transition-all duration-500" :class="row.color" :style="{ width: row.percent + '%' }"></div>
                            </div>
                            <ul class="mt-4 divide-y divide-slate-100">
                                <li v-for="row in inventoryStatus" :key="row.label" class="flex items-center justify-between py-2.5 text-sm">
                                    <span class="flex items-center gap-2 capitalize text-slate-600"><span class="h-2.5 w-2.5 rounded-full" :class="row.color"></span>{{ label(row.label) }}</span>
                                    <span><span class="font-semibold tabular-nums text-slate-900">{{ number(row.total) }}</span><span class="ml-2 text-xs text-slate-400">{{ row.percent.toFixed(0) }}%</span></span>
                                </li>
                            </ul>
                        </template>
                    </div>
                </div>
            </section>

            <!-- Future modules -->
            <section v-if="store.reports?.not_available_yet?.length" class="rounded-xl border border-amber-200 bg-amber-50/60 p-5">
                <div class="flex items-start gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" /></svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Reports requiring future modules</h2>
                        <p class="mt-1 text-sm text-slate-600">Listed in the PIMS specification, but they need additional source data before accurate reporting is possible.</p>
                    </div>
                </div>
                <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                    <li v-for="item in store.reports.not_available_yet" :key="item.key" class="rounded-lg border border-amber-100 bg-white/80 p-3 text-sm">
                        <p class="font-semibold capitalize text-slate-800">{{ label(item.key) }}</p>
                        <p class="mt-0.5 text-xs text-slate-600">{{ item.reason }}</p>
                    </li>
                </ul>
            </section>
        </div>
    </AppLayout>
</template>