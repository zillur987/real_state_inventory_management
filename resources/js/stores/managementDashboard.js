import { ref } from 'vue';
import { defineStore } from 'pinia';
import { managementDashboardApi } from '@/api/managementDashboardApi';

export const useManagementDashboardStore = defineStore('managementDashboard', () => {
    const summary = ref(null);
    const reports = ref(null);
    const loading = ref(false);
    const reportsLoading = ref(false);
    const error = ref('');

    async function fetchSummary() {
        loading.value = true;
        error.value = '';
        try {
            summary.value = await managementDashboardApi.summary();
            return summary.value;
        } catch (exception) {
            error.value = exception.response?.data?.message ?? 'Unable to load dashboard summary.';
            throw exception;
        } finally {
            loading.value = false;
        }
    }

    async function fetchReports(params = {}) {
        reportsLoading.value = true;
        error.value = '';
        try {
            reports.value = await managementDashboardApi.reports(params);
            return reports.value;
        } catch (exception) {
            error.value = exception.response?.data?.message ?? 'Unable to load management reports.';
            throw exception;
        } finally {
            reportsLoading.value = false;
        }
    }

    return { summary, reports, loading, reportsLoading, error, fetchSummary, fetchReports };
});
