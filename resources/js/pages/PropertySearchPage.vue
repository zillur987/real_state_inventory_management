
<script setup>
import { computed, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';

const router = useRouter();

const loading = ref(false);
const searched = ref(false);
const copiedId = ref(null);

const form = reactive({
    category: 'apartment',
    location: 'Banasree',
    min_size: '1200',
    max_size: '1800',
    bedrooms: '3',
    facing: 'any',
    max_budget: '15000000',
    availability: 'available',

    // Advanced filters
    developer: '',
    floor: '',
    handover_year: '',
    building_height: '',
    parking: '',
    gas_connection: '',
    registration: '',
    verification: '',
    market_type: '',
    owner_direct: false,
    recently_added: false,
    min_price_sqft: '',
    max_price_sqft: '',
});

const properties = ref([
    {
        id: 1,
        property_code: 'EPR-APT-000125',
        name: '3 Bedroom Apartment – Banasree',
        category: 'apartment',
        location: 'Banasree',
        image_url: 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=1200',
        apartment_size_sqft: 1450,
        bedrooms: 3,
        bathrooms: 3,
        facing: 'south',
        available_floor: '5th Floor',
        asking_price: 12500000,
        price_per_sqft: 8621,
        parking_spaces: 1,
        inventory_status: 'available',
        is_verified: true,
        developer: 'ABC Developers',
        handover_year: 2027,
        building_ground_plus: 10,
        gas_connection: 'central',
        paper_status: 'registered',
        market_type: 'primary',
        is_owner_direct: true,
        created_at: '2026-11-15',
    },
    {
        id: 2,
        property_code: 'EPR-APT-000126',
        name: 'Modern Family Apartment – Rampura',
        category: 'apartment',
        location: 'Rampura',
        image_url: 'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?w=1200',
        apartment_size_sqft: 1600,
        bedrooms: 3,
        bathrooms: 3,
        facing: 'east',
        available_floor: '7th Floor',
        asking_price: 14000000,
        price_per_sqft: 8750,
        parking_spaces: 1,
        inventory_status: 'available',
        is_verified: true,
        developer: 'XYZ Properties',
        handover_year: 2027,
        building_ground_plus: 12,
        gas_connection: 'central',
        paper_status: 'registered',
        market_type: 'secondary',
        is_owner_direct: false,
        created_at: '2026-11-10',
    },
    {
        id: 3,
        property_code: 'EPR-APT-000127',
        name: 'Premium Apartment – Aftabnagar',
        category: 'apartment',
        location: 'Aftabnagar',
        image_url: 'https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?w=1200',
        apartment_size_sqft: 1750,
        bedrooms: 3,
        bathrooms: 3,
        facing: 'south',
        available_floor: '5th Floor',
        asking_price: 14800000,
        price_per_sqft: 8457,
        parking_spaces: 2,
        inventory_status: 'reserved',
        is_verified: false,
        developer: 'Modern Developers',
        handover_year: 2028,
        building_ground_plus: 15,
        gas_connection: 'no',
        paper_status: 'non_registered',
        market_type: 'primary',
        is_owner_direct: false,
        created_at: '2026-11-05',
    },
]);

const results = ref([]);

const formatPrice = (price) => {
    if (!price) return '0';
    return new Intl.NumberFormat('en-BD').format(price);
};

const formatCrore = (price) => {
    return (Number(price || 0) / 10000000).toFixed(2);
};

const filteredProperties = computed(() => results.value);

async function searchProperties() {
    loading.value = true;
    searched.value = true;

    try {
        // Demo filtering. Replace with your Laravel API request
        // when connecting the database.

        results.value = properties.value.filter((property) => {
            const matches = [
                !form.category || property.category === form.category,

                !form.location ||
                    property.location.toLowerCase().includes(form.location.toLowerCase()),

                !form.min_size ||
                    property.apartment_size_sqft >= Number(form.min_size),

                !form.max_size ||
                    property.apartment_size_sqft <= Number(form.max_size),

                !form.bedrooms ||
                    Number(property.bedrooms) === Number(form.bedrooms),

                !form.facing ||
                    form.facing === 'any' ||
                    property.facing === form.facing,

                !form.max_budget ||
                    property.asking_price <= Number(form.max_budget),

                form.availability === 'all' ||
                    property.inventory_status === form.availability,

                !form.developer ||
                    property.developer.toLowerCase().includes(form.developer.toLowerCase()),

                !form.floor ||
                    property.available_floor.toLowerCase().includes(form.floor.toLowerCase()),

                !form.handover_year ||
                    Number(property.handover_year) === Number(form.handover_year),

                !form.building_height ||
                    Number(property.building_ground_plus) >= Number(form.building_height),

                !form.parking ||
                    (form.parking === 'yes'
                        ? property.parking_spaces > 0
                        : property.parking_spaces === 0),

                !form.gas_connection ||
                    property.gas_connection === form.gas_connection,

                !form.registration ||
                    property.paper_status === form.registration,

                !form.verification ||
                    (form.verification === 'verified'
                        ? property.is_verified
                        : !property.is_verified),

                !form.market_type ||
                    property.market_type === form.market_type,

                !form.owner_direct || property.is_owner_direct,

                !form.recently_added ||
                    new Date(property.created_at) >=
                    new Date(Date.now() - 30 * 24 * 60 * 60 * 1000),

                !form.min_price_sqft ||
                    property.price_per_sqft >= Number(form.min_price_sqft),

                !form.max_price_sqft ||
                    property.price_per_sqft <= Number(form.max_price_sqft),
            ];

            return matches.every(Boolean);
        });
    } finally {
        loading.value = false;
    }
}

function resetFilters() {
    Object.assign(form, {
        category: 'apartment',
        location: '',
        min_size: '',
        max_size: '',
        bedrooms: '',
        facing: 'any',
        max_budget: '',
        availability: 'available',
        developer: '',
        floor: '',
        handover_year: '',
        building_height: '',
        parking: '',
        gas_connection: '',
        registration: '',
        verification: '',
        market_type: '',
        owner_direct: false,
        recently_added: false,
        min_price_sqft: '',
        max_price_sqft: '',
    });

    results.value = [];
    searched.value = false;
}

async function copyPropertyId(id) {
    try {
        await navigator.clipboard.writeText(id);
        copiedId.value = id;

        setTimeout(() => {
            copiedId.value = null;
        }, 2000);
    } catch {
        window.prompt('Copy property ID:', id);
    }
}

function viewDetails(property) {
    router.push({
        name: 'properties.show',
        params: { property: property.id },
    });
}
</script>

<template>
    <AppLayout>
        <div class="min-h-screen bg-[#fafafa] px-4 py-6 sm:px-6 lg:px-8">

            <!-- Page Header -->
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-gray-900">
                        Property Search
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Find and manage property inventory using smart filters.
                    </p>
                </div>

                <span class="rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                    Smart Inventory Search
                </span>
            </div>

            <!-- Search Form -->
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7">

                <div class="mb-6 flex items-center justify-between border-b border-gray-100 pb-4">
                    <h2 class="text-base font-semibold uppercase tracking-wide text-gray-900">
                        Property Search
                    </h2>

                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                        Interactive
                    </span>
                </div>

                <!-- Category -->
                <div class="mb-7">
                    <label class="mb-3 block text-sm font-semibold text-gray-600">
                        Property Category
                    </label>

                    <div class="flex flex-wrap gap-x-7 gap-y-3">
                        <label v-for="category in [
                            { label: 'Apartment', value: 'apartment' },
                            { label: 'Land/Plot', value: 'land' },
                            { label: 'Commercial', value: 'commercial' }
                        ]"
                            :key="category.value"
                            class="flex cursor-pointer items-center gap-3 text-sm text-gray-800">

                            <input
                                v-model="form.category"
                                type="radio"
                                :value="category.value"
                                class="h-5 w-5 accent-black"
                            />

                            {{ category.label }}
                        </label>
                    </div>
                </div>

                <div class="mb-6 border-t border-gray-100"></div>

                <!-- Main Filters -->
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                    <!-- Location -->
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-gray-600">
                            Location / Area
                        </label>

                        <input
                            v-model="form.location"
                            type="text"
                            placeholder="Enter location e.g. Banasree, Rampura, Aftabnagar"
                            class="search-input"
                        />
                    </div>

                    <!-- Size -->
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-gray-600">
                            Minimum Size (sq. ft.)
                        </label>

                        <input
                            v-model="form.min_size"
                            type="number"
                            min="0"
                            placeholder="Minimum size"
                            class="search-input"
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-gray-600">
                            Maximum Size (sq. ft.)
                        </label>

                        <input
                            v-model="form.max_size"
                            type="number"
                            min="0"
                            placeholder="Maximum size"
                            class="search-input"
                        />
                    </div>

                    <!-- Bedrooms -->
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-gray-600">
                            Bedrooms
                        </label>

                        <select v-model="form.bedrooms" class="search-input">
                            <option value="">Any Bedrooms</option>
                            <option value="1">1 Bedroom</option>
                            <option value="2">2 Bedrooms</option>
                            <option value="3">3 Bedrooms</option>
                            <option value="4">4 Bedrooms</option>
                            <option value="5">5+ Bedrooms</option>
                        </select>
                    </div>

                    <!-- Facing -->
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-gray-600">
                            Facing
                        </label>

                        <select v-model="form.facing" class="search-input">
                            <option value="any">Any</option>
                            <option value="north">North</option>
                            <option value="south">South</option>
                            <option value="east">East</option>
                            <option value="west">West</option>
                        </select>
                    </div>

                    <!-- Budget -->
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-gray-600">
                            Maximum Budget (BDT)
                        </label>

                        <input
                            v-model="form.max_budget"
                            type="number"
                            min="0"
                            placeholder="Enter maximum budget"
                            class="search-input"
                        />
                    </div>
                </div>

                <!-- Availability -->
                <div class="mt-6">
                    <label class="mb-3 block text-sm font-semibold text-gray-600">
                        Inventory Availability
                    </label>

                    <div class="flex flex-wrap gap-7">
                        <label v-for="status in [
                            { label: 'Available', value: 'available' },
                            { label: 'All', value: 'all' },
                            { label: 'Reserved', value: 'reserved' }
                        ]"
                            :key="status.value"
                            class="flex cursor-pointer items-center gap-3 text-sm text-gray-800">

                            <input
                                v-model="form.availability"
                                type="radio"
                                :value="status.value"
                                class="h-5 w-5 accent-black"
                            />

                            {{ status.label }}
                        </label>
                    </div>
                </div>

                <!-- Advanced Filters -->
                <details class="mt-7 rounded-lg border border-gray-200">
                    <summary class="cursor-pointer px-4 py-4 text-sm font-semibold text-gray-800">
                        + Advanced Property Filters
                    </summary>

                    <div class="grid grid-cols-1 gap-5 border-t border-gray-100 p-4 md:grid-cols-2">

                        <div>
                            <label class="form-label">Developer Name</label>
                            <input v-model="form.developer" placeholder="Developer name" class="search-input" />
                        </div>

                        <div>
                            <label class="form-label">Floor Preference</label>
                            <input v-model="form.floor" placeholder="e.g. 5th Floor" class="search-input" />
                        </div>

                        <div>
                            <label class="form-label">Handover Year</label>
                            <input v-model="form.handover_year" type="number" placeholder="e.g. 2027" class="search-input" />
                        </div>

                        <div>
                            <label class="form-label">Minimum Building Height (G+)</label>
                            <input v-model="form.building_height" type="number" placeholder="e.g. 10" class="search-input" />
                        </div>

                        <div>
                            <label class="form-label">Parking Availability</label>
                            <select v-model="form.parking" class="search-input">
                                <option value="">Any</option>
                                <option value="yes">Available</option>
                                <option value="no">Not Available</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Gas Connection</label>
                            <select v-model="form.gas_connection" class="search-input">
                                <option value="">Any</option>
                                <option value="central">Central</option>
                                <option value="cylinder">Cylinder</option>
                                <option value="no">Not Available</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Registration Status</label>
                            <select v-model="form.registration" class="search-input">
                                <option value="">Any</option>
                                <option value="registered">Registered</option>
                                <option value="non_registered">Non-registered</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Verification</label>
                            <select v-model="form.verification" class="search-input">
                                <option value="">Any</option>
                                <option value="verified">Verified</option>
                                <option value="non_verified">Non-verified</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Market Type</label>
                            <select v-model="form.market_type" class="search-input">
                                <option value="">Any</option>
                                <option value="primary">Primary Market</option>
                                <option value="secondary">Secondary Market</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Minimum Price / Sq. Ft.</label>
                            <input v-model="form.min_price_sqft" type="number" placeholder="Minimum price" class="search-input" />
                        </div>

                        <div>
                            <label class="form-label">Maximum Price / Sq. Ft.</label>
                            <input v-model="form.max_price_sqft" type="number" placeholder="Maximum price" class="search-input" />
                        </div>

                        <div class="flex flex-col gap-4 md:col-span-2">
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input v-model="form.owner_direct" type="checkbox" class="h-4 w-4 accent-black" />
                                Owner-direct inventory only
                            </label>

                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input v-model="form.recently_added" type="checkbox" class="h-4 w-4 accent-black" />
                                Recently added properties (last 30 days)
                            </label>
                        </div>
                    </div>
                </details>

                <!-- Actions -->
                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <button
                        @click="searchProperties"
                        :disabled="loading"
                        class="flex flex-1 items-center justify-center gap-2 rounded-full bg-[#101010] px-5 py-3 text-sm font-semibold text-white transition hover:bg-gray-800 disabled:opacity-50">

                        <svg v-if="!loading" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="7" stroke-width="2"/>
                            <path d="m16 16 4 4" stroke-width="2" stroke-linecap="round"/>
                        </svg>

                        {{ loading ? 'Searching...' : 'Generate Search Criteria' }}
                    </button>

                    <button
                        @click="resetFilters"
                        type="button"
                        class="rounded-full border border-gray-300 px-6 py-3 text-sm font-medium text-gray-600 hover:bg-gray-50">
                        Reset Filters
                    </button>
                </div>

                <p class="mt-4 text-sm text-gray-500">
                    Search across property inventory using multiple filters simultaneously.
                </p>
            </section>

            <!-- Search Results -->
            <section v-if="searched" class="mt-8">

                <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">
                            Property Search Results
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ filteredProperties.length }} matching properties found
                        </p>
                    </div>

                    <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                        Search completed
                    </span>
                </div>

                <div v-if="filteredProperties.length" class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                    <article
                        v-for="property in filteredProperties"
                        :key="property.id"
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white p-3 shadow-sm transition hover:shadow-md">

                        <!-- Card Heading -->
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-gray-800">
                                {{ property.name }}
                            </h3>

                            <span
                                class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                :class="property.inventory_status === 'available'
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-amber-100 text-amber-700'">
                                {{ property.inventory_status }}
                            </span>
                        </div>

                        <!-- Property Image -->
                        <div class="relative h-56 overflow-hidden rounded-xl bg-gray-100 sm:h-64">
                            <img
                                :src="property.image_url"
                                :alt="property.name"
                                class="h-full w-full object-cover transition duration-500 hover:scale-105"
                            />

                            <span class="absolute bottom-3 left-3 rounded-full bg-black/70 px-3 py-1 text-xs text-white">
                                {{ property.location }}
                            </span>
                        </div>

                        <!-- Property Name -->
                        <div class="mt-3">
                            <h3 class="text-base font-semibold text-gray-900">
                                {{ property.name }}
                            </h3>

                            <p class="mt-1 text-xs text-gray-500">
                                Property ID: {{ property.property_code }}
                            </p>
                        </div>

                        <!-- Specifications -->
                        <div class="mt-4 grid grid-cols-2 gap-x-5 gap-y-3">

                            <div>
                                <p class="text-xs text-gray-500">Apartment Size</p>
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ formatPrice(property.apartment_size_sqft) }} sq. ft.
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-500">Floor</p>
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ property.available_floor }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-500">Bedrooms</p>
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ property.bedrooms }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-500">Bathrooms</p>
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ property.bathrooms }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-500">Facing</p>
                                <p class="text-sm font-semibold capitalize text-gray-900">
                                    {{ property.facing }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-500">Parking</p>
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ property.parking_spaces }}
                                </p>
                            </div>
                        </div>

                        <!-- Price -->
                        <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3">
                            <div>
                                <p class="text-xs text-gray-500">Asking Price</p>
                                <p class="text-lg font-bold text-emerald-700">
                                    ৳ {{ formatCrore(property.asking_price) }} Cr
                                </p>
                            </div>

                            <span
                                class="rounded-full px-3 py-1 text-xs font-semibold"
                                :class="property.is_verified
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-gray-100 text-gray-600'">
                                {{ property.is_verified ? 'Verified' : 'Non-verified' }}
                            </span>
                        </div>

                        <!-- Buttons -->
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <button
                                @click="viewDetails(property)"
                                class="rounded-full bg-[#101010] px-3 py-2.5 text-xs font-semibold text-white transition hover:bg-gray-800">
                                View Details
                            </button>

                            <button
                                @click="copyPropertyId(property.property_code)"
                                class="rounded-full border border-gray-300 px-3 py-2.5 text-xs font-semibold text-gray-600 transition hover:bg-gray-50">
                                {{ copiedId === property.property_code ? 'Copied!' : 'Copy Property ID' }}
                            </button>
                        </div>

                        <p class="mt-2 text-xs text-gray-400">
                            Price per sq. ft.: ৳{{ formatPrice(property.price_per_sqft) }}
                        </p>
                    </article>
                </div>

                <!-- Empty State -->
                <div v-else class="rounded-xl border border-gray-200 bg-white px-5 py-16 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100">
                        <svg class="h-7 w-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="7" stroke-width="2"/>
                            <path d="m16 16 4 4" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>

                    <h3 class="mt-4 text-base font-semibold text-gray-900">
                        No properties found
                    </h3>

                    <p class="mt-2 text-sm text-gray-500">
                        Try changing your filters to find matching properties.
                    </p>

                    <button @click="resetFilters" class="mt-5 rounded-full bg-black px-6 py-2.5 text-sm font-semibold text-white">
                        Reset Search
                    </button>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<style scoped>
.search-input {
    width: 100%;
    min-height: 45px;
    border: 1px solid #dedede;
    border-radius: 999px;
    background-color: #fff;
    padding: 10px 18px;
    font-size: 14px;
    color: #252525;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.search-input:focus {
    border-color: #222;
    box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05);
}

.search-input::placeholder {
    color: #a0a0a0;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #626262;
}

select.search-input {
    cursor: pointer;
}
</style>
