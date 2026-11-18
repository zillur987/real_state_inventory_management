<script setup>
import { computed, onMounted, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePropertyWorkflowStore } from '@/stores/propertyWorkflow';

const store = usePropertyWorkflowStore();
const filter = ref('all');
const search = ref('');
const selectedId = ref(null);
const selected = computed(() => store.selected);
const busy = ref(false);
const pageError = ref('');
const notes = ref('');
const checks = ref({ ownership: false, documents: false, property_information: false, pricing: false });

const checklistItems = [
    { key: 'ownership', label: 'Ownership details checked', hint: 'Title holder and authority to sell' },
    { key: 'documents', label: 'Property documents reviewed', hint: 'Deeds, mutation, tax receipts' },
    { key: 'property_information', label: 'Property information checked', hint: 'Specifications, size, location' },
    { key: 'pricing', label: 'Asking price checked', hint: 'Price and supporting details' },
];

const filters = [
    { key: 'all', label: 'All' },
    { key: 'pending', label: 'Awaiting verification' },
    { key: 'verified', label: 'Awaiting approval' },
    { key: 'approved', label: 'Approved' },
    { key: 'rejected', label: 'Rejected / changes' },
];

const isRejected = (item) =>
    ['rejected', 'changes_requested'].includes(item.verification_status) || item.approval_status === 'rejected';
const isPending = (item) => item.verification_status === 'submitted';
const isAwaitingApproval = (item) => item.verification_status === 'verified' && item.approval_status !== 'approved';
const isApproved = (item) => item.approval_status === 'approved';

const counts = computed(() => {
    const items = store.items ?? [];
    return {
        all: items.length,
        pending: items.filter(isPending).length,
        verified: items.filter(isAwaitingApproval).length,
        approved: items.filter(isApproved).length,
        rejected: items.filter(isRejected).length,
    };
});

const visibleItems = computed(() => {
    let list = store.items ?? [];
    if (filter.value === 'pending') list = list.filter(isPending);
    else if (filter.value === 'verified') list = list.filter(isAwaitingApproval);
    else if (filter.value === 'approved') list = list.filter(isApproved);
    else if (filter.value === 'rejected') list = list.filter(isRejected);

    const q = search.value.trim().toLowerCase();
    if (q) {
        list = list.filter((item) =>
            [item.property_code, item.name, item.area, item.district]
                .filter(Boolean)
                .some((value) => String(value).toLowerCase().includes(q)),
        );
    }
    return list;
});

const checklistDone = computed(() => Object.values(checks.value).filter(Boolean).length);
const checklistPercent = computed(() => (checklistDone.value / checklistItems.length) * 100);
const allChecked = computed(() => checklistDone.value === checklistItems.length);
const hasNotes = computed(() => notes.value.trim().length > 0);

const steps = computed(() => {
    const data = selected.value?.data;
    if (!data) return [];
    const submitted = data.verification_status !== 'not_submitted';
    const returned = ['rejected', 'changes_requested'].includes(data.verification_status);
    const verified = data.verification_status === 'verified';
    const approved = data.approval_status === 'approved';
    return [
        { label: 'Submitted', state: submitted && !returned ? 'done' : returned ? 'error' : 'current' },
        {
            label: 'Verified',
            state: verified ? 'done' : data.verification_status === 'submitted' ? 'current' : 'upcoming',
        },
        {
            label: 'Approved',
            state: approved ? 'done' : data.approval_status === 'rejected' ? 'error' : verified ? 'current' : 'upcoming',
        },
    ];
});

const reviewHint = computed(() => {
    if (!allChecked.value && !hasNotes.value) return 'Complete the checklist and add notes to mark as verified.';
    if (!allChecked.value) return 'Tick every checklist item to mark as verified.';
    if (!hasNotes.value) return 'Add review notes to continue.';
    return '';
});

const label = (status) => (status ?? '').replaceAll('_', ' ');

async function refresh() {
    pageError.value = '';
    try { await store.fetchPage(); }
    catch (error) { pageError.value = error.response?.data?.message ?? 'Unable to load verification queue.'; }
}

