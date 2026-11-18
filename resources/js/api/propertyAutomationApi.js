import { http } from './http';

const BASE = '/api/v1/automation';
const PROPERTIES = '/api/v1/properties';

export const propertyAutomationApi = {
    async overview() { const { data } = await http.get(`${BASE}/overview`); return data.data; },
    async alerts(params = {}) { const { data } = await http.get(`${BASE}/alerts`, { params }); return data; },
    async acknowledgeAlert(id) { const { data } = await http.post(`${BASE}/alerts/${id}/acknowledge`); return data; },
    async duplicates() { const { data } = await http.get(`${BASE}/duplicates`); return data; },
    async mapPoints(params = {}) { const { data } = await http.get(`${PROPERTIES}/map-points`, { params }); return data.data; },
    async updateLocation(id, payload) { const { data } = await http.put(`${PROPERTIES}/${id}/location`, payload); return data; },
    async updatePrice(id, asking_price) { const { data } = await http.put(`${PROPERTIES}/${id}/asking-price`, { asking_price }); return data; },
    async priceHistory(id, params = {}) { const { data } = await http.get(`${PROPERTIES}/${id}/price-history`, { params }); return data; },
};
