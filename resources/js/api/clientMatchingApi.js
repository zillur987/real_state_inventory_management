import { http } from './http';

const BASE = '/api/v1/clients';

export const clientMatchingApi = {
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
    async saveRequirement(id, payload) {
        const { data } = await http.put(`${BASE}/${id}/requirements`, payload);
        return data.data;
    },
    async matches(id) {
        const { data } = await http.get(`${BASE}/${id}/matches`);
        return data;
    },
    async shortlist(clientId, propertyId) {
        const { data } = await http.post(`${BASE}/${clientId}/matches/${propertyId}/shortlist`);
        return data;
    },
    async removeShortlist(clientId, propertyId) {
        const { data } = await http.delete(`${BASE}/${clientId}/matches/${propertyId}/shortlist`);
        return data;
    },
    async activities(id) {
        const { data } = await http.get(`${BASE}/${id}/activities`);
        return data.data;
    },
    async addActivity(id, payload) {
        const { data } = await http.post(`${BASE}/${id}/activities`, payload);
        return data.data;
    },
};
