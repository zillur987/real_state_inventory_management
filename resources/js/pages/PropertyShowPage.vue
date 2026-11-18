<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'
import StatusBadge from '@/components/properties/StatusBadge.vue'
import { propertyApi } from '@/api/propertyApi'
import { formFromApi } from '@/utils/propertyMapper'
import { useToast } from 'vue-toastification';

const route = useRoute()
const router = useRouter()

const p = ref(null)
const loading = ref(true)
const error = ref('')
const toast = useToast()

async function load() {
  loading.value = true
  error.value = ''
  try {
    p.value = formFromApi(await propertyApi.show(route.params.id))
  } catch (e) {
    error.value = e.response?.status === 404
      ? 'This property could not be found. It may have been removed.'
      : 'Unable to load this property. Please try again.'
    toast.error(error.value, { title: 'Could not load property' })
  } finally {
    loading.value = false
  }
}
onMounted(load)

const isLand = computed(() => ['Land', 'Plot'].includes(p.value?.category))
const n = (v) => Number(v).toLocaleString('en-US')
const has = (v) => v !== '' && v !== null && v !== undefined
const taka = (v) => (has(v) ? `৳ ${n(v)}` : '')
const floor = (v) => (!has(v) ? '' : v === 'Ground' ? 'Ground floor' : `Floor ${v}`)

const pricePerUnit = computed(() => {
  const price = Number(p.value?.asking_price)
  const size = Number(isLand.value ? p.value?.land_size : p.value?.apartment_size)
  return price > 0 && size > 0 ? `৳ ${n(Math.round(price / size))} ${isLand.value ? `per ${p.value.land_unit}` : 'per sq. ft.'}` : ''
})

