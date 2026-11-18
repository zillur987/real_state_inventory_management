<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'
import PropertyList from '@/components/properties/PropertyList.vue'
import { propertyApi } from '@/api/propertyApi'
import { listItemFromApi, queryToParams } from '@/utils/propertyMapper'
import { useToast } from 'vue-toastification';

const router = useRouter()
const toast = useToast()

const items = ref([])
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const loading = ref(true)
const error = ref('')
let lastQuery = {}
let requestId = 0

async function load(query = lastQuery) {
  lastQuery = query
  const id = ++requestId
  loading.value = true
  error.value = ''

  try {
    const res = await propertyApi.list(queryToParams(query))
    if (id !== requestId) return // a newer request already won
    items.value = (res.data ?? []).map(listItemFromApi)
    meta.value = res.meta ?? {
      current_page: 1, last_page: 1, per_page: items.value.length, total: items.value.length,
    }
  } catch (e) {
    if (id !== requestId) return
    error.value = e.response?.data?.message ?? 'Unable to load properties. Please try again.'
    toast.error(error.value, { title: 'Could not load properties' })
  } finally {
    if (id === requestId) loading.value = false
  }
}

/* ---- Bulk actions ---- */
const csvCell = (v) => `"${String(v ?? '').replace(/"/g, '""')}"`

function exportCsv(ids) {
  const rows = items.value.filter((i) => ids.includes(i.id))
  if (!rows.length) {
    toast.warning('Select at least one property to export.')
    return
  }
  const header = ['Property ID', 'Name', 'Category', 'Type', 'Area', 'District', 'Division', 'Asking price', 'Inventory status', 'Legal verification', 'Updated']
  const lines = rows.map((r) => [
    r.property_code, r.property_name, r.category, r.type, r.area, r.district, r.division,
    r.asking_price, r.inventory_status, r.legal_verification, r.updated_at,
  ].map(csvCell).join(','))

  const blob = new Blob(['\uFEFF' + [header.map(csvCell).join(','), ...lines].join('\n')], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = Object.assign(document.createElement('a'), { href: url, download: `properties-${new Date().toISOString().slice(0, 10)}.csv` })
  link.click()
  URL.revokeObjectURL(url)
  toast.success(`${rows.length} ${rows.length === 1 ? 'property' : 'properties'} exported to CSV.`, { title: 'Export ready' })
}

function onBulk({ action, ids }) {
  if (action === 'export') exportCsv(ids)
  else toast.info('This bulk action is not available yet.')
}

onMounted(() => load({}))
</script>

<template>
  <AppLayout>
    <div v-if="error" class="mb-4 flex items-center justify-between gap-3 rounded-md bg-red-50 p-3 text-sm text-red-700" role="alert">
      <span>{{ error }}</span>
      <button type="button" class="font-semibold underline" @click="load()">Retry</button>
    </div>

    <PropertyList
      :items="items"
      :meta="meta"
      :loading="loading"
      @create="router.push({ name: 'properties.create' })"
      @view="(item) => router.push({ name: 'properties.show', params: { property: item.id } })"
      @edit="(item) => router.push({ name: 'properties.edit', params: { id: item.id } })"
      @bulk="onBulk"
      @query-change="load"
    />
  </AppLayout>
</template>