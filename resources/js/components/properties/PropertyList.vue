<script setup>
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue'
import StatusBadge from './StatusBadge.vue'

/**
 * Server-driven property table.
 *
 * props.items item shape:
 *  { id, property_code, property_name, category, type, division, district, area,
 *    apartment_size, land_size, land_unit, bedrooms, bathrooms, asking_price, price_per_sqft,
 *    inventory_status, legal_verification, cover_photo, updated_at }
 *
 * props.meta : { current_page, last_page, per_page, total }  (Laravel paginator shape)
 * props.statusCounts : optional { Available: 12, Reserved: 3, ... } for the tab counters
 *
 * Emits
 *  - query-change(query) : { page, per_page, search, category, legal_verification, inventory_status, sort_by, sort_dir }
 *  - create / view(item) / edit(item) / delete(item)
 *  - bulk({ action: 'export' | 'delete', ids })
 */
const props = defineProps({
  items: { type: Array, default: () => [] },
  meta: { type: Object, default: () => ({ current_page: 1, last_page: 1, per_page: 15, total: 0 }) },
  loading: { type: Boolean, default: false },
  statusCounts: { type: Object, default: () => ({}) },
  canCreate: { type: Boolean, default: true },
  canEdit: { type: Boolean, default: true },
  canDelete: { type: Boolean, default: false },
  canViewPrice: { type: Boolean, default: true },
  canView: { type: Boolean, default: true },
})
const emit = defineEmits(['query-change', 'create', 'view', 'edit', 'delete', 'bulk'])

const CATEGORIES = ['Apartment', 'Flat', 'Duplex', 'Penthouse', 'Land', 'Plot', 'Commercial']
const LEGAL = ['Verified', 'Non-verified', 'Pending']
const TABS = ['All', 'Available', 'Reserved', 'Sold', 'Withdrawn']
const PAGE_SIZES = [15, 30, 50, 100]

const query = reactive({
  page: 1, per_page: 15, search: '', category: '', legal_verification: '', inventory_status: '',
  sort_by: 'updated_at', sort_dir: 'desc',
})
const selected = ref(new Set())
const searchInput = ref('')

const columns = computed(() => [
  { key: 'property_name', label: 'Property', sortable: true },
  { key: 'category', label: 'Category', sortable: true },
  { key: 'district', label: 'Location', sortable: true },
  { key: 'size', label: 'Size', sortable: false },
  ...(props.canViewPrice ? [{ key: 'asking_price', label: 'Asking price', sortable: true, align: 'right' }] : []),
  { key: 'inventory_status', label: 'Inventory', sortable: true },
  { key: 'legal_verification', label: 'Legal', sortable: true },
  { key: 'updated_at', label: 'Updated', sortable: true },
])

/* ----------------------------- query handling ----------------------------- */
let debounce
watch(searchInput, (v) => {
  clearTimeout(debounce)
  debounce = setTimeout(() => {
    query.search = v.trim()
    query.page = 1
  }, 350)
})
onBeforeUnmount(() => clearTimeout(debounce))

watch(query, () => {
  selected.value = new Set()
  emit('query-change', { ...query })
}, { deep: true })

const activeFilters = computed(() => [query.category, query.legal_verification, query.search].filter(Boolean).length)

function clearFilters() {
  searchInput.value = ''
  Object.assign(query, { search: '', category: '', legal_verification: '', inventory_status: '', page: 1 })
}
function setTab(tab) {
  query.inventory_status = tab === 'All' ? '' : tab
  query.page = 1
}
function sortBy(col) {
  if (!col.sortable) return
  if (query.sort_by === col.key) query.sort_dir = query.sort_dir === 'asc' ? 'desc' : 'asc'
  else Object.assign(query, { sort_by: col.key, sort_dir: 'asc' })
}
const ariaSort = (col) => (query.sort_by === col.key ? (query.sort_dir === 'asc' ? 'ascending' : 'descending') : col.sortable ? 'none' : undefined)

