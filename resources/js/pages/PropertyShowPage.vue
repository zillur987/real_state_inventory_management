<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'
import StatusBadge from '@/components/properties/StatusBadge.vue'
import { propertyApi } from '@/api/propertyApi'
import { formFromApi } from '@/utils/propertyMapper'
import { useToast } from 'vue-toastification'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const p = ref(null)
const raw = ref(null)
const loading = ref(true)
const error = ref('')
const lightbox = ref(-1)

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await propertyApi.show(route.params.id)
    raw.value = data
    p.value = formFromApi(data)
  } catch (e) {
    error.value = e.response?.status === 404
      ? 'This property could not be found. It may have been removed.'
      : 'Unable to load this property. Please try again.'
    toast.error(error.value, { title: 'Could not load property' })
  } finally {
    loading.value = false
  }
}

function onKey(e) {
  if (lightbox.value < 0) return
  if (e.key === 'Escape') closeLightbox()
  if (e.key === 'ArrowRight') step(1)
  if (e.key === 'ArrowLeft') step(-1)
}

onMounted(() => {
  load()
  window.addEventListener('keydown', onKey)
})
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))

/* ---------- Images ---------- */
function resolveUrl(item) {
  const src = typeof item === 'string'
    ? item
    : item?.url || item?.image_url || item?.path || item?.thumbnail_url || item?.src || ''
  if (!src) return ''
  if (/^(https?:)?\/\//.test(src) || src.startsWith('data:') || src.startsWith('/')) return src
  return `/storage/${src.replace(/^storage\//, '')}`
}

const images = computed(() => {
  const sources = [raw.value, p.value]
  for (const s of sources) {
    for (const key of ['images', 'photos', 'gallery', 'media']) {
      const list = s?.[key]
      if (Array.isArray(list) && list.length) {
        return list.map(resolveUrl).filter(Boolean)
      }
    }
  }
  return []
})

const heroImages = computed(() => images.value.slice(0, 5))
const extraCount = computed(() => Math.max(images.value.length - 5, 0))

function openLightbox(i) { lightbox.value = i }
function closeLightbox() { lightbox.value = -1 }
function step(d) {
  const total = images.value.length
  if (total) lightbox.value = (lightbox.value + d + total) % total
}
function hideBroken(e) { e.target.style.visibility = 'hidden' }

/* ---------- Formatting ---------- */
const isLand = computed(() => ['Land', 'Plot'].includes(p.value?.category))
const n = (v) => Number(v).toLocaleString('en-US')
const has = (v) => v !== '' && v !== null && v !== undefined
const taka = (v) => (has(v) ? `৳ ${n(v)}` : '')
const floor = (v) => (!has(v) ? '' : v === 'Ground' ? 'Ground floor' : `Floor ${v}`)

const pricePerUnit = computed(() => {
  const price = Number(p.value?.asking_price)
  const size = Number(isLand.value ? p.value?.land_size : p.value?.apartment_size)
  return price > 0 && size > 0
    ? `৳ ${n(Math.round(price / size))} ${isLand.value ? `per ${p.value.land_unit}` : 'per sq. ft.'}`
    : ''
})

const date = (d) => (d
  ? new Intl.DateTimeFormat('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(d))
  : '')

const location = computed(() => [p.value?.area, p.value?.district, p.value?.division].filter(Boolean).join(', '))

const highlights = computed(() => {
  const f = p.value
  if (!f) return []
  return [
    { label: 'Asking price', value: taka(f.asking_price), strong: true },
    { label: isLand.value ? 'Land size' : 'Apartment size',
      value: isLand.value
        ? (has(f.land_size) ? `${n(f.land_size)} ${f.land_unit}` : '')
        : (has(f.apartment_size) ? `${n(f.apartment_size)} sq. ft.` : '') },
    { label: 'Bedrooms', value: has(f.bedrooms) ? f.bedrooms : '' },
    { label: 'Bathrooms', value: has(f.bathrooms) ? f.bathrooms : '' },
    { label: 'Handover', value: has(f.handover_year) ? f.handover_year : '' },
  ].filter((h) => has(h.value))
})

const sections = computed(() => {
  const f = p.value
  if (!f) return []
  return [
    {
      title: 'Basic information', icon: 'ℹ',
      rows: [
        ['Property ID', f.property_code], ['Category', f.category], ['Property type', f.type],
        ['Property status', f.status], ['Developer', f.developer],
        ['Location', location.value],
        ['Detailed address', f.address, 3],
      ],
    },
    {
      title: 'Physical specifications', icon: '▦', skipEmpty: true,
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
      title: 'Availability and legal', icon: '⚖',
      rows: [
        ['Handover year', has(f.handover_year) ? f.handover_year : ''], ['Handover status', f.handover_status],
        ['Paper status', f.paper_status], ['Legal verification', f.legal_verification],
        ['Mortgage status', f.mortgage_status], ['Ownership type', f.ownership_type],
        ['Inventory status', f.inventory_status],
      ],
    },
    {
      title: 'Pricing', icon: '৳',
      rows: [
        ['Asking price', taka(f.asking_price)],
        [isLand.value ? 'Price per unit' : 'Price per sq. ft.', pricePerUnit.value],
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

      <div v-if="loading" class="pd__state">
        <span class="pd__spinner" aria-hidden="true"></span> Loading property…
      </div>

      <div v-else-if="error" class="pd__state pd__state--error" role="alert">
        {{ error }}
        <button type="button" class="pd__link" @click="load">Retry</button>
      </div>

      <template v-else>
        <!-- Header -->
        <header class="pd__head">
          <div class="pd__title">
            <div class="pd__badges">
              <StatusBadge :value="p.inventory_status" />
              <StatusBadge :value="p.legal_verification" />
              <StatusBadge :value="p.status" />
            </div>
            <h1>{{ p.property_name }}</h1>
            <p class="pd__loc" v-if="location">📍 {{ location }}</p>
            <p class="pd__meta">{{ p.property_code }} · Added {{ date(p.created_at) }} · Updated {{ date(p.updated_at) }}</p>
          </div>
          <button type="button" class="pd__btn" @click="router.push({ name: 'properties.edit', params: { id: route.params.id } })">
            ✎ Edit property
          </button>
        </header>

        <!-- Gallery -->
        <section v-if="images.length" class="pd__gallery" :class="`count-${Math.min(heroImages.length, 5)}`">
          <button
            v-for="(src, i) in heroImages"
            :key="src + i"
            type="button"
            class="pd__tile"
            :class="{ 'is-main': i === 0 }"
            :aria-label="`Open photo ${i + 1}`"
            @click="openLightbox(i)"
          >
            <img :src="src" :alt="`${p.property_name} photo ${i + 1}`" loading="lazy" @error="hideBroken">
            <span v-if="i === heroImages.length - 1 && extraCount" class="pd__more">+{{ extraCount }} photos</span>
          </button>
        </section>
        <div v-else class="pd__noimg">🖼 No photos uploaded for this property yet.</div>

        <!-- Highlights -->
        <section v-if="highlights.length" class="pd__stats">
          <div v-for="h in highlights" :key="h.label" class="pd__stat" :class="{ 'is-strong': h.strong }">
            <span>{{ h.label }}</span>
            <strong>{{ h.value }}</strong>
          </div>
        </section>

        <!-- Detail sections -->
        <section v-for="s in sections" :key="s.title" class="pd__card">
          <h2><span class="pd__icon">{{ s.icon }}</span>{{ s.title }}</h2>
          <dl class="pd__grid">
            <div v-for="[name, value, span] in s.rows" :key="name" :class="{ 'is-wide': span === 3 }">
              <dt>{{ name }}</dt>
              <dd>{{ has(value) ? value : '—' }}</dd>
            </div>
          </dl>
        </section>

        <section class="pd__card">
          <h2><span class="pd__icon">✎</span>Description</h2>
          <p class="pd__text">{{ p.description || 'No description added yet.' }}</p>
        </section>
      </template>

      <!-- Lightbox -->
      <div v-if="lightbox >= 0" class="lb" role="dialog" aria-modal="true" @click.self="closeLightbox">
        <button type="button" class="lb__close" aria-label="Close" @click="closeLightbox">✕</button>
        <button v-if="images.length > 1" type="button" class="lb__nav lb__prev" aria-label="Previous" @click="step(-1)">‹</button>
        <img class="lb__img" :src="images[lightbox]" :alt="`Photo ${lightbox + 1}`">
        <button v-if="images.length > 1" type="button" class="lb__nav lb__next" aria-label="Next" @click="step(1)">›</button>
        <div class="lb__count">{{ lightbox + 1 }} / {{ images.length }}</div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.pd {
  --c-border: #e4e8ee; --c-text: #101828; --c-muted: #667085; --c-bg: #f6f8fa;
  --c-primary: #0f766e; --c-primary-dark: #0b5e57; --c-tint: #e6f4f2;
  color: var(--c-text); font-size: 14.5px; max-width: 1180px; margin: 0 auto; padding-bottom: 40px;
}
.pd__back, .pd__link { border: 0; background: none; padding: 0; font: inherit; font-weight: 600; color: var(--c-primary); cursor: pointer; }
.pd__back:hover, .pd__link:hover { text-decoration: underline; }

.pd__state { margin-top: 20px; padding: 32px; border: 1px solid var(--c-border); border-radius: 16px; background: #fff; color: var(--c-muted); display: flex; align-items: center; gap: 12px; }
.pd__state--error { background: #fef3f2; border-color: #fecdca; color: #b42318; }
.pd__spinner { width: 18px; height: 18px; border: 2px solid var(--c-border); border-top-color: var(--c-primary); border-radius: 50%; animation: spin .8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.pd__head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin: 18px 0 20px; }
.pd__badges { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
.pd__head h1 { margin: 0; font-size: 30px; line-height: 1.15; letter-spacing: -.02em; font-weight: 750; }
.pd__loc { margin: 8px 0 0; font-size: 15px; color: #344054; }
.pd__meta { margin: 6px 0 0; font-size: 13px; color: var(--c-muted); }
.pd__btn { min-height: 42px; padding: 0 20px; border: 0; border-radius: 10px; background: var(--c-primary); color: #fff; font: inherit; font-weight: 650; cursor: pointer; white-space: nowrap; box-shadow: 0 1px 2px rgba(16,24,40,.12); transition: background .15s, transform .15s; }
.pd__btn:hover { background: var(--c-primary-dark); transform: translateY(-1px); }
.pd__btn:focus-visible, .pd__back:focus-visible, .pd__tile:focus-visible { outline: 2px solid var(--c-primary); outline-offset: 2px; }

/* Gallery */
.pd__gallery { display: grid; gap: 10px; height: 420px; margin-bottom: 20px; border-radius: 18px; overflow: hidden; grid-template-columns: 2fr 1fr 1fr; grid-template-rows: 1fr 1fr; }
.pd__tile { position: relative; border: 0; padding: 0; background: #e9edf2; cursor: pointer; overflow: hidden; }
.pd__tile.is-main { grid-row: 1 / span 2; }
.pd__tile img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .4s ease; }
.pd__tile:hover img { transform: scale(1.04); }
.pd__more { position: absolute; inset: 0; display: grid; place-items: center; background: rgba(16,24,40,.55); color: #fff; font-weight: 700; font-size: 18px; }
.count-1 { grid-template-columns: 1fr; grid-template-rows: 1fr; }
.count-2 { grid-template-columns: 2fr 1fr; }
.count-2 .pd__tile.is-main { grid-row: 1; }
.count-3 { grid-template-columns: 2fr 1fr; }
.count-3 .pd__tile.is-main { grid-row: 1 / span 2; }
.count-4 { grid-template-columns: 2fr 1fr 1fr; }
.count-4 .pd__tile:nth-child(4) { grid-column: 2 / span 2; }
.pd__noimg { display: grid; place-items: center; height: 140px; margin-bottom: 20px; border: 1.5px dashed var(--c-border); border-radius: 16px; background: #fff; color: var(--c-muted); }

/* Stats */
.pd__stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 20px; }
.pd__stat { padding: 16px 18px; border: 1px solid var(--c-border); border-radius: 14px; background: #fff; }
.pd__stat span { display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: var(--c-muted); }
.pd__stat strong { display: block; margin-top: 6px; font-size: 20px; font-weight: 720; }
.pd__stat.is-strong { background: var(--c-primary); border-color: var(--c-primary); }
.pd__stat.is-strong span { color: rgba(255,255,255,.8); }
.pd__stat.is-strong strong { color: #fff; font-size: 22px; }

/* Cards */
.pd__card { margin-bottom: 16px; padding: 22px 26px 26px; border: 1px solid var(--c-border); border-radius: 16px; background: #fff; box-shadow: 0 1px 2px rgba(16,24,40,.04); }
.pd__card h2 { display: flex; align-items: center; gap: 10px; margin: 0 0 18px; font-size: 16px; font-weight: 700; }
.pd__icon { display: inline-grid; place-items: center; width: 28px; height: 28px; border-radius: 8px; background: var(--c-tint); color: var(--c-primary); font-size: 14px; }
.pd__grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px 28px; margin: 0; }
.pd__grid dt { margin-bottom: 4px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--c-muted); }
.pd__grid dd { margin: 0; font-weight: 550; overflow-wrap: anywhere; }
.pd__grid .is-wide { grid-column: 1 / -1; }
.pd__text { margin: 0; line-height: 1.7; white-space: pre-line; color: #344054; }

/* Lightbox */
.lb { position: fixed; inset: 0; z-index: 1000; display: grid; place-items: center; background: rgba(8,12,20,.92); }
.lb__img { max-width: 92vw; max-height: 86vh; border-radius: 10px; object-fit: contain; }
.lb__close, .lb__nav { position: absolute; border: 0; background: rgba(255,255,255,.14); color: #fff; cursor: pointer; border-radius: 50%; width: 44px; height: 44px; font-size: 22px; display: grid; place-items: center; }
.lb__close:hover, .lb__nav:hover { background: rgba(255,255,255,.28); }
.lb__close { top: 20px; right: 20px; }
.lb__nav { top: 50%; transform: translateY(-50%); font-size: 30px; }
.lb__prev { left: 20px; }
.lb__next { right: 20px; }
.lb__count { position: absolute; bottom: 18px; left: 50%; transform: translateX(-50%); color: #fff; font-size: 13px; background: rgba(255,255,255,.14); padding: 5px 12px; border-radius: 999px; }

@media (max-width: 800px) {
  .pd__head { flex-direction: column; }
  .pd__head h1 { font-size: 24px; }
  .pd__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .pd__gallery, .pd__gallery[class*='count-'] { height: 260px; grid-template-columns: 2fr 1fr; grid-template-rows: 1fr 1fr; }
  .pd__tile:nth-child(n+4) { display: none; }
  .pd__tile.is-main { grid-row: 1 / span 2; }
  .pd__card { padding: 18px; }
}
</style>