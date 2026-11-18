import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import { propertySearchApi } from '@/api/propertySearchApi';

const emptyFilters = () => ({
    q: '',
    developer: '',
    area: '',
    market_type: '',
    floor: '',
    handover_year_from: '',
    handover_year_to: '',
    building_height_min: '',
    building_height_max: '',
    parking_min: '',
    gas_connection: '',
    paper_status: '',
    legal_verification: '',
    asking_price_min: '',
    asking_price_max: '',
    price_per_sqft_min: '',
    price_per_sqft_max: '',
    owner_direct: '',
    recent_days: '',
    sort: 'newest',
});

export const usePropertySearchStore = defineStore('propertySearch', () => {
    const filters = ref(emptyFilters());
    const items = ref([]);
    const pagination = ref(null);
    const savedSearches = ref([]);
    const loading = ref(false);
    const saving = ref(false);
    const errors = ref({});
    const comparedIds = ref([]);
    const shortlistIds = ref(JSON.parse(localStorage.getItem('pims-property-shortlist') || '[]'));

    const activeFilterCount = computed(() =>
        Object.entries(filters.value).filter(([key, value]) =>
            key !== 'sort' && value !== '' && value !== null && value !== undefined
        ).length
    );

    async function search(page = 1) {
        loading.value = true;
        errors.value = {};

        const params = Object.fromEntries(
            Object.entries({ ...filters.value, page, per_page: 12 })
                .filter(([, value]) => value !== '' && value !== null && value !== undefined)
        );

        try {
            const response = await propertySearchApi.search(params);
            items.value = response.data ?? [];
            pagination.value = response.meta ?? null;
        } catch (error) {
            errors.value = error.response?.data?.errors ?? {
                general: ['Unable to search properties. Please try again.'],
            };
            throw error;
        } finally {
            loading.value = false;
        }
    }

    function resetFilters() {
        filters.value = emptyFilters();
    }

    async function fetchSavedSearches() {
        savedSearches.value = await propertySearchApi.savedSearches();
    }

    async function saveCurrentSearch(name) {
        saving.value = true;
        try {
            const saved = await propertySearchApi.saveSearch({
                name,
                filters: Object.fromEntries(
                    Object.entries(filters.value).filter(([, value]) => value !== '')
                ),
            });
            savedSearches.value.unshift(saved);
            return saved;
        } finally {
            saving.value = false;
        }
    }

    async function deleteSavedSearch(id) {
        await propertySearchApi.deleteSavedSearch(id);
        savedSearches.value = savedSearches.value.filter(item => item.id !== id);
    }

    function loadSavedSearch(saved) {
        filters.value = { ...emptyFilters(), ...(saved.filters ?? {}) };
    }

    function toggleCompare(id) {
        comparedIds.value = comparedIds.value.includes(id)
            ? comparedIds.value.filter(item => item !== id)
            : [...comparedIds.value, id].slice(-4);
    }

    function toggleShortlist(id) {
        shortlistIds.value = shortlistIds.value.includes(id)
            ? shortlistIds.value.filter(item => item !== id)
            : [...shortlistIds.value, id];

        localStorage.setItem('pims-property-shortlist', JSON.stringify(shortlistIds.value));
    }

    return {
        filters, items, pagination, savedSearches, loading, saving, errors,
        comparedIds, shortlistIds, activeFilterCount, search, resetFilters,
        fetchSavedSearches, saveCurrentSearch, deleteSavedSearch, loadSavedSearch,
        toggleCompare, toggleShortlist,
    };
});
