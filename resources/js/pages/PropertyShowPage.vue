<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePropertiesStore } from '@/stores/properties';

const route = useRoute();
const router = useRouter();
const store = usePropertiesStore();
const property = ref(null);
const loading = ref(true);
const uploading = ref(false);
const pageError = ref('');
const selectedFiles = ref([]);
const imageInput = ref(null);
const canUpload = computed(() => selectedFiles.value.length > 0 && !uploading.value);

async function load() {
    loading.value = true;
    pageError.value = '';
    try {
        property.value = await store.fetchOne(route.params.id);
    } catch (error) {
        pageError.value = error.response?.data?.message ?? 'Unable to load property details.';
    } finally {
        loading.value = false;
    }
}

function onFilesChanged(event) {
    selectedFiles.value = Array.from(event.target.files ?? []);
}

async function upload() {
    if (!canUpload.value) return;
    uploading.value = true;
    try {
        await store.uploadImages(property.value.id, selectedFiles.value);
        selectedFiles.value = [];
        if (imageInput.value) imageInput.value.value = '';
        await load();
    } catch (error) {
        pageError.value = error.response?.data?.message ?? 'Image upload failed. Check file type and size.';
    } finally {
        uploading.value = false;
    }
}

async function removeImage(imageId) {
    if (!window.confirm('Delete this property image?')) return;
    try {
        await store.deleteImage(property.value.id, imageId);
        property.value.images = property.value.images.filter((image) => image.id !== imageId);
    } catch (error) {
        pageError.value = error.response?.data?.message ?? 'Unable to delete image.';
    }
}

const details = computed(() => {
    if (!property.value) return [];
    return [
        ['Category', property.value.category], ['Market', property.value.market_type],
        ['Developer', property.value.developer], ['Area', property.value.area],
        ['District', property.value.district], ['Division', property.value.division],
        ['Address', property.value.detailed_address], ['Status', property.value.property_status],
        ['Inventory', property.value.inventory_status], ['Apartment size', property.value.apartment_size_sqft ? `${property.value.apartment_size_sqft} sq. ft.` : null],
        ['Land size', property.value.land_size ? `${property.value.land_size} ${property.value.land_size_unit ?? ''}` : null],
        ['Floor', property.value.available_floor], ['Facing', property.value.facing],
        ['Bedrooms', property.value.bedrooms], ['Bathrooms', property.value.bathrooms],
        ['Balconies', property.value.balconies], ['Parking spaces', property.value.parking_spaces],
        ['Gas', property.value.gas_connection], ['Handover year', property.value.handover_year],
        ['Paper status', property.value.paper_status], ['Legal verification', property.value.legal_verification],
        ['Asking price', property.value.asking_price], ['Negotiable', property.value.is_negotiable ? 'Yes' : 'No'],
    ].filter(([, value]) => value !== null && value !== undefined && value !== '');
});

onMounted(load);
</script>

<template>
    <AppLayout>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <button class="text-sm text-brand-800 hover:underline" @click="router.push({ name: 'properties.index' })">← Property Inventory</button>
                <h1 class="mt-2 text-xl font-semibold text-slate-900">Property Details</h1>
                <p class="mt-1 text-sm text-slate-500">Full inventory profile and property photos</p>
            </div>
            <button v-if="property" class="rounded-md bg-brand-900 px-4 py-2 text-sm font-medium text-white" @click="router.push({ name: 'properties.edit', params: { id: property.id } })">Edit Property</button>
        </div>

        <div v-if="loading" class="mt-6 rounded-xl border bg-white p-6 text-sm text-slate-500">Loading property…</div>
        <div v-else-if="pageError && !property" class="mt-6 rounded-xl bg-red-50 p-4 text-sm text-red-700">{{ pageError }}</div>
        <template v-else-if="property">
            <p v-if="pageError" class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{{ pageError }}</p>
            <section class="mt-5 rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-brand-800">{{ property.property_code }}</p><h2 class="mt-1 text-lg font-semibold text-slate-900">{{ property.name }}</h2><p class="mt-1 text-sm text-slate-500">{{ property.area }}<span v-if="property.district">, {{ property.district }}</span></p></div>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium capitalize text-emerald-700">{{ property.inventory_status }}</span>
                </div>
                <p v-if="property.description" class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-600">{{ property.description }}</p>
                <dl class="mt-5 grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="item in details" :key="item[0]"><dt class="text-xs text-slate-500">{{ item[0] }}</dt><dd class="mt-1 text-sm font-medium capitalize text-slate-900">{{ item[1] }}</dd></div>
                </dl>
            </section>

            <section class="mt-5 rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap items-center justify-between gap-2"><div><h2 class="font-semibold text-slate-900">Property Photos</h2><p class="mt-1 text-xs text-slate-500">JPG, PNG or WEBP · maximum 5 MB each · up to 10 images per upload</p></div></div>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <input ref="imageInput" type="file" accept="image/jpeg,image/png,image/webp" multiple class="block max-w-full text-sm text-slate-600" @change="onFilesChanged" />
                    <button :disabled="!canUpload" class="rounded-md bg-brand-900 px-4 py-2 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-50" @click="upload">{{ uploading ? 'Uploading…' : 'Upload photos' }}</button>
                </div>
                <p v-if="selectedFiles.length" class="mt-2 text-xs text-slate-500">{{ selectedFiles.length }} file(s) selected</p>
                <div v-if="property.images?.length" class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    <div v-for="image in property.images" :key="image.id" class="overflow-hidden rounded-lg border border-slate-200">
                        <a :href="image.url" target="_blank" rel="noopener"><img :src="image.url" :alt="image.original_name || property.name" class="h-36 w-full object-cover" /></a>
                        <div class="flex items-center justify-between gap-2 p-2"><span class="truncate text-xs text-slate-600">{{ image.original_name }}</span><button class="shrink-0 text-xs text-red-600 hover:underline" @click="removeImage(image.id)">Delete</button></div>
                    </div>
                </div>
                <p v-else class="mt-5 rounded-lg bg-slate-50 p-5 text-center text-sm text-slate-500">No photos uploaded yet.</p>
            </section>
        </template>
    </AppLayout>
</template>
