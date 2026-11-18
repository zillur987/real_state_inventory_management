<script setup>
import { onMounted, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { ownerApi } from '@/api/ownerApi';

const items = ref([]);
const loading = ref(false);
const error = ref('');

async function load() {
    loading.value = true;
    try {
        const response = await ownerApi.contactRequests();
        items.value = response.data ?? [];
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Unable to load contact requests.';
    } finally { loading.value = false; }
}

async function review(item, status) {
    const review_note = window.prompt(`Optional note for ${status} request:`, '') ?? '';
    try {
        await ownerApi.reviewContactRequest(item.id, { status, review_note });
        await load();
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Unable to review request.';
    }
}

onMounted(load);
</script>

<template>
    <AppLayout>
        <main class="requests-page">
            <div class="head"><div><p class="eyebrow">OWNER MANAGEMENT</p><h1>Owner contact requests</h1><p>Review requests for restricted owner contact access.</p></div><button class="btn" @click="load">Refresh</button></div>
            <div v-if="error" class="error">{{ error }}</div>
            <section class="panel">
                <div v-if="loading" class="empty">Loading requests…</div>
                <div v-else-if="!items.length" class="empty">No contact requests found.</div>
                <div v-for="item in items" :key="item.id" class="request">
                    <div><strong>{{ item.owner?.name || 'Owner' }}</strong><small>{{ item.owner?.owner_code }} · Requested by {{ item.requester?.name || 'User' }}</small><p>{{ item.reason }}</p></div>
                    <div class="right"><span class="status" :class="`status-${item.status}`">{{ item.status }}</span><div v-if="item.status === 'pending'" class="actions"><button @click="review(item,'approved')">Approve</button><button @click="review(item,'rejected')">Reject</button></div><small v-else>{{ item.review_note || 'No review note' }}</small></div>
                </div>
            </section>
        </main>
    </AppLayout>
</template>

<style scoped>
.requests-page{min-height:100%;padding:28px clamp(16px,3vw,40px);background:#f5f7f9;color:#182230;font-family:ui-sans-serif,system-ui}.head{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}.eyebrow{font-size:11px;letter-spacing:.12em;color:#788493;font-weight:700}.head h1{font-size:25px;font-weight:750;margin:5px 0}.head p:last-child{font-size:13px;color:#788493}.panel{background:white;border:1px solid #e1e6ec;border-radius:13px;padding:18px}.btn{padding:9px 14px;border:1px solid #d8dee6;border-radius:8px;background:white;font-size:12px}.empty{text-align:center;padding:40px;color:#788493;font-size:13px}.request{display:flex;justify-content:space-between;gap:20px;padding:16px 4px;border-bottom:1px solid #edf0f3}.request:last-child{border:0}.request strong{font-size:14px}.request small{display:block;color:#8993a1;font-size:11px;margin-top:4px}.request p{font-size:13px;color:#475467;margin-top:10px}.right{text-align:right;min-width:140px}.status{display:inline-block;padding:4px 9px;border-radius:20px;background:#fff7e6;color:#9a6700;font-size:11px;text-transform:capitalize}.status-approved{background:#e7f7ef;color:#087443}.status-rejected{background:#fff1f0;color:#b42318}.actions{display:flex;gap:10px;justify-content:flex-end;margin-top:12px}.actions button{font-size:12px;color:#0f766e;font-weight:700}.actions button:last-child{color:#b42318}.error{background:#fff1f0;color:#b42318;padding:10px;border-radius:8px;margin-bottom:12px;font-size:13px}@media(max-width:600px){.head,.request{align-items:flex-start;flex-direction:column}.right{text-align:left}.actions{justify-content:flex-start}}
</style>
