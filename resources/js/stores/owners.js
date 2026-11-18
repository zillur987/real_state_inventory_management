
import { ref } from 'vue';
import { defineStore } from 'pinia';
import { ownerApi } from '@/api/ownerApi';

function normalizeOwnerResponse(response) {
    let data = response?.data ?? response;

    // Handle Laravel API Resource response nesting.
    if (data?.data && typeof data.data === 'object') {
        data = data.data;
    }

    return data;
}

export const useOwnersStore = defineStore('owners', () => {
    const items = ref([]);
    const current = ref(null);
    const pagination = ref(null);
    const loading = ref(false);
    const errors = ref({});

    async function fetchPage(params = {}) {
        loading.value = true;
        errors.value = {};

        try {
            const response = await ownerApi.list(params);

            items.value = response.data ?? [];
            pagination.value = response.meta ?? null;

            return response;
        } catch (error) {
            errors.value = error.response?.data?.errors ?? {
                general: ['Unable to load owners.'],
            };

            throw error;
        } finally {
            loading.value = false;
        }
    }

    async function fetchOne(id) {
        loading.value = true;
        errors.value = {};

        try {
            const response = await ownerApi.show(id);

            current.value = normalizeOwnerResponse(response);

            return current.value;
        } catch (error) {
            errors.value = error.response?.data?.errors ?? {
                general: ['Unable to load owner.'],
            };

            throw error;
        } finally {
            loading.value = false;
        }
    }

    async function create(payload) {
        errors.value = {};

        try {
            const response = await ownerApi.create(payload);
            const owner = normalizeOwnerResponse(response);

            current.value = owner;

            return owner;
        } catch (error) {
            errors.value = error.response?.data?.errors ?? {
                general: ['Unable to save owner.'],
            };

            throw error;
        }
    }

    async function update(id, payload) {
        errors.value = {};

        try {
            const response = await ownerApi.update(id, payload);
            const owner = normalizeOwnerResponse(response);

            current.value = owner;

            return owner;
        } catch (error) {
            errors.value = error.response?.data?.errors ?? {
                general: ['Unable to update owner.'],
            };

            throw error;
        }
    }

    return {
        items,
        current,
        pagination,
        loading,
        errors,
        fetchPage,
        fetchOne,
        create,
        update,
    };
});
