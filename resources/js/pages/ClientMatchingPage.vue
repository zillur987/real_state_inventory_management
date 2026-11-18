<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useClientMatchingStore } from '@/stores/clientMatching';

const store = useClientMatchingStore();
const search = ref('');
const showCreate = ref(false);
const activeTab = ref('requirements');
const notice = ref('');
const clientForm = reactive({ name: '', phone: '', email: '', source: '', assigned_to: '', notes: '' });
const requirementForm = reactive({
    category: '', market_type: '', division: '', district: '', area: '',
    budget_min: '', budget_max: '', size_min_sqft: '', size_max_sqft: '',
    bedrooms: '', bathrooms: '', parking_spaces: '', facing: '', handover_year: '', notes: '',
});
const activityForm = reactive({ type: 'call', subject: '', description: '', due_at: '' });
const selectedClient = computed(() => store.selected?.client ?? null);
const selectedRequirement = computed(() => store.selected?.requirement ?? null);

async function loadClients() {
    await store.fetchClients({ search: search.value || undefined });
}
async function createClient() {
    try {
        const result = await store.createClient({ ...clientForm, assigned_to: clientForm.assigned_to || null });
        showCreate.value = false;
        Object.assign(clientForm, { name: '', phone: '', email: '', source: '', assigned_to: '', notes: '' });
        await openClient(result.client.id);
        notice.value = 'Client created successfully.';
    } catch (error) { notice.value = error.response?.data?.message ?? 'Unable to create client.'; }
}
async function openClient(id) {
    try {
        const result = await store.fetchClient(id);
        Object.assign(requirementForm, Object.fromEntries(Object.keys(requirementForm).map((key) => [key, result.requirement?.[key] ?? ''])));
        store.matches = [];
        activeTab.value = 'requirements';
    } catch (error) { notice.value = error.response?.data?.message ?? 'Unable to open client.'; }
}
async function saveRequirement() {
    if (!selectedClient.value) return;
    try {
        const payload = Object.fromEntries(Object.entries(requirementForm).map(([key, value]) => [key, value === '' ? null : value]));
        await store.saveRequirement(selectedClient.value.id, payload);
        notice.value = 'Client requirements saved.';
    } catch (error) { notice.value = error.response?.data?.message ?? 'Unable to save requirements.'; }
}
async function generateMatches() {
    if (!selectedClient.value) return;
    try {
        await store.generateMatches(selectedClient.value.id);
        activeTab.value = 'matches';
        notice.value = `${store.matches.length} eligible property matches found.`;
    } catch (error) { notice.value = error.response?.data?.message ?? 'Unable to generate matches.'; }
}
async function shortlist(propertyId) {
    try {
        await store.shortlist(selectedClient.value.id, propertyId);
        notice.value = 'Property added to shortlist.';
    } catch (error) { notice.value = error.response?.data?.message ?? 'Unable to shortlist property.'; }
}
async function addActivity() {
    if (!selectedClient.value) return;
    try {
        await store.addActivity(selectedClient.value.id, { ...activityForm, due_at: activityForm.due_at || null });
        Object.assign(activityForm, { type: 'call', subject: '', description: '', due_at: '' });
        notice.value = 'CRM activity saved.';
    } catch (error) { notice.value = error.response?.data?.message ?? 'Unable to save activity.'; }
}
onMounted(loadClients);
</script>

