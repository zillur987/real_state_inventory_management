<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { ownerApi } from '@/api/ownerApi';
import { useOwnersStore } from '@/stores/owners';

const router = useRouter();
const store = useOwnersStore();
const filters = reactive({ search: '', status: '', owner_type: '', per_page: 15, page: 1 });
const busy = ref(false);
const error = ref('');

async function load() {
    busy.value = true;
    error.value = '';
    try {
        await store.fetchPage(filters);
    } catch {
        error.value = 'Unable to load owner records. Check your access permission and try again.';
    } finally {
        busy.value = false;
    }
}

function openOwner(owner) {
    router.push({ name: 'owners.show', params: { owner: owner.id } });
}

function editOwner(owner) {
    router.push({ name: 'owners.edit', params: { owner: owner.id } });
}

async function archiveOwner(owner) {
    if (!window.confirm(`Archive ${owner.name}?`)) return;
    try {
        await ownerApi.archive(owner.id);
        await load();
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Unable to archive owner.';
    }
}

function changePage(page) {
    filters.page = page;
    load();
}

onMounted(load);
</script>

<template>
    <AppLayout>
        <main class="owner-page">
            <header class="owner-heading">
                <div>
                    <p class="eyebrow">PIMS / DIRECTORY</p>
                    <h1>Owner Management</h1>
                    <p class="subheading">Manage owner profiles, restricted contact records and linked inventory.</p>
                </div>
                <div class="heading-actions">
                    <button class="btn btn-light" @click="router.push({ name: 'owners.contact-requests' })">Contact requests</button>
                    <button class="btn btn-primary" @click="router.push({ name: 'owners.create' })">＋ Add owner</button>
                </div>
            </header>

            <section class="stats-grid">
                <div class="stat-card"><span>Records on this page</span><strong>{{ store.items.length }}</strong></div>
                <div class="stat-card"><span>Total owners</span><strong>{{ store.pagination?.total ?? '—' }}</strong></div>
                <div class="stat-card"><span>Current page</span><strong>{{ store.pagination?.current_page ?? 1 }}</strong></div>
            </section>

            <section class="panel">
                <div class="filters">
                    <input v-model="filters.search" class="field search-field" placeholder="Search owner name, ID, email or phone…" @keyup.enter="load">
                    <select v-model="filters.status" class="field" @change="load">
                        <option value="">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option><option value="blocked">Blocked</option>
                    </select>
                    <select v-model="filters.owner_type" class="field" @change="load">
                        <option value="">All owner types</option><option value="individual">Individual</option><option value="joint">Joint</option><option value="company">Company</option>
                    </select>
                    <button class="btn btn-primary" @click="load">Search</button>
                </div>

                <p v-if="error" class="error">{{ error }}</p>
                <div v-if="busy" class="empty-state">Loading owner records…</div>
                <div v-else-if="!store.items.length" class="empty-state">No owner records found. Add an owner to start building your directory.</div>
                <div v-else class="table-wrap">
                    <table>
                        <thead><tr><th>Owner</th><th>Owner ID</th><th>Type</th><th>Relationship Manager</th><th>Status</th><th>Created</th><th class="right">Actions</th></tr></thead>
                        <tbody>
                            <tr v-for="owner in store.items" :key="owner.id">
                                <td><strong>{{ owner.name }}</strong><small>{{ owner.email || 'Contact details restricted' }}</small></td>
                                <td class="mono">{{ owner.owner_code }}</td>
                                <td class="capitalize">{{ owner.owner_type }}</td>
                                <td>{{ owner.relationship_manager?.name || 'Unassigned' }}</td>
                                <td><span class="status" :class="`status-${owner.status}`">{{ owner.status }}</span></td>
                                <td>{{ owner.created_at ? new Date(owner.created_at).toLocaleDateString() : '—' }}</td>
                                <td class="right actions">
                                    <button @click="openOwner(owner)">View</button>
                                    <button @click="editOwner(owner)">Edit</button>
                                    <button class="danger-link" @click="archiveOwner(owner)">Archive</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <footer v-if="store.pagination" class="pagination">
                    <span>Showing {{ store.pagination.from ?? 0 }}–{{ store.pagination.to ?? 0 }} of {{ store.pagination.total ?? 0 }}</span>
                    <div>
                        <button class="btn btn-light" :disabled="!store.pagination.prev_page_url" @click="changePage(filters.page - 1)">Previous</button>
                        <button class="btn btn-light" :disabled="!store.pagination.next_page_url" @click="changePage(filters.page + 1)">Next</button>
                    </div>
                </footer>
            </section>
        </main>
    </AppLayout>