/* ------------------------------- selection -------------------------------- */
const allSelected = computed(() => props.items.length > 0 && props.items.every((i) => selected.value.has(i.id)))
const someSelected = computed(() => selected.value.size > 0 && !allSelected.value)
function toggleAll() {
  selected.value = allSelected.value ? new Set() : new Set(props.items.map((i) => i.id))
}
function toggleOne(id) {
  const next = new Set(selected.value)
  next.has(id) ? next.delete(id) : next.add(id)
  selected.value = next
}
const bulk = (action) => emit('bulk', { action, ids: [...selected.value] })

/* ------------------------------- pagination ------------------------------- */
const range = computed(() => {
  const { current_page: p, per_page: n, total } = props.meta
  return total ? { from: (p - 1) * n + 1, to: Math.min(p * n, total) } : { from: 0, to: 0 }
})
const pages = computed(() => {
  const { current_page: cur, last_page: last } = props.meta
  const set = new Set([1, last, cur, cur - 1, cur + 1])
  const list = [...set].filter((p) => p >= 1 && p <= last).sort((a, b) => a - b)
  return list.flatMap((p, i) => (i && p - list[i - 1] > 1 ? ['…', p] : [p]))
})
const goPage = (p) => { if (p >= 1 && p <= props.meta.last_page && p !== props.meta.current_page) query.page = p }
function setPerPage(e) {
  query.per_page = Number(e.target.value)
  query.page = 1
}

