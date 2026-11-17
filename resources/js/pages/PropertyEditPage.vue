<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePropertiesStore } from '@/stores/properties';

const route = useRoute();
const router = useRouter();
const properties = usePropertiesStore();
const loading = ref(true);
const saving = ref(false);
const pageError = ref('');
const form = reactive({
    name: '', category: 'apartment', market_type: 'primary', division: '', district: '', area: '', detailed_address: '', developer: '',
    property_status: 'new', apartment_size_sqft: '', land_size: '', land_size_unit: 'katha', building_ground_plus: '', units_per_floor: '',
    available_floor: '', facing: '', bedrooms: '', bathrooms: '', balconies: '', drawing_room: false, dining_space: false,
    parking_spaces: 0, gas_connection: '', has_lift: false, has_generator: false, handover_year: '', handover_status: '',
    paper_status: '', legal_verification: 'pending', mortgage_status: 'unknown', ownership_type: '', inventory_status: 'available',
    asking_price: '', is_negotiable: false, description: '',
});

onMounted(async () => {
    try {
        const property = await properties.fetchOne(route.params.id);
        Object.keys(form).forEach((key) => {
            if (property[key] !== null && property[key] !== undefined) form[key] = property[key];
        });
    } catch (error) {
        pageError.value = error.response?.data?.message ?? 'Unable to load this property. Please try again.';
    } finally {
        loading.value = false;
    }
});