<template>
    <main class="client-crm-page">
        <header class="page-header">
            <div>
                <p class="eyebrow">PIMS · STEP 6</p>
                <h1>Client Matching & CRM</h1>
                <p class="muted">Manage client requirements, find approved inventory and track follow-ups.</p>
            </div>
            <button class="primary-button" @click="showCreate = !showCreate">{{ showCreate ? 'Close' : '+ Add Client' }}</button>
        </header>

        <p v-if="notice" class="notice" role="status">{{ notice }}</p>
        <p v-if="store.error" class="error-message">{{ store.error }}</p>

        <section v-if="showCreate" class="panel create-panel">
            <h2>Register Client</h2>
            <form class="form-grid" @submit.prevent="createClient">
                <label>Client name *<input v-model.trim="clientForm.name" required maxlength="150"></label>
                <label>Phone<input v-model.trim="clientForm.phone" maxlength="40"></label>
                <label>Email<input v-model.trim="clientForm.email" type="email"></label>
                <label>Lead source<input v-model.trim="clientForm.source" placeholder="Website, referral, walk-in"></label>
                <label class="wide">Notes<textarea v-model="clientForm.notes" rows="2"></textarea></label>
                <div class="wide actions"><button class="primary-button" :disabled="store.saving">{{ store.saving ? 'Saving…' : 'Save Client' }}</button></div>
            </form>
        </section>

        <div class="workspace">
            <aside class="panel client-list-panel">
                <div class="panel-heading"><h2>Clients</h2><span class="count">{{ store.pagination?.total ?? store.clients.length }}</span></div>
                <form class="search-row" @submit.prevent="loadClients"><input v-model="search" placeholder="Search name, phone or code"><button type="submit">Search</button></form>
                <div v-if="store.loading && !store.clients.length" class="empty-state">Loading clients…</div>
                <button v-for="client in store.clients" :key="client.id" class="client-item" :class="{ active: selectedClient?.id === client.id }" @click="openClient(client.id)">
                    <span class="client-avatar">{{ client.name?.slice(0, 1)?.toUpperCase() }}</span>
                    <span class="client-copy"><strong>{{ client.name }}</strong><small>{{ client.client_code }} · {{ client.phone || 'No phone' }}</small></span>
                    <span class="status-pill">{{ client.status }}</span>
                </button>
                <p v-if="!store.clients.length && !store.loading" class="empty-state">No clients found. Add your first client.</p>
            </aside>

            <section class="panel detail-panel">
                <template v-if="selectedClient">
                    <div class="client-detail-header">
                        <div><p class="eyebrow">{{ selectedClient.client_code }}</p><h2>{{ selectedClient.name }}</h2><p class="muted">{{ selectedClient.phone || 'No phone' }} <span v-if="selectedClient.email">· {{ selectedClient.email }}</span></p></div>
                        <span class="status-pill">{{ selectedClient.status }}</span>
                    </div>
                    <nav class="tabs" aria-label="Client workspace tabs">
                        <button :class="{ active: activeTab === 'requirements' }" @click="activeTab = 'requirements'">Requirements</button>
                        <button :class="{ active: activeTab === 'matches' }" @click="activeTab = 'matches'">Property Matches ({{ store.matches.length }})</button>
                        <button :class="{ active: activeTab === 'activities' }" @click="activeTab = 'activities'">CRM Activities</button>
                    </nav>

                    <form v-if="activeTab === 'requirements'" class="form-grid requirement-form" @submit.prevent="saveRequirement">
                        <label>Property category<select v-model="requirementForm.category"><option value="">Any category</option><option>Apartment</option><option>Flat</option><option>Duplex</option><option>Penthouse</option><option>Land</option><option>Plot</option><option>Commercial</option></select></label>
                        <label>Market type<select v-model="requirementForm.market_type"><option value="">Any market</option><option value="primary">Primary</option><option value="secondary">Secondary</option></select></label>
                        <label>Division<input v-model.trim="requirementForm.division"></label>
                        <label>District<input v-model.trim="requirementForm.district"></label>
                        <label>Area<input v-model.trim="requirementForm.area"></label>
                        <label>Facing<select v-model="requirementForm.facing"><option value="">Any facing</option><option>North</option><option>South</option><option>East</option><option>West</option><option>Corner</option></select></label>
                        <label>Minimum budget<input v-model="requirementForm.budget_min" type="number" min="0"></label>
                        <label>Maximum budget<input v-model="requirementForm.budget_max" type="number" min="0"></label>
                        <label>Minimum size (sq. ft.)<input v-model="requirementForm.size_min_sqft" type="number" min="0"></label>
                        <label>Maximum size (sq. ft.)<input v-model="requirementForm.size_max_sqft" type="number" min="0"></label>
                        <label>Bedrooms (minimum)<input v-model="requirementForm.bedrooms" type="number" min="0" max="20"></label>
                        <label>Bathrooms (minimum)<input v-model="requirementForm.bathrooms" type="number" min="0" max="20"></label>
                        <label>Parking spaces (minimum)<input v-model="requirementForm.parking_spaces" type="number" min="0" max="20"></label>
                        <label>Handover year (from)<input v-model="requirementForm.handover_year" type="number" min="2000" max="2150"></label>
                        <label class="wide">Additional requirements<textarea v-model="requirementForm.notes" rows="2"></textarea></label>
                        <div class="wide actions"><button class="primary-button" :disabled="store.saving">Save Requirements</button><button type="button" class="secondary-button" @click="generateMatches" :disabled="store.loading">{{ store.loading ? 'Matching…' : 'Find Matching Properties' }}</button></div>
                    </form>

                    <section v-else-if="activeTab === 'matches'" class="match-list">
                        <div class="section-intro"><div><h3>Eligible property matches</h3><p class="muted">Only available properties with latest verification = Verified and approval = Approved are shown.</p></div><button class="secondary-button" @click="generateMatches" :disabled="store.loading">Refresh matches</button></div>
                        <article v-for="match in store.matches" :key="match.property.id" class="match-card">
                            <div class="match-top"><div><strong>{{ match.property.name }}</strong><small>{{ match.property.property_code }} · {{ match.property.category }} · {{ match.property.area || match.property.district }}</small></div><span class="score">{{ match.match_score }}% match</span></div>
                            <div class="match-meta"><span>{{ match.property.bedrooms ?? '—' }} beds</span><span>{{ match.property.apartment_size_sqft ?? '—' }} sq. ft.</span><strong>{{ match.property.asking_price ?? 'Price on request' }}</strong></div>
                            <p class="muted">Matched on: {{ match.match_reasons.join(', ') || 'Available approved inventory' }}</p>
                            <button class="secondary-button" :disabled="match.shortlisted" @click="shortlist(match.property.id)">{{ match.shortlisted ? 'Already shortlisted' : 'Add to shortlist' }}</button>
                        </article>
                        <p v-if="!store.matches.length" class="empty-state">No matches loaded. Save requirements and select Find Matching Properties.</p>
                    </section>

                    <section v-else class="activities-panel">
                        <form class="activity-form" @submit.prevent="addActivity">
                            <h3>Record client interaction / follow-up</h3>
                            <div class="form-grid">
                                <label>Activity type<select v-model="activityForm.type"><option value="call">Call</option><option value="meeting">Meeting</option><option value="email">Email</option><option value="visit">Property visit</option><option value="follow_up">Follow-up</option><option value="note">Note</option></select></label>
                                <label>Follow-up date<input v-model="activityForm.due_at" type="datetime-local"></label>
                                <label class="wide">Subject *<input v-model.trim="activityForm.subject" required maxlength="180"></label>
                                <label class="wide">Details<textarea v-model="activityForm.description" rows="2"></textarea></label>
                                <div class="wide actions"><button class="primary-button" :disabled="store.saving">Save Activity</button></div>
                            </div>
                        </form>
                        <h3>Activity history</h3>
                        <article v-for="activity in store.activities" :key="activity.id" class="activity-item"><strong>{{ activity.subject }}</strong><small>{{ activity.type }} · {{ activity.due_at || activity.created_at }}</small><p>{{ activity.description }}</p></article>
                        <p v-if="!store.activities.length" class="empty-state">No CRM activities recorded yet.</p>
                    </section>
                </template>
                <div v-else class="empty-detail"><div class="empty-icon">◎</div><h2>Select a client</h2><p class="muted">Choose a client from the list or add a new client to begin matching.</p></div>
            </section>
        </div>
    </main>
