import { http } from './http';

const BASE = '/api/v1/properties';

export const propertyApi = {
    async list(params = {}) {
        const { data } = await http.get(BASE, { params });
        return data;
    },

    async create(payload) {
        const { data } = await http.post(BASE, payload);
        return data.data;
    },

    async show(id) {
        const { data } = await http.get(`${BASE}/${id}`);
        return data.data;
    },

    async update(id, payload) {
        const { data } = await http.put(`${BASE}/${id}`, payload);
        return data.data;
    },

    async uploadImages(id, files) {
        const form = new FormData();
        files.forEach((file) => form.append('images[]', file));
        const { data } = await http.post(`${BASE}/${id}/images`, form);
        return data.data;
    },

    async deleteImage(propertyId, imageId) {
        const { data } = await http.delete(`${BASE}/${propertyId}/images/${imageId}`);
        return data;
    },
};
