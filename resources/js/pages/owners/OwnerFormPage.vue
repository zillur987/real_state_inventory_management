<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { useOwnersStore } from '@/stores/owners';
import { useToast } from 'vue-toastification';

const route = useRoute();
const router = useRouter();
const store = useOwnersStore();
const toast = useToast();

const editing = computed(() => Boolean(route.params.owner));
const saving = ref(false);
const loading = ref(false);
const errors = ref({});

const form = reactive({
    name: '',
    phone: '',
    email: '',
    profession: '',
    detailed_address: '',
    legal_status_note: '',
    expected_price: '',
    relationship_manager_id: '',
    owner_type: 'individual',
    status: 'active',
    notes: '',
});

function fieldError(key) {
    return errors.value[key]?.[0] || '';
}

/** Normalize common Laravel API Resource response structures. */
function getOwnerData(response) {
    let data = response?.data ?? response;
    if (data?.data) data = data.data;
    return data;
}

/** Load existing owner information for edit. */
async function loadOwner() {
    if (!editing.value) return;

    loading.value = true;
    errors.value = {};

    try {
        const response = await store.fetchOne(route.params.owner);
        const owner = getOwnerData(response);

        if (!owner || !owner.id) {
            throw new Error('Owner information was not returned by the server.');
        }

        Object.keys(form).forEach((key) => {
            if (owner[key] !== undefined && owner[key] !== null) {
                form[key] = owner[key];
            }
        });

        // Fallback: manager ID from the nested relation if the flat key is missing.
        if (
            (form.relationship_manager_id === '' || form.relationship_manager_id === null) &&
            owner.relationship_manager?.id
        ) {
            form.relationship_manager_id = owner.relationship_manager.id;
        }

        form.expected_price = owner.expected_price ?? '';
    } catch (error) {
        console.error('Owner loading error:', error);

        errors.value = {
            general: [
                error.response?.data?.message ||
                error.message ||
                'Unable to load owner information.',
            ],
        };
        toast.error(errors.value.general[0]);
    } finally {
        loading.value = false;
    }
}

onMounted(loadOwner);

