import { http } from './http';

const BASE = '/api/v1/auth';

export const authApi = {
    csrf: () => http.get('/sanctum/csrf-cookie'),

    async login({ email, password, remember = false }) {
        await this.csrf();
        const { data } = await http.post(`${BASE}/login`, { email, password, remember });
        return data.data;
    },

    async logout() {
        await http.post(`${BASE}/logout`);
    },

    async me() {
        const { data } = await http.get(`${BASE}/me`);
        return data.data;
    },
};
