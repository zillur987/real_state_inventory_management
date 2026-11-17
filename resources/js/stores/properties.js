import { ref } from 'vue';
import { defineStore } from 'pinia';
import { propertyApi } from '@/api/propertyApi';

export const usePropertiesStore = defineStore('properties', () => {
    const items = ref([]);
    const pagination = ref(null);
    const loading = ref(false);
    const errors = ref({});

    async function fetchPage(page = 1) {
        loading.value = true;
        errors.value = {};
        try {
            const response = await propertyApi.list({ page });
            items.value = response.data ?? [];
            pagination.value = response.meta ?? null;
        } catch (error) {
            errors.value = error.response?.data?.errors ?? { general: ['Unable to load properties. Please try again.'] };
            throw error;
        } finally {
            loading.value = false;
        }
    }

    async function create(payload) {
        errors.value = {};
        try {
            return await propertyApi.create(payload);
        } catch (error) {
            errors.value = error.response?.data?.errors ?? { general: ['Unable to save the property. Please try again.'] };
            throw error;
        }
    }

    async function fetchOne(id) {
        errors.value = {};
        try {
            return await propertyApi.show(id);
        } catch (error) {
            errors.value = error.response?.data?.errors ?? { general: ['Unable to load this property. Please try again.'] };
            throw error;
        }
    }

    async function update(id, payload) {
        errors.value = {};
        try {
            return await propertyApi.update(id, payload);
        } catch (error) {
            errors.value = error.response?.data?.errors ?? { general: ['Unable to update the property. Please try again.'] };
            throw error;
        }
    }

    async function uploadImages(id, files) {
        errors.value = {};
        try {
            return await propertyApi.uploadImages(id, files);
        } catch (error) {
            errors.value = error.response?.data?.errors ?? { general: ['Unable to upload property images. Please try again.'] };
            throw error;
        }
    }

    async function deleteImage(propertyId, imageId) {
        return propertyApi.deleteImage(propertyId, imageId);
    }

    return { items, pagination, loading, errors, fetchPage, create, fetchOne, update, uploadImages, deleteImage };
});
