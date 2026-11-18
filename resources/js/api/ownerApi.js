import { http } from './http';

const BASE = '/api/v1/owners';
const REQUESTS = '/api/v1/owner-contact-requests';

export const ownerApi = {
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
    async archive(id) {
        const { data } = await http.delete(`${BASE}/${id}`);
        return data;
    },
    async requestContact(id, reason) {
        const { data } = await http.post(`${BASE}/${id}/contact-requests`, { reason });
        return data.data;
    },
    async contactRequests(params = {}) {
        const { data } = await http.get(REQUESTS, { params });
        return data;
    },
    async reviewContactRequest(id, payload) {
        const { data } = await http.patch(`${REQUESTS}/${id}/review`, payload);
        return data.data;
    },
};