async function submit() {
    saving.value = true;
    pageError.value = '';
    try {
        const updated = await properties.update(route.params.id, { ...form });
        await router.push({ name: 'properties.show', params: { id: updated.id ?? route.params.id } });
    } catch (error) {
        pageError.value = error.response?.data?.message ?? 'Unable to update the property. Please review the highlighted fields.';
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <AppLayout>
        <div class="mb-5">
            <button class="text-sm text-brand-700 hover:underline" type="button" @click="router.back()">
                ← Back to property details
            </button>
            <h1 class="mt-2 text-xl font-semibold text-slate-900">Edit Property</h1>
            <p class="mt-1 text-sm text-slate-500">Update the property information below. Your changes will be saved to the existing inventory record.</p>
        </div>

        <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-500">Loading property information…</div>
        <form v-else class="space-y-5" @submit.prevent="submit">
            <p v-if="pageError" class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ pageError }}</p>
            <section class="rounded-xl border border-slate-200 bg-white p-5">
                <h2 class="mb-4 font-semibold text-slate-900">A. Basic property information</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="text-sm">Property name *
                        <input v-model="form.name" required maxlength="180" class="form-input">
                        <span v-if="properties.errors.name" class="field-error">{{ properties.errors.name[0] }}</span>
                    </label>
                    <label class="text-sm">Category *
                        <select v-model="form.category" required class="form-input">
                            <option value="apartment">Apartment</option><option value="flat">Flat</option>
                            <option value="duplex">Duplex</option><option value="penthouse">Penthouse</option>
                            <option value="land">Land</option><option value="plot">Plot</option><option value="commercial">Commercial</option>
                        </select>
                    </label>
                    <label class="text-sm">Market type *
                        <select v-model="form.market_type" class="form-input"><option value="primary">Primary</option><option value="secondary">Secondary</option></select>
                    </label>
                    <label class="text-sm">Developer
                        <input v-model="form.developer" class="form-input">
                    </label>
                    <label class="text-sm">Division
                        <input v-model="form.division" class="form-input">
                    </label>
                    <label class="text-sm">District
                        <input v-model="form.district" class="form-input">
                    </label>
                    <label class="text-sm">Area *
                        <input v-model="form.area" required class="form-input">
                        <span v-if="properties.errors.area" class="field-error">{{ properties.errors.area[0] }}</span>
                    </label>
                    <label class="text-sm sm:col-span-2">Detailed address
                        <textarea v-model="form.detailed_address" rows="2" class="form-input"></textarea>
                    </label>
                    <label class="text-sm">Property condition *
                        <select v-model="form.property_status" class="form-input">
                            <option value="new">New</option><option value="used">Used</option>
                            <option value="semi_ready">Semi-ready</option><option value="under_construction">Under construction</option>
                        </select>
                    </label>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5">
                <h2 class="mb-4 font-semibold text-slate-900">B. Physical specifications</h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="text-sm">Apartment size (sq. ft.)<input v-model="form.apartment_size_sqft" type="number" min="0" step="0.01" class="form-input"></label>
                    <label class="text-sm">Land size<input v-model="form.land_size" type="number" min="0" step="0.01" class="form-input"></label>
                    <label class="text-sm">Land size unit<select v-model="form.land_size_unit" class="form-input"><option value="katha">Katha</option><option value="decimal">Decimal</option><option value="bigha">Bigha</option></select></label>
                    <label class="text-sm">Building height (G+)<input v-model="form.building_ground_plus" type="number" min="0" class="form-input"></label>
                    <label class="text-sm">Units per floor<input v-model="form.units_per_floor" type="number" min="0" class="form-input"></label>
                    <label class="text-sm">Available floor<input v-model="form.available_floor" class="form-input"></label>
                    <label class="text-sm">Facing<select v-model="form.facing" class="form-input"><option value="">Select</option><option value="north">North</option><option value="south">South</option><option value="east">East</option><option value="west">West</option><option value="corner">Corner</option></select></label>
                    <label class="text-sm">Bedrooms<input v-model="form.bedrooms" type="number" min="0" class="form-input"></label>
                    <label class="text-sm">Bathrooms<input v-model="form.bathrooms" type="number" min="0" class="form-input"></label>
                    <label class="text-sm">Balconies<input v-model="form.balconies" type="number" min="0" class="form-input"></label>
                    <label class="text-sm">Parking spaces<input v-model="form.parking_spaces" type="number" min="0" class="form-input"></label>
                    <label class="text-sm">Gas connection<select v-model="form.gas_connection" class="form-input"><option value="">Select</option><option value="titas">Titas</option><option value="lpg">LPG</option><option value="none">No gas</option></select></label>
                </div>
                <div class="mt-4 flex flex-wrap gap-5 text-sm">
                    <label><input v-model="form.drawing_room" type="checkbox"> Drawing room</label>
                    <label><input v-model="form.dining_space" type="checkbox"> Dining space</label>
                    <label><input v-model="form.has_lift" type="checkbox"> Lift</label>
                    <label><input v-model="form.has_generator" type="checkbox"> Generator</label>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5">
                <h2 class="mb-4 font-semibold text-slate-900">C–D. Availability, legal and pricing</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="text-sm">Handover year<input v-model="form.handover_year" type="number" min="1950" max="2200" class="form-input"></label>
                    <label class="text-sm">Handover status<select v-model="form.handover_status" class="form-input"><option value="">Select</option><option value="handed_over">Handed over</option><option value="upcoming">Upcoming</option></select></label>
                    <label class="text-sm">Paper status<select v-model="form.paper_status" class="form-input"><option value="">Select</option><option value="registered">Registered</option><option value="non_registered">Non-registered</option><option value="on_process">On process</option></select></label>
                    <label class="text-sm">Legal verification<select v-model="form.legal_verification" class="form-input"><option value="pending">Pending</option><option value="verified">Verified</option><option value="non_verified">Non-verified</option></select></label>
                    <label class="text-sm">Mortgage status<select v-model="form.mortgage_status" class="form-input"><option value="unknown">Unknown</option><option value="mortgaged">Mortgaged</option><option value="non_mortgaged">Non-mortgaged</option></select></label>
                    <label class="text-sm">Ownership type<select v-model="form.ownership_type" class="form-input"><option value="">Select</option><option value="individual">Individual</option><option value="joint">Joint</option><option value="company">Company</option></select></label>
                    <label class="text-sm">Inventory status *
                        <select v-model="form.inventory_status" class="form-input"><option value="available">Available</option><option value="reserved">Reserved</option><option value="sold">Sold</option><option value="withdrawn">Withdrawn</option></select>
                    </label>
                    <label class="text-sm">Asking price (BDT)<input v-model="form.asking_price" type="number" min="0" step="0.01" class="form-input"></label>
                </div>
                <label class="mt-4 inline-flex items-center gap-2 text-sm"><input v-model="form.is_negotiable" type="checkbox"> Price is negotiable</label>
                <label class="mt-4 block text-sm">Property description<textarea v-model="form.description" rows="3" class="form-input"></textarea></label>
            </section>

            <p v-if="properties.errors.general" class="rounded-md bg-red-50 p-3 text-sm text-red-700">{{ properties.errors.general[0] }}</p>
            <div v-if="Object.keys(properties.errors).length" class="text-sm text-red-700">
                <p v-for="(messages, field) in properties.errors" :key="field">{{ field }}: {{ messages[0] }}</p>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" class="rounded-md border border-slate-300 px-4 py-2 text-sm" @click="router.back()">Cancel</button>
                <button type="submit" :disabled="saving" class="rounded-md bg-brand-900 px-5 py-2 text-sm font-medium text-white disabled:opacity-60">{{ saving ? 'Saving changes…' : 'Save Changes' }}</button>
            </div>
        </form>
    </AppLayout>
</template>

<style scoped>
@reference "../../css/app.css";

.form-input { @apply mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-brand-700 focus:outline-none; }
.field-error { @apply mt-1 block text-xs text-red-600; }
</style>
