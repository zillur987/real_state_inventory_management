import { ref } from 'vue';
import { defineStore } from 'pinia';
import { clientMatchingApi } from '@/api/clientMatchingApi';

export const useClientMatchingStore = defineStore('clientMatching', () => {
    const clients = ref([]);
    const pagination = ref(null);
    const selected = ref(null);
    const matches = ref([]);
    const activities = ref([]);
    const loading = ref(false);
    const saving = ref(false);
    const error = ref('');

    async function fetchClients(params = {}) {
        loading.value = true;
        error.value = '';
        try {
            const response = await clientMatchingApi.list(params);
            clients.value = response.data ?? [];
            pagination.value = response.meta ?? null;
            return response;
        } catch (exception) {
            error.value = exception.response?.data?.message ?? 'Unable to load clients.';
            throw exception;
        } finally { loading.value = false; }
    }

    async function createClient(payload) {
        saving.value = true;
        try {
            const result = await clientMatchingApi.create(payload);
            await fetchClients();
            return result;
        } finally { saving.value = false; }
    }

    async function fetchClient(id) {
        loading.value = true;
        error.value = '';
        try {
            const result = await clientMatchingApi.show(id);
            selected.value = result;
            activities.value = result.activities ?? [];
            return result;
        } catch (exception) {
            error.value = exception.response?.data?.message ?? 'Unable to load client details.';
            throw exception;
        } finally { loading.value = false; }
    }

    async function saveRequirement(id, payload) {
        saving.value = true;
        try {
            await clientMatchingApi.saveRequirement(id, payload);
            return await fetchClient(id);
        } finally { saving.value = false; }
    }

    async function generateMatches(id) {
        loading.value = true;
        error.value = '';
        try {
            const response = await clientMatchingApi.matches(id);
            matches.value = response.data ?? [];
            return matches.value;
        } catch (exception) {
            error.value = exception.response?.data?.message ?? 'Unable to generate property matches.';
            throw exception;
        } finally { loading.value = false; }
    }

    async function shortlist(clientId, propertyId) {
        await clientMatchingApi.shortlist(clientId, propertyId);
        const item = matches.value.find((match) => match.property.id === propertyId);
        if (item) { item.shortlisted = true; item.shortlist_status = 'shortlisted'; }
        await fetchClient(clientId);
    }

    async function addActivity(id, payload) {
        saving.value = true;
        try {
            const activity = await clientMatchingApi.addActivity(id, payload);
            activities.value = [activity, ...activities.value];
            return activity;
        } finally { saving.value = false; }
    }

    return { clients, pagination, selected, matches, activities, loading, saving, error,
        fetchClients, createClient, fetchClient, saveRequirement, generateMatches, shortlist, addActivity };
});