</template>

<style scoped>
.owner-page{min-height:100%;padding:28px clamp(16px,3vw,40px);background:#f5f7f9;color:#182230;font-family:ui-sans-serif,system-ui}
.owner-heading{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;margin-bottom:24px}.eyebrow{font-size:11px;letter-spacing:.12em;color:#788493;font-weight:700}.owner-heading h1{font-size:27px;font-weight:750;margin:5px 0}.subheading{color:#697586;font-size:13px}.heading-actions{display:flex;gap:9px;flex-wrap:wrap}
.btn{border-radius:9px;padding:10px 15px;font-size:13px;font-weight:650;border:1px solid #d8dee6}.btn-primary{background:#0f766e;color:white;border-color:#0f766e}.btn-light{background:white;color:#344054}.btn:disabled{opacity:.45}
.stats-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:18px}.stat-card,.panel{background:#fff;border:1px solid #e1e6ec;border-radius:13px}.stat-card{padding:17px}.stat-card span{display:block;color:#788493;font-size:12px}.stat-card strong{display:block;font-size:23px;margin-top:5px}
.panel{padding:18px}.filters{display:grid;grid-template-columns:minmax(220px,1fr) 170px 180px auto;gap:10px;margin-bottom:17px}.field{height:42px;border:1px solid #d8dee6;border-radius:9px;padding:0 12px;background:white;color:#344054;font-size:13px;min-width:0}.field:focus{outline:2px solid #c7e8e4;border-color:#0f766e}.error{padding:10px;background:#fff1f0;color:#b42318;border-radius:8px;font-size:13px}.empty-state{text-align:center;padding:45px 12px;color:#7a8593;font-size:13px}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;white-space:nowrap}th{text-align:left;background:#f8fafb;color:#788493;font-size:11px;text-transform:uppercase;letter-spacing:.06em;padding:12px}td{border-top:1px solid #edf0f3;padding:14px 12px;font-size:13px;color:#344054}td strong{display:block;color:#182230}td small{display:block;color:#98a2b3;margin-top:3px;font-size:11px}.mono{font-family:ui-monospace,monospace;font-size:12px}.capitalize{text-transform:capitalize}.right{text-align:right}.actions{display:flex;justify-content:flex-end;gap:12px}.actions button{font-size:12px;color:#0f766e;font-weight:650}.actions .danger-link{color:#b42318}.status{padding:4px 8px;border-radius:20px;font-size:11px;text-transform:capitalize;background:#edf2f7}.status-active{background:#e7f7ef;color:#087443}.status-inactive{background:#f2f4f7;color:#667085}.status-blocked{background:#fff1f0;color:#b42318}.pagination{display:flex;justify-content:space-between;align-items:center;gap:12px;border-top:1px solid #edf0f3;padding-top:15px;margin-top:10px;color:#7a8593;font-size:12px}.pagination div{display:flex;gap:7px}
@media(max-width:800px){.owner-heading{flex-direction:column}.filters{grid-template-columns:1fr 1fr}.search-field{grid-column:1/-1}.stats-grid{grid-template-columns:1fr}.pagination{align-items:flex-start;flex-direction:column}}@media(max-width:480px){.filters{grid-template-columns:1fr}.search-field{grid-column:auto}}
</style>
