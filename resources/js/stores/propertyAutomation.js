import { ref } from 'vue';
import { defineStore } from 'pinia';
import { propertyAutomationApi } from '@/api/propertyAutomationApi';

export const usePropertyAutomationStore = defineStore('propertyAutomation', () => {
    const overview = ref({});
    const alerts = ref([]);
    const alertMeta = ref(null);
    const duplicates = ref([]);
    const mapPoints = ref([]);
    const loading = ref(false);
    const error = ref('');

    async function loadOverview() { loading.value = true; error.value = ''; try { overview.value = await propertyAutomationApi.overview(); } catch (e) { error.value = e.response?.data?.message ?? 'Unable to load automation overview.'; throw e; } finally { loading.value = false; } }
    async function loadAlerts(params = {}) { loading.value = true; try { const r = await propertyAutomationApi.alerts(params); alerts.value = r.data ?? []; alertMeta.value = r.meta ?? null; } finally { loading.value = false; } }
    async function acknowledge(id) { await propertyAutomationApi.acknowledgeAlert(id); alerts.value = alerts.value.filter((item) => item.id !== id); await loadOverview(); }
    async function loadDuplicates() { loading.value = true; try { const r = await propertyAutomationApi.duplicates(); duplicates.value = r.data ?? []; } finally { loading.value = false; } }
    async function loadMap(params = {}) { loading.value = true; try { mapPoints.value = await propertyAutomationApi.mapPoints(params); } finally { loading.value = false; } }
    return { overview, alerts, alertMeta, duplicates, mapPoints, loading, error, loadOverview, loadAlerts, acknowledge, loadDuplicates, loadMap };
});