async function openProperty(item) {
    selectedId.value = item.id;
    notes.value = '';
    checks.value = { ownership: false, documents: false, property_information: false, pricing: false };
    try { await store.fetchOne(item.id); }
    catch (error) { pageError.value = error.response?.data?.message ?? 'Unable to load property workflow.'; }
}

async function submitForVerification(item) {
    busy.value = true;
    pageError.value = '';
    try { await store.submit(item.id, { notes: notes.value || null }); await refresh(); await openProperty(item); }
    catch (error) { pageError.value = error.response?.data?.message ?? 'Unable to submit property.'; }
    finally { busy.value = false; }
}

async function review(status) {
    if (!selectedId.value) return;
    busy.value = true;
    pageError.value = '';
    try {
        await store.review(selectedId.value, { status, notes: notes.value, checklist: checks.value });
        await refresh(); await openProperty({ id: selectedId.value });
    } catch (error) { pageError.value = error.response?.data?.message ?? 'Unable to save verification review.'; }
    finally { busy.value = false; }
}

async function decide(status) {
    if (!selectedId.value) return;
    busy.value = true;
    pageError.value = '';
    try {
        await store.decide(selectedId.value, { status, notes: notes.value });
        await refresh(); await openProperty({ id: selectedId.value });
    } catch (error) { pageError.value = error.response?.data?.message ?? 'Unable to save manager decision.'; }
    finally { busy.value = false; }
}

function statusClass(status) {
    return ({
        approved: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        verified: 'bg-sky-50 text-sky-700 ring-sky-600/20',
        submitted: 'bg-amber-50 text-amber-700 ring-amber-600/20',
        rejected: 'bg-red-50 text-red-700 ring-red-600/20',
        changes_requested: 'bg-orange-50 text-orange-700 ring-orange-600/20',
        not_submitted: 'bg-slate-100 text-slate-600 ring-slate-500/20',
    })[status] ?? 'bg-slate-100 text-slate-600 ring-slate-500/20';
}

function dotClass(status) {
    return ({
        approved: 'bg-emerald-500',
        verified: 'bg-sky-500',
        submitted: 'bg-amber-500',
        rejected: 'bg-red-500',
        changes_requested: 'bg-orange-500',
        not_submitted: 'bg-slate-400',
    })[status] ?? 'bg-slate-400';
}

