import { http } from './http';

const BASE = '/api/v1';

export const propertyWorkflowApi = {
    async list(params = {}) {
        const { data } = await http.get(`${BASE}/property-workflow`, { params });
        return data;
    },
    async show(propertyId) {
        const { data } = await http.get(`${BASE}/property-workflow/${propertyId}`);
        return data;
    },
    async submit(propertyId, payload = {}) {
        const { data } = await http.post(`${BASE}/properties/${propertyId}/workflow/submit`, payload);
        return data.data;
    },
    async review(propertyId, payload) {
        const { data } = await http.post(`${BASE}/properties/${propertyId}/workflow/review`, payload);
        return data.data;
    },
    async decide(propertyId, payload) {
        const { data } = await http.post(`${BASE}/properties/${propertyId}/workflow/decision`, payload);
        return data.data;
    },
};