const date = (d) => (d ? new Intl.DateTimeFormat('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(d)) : '')

const sections = computed(() => {
  const f = p.value
  if (!f) return []
  return [
    {
      title: 'Basic information',
      rows: [
        ['Property ID', f.property_code], ['Category', f.category], ['Property type', f.type],
        ['Property status', f.status], ['Developer', f.developer],
        ['Location', [f.area, f.district, f.division].filter(Boolean).join(', ')],
        ['Detailed address', f.address, 3],
      ],
    },
    {
      title: 'Physical specifications',
      skipEmpty: true,
      rows: [
        ['Apartment size', has(f.apartment_size) ? `${n(f.apartment_size)} sq. ft.` : ''],
        ['Land size', has(f.land_size) ? `${n(f.land_size)} ${f.land_unit}` : ''],
        ['Building height', has(f.building_height) ? `G+${f.building_height}` : ''],
        ['Units per floor', has(f.units_per_floor) ? f.units_per_floor : ''],
        ['Available floor', floor(f.available_floor)], ['Facing', f.facing],
        ['Bedrooms', has(f.bedrooms) ? f.bedrooms : ''], ['Bathrooms', has(f.bathrooms) ? f.bathrooms : ''],
        ['Balconies', has(f.balconies) ? f.balconies : ''], ['Drawing room', f.drawing_room],
        ['Dining space', f.dining_space], ['Parking', f.parking], ['Gas', f.gas],
        ['Lift', f.lift], ['Generator', f.generator],
      ],
    },
    {
      title: 'Availability and legal',
      rows: [
        ['Handover year', has(f.handover_year) ? f.handover_year : ''], ['Handover status', f.handover_status],
        ['Paper status', f.paper_status], ['Legal verification', f.legal_verification],
        ['Mortgage status', f.mortgage_status], ['Ownership type', f.ownership_type],
        ['Inventory status', f.inventory_status],
      ],
    },
    {
      title: 'Pricing',
      rows: [
        ['Asking price', taka(f.asking_price)], [isLand.value ? 'Price per unit' : 'Price per sq. ft.', pricePerUnit.value],
        ['Negotiable', f.negotiable],
      ],
    },
  ].map((s) => ({ ...s, rows: s.skipEmpty ? s.rows.filter(([, v]) => has(v)) : s.rows }))
})
</script>

<template>
  <AppLayout>
    <div class="pd">
      <button type="button" class="pd__back" @click="router.push({ name: 'properties.index' })">← Back to inventory</button>

      <div v-if="loading" class="pd__state">Loading property…</div>

      <div v-else-if="error" class="pd__state pd__state--error" role="alert">
        {{ error }}
        <button type="button" class="pd__link" @click="load">Retry</button>
      </div>

      <template v-else>
        <header class="pd__head">
          <div>
            <h1>{{ p.property_name }}</h1>
            <p>{{ p.property_code }} · Added {{ date(p.created_at) }} · Updated {{ date(p.updated_at) }}</p>
            <div class="pd__badges">
              <StatusBadge :value="p.inventory_status" />
              <StatusBadge :value="p.legal_verification" />
              <StatusBadge :value="p.status" />
            </div>
          </div>
          <button type="button" class="pd__btn" @click="router.push({ name: 'properties.edit', params: { id: route.params.id } })">
            Edit property
          </button>
        </header>

        <section v-for="s in sections" :key="s.title" class="pd__card">
          <h2>{{ s.title }}</h2>
          <dl class="pd__grid">
            <div v-for="[name, value, span] in s.rows" :key="name" :class="{ 'is-wide': span === 3 }">
              <dt>{{ name }}</dt>
              <dd>{{ has(value) ? value : '—' }}</dd>
            </div>
          </dl>
        </section>

        <section class="pd__card">
          <h2>Description</h2>
          <p class="pd__text">{{ p.description || 'No description added yet.' }}</p>
        </section>
      </template>
    </div>
  </AppLayout>
</template>

<style scoped>
.pd { --c-border: #dfe4ea; --c-text: #17212b; --c-muted: #5f6b7a; --c-primary: #0f766e; --c-primary-dark: #0b5e57; color: var(--c-text); font-size: 14.5px; max-width: 1100px; }
.pd__back, .pd__link { border: 0; background: none; padding: 0; font: inherit; font-weight: 600; color: var(--c-primary); cursor: pointer; }
.pd__back:hover, .pd__link:hover { text-decoration: underline; }
.pd__state { margin-top: 20px; padding: 32px; border: 1px solid var(--c-border); border-radius: 14px; background: #fff; color: var(--c-muted); }
.pd__state--error { background: #fef2f2; border-color: #f3c0c0; color: #b42318; }
.pd__head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin: 16px 0 20px; }
.pd__head h1 { margin: 0; font-size: 24px; letter-spacing: -.01em; }
.pd__head p { margin: 6px 0 12px; color: var(--c-muted); }
.pd__badges { display: flex; flex-wrap: wrap; gap: 8px; }
.pd__btn { min-height: 40px; padding: 0 18px; border: 0; border-radius: 9px; background: var(--c-primary); color: #fff; font: inherit; font-weight: 600; cursor: pointer; white-space: nowrap; }
.pd__btn:hover { background: var(--c-primary-dark); }
.pd__btn:focus-visible, .pd__back:focus-visible { outline: 2px solid var(--c-primary); outline-offset: 2px; }
.pd__card { margin-bottom: 18px; padding: 20px 24px 24px; border: 1px solid var(--c-border); border-radius: 14px; background: #fff; }
.pd__card h2 { margin: 0 0 16px; padding-bottom: 12px; border-bottom: 1px solid var(--c-border); font-size: 16px; }
.pd__grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px 24px; margin: 0; }
.pd__grid dt { margin-bottom: 4px; font-size: 12.5px; font-weight: 600; color: var(--c-muted); }
.pd__grid dd { margin: 0; font-weight: 500; overflow-wrap: anywhere; }
.pd__grid .is-wide { grid-column: 1 / -1; }
.pd__text { margin: 0; line-height: 1.6; white-space: pre-line; }
@media (max-width: 800px) {
  .pd__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .pd__head { flex-direction: column; }
}
</style>