function formatDate(value) {
    if (!value) return '';
    return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

function initials(name) {
    return (name || 'U').split(' ').map((part) => part[0]).slice(0, 2).join('').toUpperCase();
}

onMounted(refresh);
</script>

<template>
    <AppLayout>
        <!-- Header -->
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Verification &amp; Approval</h1>
                <p class="mt-1 max-w-2xl text-sm text-slate-500">
                    Review ownership, documents and property information before inventory approval.
                </p>
            </div>
            <button
                class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-brand-700/30 disabled:opacity-50"
                :disabled="store.loading"
                @click="refresh"
            >
                <svg class="h-4 w-4" :class="store.loading ? 'animate-spin' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7" /><path d="M21 3v6h-6" /></svg>
                Refresh
            </button>
        </div>

        <!-- Error banner -->
        <div v-if="pageError" class="mt-4 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert">
            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM9 6h2v5H9V6Zm0 6h2v2H9v-2Z" clip-rule="evenodd" /></svg>
            <p class="flex-1">{{ pageError }}</p>
            <button class="text-red-600 hover:text-red-800" aria-label="Dismiss" @click="pageError = ''">✕</button>
        </div>

        <!-- KPI strip -->
        <div class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <button
                v-for="kpi in [
                    { key: 'pending', title: 'Awaiting verification', tone: 'text-amber-600', dot: 'bg-amber-500' },
                    { key: 'verified', title: 'Awaiting approval', tone: 'text-sky-600', dot: 'bg-sky-500' },
                    { key: 'approved', title: 'Approved', tone: 'text-emerald-600', dot: 'bg-emerald-500' },
                    { key: 'rejected', title: 'Rejected / changes', tone: 'text-red-600', dot: 'bg-red-500' },
                ]"
                :key="kpi.key"
                class="group rounded-xl border bg-white p-4 text-left shadow-sm transition hover:border-slate-300 hover:shadow"
                :class="filter === kpi.key ? 'border-brand-700 ring-1 ring-brand-700/30' : 'border-slate-200'"
                @click="filter = filter === kpi.key ? 'all' : kpi.key"
            >
                <div class="flex items-center gap-2 text-xs font-medium text-slate-500">
                    <span class="h-2 w-2 rounded-full" :class="kpi.dot"></span>{{ kpi.title }}
                </div>
                <p class="mt-2 text-2xl font-semibold tabular-nums text-slate-900">{{ counts[kpi.key] }}</p>
            </button>
        </div>

        <div class="mt-6 grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(380px,0.85fr)]">
            <!-- Queue -->
            <section class="min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-base font-semibold text-slate-900">Property queue</h2>
                        <div class="relative w-full sm:w-64">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
                            <input
                                v-model="search"
                                type="search"
                                placeholder="Search code, name or area…"
                                class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm placeholder:text-slate-400 focus:border-brand-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-700/20"
                            />
                        </div>
                    </div>
                    <div class="-mb-1 mt-3 flex gap-1 overflow-x-auto pb-1">
                        <button
                            v-for="tab in filters"
                            :key="tab.key"
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium transition"
                            :class="filter === tab.key ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            @click="filter = tab.key"
                        >
                            {{ tab.label }}
                            <span class="rounded-full px-1.5 text-[10px] tabular-nums" :class="filter === tab.key ? 'bg-white/20' : 'bg-white text-slate-500'">{{ counts[tab.key] }}</span>
                        </button>
                    </div>
                </div>

                <!-- Loading skeleton -->
                <div v-if="store.loading" class="divide-y divide-slate-100">
                    <div v-for="n in 5" :key="n" class="animate-pulse space-y-2 px-4 py-4">
                        <div class="h-3 w-20 rounded bg-slate-200"></div>
                        <div class="h-4 w-2/3 rounded bg-slate-200"></div>
                        <div class="h-3 w-1/3 rounded bg-slate-100"></div>
                    </div>
                </div>

                <ul v-else-if="visibleItems.length" class="max-h-[640px] divide-y divide-slate-100 overflow-y-auto">
                    <li v-for="item in visibleItems" :key="item.id">
                        <button
                            class="relative block w-full px-4 py-3.5 text-left transition hover:bg-slate-50 focus:outline-none focus-visible:bg-slate-50"
                            :class="selectedId === item.id ? 'bg-brand-50/40' : ''"
                            @click="openProperty(item)"
                        >
                            <span v-if="selectedId === item.id" class="absolute inset-y-0 left-0 w-0.5 bg-brand-800"></span>
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-brand-800">{{ item.property_code }}</p>
                                    <p class="mt-0.5 truncate text-sm font-semibold text-slate-900">{{ item.name }}</p>
                                    <p class="mt-1 flex items-center gap-1.5 text-xs text-slate-500">
                                        <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11Z" /><circle cx="12" cy="10" r="2.5" /></svg>
                                        <span class="truncate">{{ item.area }}<span v-if="item.district">, {{ item.district }}</span></span>
                                        <span class="text-slate-300">•</span>
                                        <span class="shrink-0">{{ item.images_count }} photo{{ item.images_count === 1 ? '' : 's' }}</span>
                                    </p>
                                </div>
                                <div class="flex shrink-0 flex-col items-end gap-1.5">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-medium capitalize ring-1 ring-inset" :class="statusClass(item.verification_status)">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="dotClass(item.verification_status)"></span>
                                        {{ label(item.verification_status) }}
                                    </span>
                                    <span class="text-[11px] capitalize text-slate-400">Approval · {{ label(item.approval_status) }}</span>
                                </div>
                            </div>
                        </button>
                    </li>
                </ul>

                <div v-else class="flex flex-col items-center px-6 py-14 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5V21H3V10.5Z" /><path d="M9 21v-6h6v6" /></svg>
                    </div>
                    <p class="mt-3 text-sm font-medium text-slate-900">No properties found</p>
                    <p class="mt-1 text-xs text-slate-500">Try a different filter or search term.</p>
                    <button v-if="filter !== 'all' || search" class="mt-3 text-xs font-medium text-brand-800 hover:underline" @click="filter = 'all'; search = ''">Clear filters</button>
                </div>
            </section>

            <!-- Detail panel -->
            <section class="min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm xl:sticky xl:top-4">
                <template v-if="selected?.data">
                    <!-- Header -->
                    <div class="border-b border-slate-100 p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-brand-800">{{ selected.data.property_code }}</p>
                                <h2 class="mt-1 truncate text-lg font-semibold text-slate-900">{{ selected.data.name }}</h2>
                                <p class="mt-1 text-sm text-slate-500">{{ selected.data.area }}<span v-if="selected.data.district">, {{ selected.data.district }}</span></p>
                            </div>
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1 ring-inset" :class="statusClass(selected.data.verification_status)">
                                <span class="h-1.5 w-1.5 rounded-full" :class="dotClass(selected.data.verification_status)"></span>
                                {{ label(selected.data.verification_status) }}
                            </span>
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-3 rounded-lg bg-slate-50 p-3 text-sm">
                            <div><dt class="text-xs text-slate-500">Category</dt><dd class="mt-0.5 font-medium text-slate-800">{{ selected.data.category || '—' }}</dd></div>
                            <div><dt class="text-xs text-slate-500">Developer</dt><dd class="mt-0.5 font-medium text-slate-800">{{ selected.data.developer || '—' }}</dd></div>
                        </dl>

                        <!-- Stepper -->
                        <ol class="mt-5 flex items-center">
                            <li v-for="(step, index) in steps" :key="step.label" class="flex flex-1 items-center last:flex-none">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="flex h-6 w-6 items-center justify-center rounded-full text-[11px] font-semibold"
                                        :class="{
                                            'bg-emerald-600 text-white': step.state === 'done',
                                            'bg-brand-900 text-white ring-4 ring-brand-900/10': step.state === 'current',
                                            'bg-red-600 text-white': step.state === 'error',
                                            'bg-slate-200 text-slate-500': step.state === 'upcoming',
                                        }"
                                    >
                                        <template v-if="step.state === 'done'">✓</template>
                                        <template v-else-if="step.state === 'error'">!</template>
                                        <template v-else>{{ index + 1 }}</template>
                                    </span>
                                    <span class="text-xs font-medium" :class="step.state === 'upcoming' ? 'text-slate-400' : 'text-slate-800'">{{ step.label }}</span>
                                </div>
                                <span v-if="index < steps.length - 1" class="mx-3 h-px flex-1" :class="step.state === 'done' ? 'bg-emerald-500' : 'bg-slate-200'"></span>
                            </li>
                        </ol>
                    </div>

                    <div class="space-y-5 p-5">
                        <!-- Checklist -->
                        <div>
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-slate-900">Verification checklist</h3>
                                <span class="text-xs tabular-nums text-slate-500">{{ checklistDone }}/{{ checklistItems.length }}</span>
                            </div>
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-emerald-500 transition-all duration-300" :style="{ width: checklistPercent + '%' }"></div>
                            </div>
                            <div class="mt-3 space-y-2">
                                <label
                                    v-for="entry in checklistItems"
                                    :key="entry.key"
                                    class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition"
                                    :class="checks[entry.key] ? 'border-emerald-200 bg-emerald-50/50' : 'border-slate-200 hover:border-slate-300'"
                                >
                                    <input v-model="checks[entry.key]" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                                    <span>
                                        <span class="block text-sm font-medium text-slate-800">{{ entry.label }}</span>
                                        <span class="block text-xs text-slate-500">{{ entry.hint }}</span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label for="review-notes" class="text-sm font-semibold text-slate-900">Review / decision notes</label>
                            <textarea
                                id="review-notes"
                                v-model="notes"
                                rows="3"
                                class="mt-2 w-full resize-none rounded-lg border border-slate-200 p-3 text-sm placeholder:text-slate-400 focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20"
                                placeholder="Write findings, missing documents or approval remarks…"
                            ></textarea>
                        </div>

                        <!-- Actions -->
                        <div>
                            <button
                                v-if="selected.data.verification_status === 'not_submitted' || ['rejected', 'changes_requested'].includes(selected.data.verification_status)"
                                class="w-full rounded-lg bg-brand-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-700/40 disabled:opacity-50"
                                :disabled="busy"
                                @click="submitForVerification({ id: selected.data.id })"
                            >
                                {{ busy ? 'Processing…' : 'Submit for verification' }}
                            </button>

                            <template v-else-if="selected.data.verification_status === 'submitted'">
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                                    <button class="rounded-lg bg-emerald-600 px-3 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-40" :disabled="busy || !allChecked || !hasNotes" @click="review('verified')">Mark verified</button>
                                    <button class="rounded-lg border border-orange-300 bg-orange-50 px-3 py-2.5 text-sm font-medium text-orange-700 transition hover:bg-orange-100 disabled:cursor-not-allowed disabled:opacity-40" :disabled="busy || !hasNotes" @click="review('changes_requested')">Request changes</button>
                                    <button class="rounded-lg border border-red-300 bg-red-50 px-3 py-2.5 text-sm font-medium text-red-700 transition hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-40" :disabled="busy || !hasNotes" @click="review('rejected')">Reject</button>
                                </div>
                                <p v-if="reviewHint" class="mt-2 text-xs text-slate-500">{{ reviewHint }}</p>
                            </template>

                            <template v-else-if="selected.data.verification_status === 'verified' && selected.data.approval_status !== 'approved'">
                                <div class="grid grid-cols-2 gap-2">
                                    <button class="rounded-lg bg-emerald-600 px-3 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-40" :disabled="busy || !hasNotes" @click="decide('approved')">Approve inventory</button>
                                    <button class="rounded-lg border border-red-300 bg-red-50 px-3 py-2.5 text-sm font-medium text-red-700 transition hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-40" :disabled="busy || !hasNotes" @click="decide('rejected')">Reject approval</button>
                                </div>
                                <p v-if="!hasNotes" class="mt-2 text-xs text-slate-500">Add decision notes to approve or reject.</p>
                            </template>

                            <div v-else-if="selected.data.approval_status === 'approved'" class="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-[11px] text-white">✓</span>
                                This property has been approved for internal inventory circulation.
                            </div>
                        </div>

                        <!-- Timeline -->
                        <div class="border-t border-slate-100 pt-5">
                            <h3 class="text-sm font-semibold text-slate-900">Activity history</h3>
                            <ol v-if="selected.history?.length" class="mt-4 space-y-5">
                                <li v-for="record in selected.history" :key="record.id" class="relative flex gap-3">
                                    <span class="absolute left-3 top-7 -bottom-5 w-px bg-slate-200 last:hidden"></span>
                                    <span class="z-10 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-900 text-[10px] font-semibold text-white">{{ initials(record.actor?.name) }}</span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                            <span class="text-xs font-semibold capitalize text-slate-800">{{ record.stage }}</span>
                                            <span class="rounded-full px-2 py-0.5 text-[10px] font-medium capitalize ring-1 ring-inset" :class="statusClass(record.status)">{{ label(record.status) }}</span>
                                            <span class="text-[11px] text-slate-400">{{ formatDate(record.acted_at) }}</span>
                                        </div>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ record.actor?.name || 'User' }}</p>
                                        <p v-if="record.notes" class="mt-1.5 rounded-md bg-slate-50 px-2.5 py-2 text-xs text-slate-600">{{ record.notes }}</p>
                                    </div>
                                </li>
                            </ol>
                            <p v-else class="mt-2 text-xs text-slate-500">No workflow activity recorded yet.</p>
                        </div>
                    </div>
                </template>

                <div v-else class="flex min-h-80 flex-col items-center justify-center px-6 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3 8-8" /><path d="M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h9" /></svg>
                    </div>
                    <p class="mt-3 text-sm font-medium text-slate-900">No property selected</p>
                    <p class="mt-1 max-w-xs text-xs text-slate-500">Choose a property from the queue to review its verification and approval status.</p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>