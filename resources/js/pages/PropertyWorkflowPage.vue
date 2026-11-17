<script setup>
import { computed, onMounted, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePropertyWorkflowStore } from '@/stores/propertyWorkflow';

const store = usePropertyWorkflowStore();
const filter = ref('all');
const selectedId = ref(null);
const selected = computed(() => store.selected);
const busy = ref(false);
const pageError = ref('');
const notes = ref('');
const checks = ref({ ownership: false, documents: false, property_information: false, pricing: false });

const checklistItems = [
    ['ownership', 'Ownership details checked'],
    ['documents', 'Property documents reviewed'],
    ['property_information', 'Property information and specifications checked'],
    ['pricing', 'Asking price and supporting details checked'],
];

const visibleItems = computed(() => {
    if (filter.value === 'all') return store.items;
    if (filter.value === 'pending') return store.items.filter((item) => item.verification_status === 'submitted');
    if (filter.value === 'verified') return store.items.filter((item) => item.verification_status === 'verified' && item.approval_status !== 'approved');
    if (filter.value === 'approved') return store.items.filter((item) => item.approval_status === 'approved');
    if (filter.value === 'rejected') return store.items.filter((item) => ['rejected', 'changes_requested'].includes(item.verification_status) || item.approval_status === 'rejected');
    return store.items;
});

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
    return ({ approved: 'bg-emerald-50 text-emerald-700', verified: 'bg-sky-50 text-sky-700', submitted: 'bg-amber-50 text-amber-700', rejected: 'bg-red-50 text-red-700', changes_requested: 'bg-orange-50 text-orange-700', not_submitted: 'bg-slate-100 text-slate-600' })[status] ?? 'bg-slate-100 text-slate-600';
}

onMounted(refresh);
</script>

