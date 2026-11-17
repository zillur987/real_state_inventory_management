import axios from 'axios';

/**
 * Single configured HTTP client (Sanctum SPA cookie auth).
 * Every API module imports this - never use axios directly elsewhere.
 */
export const http = axios.create({
    baseURL: '/',
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});
