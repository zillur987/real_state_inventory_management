import { ref } from 'vue';
import { defineStore } from 'pinia';
import { propertyWorkflowApi } from '@/api/propertyWorkflowApi';

export const usePropertyWorkflowStore = defineStore('propertyWorkflow', () => {
    const items = ref([]);
    const pagination = ref(null);
    const selected = ref(null);
    const loading = ref(false);
    const errors = ref({});

    async function fetchPage(params = {}) {
        loading.value = true;
        errors.value = {};
        try {
            const response = await propertyWorkflowApi.list(params);
            items.value = response.data ?? [];
            pagination.value = response.meta ?? null;
            return response;
        } catch (error) {
            errors.value = error.response?.data?.errors ?? { general: ['Unable to load workflow records.'] };
            throw error;
        } finally { loading.value = false; }
    }

    async function fetchOne(id) {
        errors.value = {};
        try {
            selected.value = await propertyWorkflowApi.show(id);
            return selected.value;
        } catch (error) {
            errors.value = error.response?.data?.errors ?? { general: ['Unable to load workflow details.'] };
            throw error;
        }
    }

    async function submit(id, payload) { return propertyWorkflowApi.submit(id, payload); }
    async function review(id, payload) { return propertyWorkflowApi.review(id, payload); }
    async function decide(id, payload) { return propertyWorkflowApi.decide(id, payload); }

    return { items, pagination, selected, loading, errors, fetchPage, fetchOne, submit, review, decide };
});
