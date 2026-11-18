import { http } from './http';

const SEARCH_BASE = '/api/v1/property-search';
const SAVED_BASE = '/api/v1/saved-property-searches';

export const propertySearchApi = {
    async search(params = {}) {
        const { data } = await http.get(SEARCH_BASE, { params });
        return data;
    },

    async savedSearches() {
        const { data } = await http.get(SAVED_BASE);
        return data.data ?? [];
    },

    async saveSearch(payload) {
        const { data } = await http.post(SAVED_BASE, payload);
        return data.data;
    },

    async deleteSavedSearch(id) {
        return http.delete(`${SAVED_BASE}/${id}`);
    },

    async requestOwnerContact(propertyId, reason) {
        const { data } = await http.post(
            `/api/v1/properties/${propertyId}/owner-contact-requests`,
            { reason }
        );
        return data;
    },
};