/* ------------------------------- formatters ------------------------------- */
function money(n) {
  if (n == null || n === '') return '—'
  const v = Number(n)
  if (v >= 1e7) return `৳ ${Number((v / 1e7).toFixed(2))} Cr`
  if (v >= 1e5) return `৳ ${Number((v / 1e5).toFixed(2))} Lac`
  return `৳ ${v.toLocaleString('en-US')}`
}
const dateFmt = new Intl.DateTimeFormat('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
const date = (d) => (d ? dateFmt.format(new Date(d)) : '—')
const sizeOf = (i) => (i.land_size ? `${i.land_size} ${i.land_unit ?? ''}`.trim() : i.apartment_size ? `${Number(i.apartment_size).toLocaleString('en-US')} sq. ft.` : '—')
const layoutOf = (i) => [i.bedrooms ? `${i.bedrooms} bed` : '', i.bathrooms ? `${i.bathrooms} bath` : ''].filter(Boolean).join(' · ')
const initials = (n = '') => n.split(' ').slice(0, 2).map((w) => w[0]).join('').toUpperCase()
const tabCount = (t) => (t === 'All' ? Object.values(props.statusCounts).reduce((a, b) => a + b, 0) : props.statusCounts[t])
const hasCounts = computed(() => Object.keys(props.statusCounts).length > 0)
const activeTab = computed(() => query.inventory_status || 'All')
const isEmpty = computed(() => !props.loading && props.items.length === 0)
</script>

<template>
  <section class="pl">
    <header class="pl__head">
      <div>
        <h1>Property inventory</h1>
        <p>{{ meta.total.toLocaleString('en-US') }} {{ meta.total === 1 ? 'property' : 'properties' }} in your inventory</p>
      </div>
      <button v-if="canCreate" type="button" class="btn btn--primary" @click="emit('create')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
        Add property
      </button>
    </header>

    <div class="pl__panel">
      <!-- Status tabs -->
      <div class="tabs" role="tablist" aria-label="Inventory status">
        <button
          v-for="t in TABS"
          :key="t"
          type="button"
          role="tab"
          class="tab"
          :class="{ 'is-active': activeTab === t }"
          :aria-selected="activeTab === t"
          @click="setTab(t)"
        >
          {{ t }}
          <span v-if="hasCounts && tabCount(t) != null" class="tab__count">{{ tabCount(t) }}</span>
        </button>
      </div>

      <!-- Toolbar -->
      <div class="toolbar">
        <label class="search">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="M20 20l-3.5-3.5" /></svg>
          <input v-model="searchInput" type="search" placeholder="Search by name, ID, area or developer" aria-label="Search properties" />
        </label>
        <select v-model="query.category" class="filter" aria-label="Filter by category">
          <option value="">All categories</option>
          <option v-for="c in CATEGORIES" :key="c" :value="c">{{ c }}</option>
        </select>
        <select v-model="query.legal_verification" class="filter" aria-label="Filter by legal verification">
          <option value="">Any legal status</option>
          <option v-for="l in LEGAL" :key="l" :value="l">{{ l }}</option>
        </select>
        <button v-if="activeFilters" type="button" class="link-btn" @click="clearFilters">Clear filters</button>
      </div>

      <!-- Bulk bar -->
      <div v-if="selected.size" class="bulk" role="status">
        <strong>{{ selected.size }} selected</strong>
        <span class="bulk__spacer" />
        <button type="button" class="btn btn--sm" @click="bulk('export')">Export</button>
        <button v-if="canDelete" type="button" class="btn btn--sm btn--danger" @click="bulk('delete')">Delete</button>
        <button type="button" class="link-btn" @click="selected = new Set()">Clear</button>
      </div>

      <!-- Table -->
      <div class="table-wrap" :class="{ 'is-loading': loading && items.length }" :aria-busy="loading">
        <table class="table">
          <thead>
            <tr>
              <th class="col-check">
                <input type="checkbox" :checked="allSelected" :indeterminate.prop="someSelected" aria-label="Select all properties on this page" @change="toggleAll" />
              </th>
              <th
                v-for="col in columns"
                :key="col.key"
                :class="{ 'is-right': col.align === 'right', 'is-sortable': col.sortable }"
                :aria-sort="ariaSort(col)"
              >
                <button v-if="col.sortable" type="button" class="th-btn" @click="sortBy(col)">
                  {{ col.label }}
                  <svg class="sort" :class="{ 'is-on': query.sort_by === col.key, 'is-asc': query.sort_by === col.key && query.sort_dir === 'asc' }" width="10" height="12" viewBox="0 0 10 12" aria-hidden="true">
                    <path d="M5 1l3.5 4h-7zM5 11L1.5 7h7z" fill="currentColor" />
                  </svg>
                </button>
                <span v-else>{{ col.label }}</span>
              </th>
              <th class="col-actions"><span class="sr-only">Actions</span></th>
            </tr>
          </thead>

          <tbody v-if="loading && !items.length">
            <tr v-for="n in 8" :key="n" class="skeleton">
              <td v-for="c in columns.length + 2" :key="c"><i class="sk" :style="{ width: `${40 + ((n * c * 13) % 50)}%` }" /></td>
            </tr>
          </tbody>

          <tbody v-else-if="!isEmpty">
            <tr v-for="item in items" :key="item.id" :class="{ 'is-selected': selected.has(item.id) }">
              <td class="col-check">
                <input type="checkbox" :checked="selected.has(item.id)" :aria-label="`Select ${item.property_name}`" @change="toggleOne(item.id)" />
              </td>

              <td>
                <button type="button" class="prop" :disabled="!canView" @click="emit('view', item)">
                  <span class="prop__img">
                    <img v-if="item.cover_photo" :src="item.cover_photo" :alt="item.property_name" loading="lazy" />
                    <b v-else>{{ initials(item.property_name) }}</b>
                  </span>
                  <span class="prop__text">
                    <span class="prop__name">{{ item.property_name }}</span>
                    <span class="prop__meta">{{ item.property_code }}<template v-if="item.type"> · {{ item.type }}</template></span>
                  </span>
                </button>
              </td>

              <td>
                <span class="cell-main">{{ item.category }}</span>
                <span v-if="layoutOf(item)" class="cell-sub">{{ layoutOf(item) }}</span>
              </td>
              <td>
                <span class="cell-main">{{ item.area }}</span>
                <span class="cell-sub">{{ [item.district, item.division].filter(Boolean).join(', ') }}</span>
              </td>
              <td class="nowrap">{{ sizeOf(item) }}</td>
              <td v-if="canViewPrice" class="is-right nowrap">
                <span class="cell-main price">{{ money(item.asking_price) }}</span>
                <span v-if="item.price_per_sqft" class="cell-sub">৳ {{ Number(item.price_per_sqft).toLocaleString('en-US') }} / sq. ft.</span>
              </td>
              <td><StatusBadge :value="item.inventory_status" /></td>
              <td><StatusBadge :value="item.legal_verification" /></td>
              <td class="nowrap muted">{{ date(item.updated_at) }}</td>

              <td class="col-actions">
                <div class="row-actions">
                  <button v-if="canView" type="button" class="icon-btn" :aria-label="`View ${item.property_name}`" title="View" @click="emit('view', item)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" /><circle cx="12" cy="12" r="3" /></svg>
                  </button>
                  <button v-if="canEdit" type="button" class="icon-btn" :aria-label="`Edit ${item.property_name}`" title="Edit" @click="emit('edit', item)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16v4z" /></svg>
                  </button>
                  <button v-if="canDelete" type="button" class="icon-btn icon-btn--danger" :aria-label="`Delete ${item.property_name}`" title="Delete" @click="emit('delete', item)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3" /></svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="isEmpty" class="empty">
          <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21V9l9-6 9 6v12M9 21v-6h6v6" /></svg>
          <h3>{{ activeFilters || query.inventory_status ? 'No properties match these filters' : 'No properties yet' }}</h3>
          <p>{{ activeFilters || query.inventory_status ? 'Try a different search or clear the filters.' : 'Add your first property to start building the inventory.' }}</p>
          <button v-if="activeFilters || query.inventory_status" type="button" class="btn" @click="clearFilters(); setTab('All')">Clear filters</button>
          <button v-else-if="canCreate" type="button" class="btn btn--primary" @click="emit('create')">Add property</button>
        </div>
      </div>

      <!-- Footer / pagination -->
      <footer class="foot">
        <span class="foot__info">Showing {{ range.from }}–{{ range.to }} of {{ meta.total.toLocaleString('en-US') }}</span>
        <label class="foot__size">
          Rows
          <select :value="query.per_page" aria-label="Rows per page" @change="setPerPage">
            <option v-for="s in PAGE_SIZES" :key="s" :value="s">{{ s }}</option>
          </select>
        </label>
        <nav class="pager" aria-label="Pagination">
          <button type="button" class="pager__btn" :disabled="meta.current_page <= 1" aria-label="Previous page" @click="goPage(meta.current_page - 1)">‹</button>
          <template v-for="(p, i) in pages" :key="`${p}-${i}`">
            <span v-if="p === '…'" class="pager__gap">…</span>
            <button v-else type="button" class="pager__btn" :class="{ 'is-active': p === meta.current_page }" :aria-current="p === meta.current_page ? 'page' : undefined" @click="goPage(p)">{{ p }}</button>
          </template>
          <button type="button" class="pager__btn" :disabled="meta.current_page >= meta.last_page" aria-label="Next page" @click="goPage(meta.current_page + 1)">›</button>
        </nav>
      </footer>
    </div>
  </section>
</template>

<style scoped>
.pl {
  --c-bg: #f3f5f7; --c-surface: #fff; --c-border: #dfe4ea; --c-border-strong: #c5cdd6;
  --c-text: #17212b; --c-muted: #5f6b7a; --c-primary: #0f766e; --c-primary-dark: #0b5e57;
  --c-primary-soft: #e3f4f1; --c-danger: #dc2626;
  background: var(--c-bg); color: var(--c-text); padding: 28px clamp(16px, 2.5vw, 32px) 40px;
  font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; font-size: 14.5px;
}
.pl__head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
.pl__head h1 { margin: 0; font-size: 24px; letter-spacing: -.01em; }
.pl__head p { margin: 4px 0 0; color: var(--c-muted); }
.pl__panel { background: var(--c-surface); border: 1px solid var(--c-border); border-radius: 14px; overflow: hidden; }

/* Buttons */
.btn {
  display: inline-flex; align-items: center; gap: 8px; min-height: 40px; padding: 0 16px; border-radius: 9px;
  border: 1px solid var(--c-border-strong); background: var(--c-surface); color: var(--c-text);
  font: inherit; font-weight: 600; cursor: pointer; transition: background .15s;
}
.btn:hover { background: #f3f5f7; }
.btn--primary { background: var(--c-primary); border-color: var(--c-primary); color: #fff; }
.btn--primary:hover { background: var(--c-primary-dark); }
.btn--sm { min-height: 32px; padding: 0 12px; font-size: 13px; }
.btn--danger { color: var(--c-danger); border-color: #f3c0c0; }
.btn--danger:hover { background: #fef2f2; }
.btn:focus-visible, .link-btn:focus-visible, .tab:focus-visible, .icon-btn:focus-visible, .pager__btn:focus-visible, .th-btn:focus-visible, .prop:focus-visible { outline: 2px solid var(--c-primary); outline-offset: 2px; }
.link-btn { border: 0; background: none; color: var(--c-primary); font: inherit; font-weight: 600; cursor: pointer; padding: 4px 6px; border-radius: 6px; }
.link-btn:hover { text-decoration: underline; }

/* Tabs */
.tabs { display: flex; gap: 4px; padding: 10px 14px 0; border-bottom: 1px solid var(--c-border); overflow-x: auto; }
.tab { display: inline-flex; align-items: center; gap: 8px; padding: 10px 12px; border: 0; background: none; font: inherit; font-weight: 600; color: var(--c-muted); cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; white-space: nowrap; }
.tab:hover { color: var(--c-text); }
.tab.is-active { color: var(--c-primary-dark); border-bottom-color: var(--c-primary); }
.tab__count { min-width: 22px; padding: 1px 7px; border-radius: 999px; background: #eceff3; color: var(--c-muted); font-size: 12px; text-align: center; }
.tab.is-active .tab__count { background: var(--c-primary-soft); color: var(--c-primary-dark); }

/* Toolbar */
.toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 14px; }
.search { position: relative; flex: 1 1 280px; max-width: 420px; color: var(--c-muted); }
.search svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; }
.search input, .filter { min-height: 38px; border: 1px solid var(--c-border-strong); border-radius: 9px; background: var(--c-surface); font: inherit; color: var(--c-text); box-sizing: border-box; }
.search input { width: 100%; padding: 0 12px 0 36px; }
.filter { padding: 0 12px; cursor: pointer; }
.search input:focus, .filter:focus { outline: none; border-color: var(--c-primary); box-shadow: 0 0 0 3px rgba(15, 118, 110, .15); }

.bulk { display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: var(--c-primary-soft); border-top: 1px solid #c4e5df; border-bottom: 1px solid #c4e5df; color: var(--c-primary-dark); }
.bulk__spacer { flex: 1; }

/* Table */
.table-wrap { position: relative; overflow-x: auto; transition: opacity .15s; }
.table-wrap.is-loading { opacity: .55; pointer-events: none; }
.table { width: 100%; border-collapse: collapse; min-width: 1240px; }
.table th { position: sticky; top: 0; padding: 14px 18px; background: #f8fafb; border-bottom: 1px solid var(--c-border); border-top: 1px solid var(--c-border); font-size: 13px; font-weight: 600; color: var(--c-muted); text-align: left; white-space: nowrap; }
.table td { padding: 20px 18px; border-bottom: 1px solid #eef1f4; vertical-align: middle; }
.table tbody tr { transition: background .12s; }
.table tbody tr:hover { background: #f8fbfb; }
.table tbody tr.is-selected { background: #eef8f6; }
.table tbody tr:last-child td { border-bottom: 0; }
.is-right { text-align: right !important; }
.nowrap { white-space: nowrap; }
.muted { color: var(--c-muted); }
.col-check { width: 44px; padding-right: 0 !important; }
.col-check input { width: 16px; height: 16px; accent-color: var(--c-primary); cursor: pointer; }
.col-actions { width: 1%; text-align: right; }

.th-btn { display: inline-flex; align-items: center; gap: 6px; padding: 0; border: 0; background: none; font: inherit; font-weight: 600; color: inherit; cursor: pointer; }
.th-btn:hover { color: var(--c-text); }
.is-right .th-btn { flex-direction: row-reverse; }
.sort { color: #b3bcc6; }
.sort.is-on { color: var(--c-primary); }
.sort.is-on:not(.is-asc) path:first-child, .sort.is-on.is-asc path:last-child { opacity: .25; }

.prop { display: flex; align-items: center; gap: 12px; padding: 0; border: 0; background: none; font: inherit; text-align: left; cursor: pointer; color: inherit; max-width: 400px; border-radius: 8px; }
.prop__img { display: grid; place-items: center; width: 60px; height: 60px; flex: none; border-radius: 12px; overflow: hidden; background: linear-gradient(135deg, #d9efec, #eaf1f6); color: var(--c-primary-dark); font-size: 13px; }
.prop__img img { width: 100%; height: 100%; object-fit: cover; }
.prop__text { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.prop__name { font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.prop:disabled { cursor: default; }
.prop:hover:not(:disabled) .prop__name { color: var(--c-primary-dark); text-decoration: underline; }
.prop__meta, .cell-sub { font-size: 12.5px; color: var(--c-muted); }
.cell-main { display: block; font-weight: 500; }
.cell-sub { display: block; margin-top: 2px; }
.price { font-size: 15.5px; font-weight: 700; font-variant-numeric: tabular-nums; }

.row-actions { display: inline-flex; gap: 2px; opacity: .55; transition: opacity .15s; }
tr:hover .row-actions, .row-actions:focus-within { opacity: 1; }
.icon-btn { display: grid; place-items: center; width: 36px; height: 36px; border: 0; border-radius: 8px; background: none; color: var(--c-muted); cursor: pointer; }
.icon-btn:hover { background: #eceff3; color: var(--c-text); }
.icon-btn--danger:hover { background: #fef2f2; color: var(--c-danger); }

.skeleton td { padding: 26px 18px; }
.sk { display: block; height: 12px; border-radius: 6px; background: linear-gradient(90deg, #eef1f4 25%, #f6f8fa 50%, #eef1f4 75%); background-size: 200% 100%; animation: shimmer 1.3s infinite linear; }
@keyframes shimmer { to { background-position: -200% 0; } }

.empty { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 96px 20px; text-align: center; color: var(--c-muted); }
.empty svg { color: #a8b3bf; margin-bottom: 6px; }
.empty h3 { margin: 0; color: var(--c-text); font-size: 16px; }
.empty p { margin: 0 0 12px; max-width: 360px; }

/* Footer */
.foot { display: flex; flex-wrap: wrap; align-items: center; gap: 16px; padding: 12px 16px; border-top: 1px solid var(--c-border); color: var(--c-muted); }
.foot__info { margin-right: auto; }
.foot__size { display: inline-flex; align-items: center; gap: 8px; }
.foot__size select { min-height: 32px; border: 1px solid var(--c-border-strong); border-radius: 8px; background: var(--c-surface); padding: 0 8px; font: inherit; color: var(--c-text); }
.pager { display: inline-flex; align-items: center; gap: 4px; }
.pager__btn { min-width: 32px; height: 32px; padding: 0 8px; border: 1px solid transparent; border-radius: 8px; background: none; font: inherit; font-weight: 600; color: var(--c-text); cursor: pointer; }
.pager__btn:hover:not(:disabled):not(.is-active) { background: #eceff3; }
.pager__btn.is-active { background: var(--c-primary); color: #fff; }
.pager__btn:disabled { color: #b3bcc6; cursor: not-allowed; }
.pager__gap { padding: 0 4px; }

.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }

@media (max-width: 700px) {
  .pl__head { align-items: flex-start; flex-direction: column; }
  .foot { justify-content: center; }
  .foot__info { margin-right: 0; width: 100%; text-align: center; }
}
@media (prefers-reduced-motion: reduce) {
  .sk { animation: none; }
  * { transition: none !important; }
}
</style>