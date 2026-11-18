<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { propertyApi } from '@/api/propertyApi';

const route = useRoute();
const router = useRouter();
const property = ref(null);
const loading = ref(true);
const error = ref('');

onMounted(async () => {
    try {
        property.value = await propertyApi.show(route.params.property);
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Unable to load property details.';
    } finally {
        loading.value = false;
    }
});

function money(value) {
    if (value === null || value === undefined || value === '') return '—';
    return new Intl.NumberFormat('en-BD', { maximumFractionDigits: 0 }).format(Number(value));
}
</script>

<template>
    <AppLayout>
        <div class="mb-5 flex items-center justify-between gap-3">
            <div>
                <p class="text-sm text-slate-500">Inventory / Property details</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-900">Property profile</h1>
            </div>
            <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium" @click="router.back()">Back to search</button>
        </div>

        <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-8 text-sm text-slate-500">Loading property…</div>
        <div v-else-if="error" class="rounded-xl border border-red-100 bg-red-50 p-4 text-sm text-red-700">{{ error }}</div>
        <div v-else-if="property" class="space-y-5">
            <section class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <span class="rounded-md bg-brand-50 px-2 py-1 text-xs font-bold text-brand-900">{{ property.property_code }}</span>
                        <h2 class="mt-3 text-xl font-semibold text-slate-900">{{ property.name }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ property.area }}<span v-if="property.district">, {{ property.district }}</span></p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-brand-900">৳{{ money(property.asking_price) }}</p>
                        <p class="text-xs capitalize text-slate-500">{{ property.inventory_status }}</p>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="font-semibold text-slate-900">Property specifications</h3>
                <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="item in [
                        ['Category', property.category], ['Market type', property.market_type],
                        ['Developer', property.developer], ['Property status', property.property_status],
                        ['Apartment size', property.apartment_size_sqft ? property.apartment_size_sqft + ' sq. ft.' : '—'],
                        ['Land size', property.land_size ? property.land_size + ' ' + (property.land_size_unit ?? '') : '—'],
                        ['Building height', property.building_ground_plus !== null ? 'G+' + property.building_ground_plus : '—'],
                        ['Units per floor', property.units_per_floor], ['Available floor', property.available_floor],
                        ['Facing', property.facing], ['Bedrooms', property.bedrooms], ['Bathrooms', property.bathrooms],
                        ['Balconies', property.balconies], ['Parking spaces', property.parking_spaces],
                        ['Gas connection', property.gas_connection], ['Handover year', property.handover_year],
                        ['Paper status', property.paper_status], ['Legal verification', property.legal_verification],
                        ['Mortgage status', property.mortgage_status], ['Owner direct', property.is_owner_direct ? 'Yes' : 'No']
                    ]" :key="item[0]" class="border-b border-slate-100 pb-3">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ item[0] }}</dt>
                        <dd class="mt-1 text-sm font-medium capitalize text-slate-800">{{ item[1] ?? '—' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="font-semibold text-slate-900">Description</h3>
                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ property.description || 'No description available.' }}</p>
            </section>
            <p class="rounded-lg bg-amber-50 p-3 text-xs text-amber-800">Owner contact details, confidential legal notes and final negotiated prices are restricted and are not included in this profile.</p>
        </div>
    </AppLayout>
</template>