</template>

<style scoped>
.client-crm-page{padding:24px;color:var(--text-primary,#202938)}.page-header,.client-detail-header,.panel-heading,.section-intro,.match-top,.match-meta,.actions{display:flex;align-items:center;justify-content:space-between;gap:14px}.page-header{margin-bottom:22px}.page-header h1{font-size:25px;margin:3px 0}.eyebrow{font-size:11px;letter-spacing:.09em;color:#64748b;font-weight:700;margin:0 0 5px}.muted,.page-header p{color:#64748b;font-size:13px}.panel{background:var(--surface,#fff);border:1px solid var(--border-color,#e5e7eb);border-radius:12px;padding:18px;min-width:0}.panel h2{font-size:17px;margin:0 0 12px}.workspace{display:grid;grid-template-columns:minmax(260px,330px) minmax(0,1fr);gap:18px}.client-list-panel{align-self:start}.search-row{display:flex;gap:7px;margin:12px 0}.search-row input{min-width:0;flex:1}.client-item{width:100%;display:flex;align-items:center;gap:10px;text-align:left;border:0;border-top:1px solid #edf0f3;background:transparent;padding:12px 4px;cursor:pointer}.client-item.active{background:#f4f7fb}.client-avatar{width:34px;height:34px;border-radius:50%;display:grid;place-items:center;background:#e8eef8;color:#29466f;font-weight:700}.client-copy{display:grid;gap:3px;min-width:0;flex:1}.client-copy strong,.match-top strong{font-size:13px}.client-copy small,.match-top small,.activity-item small{font-size:11px;color:#64748b}.status-pill,.count,.score{font-size:11px;border-radius:20px;padding:4px 9px;background:#edf2f7;color:#475569;white-space:nowrap}.count{padding:3px 8px}.primary-button,.secondary-button,.search-row button{border-radius:7px;padding:9px 13px;font-size:12px;font-weight:600;cursor:pointer}.primary-button{background:#244a78;color:#fff;border:1px solid #244a78}.secondary-button,.search-row button{background:#fff;color:#334155;border:1px solid #cbd5e1}.primary-button:disabled,.secondary-button:disabled{opacity:.55;cursor:not-allowed}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px}.form-grid label{display:grid;gap:6px;font-size:12px;color:#475569;font-weight:600}.form-grid input,.form-grid select,.form-grid textarea,.search-row input{width:100%;border:1px solid #cbd5e1;border-radius:7px;padding:9px 10px;background:#fff;color:#1f2937;font:inherit;font-weight:400;min-width:0}.wide{grid-column:1/-1}.create-panel{margin-bottom:18px}.detail-panel{min-height:540px}.client-detail-header{padding-bottom:14px}.client-detail-header h2{font-size:20px;margin:0}.tabs{display:flex;gap:5px;border-bottom:1px solid #e5e7eb;margin-bottom:18px;overflow-x:auto}.tabs button{padding:10px 12px;border:0;border-bottom:2px solid transparent;background:transparent;color:#64748b;white-space:nowrap;cursor:pointer;font-size:12px}.tabs button.active{color:#244a78;border-bottom-color:#244a78;font-weight:700}.requirement-form{padding-top:3px}.match-list,.activities-panel{display:grid;gap:12px}.section-intro{align-items:flex-start}.section-intro h3,.activities-panel h3,.activity-form h3{margin:0 0 5px;font-size:15px}.match-card,.activity-item{border:1px solid #e5e7eb;border-radius:9px;padding:13px}.match-top{align-items:flex-start}.match-top>div{display:grid;gap:4px}.score{background:#eaf2fb;color:#244a78;font-weight:700}.match-meta{justify-content:flex-start;flex-wrap:wrap;margin:12px 0;font-size:12px;color:#475569}.match-meta strong{margin-left:auto}.activity-form{background:#f8fafc;border-radius:9px;padding:14px}.activity-item{display:grid;gap:5px}.activity-item p{font-size:13px;margin:0;color:#475569}.empty-state,.empty-detail{color:#64748b;text-align:center;padding:28px 12px;font-size:13px}.empty-detail{padding-top:100px}.empty-detail h2{color:#334155;margin:12px 0 5px}.empty-icon{font-size:38px;color:#94a3b8}.notice{padding:10px 12px;border-radius:8px;background:#eff6ff;color:#1d4ed8;font-size:13px;margin:0 0 14px}.error-message{color:#b91c1c;font-size:13px}.actions{justify-content:flex-start}.activity-form .form-grid{margin-top:12px}@media(max-width:900px){.workspace{grid-template-columns:1fr}.client-list-panel{max-height:360px;overflow:auto}}@media(max-width:600px){.client-crm-page{padding:14px}.page-header{align-items:flex-start}.page-header h1{font-size:21px}.form-grid{grid-template-columns:1fr}.wide{grid-column:auto}.match-meta strong{margin-left:0}}
</style>
