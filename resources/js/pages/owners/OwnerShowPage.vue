<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { ownerApi } from '@/api/ownerApi';
import { useOwnersStore } from '@/stores/owners';

const route = useRoute();
const router = useRouter();
const store = useOwnersStore();
const loading = ref(true);
const error = ref('');
const reason = ref('');
const requesting = ref(false);
const notice = ref('');

onMounted(async () => {
    try { await store.fetchOne(route.params.owner); }
    catch (e) { error.value = e.response?.data?.message ?? 'Unable to load owner profile.'; }
    finally { loading.value = false; }
});

async function requestContact() {
    requesting.value = true;
    notice.value = '';
    try {
        await ownerApi.requestContact(route.params.owner, reason.value);
        notice.value = 'Contact access request submitted for review.';
        reason.value = '';
    } catch (e) {
        notice.value = e.response?.data?.message ?? 'Unable to submit request.';
    } finally { requesting.value = false; }
}
</script>

<template>
    <AppLayout>
        <main class="show-page">
            <div class="top"><div><p class="eyebrow">OWNER PROFILE</p><h1>Owner details</h1></div><div class="top-actions"><button class="btn-light" @click="router.back()">Back</button><button v-if="store.current" class="btn-primary" @click="router.push({name:'owners.edit',params:{owner:store.current.id}})">Edit owner</button></div></div>
            <div v-if="loading" class="panel">Loading owner profile…</div>
            <div v-else-if="error" class="alert">{{ error }}</div>
            <template v-else-if="store.current">
                <section class="profile panel">
                    <div><span class="owner-code">{{ store.current.owner_code }}</span><h2>{{ store.current.name }}</h2><p>{{ store.current.owner_type }} owner · {{ store.current.status }}</p></div>
                    <span class="status" :class="`status-${store.current.status}`">{{ store.current.status }}</span>
                </section>

                <section class="panel">
                    <h3>Owner information</h3>
                    <div class="details">
                        <div><span>Phone number</span><strong>{{ store.current.phone || 'Restricted — request access' }}</strong></div>
                        <div><span>Email</span><strong>{{ store.current.email || 'Restricted / not provided' }}</strong></div>
                        <div><span>Profession</span><strong>{{ store.current.profession || '—' }}</strong></div>
                        <div><span>Relationship manager</span><strong>{{ store.current.relationship_manager?.name || 'Unassigned' }}</strong></div>
                        <div class="wide"><span>Detailed address</span><strong>{{ store.current.detailed_address || 'Restricted / not provided' }}</strong></div>
                        <div class="wide"><span>Legal status note</span><strong>{{ store.current.legal_status_note || 'Restricted / not provided' }}</strong></div>
                        <div><span>Owner's expected price</span><strong>{{ store.current.expected_price ? '৳ ' + Number(store.current.expected_price).toLocaleString('en-BD') : 'Restricted / not provided' }}</strong></div>
                        <div v-if="store.current.final_negotiated_price !== undefined"><span>Final negotiated price</span><strong>৳ {{ Number(store.current.final_negotiated_price).toLocaleString('en-BD') }}</strong></div>
                    </div>
                    <div v-if="!store.current.phone" class="request-box">
                        <h4>Request owner contact</h4>
                        <p>Contact information is restricted. Submit a reason for authorized review.</p>
                        <textarea v-model="reason" placeholder="Explain why owner contact is required (minimum 10 characters)"></textarea>
                        <button class="btn-primary" :disabled="requesting || reason.trim().length < 10" @click="requestContact">{{ requesting ? 'Submitting…' : 'Request contact access' }}</button>
                        <small v-if="notice">{{ notice }}</small>
                    </div>
                </section>

                <section class="panel">
                    <h3>Linked properties ({{ store.current.properties?.length || 0 }})</h3>
                    <div v-if="!store.current.properties?.length" class="muted">No properties are currently linked to this owner.</div>
                    <div v-for="property in store.current.properties" :key="property.id" class="property-row">
                        <div><strong>{{ property.name }}</strong><small>{{ property.property_code }} · {{ property.area }}</small></div>
                        <span>{{ property.inventory_status }}</span>
                        <strong>{{ property.asking_price ? '৳ ' + Number(property.asking_price).toLocaleString('en-BD') : '—' }}</strong>
                    </div>
                </section>
            </template>
        </main>
    </AppLayout>
</template>

<style scoped>
.show-page{min-height:100%;padding:28px clamp(16px,3vw,40px);background:#f5f7f9;color:#182230;font-family:ui-sans-serif,system-ui}.top{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}.eyebrow{font-size:11px;letter-spacing:.12em;color:#788493;font-weight:700}.top h1{font-size:26px;font-weight:750;margin-top:5px}.top-actions{display:flex;gap:8px}.panel{background:white;border:1px solid #e1e6ec;border-radius:13px;padding:20px;margin-bottom:16px}.profile{display:flex;justify-content:space-between;align-items:center}.owner-code{font:12px ui-monospace,monospace;color:#0f766e}.profile h2{font-size:21px;font-weight:750;margin:5px 0}.profile p,.muted{font-size:13px;color:#788493;text-transform:capitalize}.status{font-size:11px;border-radius:20px;padding:5px 9px;text-transform:capitalize;background:#e7f7ef;color:#087443}.status-inactive{background:#f2f4f7;color:#667085}.status-blocked{background:#fff1f0;color:#b42318}.panel h3{font-size:15px;font-weight:750;margin-bottom:17px}.details{display:grid;grid-template-columns:1fr 1fr;gap:18px}.details div{display:flex;flex-direction:column;gap:5px}.details span{font-size:11px;color:#8993a1}.details strong{font-size:13px;font-weight:650;overflow-wrap:anywhere}.wide{grid-column:1/-1}.btn-light,.btn-primary{border-radius:8px;padding:9px 14px;font-size:12px;font-weight:650;border:1px solid #d8dee6}.btn-light{background:white;color:#344054}.btn-primary{background:#0f766e;border-color:#0f766e;color:white}.btn-primary:disabled{opacity:.5}.request-box{margin-top:22px;padding:16px;background:#f7faf9;border:1px solid #dcece8;border-radius:10px}.request-box h4{font-size:13px;font-weight:750}.request-box p,.request-box small{display:block;font-size:12px;color:#788493;margin:6px 0 10px}.request-box textarea{width:100%;min-height:75px;border:1px solid #d8dee6;border-radius:8px;padding:10px;font-size:13px;margin-bottom:9px}.property-row{display:grid;grid-template-columns:1fr 100px 150px;align-items:center;gap:10px;padding:13px 0;border-top:1px solid #edf0f3;font-size:12px}.property-row small{display:block;color:#8993a1;margin-top:4px}.property-row span{text-transform:capitalize;color:#667085}@media(max-width:600px){.details{grid-template-columns:1fr}.wide{grid-column:auto}.property-row{grid-template-columns:1fr}.top{align-items:flex-start;gap:10px}}
</style>