<template>
    <AppLayout>
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Verification & Approval</h1>
                <p class="mt-1 text-sm text-slate-500">Review property ownership, documents and information before inventory approval.</p>
            </div>
            <button class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" :disabled="store.loading" @click="refresh">Refresh</button>
        </div>

        <p v-if="pageError" class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{{ pageError }}</p>

        <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(360px,0.85fr)]">
            <section class="min-w-0 rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-semibold text-slate-900">Property queue</h2>
                    <select v-model="filter" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                        <option value="all">All properties</option><option value="pending">Awaiting verification</option><option value="verified">Verified / awaiting approval</option><option value="approved">Approved</option><option value="rejected">Rejected / changes requested</option>
                    </select>
                </div>
                <div v-if="store.loading" class="py-8 text-center text-sm text-slate-500">Loading properties…</div>
                <div v-else-if="visibleItems.length" class="mt-4 divide-y divide-slate-100">
                    <button v-for="item in visibleItems" :key="item.id" class="block w-full rounded-lg px-3 py-3 text-left hover:bg-slate-50" :class="selectedId === item.id ? 'bg-slate-50 ring-1 ring-brand-200' : ''" @click="openProperty(item)">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0"><p class="text-xs font-semibold text-brand-800">{{ item.property_code }}</p><p class="mt-1 truncate text-sm font-semibold text-slate-900">{{ item.name }}</p><p class="mt-1 text-xs text-slate-500">{{ item.area }}<span v-if="item.district">, {{ item.district }}</span> · {{ item.images_count }} photo(s)</p></div>
                            <div class="flex shrink-0 flex-col items-end gap-1"><span class="rounded-full px-2 py-1 text-[11px] font-medium" :class="statusClass(item.verification_status)">{{ item.verification_status.replaceAll('_', ' ') }}</span><span class="text-[11px] text-slate-500">Approval: {{ item.approval_status.replaceAll('_', ' ') }}</span></div>
                        </div>
                    </button>
                </div>
                <p v-else class="py-8 text-center text-sm text-slate-500">No properties found for this filter.</p>
            </section>

            <section class="min-w-0 rounded-xl border border-slate-200 bg-white p-4">
                <template v-if="selected?.data">
                    <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-semibold text-brand-800">{{ selected.data.property_code }}</p><h2 class="mt-1 text-lg font-semibold text-slate-900">{{ selected.data.name }}</h2><p class="mt-1 text-sm text-slate-500">{{ selected.data.area }}<span v-if="selected.data.district">, {{ selected.data.district }}</span></p></div><span class="rounded-full px-2 py-1 text-xs font-medium" :class="statusClass(selected.data.verification_status)">{{ selected.data.verification_status.replaceAll('_', ' ') }}</span></div>
                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm"><div><p class="text-xs text-slate-500">Category</p><p class="mt-1 font-medium text-slate-800">{{ selected.data.category || '—' }}</p></div><div><p class="text-xs text-slate-500">Developer</p><p class="mt-1 font-medium text-slate-800">{{ selected.data.developer || '—' }}</p></div></div>
                    <div class="mt-4 border-t border-slate-100 pt-4"><h3 class="text-sm font-semibold text-slate-900">Verification checklist</h3><p class="mt-1 text-xs text-slate-500">Tick each item after checking the supporting information and documents.</p><label v-for="entry in checklistItems" :key="entry[0]" class="mt-3 flex items-center gap-2 text-sm text-slate-700"><input v-model="checks[entry[0]]" type="checkbox" class="rounded border-slate-300 text-brand-800 focus:ring-brand-700" />{{ entry[1] }}</label></div>
                    <div class="mt-4"><label class="text-sm font-medium text-slate-700">Review / decision notes</label><textarea v-model="notes" rows="3" class="mt-1 w-full rounded-md border border-slate-300 p-3 text-sm focus:border-brand-700 focus:outline-none" placeholder="Write findings, missing documents or approval remarks…"></textarea></div>
                    <div v-if="selected.data.verification_status === 'not_submitted' || ['rejected', 'changes_requested'].includes(selected.data.verification_status)" class="mt-4"><button class="w-full rounded-md bg-brand-900 px-4 py-2.5 text-sm font-medium text-white disabled:opacity-50" :disabled="busy" @click="submitForVerification({ id: selected.data.id })">{{ busy ? 'Processing…' : 'Submit for verification' }}</button></div>
                    <div v-else-if="selected.data.verification_status === 'submitted'" class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-3"><button class="rounded-md bg-emerald-700 px-3 py-2 text-sm font-medium text-white disabled:opacity-50" :disabled="busy || !Object.values(checks).every(Boolean) || !notes.trim()" @click="review('verified')">Mark verified</button><button class="rounded-md bg-orange-600 px-3 py-2 text-sm font-medium text-white disabled:opacity-50" :disabled="busy || !notes.trim()" @click="review('changes_requested')">Request changes</button><button class="rounded-md bg-red-700 px-3 py-2 text-sm font-medium text-white disabled:opacity-50" :disabled="busy || !notes.trim()" @click="review('rejected')">Reject</button></div>
                    <div v-else-if="selected.data.verification_status === 'verified' && selected.data.approval_status !== 'approved'" class="mt-4 grid grid-cols-2 gap-2"><button class="rounded-md bg-emerald-700 px-3 py-2 text-sm font-medium text-white disabled:opacity-50" :disabled="busy || !notes.trim()" @click="decide('approved')">Approve inventory</button><button class="rounded-md bg-red-700 px-3 py-2 text-sm font-medium text-white disabled:opacity-50" :disabled="busy || !notes.trim()" @click="decide('rejected')">Reject approval</button></div>
                    <div v-else-if="selected.data.approval_status === 'approved'" class="mt-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-800">This property has been approved for internal inventory circulation.</div>
                    <div class="mt-5 border-t border-slate-100 pt-4"><h3 class="text-sm font-semibold text-slate-900">Activity history</h3><div v-if="selected.history?.length" class="mt-3 space-y-3"><div v-for="record in selected.history" :key="record.id" class="border-l-2 border-slate-200 pl-3"><div class="flex flex-wrap items-center gap-2"><span class="text-xs font-semibold capitalize text-slate-800">{{ record.stage }}: {{ record.status.replaceAll('_', ' ') }}</span><span class="text-xs text-slate-400">{{ record.acted_at ? new Date(record.acted_at).toLocaleString() : '' }}</span></div><p class="mt-1 text-xs text-slate-500">{{ record.actor?.name || 'User' }}<span v-if="record.notes"> · {{ record.notes }}</span></p></div></div><p v-else class="mt-2 text-xs text-slate-500">No workflow activity recorded yet.</p></div>
                </template>
                <div v-else class="flex min-h-64 items-center justify-center text-center text-sm text-slate-500">Select a property from the queue to review its verification and approval status.</div>
            </section>
        </div>
    </AppLayout>
</template>
