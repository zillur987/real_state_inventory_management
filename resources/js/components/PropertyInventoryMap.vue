<script setup>
import { onBeforeUnmount, onMounted, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({ properties: { type: Array, default: () => [] } });
let map;
let markerLayer;

function renderMarkers() {
    if (!map || !markerLayer) return;
    markerLayer.clearLayers();
    const bounds = [];
    props.properties.forEach((property) => {
        const latitude = Number(property.latitude);
        const longitude = Number(property.longitude);
        if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return;
        const marker = L.marker([latitude, longitude]);
        const title = String(property.name ?? 'Property').replace(/[<>]/g, '');
        const code = String(property.property_code ?? '').replace(/[<>]/g, '');
        const area = String(property.area ?? '').replace(/[<>]/g, '');
        const district = String(property.district ?? '').replace(/[<>]/g, '');
        marker.bindPopup(`<strong>${code} · ${title}</strong><br>${area}, ${district}<br>BDT ${Number(property.asking_price ?? 0).toLocaleString()}`);
        marker.addTo(markerLayer);
        bounds.push([latitude, longitude]);
    });
    if (bounds.length === 1) map.setView(bounds[0], 15);
    else if (bounds.length > 1) map.fitBounds(bounds, { padding: [24, 24], maxZoom: 14 });
    else map.setView([23.8103, 90.4125], 11);
}

onMounted(() => {
    map = L.map('pims-property-map', { scrollWheelZoom: false }).setView([23.8103, 90.4125], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);
    markerLayer = L.layerGroup().addTo(map);
    renderMarkers();
});
watch(() => props.properties, renderMarkers, { deep: true });
onBeforeUnmount(() => { map?.remove(); map = null; });
</script>

<template><div id="pims-property-map" class="inventory-map" aria-label="Map of available properties"></div></template>

<style scoped>
.inventory-map{width:100%;height:520px;border-radius:10px;z-index:0;background:#f1f5f9}
@media(max-width:760px){.inventory-map{height:360px}}
</style>