/** Create or update owner. */
async function submit() {
    if (saving.value || loading.value) return;

    errors.value = {};
    saving.value = true;

    try {
        const payload = {
            ...form,
            expected_price:
                form.expected_price === '' || form.expected_price === null
                    ? null
                    : Number(form.expected_price),
            relationship_manager_id:
                form.relationship_manager_id === '' || form.relationship_manager_id === null
                    ? null
                    : Number(form.relationship_manager_id),
        };

        const response = editing.value
            ? await store.update(route.params.owner, payload)
            : await store.create(payload);

        const owner = getOwnerData(response);

        toast.success(
            editing.value ? 'Owner updated successfully!' : 'Owner created successfully!'
        );

        const ownerId = owner?.id ?? route.params.owner;

        if (!ownerId) {
            await router.push({ name: 'owners.index' });
            return;
        }

        await router.push({ name: 'owners.show', params: { owner: ownerId } });
    } catch (error) {
        console.error('Owner saving error:', error);

        const response = error.response?.data;

        errors.value = response?.errors ?? {
            general: [response?.message || 'Unable to save owner information.'],
        };

        toast.error(
            response?.errors
                ? 'Please correct the highlighted fields.'
                : errors.value.general[0]
        );
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <AppLayout>
        <main class="form-page">
            <div class="form-top">
                <div>
                    <p class="eyebrow">OWNER DIRECTORY</p>
                    <h1>{{ editing ? 'Edit owner' : 'Register owner' }}</h1>
                    <p>Owner contact and relationship information is restricted to authorized personnel.</p>
                </div>
                <button type="button" class="btn-light" @click="router.back()">Back</button>
            </div>

            <form class="form-card" novalidate @submit.prevent="submit">
                <p v-if="errors.general" class="alert">{{ errors.general[0] }}</p>

                <section>
                    <h2>Owner information</h2>
                    <p class="section-help">Register the owner details collected during property sourcing.</p>
                    <div class="grid">
                        <label>Owner name
                            <input v-model.trim="form.name" class="field" placeholder="Enter full owner name">
                            <small>{{ fieldError('name') }}</small>
                        </label>
                        <label>Owner number
                            <input v-model.trim="form.phone" class="field" placeholder="Enter phone number">
                            <small>{{ fieldError('phone') }}</small>
                        </label>
                        <label>Email address
                            <input v-model.trim="form.email" type="email" class="field" placeholder="name@example.com">
                            <small>{{ fieldError('email') }}</small>
                        </label>
                        <label>Owner profession
                            <input v-model.trim="form.profession" class="field" placeholder="Enter profession">
                            <small>{{ fieldError('profession') }}</small>
                        </label>
                        <label>Owner type
                            <select v-model="form.owner_type" class="field">
                                <option value="individual">Individual</option>
                                <option value="joint">Joint</option>
                                <option value="company">Company</option>
                            </select>
                            <small>{{ fieldError('owner_type') }}</small>
                        </label>
                        <label>Status
                            <select v-model="form.status" class="field">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="blocked">Blocked</option>
                            </select>
                            <small>{{ fieldError('status') }}</small>
                        </label>
                        <label class="full">Property address in detail
                            <textarea v-model="form.detailed_address" class="field textarea" placeholder="Enter owner's/property's detailed address"></textarea>
                            <small>{{ fieldError('detailed_address') }}</small>
                        </label>
                    </div>
                </section>

                <section>
                    <h2>Restricted official information</h2>
                    <p class="section-help">Visible and editable only when the user's permissions allow it.</p>
                    <div class="grid">
                        <label>Owner's expected price (BDT)
                            <input v-model="form.expected_price" type="number" min="0" class="field" placeholder="Enter expected price">
                            <small>{{ fieldError('expected_price') }}</small>
                        </label>
                        <label>Assigned relationship manager (User ID)
                            <input v-model="form.relationship_manager_id" type="number" min="1" class="field" placeholder="Enter manager user ID">
                            <small>{{ fieldError('relationship_manager_id') }}</small>
                        </label>
                        <label class="full">Legal status note
                            <textarea v-model="form.legal_status_note" class="field textarea" placeholder="Record restricted legal/ownership notes"></textarea>
                            <small>{{ fieldError('legal_status_note') }}</small>
                        </label>
                    </div>
                </section>

                <section>
                    <h2>Internal notes</h2>
                    <label class="block">Internal remarks
                        <textarea v-model="form.notes" class="field textarea" placeholder="Internal owner relationship notes"></textarea>
                        <small>{{ fieldError('notes') }}</small>
                    </label>
                </section>

                <div class="form-actions">
                    <button type="button" class="btn-light" @click="router.back()">Cancel</button>
                    <button class="btn-primary" type="submit" :disabled="saving || loading">
                        {{ saving ? 'Saving…' : editing ? 'Save changes' : 'Create owner' }}
                    </button>
                </div>
            </form>
        </main>
    </AppLayout>
</template>

<style scoped>
.form-page{min-height:100%;background:#f5f7f9;padding:28px clamp(16px,3vw,40px);font-family:ui-sans-serif,system-ui;color:#182230}.form-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;gap:15px}.eyebrow{font-size:11px;letter-spacing:.12em;font-weight:700;color:#788493}.form-top h1{font-size:26px;font-weight:750;margin:5px 0}.form-top p:last-child,.section-help{font-size:13px;color:#788493}.form-card{max-width:1000px;background:white;border:1px solid #e1e6ec;border-radius:13px;padding:24px}.form-card section{padding:4px 0 24px;margin-bottom:22px;border-bottom:1px solid #edf0f3}.form-card h2{font-size:16px;font-weight:700}.section-help{margin:5px 0 18px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:17px}.grid label,.block{display:flex;flex-direction:column;gap:7px;font-size:12px;font-weight:650;color:#475467}.full{grid-column:1/-1}.field{width:100%;min-height:42px;border:1px solid #d8dee6;border-radius:8px;padding:10px 12px;font-size:13px;font-weight:400;color:#344054;background:white;outline:none}.field:focus{border-color:#0f766e;box-shadow:0 0 0 3px #e3f4f1}.textarea{min-height:90px;resize:vertical}.grid small,.block small{color:#b42318;font-weight:400}.form-actions{display:flex;justify-content:flex-end;gap:9px}.btn-light,.btn-primary{border-radius:8px;padding:10px 16px;font-size:13px;font-weight:650;border:1px solid #d8dee6}.btn-light{background:white;color:#344054}.btn-primary{background:#0f766e;color:white;border-color:#0f766e}.btn-primary:disabled{opacity:.5}.alert{background:#fff1f0;color:#b42318;padding:11px;border-radius:8px;font-size:13px;margin-bottom:15px}@media(max-width:650px){.grid{grid-template-columns:1fr}.full{grid-column:auto}.form-card{padding:16px}}
</style>