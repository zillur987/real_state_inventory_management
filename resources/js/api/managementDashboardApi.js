import { http } from './http';

const BASE = '/api/v1/dashboard';

export const managementDashboardApi = {
    async summary() {
        const { data } = await http.get(`${BASE}/summary`);
        return data.data;
    },
    async reports(params = {}) {
        const { data } = await http.get(`${BASE}/reports`, { params });
        return data.data;
    },
};
