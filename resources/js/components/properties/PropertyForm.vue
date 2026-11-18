<script setup>
import { useRouter } from 'vue-router'
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import SegmentedControl from './SegmentedControl.vue'
import FormField from './FormField.vue'
import FileUploader from './FileUploader.vue'
import { PROPERTY_FIELD_LABELS as L, pricePerUnitLabel } from '@/constants/propertyFieldLabels'

/**
 * Property create / edit form (Sections A–F of the PIMS spec).
 *
 * Props
 *  - initialValues : existing property (edit mode). Keys are snake_case, same as the payload.
 *  - developers    : string[] for the developer suggestions list.
 *  - managers      : { id, name }[] for "Assigned Relationship Manager".
 *  - permissions   : { editAsking, setFinalPrice, viewManagement, viewOwner } (all default true).
 *  - serverErrors  : Laravel 422 `errors` object ({ field: [messages] }).
 *  - saving        : disables the submit button while the request is in flight.
 *
 * Emits
 *  - submit(payload, formData) : validated plain object + multipart FormData.
 *  - cancel()
 */
const props = defineProps({
  mode: { type: String, default: 'create' },
  initialValues: { type: Object, default: () => ({}) },
  developers: { type: Array, default: () => [] },
  managers: { type: Array, default: () => [] },
  permissions: { type: Object, default: () => ({}) },
  serverErrors: { type: Object, default: () => ({}) },
  saving: { type: Boolean, default: false },
  requirePhotos: { type: Boolean, default: true },
})
const emit = defineEmits(['submit', 'cancel'])
const router = useRouter()

/* ------------------------------------------------------------------ */
/* Static options                                                      */
/* ------------------------------------------------------------------ */
const CATEGORIES = ['Apartment', 'Flat', 'Duplex', 'Penthouse', 'Land', 'Plot', 'Commercial']
const LAND_CATEGORIES = ['Land', 'Plot']
const COMMERCIAL = 'Commercial'
const TYPES = ['Primary', 'Secondary']
const PROPERTY_STATUS = ['New', 'Used', 'Semi-ready', 'Under-construction']
const LAND_UNITS = ['katha', 'decimal', 'bigha']
const FACING = ['North', 'South', 'East', 'West', 'Corner']
const YES_NO = ['Yes', 'No']
const PARKING = ['No', '1', '2', '3+']
const GAS = ['Titas', 'LPG', 'No Gas']
const HANDOVER_STATUS = ['Handed over', 'Upcoming']
const PAPER_STATUS = ['Registered', 'Non-registered', 'On process']
const LEGAL_VERIFICATION = ['Verified', 'Non-verified', 'Pending']
const MORTGAGE_STATUS = ['Mortgaged', 'Non-mortgaged', 'Unknown']
const OWNERSHIP = ['Individual', 'Joint', 'Company']
const INVENTORY_STATUS = ['Available', 'Reserved', 'Sold', 'Withdrawn']

const GEO = {
  Barishal: ['Barguna', 'Barishal', 'Bhola', 'Jhalokati', 'Patuakhali', 'Pirojpur'],
  Chattogram: ['Bandarban', 'Brahmanbaria', 'Chandpur', 'Chattogram', "Cox's Bazar", 'Cumilla', 'Feni', 'Khagrachhari', 'Lakshmipur', 'Noakhali', 'Rangamati'],
  Dhaka: ['Dhaka', 'Faridpur', 'Gazipur', 'Gopalganj', 'Kishoreganj', 'Madaripur', 'Manikganj', 'Munshiganj', 'Narayanganj', 'Narsingdi', 'Rajbari', 'Shariatpur', 'Tangail'],
  Khulna: ['Bagerhat', 'Chuadanga', 'Jashore', 'Jhenaidah', 'Khulna', 'Kushtia', 'Magura', 'Meherpur', 'Narail', 'Satkhira'],
  Mymensingh: ['Jamalpur', 'Mymensingh', 'Netrokona', 'Sherpur'],
  Rajshahi: ['Bogura', 'Chapainawabganj', 'Joypurhat', 'Naogaon', 'Natore', 'Pabna', 'Rajshahi', 'Sirajganj'],
  Rangpur: ['Dinajpur', 'Gaibandha', 'Kurigram', 'Lalmonirhat', 'Nilphamari', 'Panchagarh', 'Rangpur', 'Thakurgaon'],
  Sylhet: ['Habiganj', 'Moulvibazar', 'Sunamganj', 'Sylhet'],
}
const DIVISIONS = Object.keys(GEO)

const INTERNAL_NOTES = [
  { key: 'description', label: L.description, required: true, span: 3, rows: 5, placeholder: 'Describe the property: layout, condition, surroundings, nearby landmarks…' },
  { key: 'selling_points', label: L.selling_points, span: 3, rows: 3, placeholder: 'e.g. Lake view, corner plot, close to metro station, ready for immediate handover' },
  { key: 'negotiation_history', label: L.negotiation_history, span: 3, rows: 3, placeholder: 'Record offers, counter-offers and who made them, with dates' },
  { key: 'visit_feedback', label: L.visit_feedback, span: 3, rows: 3, placeholder: 'What did visiting clients like or dislike? Include the client name and visit date' },
  { key: 'legal_remarks', label: L.legal_remarks, span: 3, rows: 3, placeholder: 'Missing papers, pending clearances, deed or mutation remarks' },
  { key: 'internal_notes', label: L.internal_notes, span: 3, rows: 3, placeholder: 'Anything the team should know before contacting the owner or client' },
]

/* ------------------------------------------------------------------ */
/* State                                                               */
/* ------------------------------------------------------------------ */
const blank = () => ({
  property_code: '', property_name: '', category: '', type: '', division: '', district: '', area: '',
  address: '', developer: '', status: '', photos: [], documents: [],
  apartment_size: '', land_size: '', land_unit: 'katha', building_height: '', units_per_floor: '',
  available_floor: '', facing: '', bedrooms: '', bathrooms: '', balconies: '', drawing_room: '',
  dining_space: '', parking: '', gas: '', lift: '', generator: '',
  handover_year: '', handover_status: '', paper_status: '', legal_verification: '', mortgage_status: '',
  ownership_type: '', inventory_status: '',
  asking_price: '', negotiable: '', final_price: '', company_commission: '', expected_commission: '',
  owner_name: '', owner_phone: '', owner_profession: '', owner_address: '', legal_status_note: '',
  owner_expected_price: '', final_negotiated_price: '', relationship_manager_id: '',
  description: '', selling_points: '', negotiation_history: '', visit_feedback: '', legal_remarks: '', internal_notes: '',
})

const form = reactive({ ...blank(), ...props.initialValues })
const touched = reactive(new Set())
const submitted = ref(false)
const dirty = ref(false)
const serverErrs = ref({})
const activeSection = ref('basic')

const can = computed(() => ({
  editAsking: true, setFinalPrice: true, viewManagement: true, viewOwner: true, ...props.permissions,
}))

const isLand = computed(() => LAND_CATEGORIES.includes(form.category))
const isBuilding = computed(() => !!form.category && !isLand.value)
const isResidential = computed(() => isBuilding.value && form.category !== COMMERCIAL)
const needsHandover = computed(() => ['Under-construction', 'Semi-ready'].includes(form.status))

const districts = computed(() => GEO[form.division] ?? [])
const years = computed(() => {
  const now = new Date().getFullYear()
  return Array.from({ length: 41 }, (_, i) => now + 8 - i)
})
const floorOptions = computed(() => {
  const h = Number(form.building_height)
  const max = Number.isInteger(h) && h > 0 ? h : 40
  return ['Ground', ...Array.from({ length: max }, (_, i) => String(i + 1))]
})

const pricePerUnit = computed(() => {
  const price = Number(form.asking_price)
  const size = Number(isLand.value ? form.land_size : form.apartment_size)
  return price > 0 && size > 0 ? Math.round(price / size) : null
})
const pricePerLabel = computed(() => pricePerUnitLabel(isLand.value, form.land_unit))
const pricePerDisplay = computed(() => (pricePerUnit.value ? `৳ ${pricePerUnit.value.toLocaleString('en-US')}` : ''))

/* ------------------------------------------------------------------ */
/* Validation (frontend only, no HTML `required` attributes)           */
/* ------------------------------------------------------------------ */
const isBlank = (v) => v === '' || v === null || v === undefined || (typeof v === 'string' && !v.trim())
const lc = (k) => (L[k] ?? k).toLowerCase()

const allErrors = computed(() => {
  const f = form
  const e = {}

  // Messages are built from the shared label map, so copy changes happen in one place.
  const required = (k, verb = 'Enter') => {
    if (isBlank(f[k])) e[k] = `${verb} ${lc(k)}`
  }
  const text = (k, { min = 0, max = 255, req = false } = {}) => {
    if (isBlank(f[k])) {
      if (req) e[k] = `Enter ${lc(k)}`
      return
    }
    const len = String(f[k]).trim().length
    if (len < min) e[k] = `${L[k]} must be at least ${min} characters`
    else if (len > max) e[k] = `${L[k]} must be ${max} characters or fewer`
  }
  const number = (k, { req = false, min = 0, max = Infinity, integer = false, positive = false } = {}) => {
    const v = f[k]
    if (isBlank(v)) {
      if (req) e[k] = `Enter ${lc(k)}`
      return
    }
    const n = Number(v)
    if (!Number.isFinite(n)) e[k] = `${L[k]} must be a number`
    else if (integer && !Number.isInteger(n)) e[k] = `${L[k]} must be a whole number`
    else if (positive && n <= 0) e[k] = `${L[k]} must be greater than 0`
    else if (n < min) e[k] = `${L[k]} cannot be less than ${min}`
    else if (n > max) e[k] = `${L[k]} cannot be more than ${max.toLocaleString('en-US')}`
  }

  // A. Basic information
  text('property_name', { min: 3, max: 150, req: true })
  required('category', 'Select')
  required('type', 'Select')
  required('division', 'Select')
  required('district', 'Select')
  text('area', { min: 2, max: 100, req: true })
  text('address', { min: 10, max: 500, req: true })
  text('developer', { max: 120 })
  required('status', 'Select')
  if (props.requirePhotos && !f.photos.length) e.photos = 'Add at least one property photo'
  else if (f.photos.length > 20) e.photos = 'You can upload up to 20 photos'

  // B. Physical specifications
  if (isLand.value) {
    number('land_size', { req: true, positive: true, max: 100000 })
    required('land_unit', 'Select')
  }
  if (isBuilding.value) {
    number('apartment_size', { req: true, positive: true, max: 1000000 })
    number('building_height', { integer: true, positive: true, max: 150 })
    number('units_per_floor', { integer: true, positive: true, max: 50 })
    if (!isBlank(f.available_floor) && !floorOptions.value.includes(String(f.available_floor))) {
      e.available_floor = 'Floor is higher than the building height'
    }
    number('bathrooms', { req: true, integer: true, max: 20 })
    required('parking', 'Select')
    required('gas', 'Select')
    required('lift', 'Select')
    required('generator', 'Select')
  }
  if (isResidential.value) {
    number('bedrooms', { req: true, integer: true, positive: true, max: 20 })
    number('balconies', { integer: true, max: 20 })
  }

  // C. Availability and legal
  if (needsHandover.value) {
    required('handover_year', 'Select')
    required('handover_status', 'Select')
  }
  required('paper_status', 'Select')
  required('legal_verification', 'Select')
  required('mortgage_status', 'Select')
  required('ownership_type', 'Select')
  required('inventory_status', 'Select')

  // D. Pricing
  if (can.value.editAsking) number('asking_price', { req: true, positive: true, max: 100000000000 })
  if (can.value.setFinalPrice) number('final_price', { positive: true, max: 100000000000 })
  if (can.value.viewManagement) {
    number('company_commission', { max: 100000000000 })
    number('expected_commission', { max: 100000000000 })
    const price = Number(f.asking_price)
    if (!e.company_commission && price > 0 && Number(f.company_commission) > price) {
      e.company_commission = 'Commission cannot be more than the asking price'
    }
  }

  // E. Owner
  if (can.value.viewOwner) {
    text('owner_name', { min: 2, max: 120, req: true })
    if (isBlank(f.owner_phone)) e.owner_phone = `Enter ${lc('owner_phone')}`
    else if (!/^(?:\+?88)?01[3-9]\d{8}$/.test(String(f.owner_phone).replace(/[\s-]/g, ''))) {
      e.owner_phone = 'Enter a valid Bangladeshi mobile number, e.g. 01712 345678'
    }
    text('owner_profession', { max: 100 })
    text('owner_address', { max: 500 })
    text('legal_status_note', { max: 1000 })
    number('owner_expected_price', { positive: true, max: 100000000000 })
    if (can.value.viewManagement) {
      number('final_negotiated_price', { positive: true, max: 100000000000 })
    }
    if (props.managers.length) required('relationship_manager_id', 'Select')
  }

  // F. Internal remarks
  text('description', { min: 20, max: 2000, req: true })
  for (const n of INTERNAL_NOTES.slice(1)) text(n.key, { max: 2000 })

  return e
})

const visibleErrors = computed(() => {
  const out = {}
  for (const [k, msg] of Object.entries(allErrors.value)) {
    if (submitted.value || touched.has(k)) out[k] = msg
  }
  return out
})

const err = (k) => visibleErrors.value[k] || serverErrs.value[k] || ''
const touch = (k) => touched.add(k)
const bind = (k, idSuffix = k) => ({ id: `f-${idSuffix}`, 'aria-invalid': !!err(k), onBlur: () => touch(k) })
const seg = (k) => ({ id: `f-${k}`, invalid: !!err(k), onBlur: () => touch(k) })
const inv = (k) => ({ 'is-invalid': !!err(k) })

/* ------------------------------------------------------------------ */
/* Sections / navigation                                               */
/* ------------------------------------------------------------------ */
const SECTIONS = [
  { id: 'basic', letter: 'A', label: 'Basic information', keys: ['property_name', 'category', 'type', 'division', 'district', 'area', 'address', 'developer', 'status', 'photos', 'documents'] },
  { id: 'physical', letter: 'B', label: 'Physical specifications', keys: ['apartment_size', 'land_size', 'building_height', 'units_per_floor', 'available_floor', 'facing', 'bedrooms', 'bathrooms', 'balconies', 'drawing_room', 'dining_space', 'parking', 'gas', 'lift', 'generator'] },
  { id: 'legal', letter: 'C', label: 'Availability & legal', keys: ['handover_year', 'handover_status', 'paper_status', 'legal_verification', 'mortgage_status', 'ownership_type', 'inventory_status'] },
  { id: 'pricing', letter: 'D', label: 'Pricing', keys: ['asking_price', 'negotiable', 'final_price', 'company_commission', 'expected_commission'] },
  { id: 'owner', letter: 'E', label: 'Owner information', keys: ['owner_name', 'owner_phone', 'owner_profession', 'owner_address', 'legal_status_note', 'owner_expected_price', 'final_negotiated_price', 'relationship_manager_id'] },
  { id: 'remarks', letter: 'F', label: 'Internal remarks', keys: INTERNAL_NOTES.map((n) => n.key) },
]
const FIELD_ORDER = SECTIONS.flatMap((s) => s.keys)

const errorCounts = computed(() =>
  Object.fromEntries(SECTIONS.map((s) => [s.id, s.keys.filter((k) => err(k)).length])),
)
const totalErrors = computed(() => Object.values(errorCounts.value).reduce((a, b) => a + b, 0))

function goTo(id) {
  document.getElementById(`sec-${id}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

let observer
onMounted(() => {
  observer = new IntersectionObserver(
    (entries) => {
      const hit = entries.filter((en) => en.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0]
      if (hit) activeSection.value = hit.target.id.replace('sec-', '')
    },
    { rootMargin: '-20% 0px -65% 0px' },
  )
  SECTIONS.forEach((s) => {
    const el = document.getElementById(`sec-${s.id}`)
    if (el) observer.observe(el)
  })
  window.addEventListener('beforeunload', warnIfDirty)
})
onBeforeUnmount(() => {
  observer?.disconnect()
  window.removeEventListener('beforeunload', warnIfDirty)
})

function warnIfDirty(e) {
  if (!dirty.value || props.saving) return
  e.preventDefault()
  e.returnValue = ''
}

async function focusFirstError() {
  await nextTick()
  const key = FIELD_ORDER.find((k) => err(k))
  if (!key) return
  const el = document.getElementById(`f-${key}`)
  if (!el) return
  el.scrollIntoView({ behavior: 'smooth', block: 'center' })
  const target = el.matches('input,select,textarea,button') ? el : el.querySelector('input,select,textarea,button,[tabindex]')
  target?.focus({ preventScroll: true })
}

/* ------------------------------------------------------------------ */
/* Watchers                                                            */
/* ------------------------------------------------------------------ */
watch(() => form.division, () => {
  if (!districts.value.includes(form.district)) form.district = ''
})
watch(() => form.building_height, () => {
  if (form.available_floor && !floorOptions.value.includes(String(form.available_floor))) form.available_floor = ''
})
watch(form, () => { dirty.value = true }, { deep: true })

watch(() => props.serverErrors, (errors) => {
  const mapped = {}
  for (const [key, msgs] of Object.entries(errors || {})) {
    mapped[key.split('.')[0]] ??= Array.isArray(msgs) ? msgs[0] : msgs
  }
  serverErrs.value = mapped
  if (Object.keys(mapped).length) {
    submitted.value = true
    focusFirstError()
  }
}, { deep: true, immediate: true })

watch(() => ({ ...form }), (next, prev) => {
  const keys = Object.keys(serverErrs.value)
  if (!keys.length) return
  const remaining = { ...serverErrs.value }
  keys.forEach((k) => { if (next[k] !== prev[k]) delete remaining[k] })
  if (Object.keys(remaining).length !== keys.length) serverErrs.value = remaining
})

/* ------------------------------------------------------------------ */
/* Submit                                                              */
/* ------------------------------------------------------------------ */
const NUMERIC = ['apartment_size', 'land_size', 'building_height', 'units_per_floor', 'bedrooms', 'bathrooms', 'balconies', 'handover_year', 'asking_price', 'final_price', 'company_commission', 'expected_commission', 'owner_expected_price', 'final_negotiated_price']
const OMIT = computed(() => {
  const omit = new Set(['property_code'])
  if (!isLand.value) ['land_size', 'land_unit'].forEach((k) => omit.add(k))
  if (!isBuilding.value) ['apartment_size', 'building_height', 'units_per_floor', 'available_floor', 'bathrooms', 'parking', 'gas', 'lift', 'generator'].forEach((k) => omit.add(k))
  if (!isResidential.value) ['bedrooms', 'balconies', 'drawing_room', 'dining_space'].forEach((k) => omit.add(k))
  if (!can.value.editAsking) omit.add('asking_price')
  if (!can.value.setFinalPrice) omit.add('final_price')
  if (!can.value.viewManagement) ['company_commission', 'expected_commission', 'final_negotiated_price'].forEach((k) => omit.add(k))
  if (!can.value.viewOwner) ['owner_name', 'owner_phone', 'owner_profession', 'owner_address', 'legal_status_note', 'owner_expected_price', 'relationship_manager_id'].forEach((k) => omit.add(k))
  return omit
})

function buildPayload() {
  const payload = {}
  for (const [k, v] of Object.entries(form)) {
    if (OMIT.value.has(k)) continue
    if (Array.isArray(v)) payload[k] = v
    else if (isBlank(v)) payload[k] = null
    else if (NUMERIC.includes(k)) payload[k] = Number(v)
    else payload[k] = typeof v === 'string' ? v.trim() : v
  }
  return payload
}

function toFormData(payload) {
  const fd = new FormData()
  for (const [k, v] of Object.entries(payload)) {
    if (Array.isArray(v)) {
      v.forEach((item) => {
        if (item instanceof File) fd.append(`${k}[]`, item)
        else if (item?.id != null) fd.append(`existing_${k}[]`, item.id)
      })
    } else if (v !== null && v !== undefined) {
      fd.append(k, v)
    }
  }
  return fd
}

function onSubmit() {
  submitted.value = true
  if (Object.keys(allErrors.value).length) {
    focusFirstError()
    return
  }
  const payload = buildPayload()
  emit('submit', payload, toFormData(payload))
}

function onCancel() {
  if (dirty.value && !window.confirm('You have unsaved changes. Leave without saving?')) return
  emit('cancel')
}

defineExpose({ markClean: () => { dirty.value = false } })
</script>

<template>
  <div class="pf">
    <header class="pf__top">
      <button type="button" class="pd__back" @click="router.push({ name: 'properties.index' })">← Back to inventory</button>
      <div>
        <h1>{{ mode === 'edit' ? 'Edit property' : 'Add new property' }}</h1>
        <p>Fields marked <span class="req">*</span> are required. Restricted sections are only shown to authorised roles.</p>
      </div>
    </header>

    <div class="pf__layout">
      <!-- Section navigation -->
      <nav class="pf__nav" aria-label="Form sections">
        <button
          v-for="s in SECTIONS"
          :key="s.id"
          type="button"
          class="nav-item"
          :class="{ 'is-active': activeSection === s.id }"
          :aria-current="activeSection === s.id ? 'true' : undefined"
          @click="goTo(s.id)"
        >
          <span class="nav-item__letter">{{ s.letter }}</span>
          <span class="nav-item__label">{{ s.label }}</span>
          <span v-if="errorCounts[s.id]" class="nav-item__err" :title="`${errorCounts[s.id]} field(s) need attention`">{{ errorCounts[s.id] }}</span>
        </button>
      </nav>

      <form class="pf__form" novalidate @submit.prevent="onSubmit">
        <!-- A. BASIC -->
        <section id="sec-basic" class="card">
          <header class="card__head">
            <span class="card__letter">A</span>
            <div><h2>Basic property information</h2><p>Identify the property and where it is located.</p></div>
          </header>
          <div class="grid">
            <FormField :label="L.property_code" for-id="f-property_code" hint="Generated automatically when you save">
              <input id="f-property_code" class="input" type="text" :value="form.property_code || 'Auto-generated'" disabled />
            </FormField>
            <FormField :label="L.property_name" for-id="f-property_name" required :error="err('property_name')" :span="2">
              <input v-model="form.property_name" v-bind="bind('property_name')" class="input" :class="inv('property_name')" type="text" maxlength="150" placeholder="e.g. Lakeview Residence, Block C – 5B" autocomplete="off" />
            </FormField>

            <FormField :label="L.category" for-id="f-category" required :error="err('category')">
              <select v-model="form.category" v-bind="bind('category')" class="input select" :class="inv('category')">
                <option value="" disabled>Select category</option>
                <option v-for="c in CATEGORIES" :key="c" :value="c">{{ c }}</option>
              </select>
            </FormField>
            <FormField :label="L.type" for-id="f-type" required :error="err('type')">
              <SegmentedControl v-model="form.type" :options="TYPES" v-bind="seg('type')" />
            </FormField>
            <FormField :label="L.status" for-id="f-status" required :error="err('status')">
              <select v-model="form.status" v-bind="bind('status')" class="input select" :class="inv('status')">
                <option value="" disabled>Select status</option>
                <option v-for="s in PROPERTY_STATUS" :key="s" :value="s">{{ s }}</option>
              </select>
            </FormField>

            <FormField :label="L.division" for-id="f-division" required :error="err('division')">
              <select v-model="form.division" v-bind="bind('division')" class="input select" :class="inv('division')">
                <option value="" disabled>Select division</option>
                <option v-for="d in DIVISIONS" :key="d" :value="d">{{ d }}</option>
              </select>
            </FormField>
            <FormField :label="L.district" for-id="f-district" required :error="err('district')">
              <select v-model="form.district" v-bind="bind('district')" class="input select" :class="inv('district')" :disabled="!form.division">
                <option value="" disabled>{{ form.division ? 'Select district' : 'Select a division first' }}</option>
                <option v-for="d in districts" :key="d" :value="d">{{ d }}</option>
              </select>
            </FormField>
            <FormField :label="L.area" for-id="f-area" required :error="err('area')">
              <input v-model="form.area" v-bind="bind('area')" class="input" :class="inv('area')" type="text" maxlength="100" placeholder="e.g. Gulshan 2, Bashundhara R/A" autocomplete="off" />
            </FormField>

            <FormField :label="L.address" for-id="f-address" required :error="err('address')" :span="3">
              <textarea v-model="form.address" v-bind="bind('address')" class="input textarea" :class="inv('address')" rows="2" maxlength="500" placeholder="House / plot no., road, block, landmark" />
            </FormField>

            <FormField :label="L.developer" for-id="f-developer" :error="err('developer')" hint="Pick from the list or type a new developer name" :span="3">
              <input v-model="form.developer" v-bind="bind('developer')" class="input" :class="inv('developer')" type="text" list="pf-developers" maxlength="120" placeholder="Search or type developer name" autocomplete="off" />
              <datalist id="pf-developers"><option v-for="d in developers" :key="d" :value="d" /></datalist>
            </FormField>

            <FormField :label="L.photos" for-id="f-photos" required :error="err('photos')" hint="The first photo is used as the cover. Up to 20 photos." :span="3">
              <FileUploader v-model="form.photos" kind="image" :max-files="20" :max-size-mb="5" v-bind="seg('photos')" />
            </FormField>
            <FormField :label="L.documents" for-id="f-documents" :error="err('documents')" hint="Stored privately. Only authorised roles can open them." :span="3">
              <FileUploader v-model="form.documents" kind="document" :max-files="15" :max-size-mb="10" v-bind="seg('documents')" />
            </FormField>
          </div>
        </section>

        <!-- B. PHYSICAL -->
        <section id="sec-physical" class="card">
          <header class="card__head">
            <span class="card__letter">B</span>
            <div><h2>Physical specifications</h2><p>The fields shown depend on the property category.</p></div>
          </header>

          <p v-if="!form.category" class="notice">Select a property category in section A to see the matching specifications.</p>

          <div v-else class="grid">
            <FormField v-if="isBuilding" :label="L.apartment_size" for-id="f-apartment_size" required :error="err('apartment_size')">
              <div class="affix" :class="inv('apartment_size')">
                <input v-model="form.apartment_size" v-bind="bind('apartment_size')" type="number" inputmode="decimal" min="0" step="any" placeholder="e.g. 1650" />
                <span class="affix__post">sq. ft.</span>
              </div>
            </FormField>

            <template v-if="isLand">
              <FormField :label="L.land_size" for-id="f-land_size" required :error="err('land_size')" :span="2">
                <div class="affix" :class="inv('land_size')">
                  <input v-model="form.land_size" v-bind="bind('land_size')" type="number" inputmode="decimal" min="0" step="any" placeholder="e.g. 5" />
                  <select v-model="form.land_unit" class="affix__select" aria-label="Land size unit">
                    <option v-for="u in LAND_UNITS" :key="u" :value="u">{{ u }}</option>
                  </select>
                </div>
              </FormField>
            </template>

            <template v-if="isBuilding">
              <FormField :label="L.building_height" for-id="f-building_height" :error="err('building_height')" hint="Number of floors above ground">
                <div class="affix" :class="inv('building_height')">
                  <span class="affix__pre">G +</span>
                  <input v-model="form.building_height" v-bind="bind('building_height')" type="number" inputmode="numeric" min="1" step="1" placeholder="e.g. 9" />
                </div>
              </FormField>
              <FormField :label="L.units_per_floor" for-id="f-units_per_floor" :error="err('units_per_floor')">
                <input v-model="form.units_per_floor" v-bind="bind('units_per_floor')" class="input" :class="inv('units_per_floor')" type="number" inputmode="numeric" min="1" step="1" placeholder="e.g. 4" />
              </FormField>
              <FormField :label="L.available_floor" for-id="f-available_floor" :error="err('available_floor')">
                <select v-model="form.available_floor" v-bind="bind('available_floor')" class="input select" :class="inv('available_floor')">
                  <option value="">Select floor</option>
                  <option v-for="fl in floorOptions" :key="fl" :value="fl">{{ fl === 'Ground' ? 'Ground floor' : `Floor ${fl}` }}</option>
                </select>
              </FormField>
            </template>

            <FormField :label="L.facing" for-id="f-facing" :error="err('facing')">
              <select v-model="form.facing" v-bind="bind('facing')" class="input select" :class="inv('facing')">
                <option value="">Select facing</option>
                <option v-for="d in FACING" :key="d" :value="d">{{ d }}</option>
              </select>
            </FormField>

            <template v-if="isResidential">
              <FormField :label="L.bedrooms" for-id="f-bedrooms" required :error="err('bedrooms')">
                <input v-model="form.bedrooms" v-bind="bind('bedrooms')" class="input" :class="inv('bedrooms')" type="number" inputmode="numeric" min="1" step="1" placeholder="e.g. 3" />
              </FormField>
            </template>
            <template v-if="isBuilding">
              <FormField :label="L.bathrooms" for-id="f-bathrooms" required :error="err('bathrooms')">
                <input v-model="form.bathrooms" v-bind="bind('bathrooms')" class="input" :class="inv('bathrooms')" type="number" inputmode="numeric" min="0" step="1" placeholder="e.g. 3" />
              </FormField>
            </template>
            <template v-if="isResidential">
              <FormField :label="L.balconies" for-id="f-balconies" :error="err('balconies')">
                <input v-model="form.balconies" v-bind="bind('balconies')" class="input" :class="inv('balconies')" type="number" inputmode="numeric" min="0" step="1" placeholder="e.g. 2" />
              </FormField>
              <FormField :label="L.drawing_room" for-id="f-drawing_room" :error="err('drawing_room')">
                <SegmentedControl v-model="form.drawing_room" :options="YES_NO" v-bind="seg('drawing_room')" />
              </FormField>
              <FormField :label="L.dining_space" for-id="f-dining_space" :error="err('dining_space')">
                <SegmentedControl v-model="form.dining_space" :options="YES_NO" v-bind="seg('dining_space')" />
              </FormField>
            </template>

            <template v-if="isBuilding">
              <FormField :label="L.parking" for-id="f-parking" required :error="err('parking')">
                <SegmentedControl v-model="form.parking" :options="PARKING" v-bind="seg('parking')" />
              </FormField>
              <FormField :label="L.gas" for-id="f-gas" required :error="err('gas')">
                <SegmentedControl v-model="form.gas" :options="GAS" v-bind="seg('gas')" />
              </FormField>
              <FormField :label="L.lift" for-id="f-lift" required :error="err('lift')">
                <SegmentedControl v-model="form.lift" :options="YES_NO" v-bind="seg('lift')" />
              </FormField>
              <FormField :label="L.generator" for-id="f-generator" required :error="err('generator')">
                <SegmentedControl v-model="form.generator" :options="YES_NO" v-bind="seg('generator')" />
              </FormField>
            </template>
          </div>
        </section>

        <!-- C. LEGAL -->
        <section id="sec-legal" class="card">
          <header class="card__head">
            <span class="card__letter">C</span>
            <div><h2>Property availability and legal information</h2><p>Handover, paperwork and current inventory state.</p></div>
          </header>
          <div class="grid">
            <FormField :label="L.status" for-id="f-status-c" required :error="err('status')" hint="Same value as in section A">
              <select v-model="form.status" v-bind="bind('status', 'status-c')" class="input select" :class="inv('status')">
                <option value="" disabled>Select status</option>
                <option v-for="s in PROPERTY_STATUS" :key="s" :value="s">{{ s }}</option>
              </select>
            </FormField>
            <FormField :label="L.handover_year" for-id="f-handover_year" :required="needsHandover" :error="err('handover_year')" :hint="needsHandover ? '' : 'Optional for new and used properties'">
              <select v-model="form.handover_year" v-bind="bind('handover_year')" class="input select" :class="inv('handover_year')">
                <option value="">Select year</option>
                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
              </select>
            </FormField>
            <FormField :label="L.handover_status" for-id="f-handover_status" :required="needsHandover" :error="err('handover_status')">
              <SegmentedControl v-model="form.handover_status" :options="HANDOVER_STATUS" v-bind="seg('handover_status')" />
            </FormField>

            <FormField :label="L.paper_status" for-id="f-paper_status" required :error="err('paper_status')">
              <select v-model="form.paper_status" v-bind="bind('paper_status')" class="input select" :class="inv('paper_status')">
                <option value="" disabled>Select paper status</option>
                <option v-for="o in PAPER_STATUS" :key="o" :value="o">{{ o }}</option>
              </select>
            </FormField>
            <FormField :label="L.legal_verification" for-id="f-legal_verification" required :error="err('legal_verification')">
              <select v-model="form.legal_verification" v-bind="bind('legal_verification')" class="input select" :class="inv('legal_verification')">
                <option value="" disabled>Select verification status</option>
                <option v-for="o in LEGAL_VERIFICATION" :key="o" :value="o">{{ o }}</option>
              </select>
            </FormField>
            <FormField :label="L.mortgage_status" for-id="f-mortgage_status" required :error="err('mortgage_status')">
              <select v-model="form.mortgage_status" v-bind="bind('mortgage_status')" class="input select" :class="inv('mortgage_status')">
                <option value="" disabled>Select mortgage status</option>
                <option v-for="o in MORTGAGE_STATUS" :key="o" :value="o">{{ o }}</option>
              </select>
            </FormField>

            <FormField :label="L.ownership_type" for-id="f-ownership_type" required :error="err('ownership_type')">
              <select v-model="form.ownership_type" v-bind="bind('ownership_type')" class="input select" :class="inv('ownership_type')">
                <option value="" disabled>Select ownership type</option>
                <option v-for="o in OWNERSHIP" :key="o" :value="o">{{ o }}</option>
              </select>
            </FormField>
            <FormField :label="L.inventory_status" for-id="f-inventory_status" required :error="err('inventory_status')">
              <select v-model="form.inventory_status" v-bind="bind('inventory_status')" class="input select" :class="inv('inventory_status')">
                <option value="" disabled>Select inventory status</option>
                <option v-for="o in INVENTORY_STATUS" :key="o" :value="o">{{ o }}</option>
              </select>
            </FormField>
          </div>
        </section>

        <!-- D. PRICING -->
        <section id="sec-pricing" class="card">
          <header class="card__head">
            <span class="card__letter">D</span>
            <div><h2>Pricing information</h2><p>Amounts are in Bangladeshi taka (৳).</p></div>
          </header>
          <div class="grid">
            <FormField :label="L.asking_price" for-id="f-asking_price" :required="can.editAsking" :error="err('asking_price')" :locked="can.editAsking ? '' : 'Sales team only'">
              <div class="affix" :class="inv('asking_price')">
                <span class="affix__pre">৳</span>
                <input v-model="form.asking_price" v-bind="bind('asking_price')" type="number" inputmode="decimal" min="0" step="any" :disabled="!can.editAsking" :placeholder="can.editAsking ? 'e.g. 12500000' : 'Restricted'" />
              </div>
            </FormField>
            <FormField :label="pricePerLabel" for-id="f-price_per_unit" hint="Calculated from asking price and size">
              <input id="f-price_per_unit" class="input" type="text" :value="pricePerDisplay" placeholder="Auto-calculated" disabled />
            </FormField>
            <FormField :label="L.negotiable" for-id="f-negotiable" :error="err('negotiable')">
              <SegmentedControl v-model="form.negotiable" :options="YES_NO" v-bind="seg('negotiable')" />
            </FormField>

            <FormField :label="L.final_price" for-id="f-final_price" :error="err('final_price')" :locked="can.setFinalPrice ? '' : 'Authorised only'">
              <div class="affix" :class="inv('final_price')">
                <span class="affix__pre">৳</span>
                <input v-model="form.final_price" v-bind="bind('final_price')" type="number" inputmode="decimal" min="0" step="any" :disabled="!can.setFinalPrice" :placeholder="can.setFinalPrice ? 'Agreed closing price' : 'Restricted'" />
              </div>
            </FormField>
            <FormField :label="L.company_commission" for-id="f-company_commission" :error="err('company_commission')" :locked="can.viewManagement ? '' : 'Management only'">
              <div class="affix" :class="inv('company_commission')">
                <span class="affix__pre">৳</span>
                <input v-model="form.company_commission" v-bind="bind('company_commission')" type="number" inputmode="decimal" min="0" step="any" :disabled="!can.viewManagement" :placeholder="can.viewManagement ? 'Commission amount' : 'Restricted'" />
              </div>
            </FormField>
            <FormField :label="L.expected_commission" for-id="f-expected_commission" :error="err('expected_commission')" :locked="can.viewManagement ? '' : 'Management only'">
              <div class="affix" :class="inv('expected_commission')">
                <span class="affix__pre">৳</span>
                <input v-model="form.expected_commission" v-bind="bind('expected_commission')" type="number" inputmode="decimal" min="0" step="any" :disabled="!can.viewManagement" :placeholder="can.viewManagement ? 'Expected amount' : 'Restricted'" />
              </div>
            </FormField>
          </div>
        </section>

        <!-- E. OWNER -->
        <section id="sec-owner" class="card card--restricted">
          <header class="card__head">
            <span class="card__letter">E</span>
            <div><h2>Restricted owner information</h2><p>Visible only to authorised personnel. Every view is logged.</p></div>
            <span class="chip-lock">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2" /><path d="M8 11V7a4 4 0 0 1 8 0v4" /></svg>
              Restricted access
            </span>
          </header>

          <p v-if="!can.viewOwner" class="notice notice--lock">You don't have permission to view or edit owner details. Ask a manager if you need access.</p>

          <div v-else class="grid">
            <FormField :label="L.owner_name" for-id="f-owner_name" required :error="err('owner_name')">
              <input v-model="form.owner_name" v-bind="bind('owner_name')" class="input" :class="inv('owner_name')" type="text" maxlength="120" placeholder="Full name of the owner" autocomplete="off" />
            </FormField>
            <FormField :label="L.owner_phone" for-id="f-owner_phone" required :error="err('owner_phone')">
              <input v-model="form.owner_phone" v-bind="bind('owner_phone')" class="input" :class="inv('owner_phone')" type="tel" inputmode="tel" maxlength="16" placeholder="01712 345678" autocomplete="off" />
            </FormField>
            <FormField :label="L.owner_profession" for-id="f-owner_profession" :error="err('owner_profession')">
              <input v-model="form.owner_profession" v-bind="bind('owner_profession')" class="input" :class="inv('owner_profession')" type="text" maxlength="100" placeholder="e.g. Businessman, Doctor, Expatriate" autocomplete="off" />
            </FormField>

            <FormField :label="L.owner_address" for-id="f-owner_address" :error="err('owner_address')" :span="3">
              <textarea v-model="form.owner_address" v-bind="bind('owner_address')" class="input textarea" :class="inv('owner_address')" rows="2" maxlength="500" placeholder="Exact address, floor and unit number as given by the owner" />
            </FormField>
            <FormField :label="L.legal_status_note" for-id="f-legal_status_note" :error="err('legal_status_note')" :span="3">
              <textarea v-model="form.legal_status_note" v-bind="bind('legal_status_note')" class="input textarea" :class="inv('legal_status_note')" rows="2" maxlength="1000" placeholder="Disputes, inheritance, power of attorney or other legal context" />
            </FormField>

            <FormField :label="L.owner_expected_price" for-id="f-owner_expected_price" :error="err('owner_expected_price')">
              <div class="affix" :class="inv('owner_expected_price')">
                <span class="affix__pre">৳</span>
                <input v-model="form.owner_expected_price" v-bind="bind('owner_expected_price')" type="number" inputmode="decimal" min="0" step="any" placeholder="Price the owner wants" />
              </div>
            </FormField>
            <FormField :label="L.final_negotiated_price" for-id="f-final_negotiated_price" :error="err('final_negotiated_price')" :locked="can.viewManagement ? '' : 'Management only'">
              <div class="affix" :class="inv('final_negotiated_price')">
                <span class="affix__pre">৳</span>
                <input v-model="form.final_negotiated_price" v-bind="bind('final_negotiated_price')" type="number" inputmode="decimal" min="0" step="any" :disabled="!can.viewManagement" :placeholder="can.viewManagement ? 'Agreed with the owner' : 'Restricted'" />
              </div>
            </FormField>
            <FormField :label="L.relationship_manager_id" for-id="f-relationship_manager_id" :required="managers.length > 0" :error="err('relationship_manager_id')">
              <select v-model="form.relationship_manager_id" v-bind="bind('relationship_manager_id')" class="input select" :class="inv('relationship_manager_id')">
                <option value="" disabled>Select manager</option>
                <option v-for="m in managers" :key="m.id" :value="m.id">{{ m.name }}</option>
              </select>
            </FormField>
          </div>
        </section>

        <!-- F. REMARKS -->
        <section id="sec-remarks" class="card">
          <header class="card__head">
            <span class="card__letter">F</span>
            <div><h2>Internal remarks</h2><p>Only visible to staff. Never shown to clients.</p></div>
          </header>
          <div class="grid">
            <FormField
              v-for="n in INTERNAL_NOTES"
              :key="n.key"
              :label="n.label"
              :for-id="`f-${n.key}`"
              :required="n.required"
              :error="err(n.key)"
              :span="n.span"
            >
              <textarea v-model="form[n.key]" v-bind="bind(n.key)" class="input textarea" :class="inv(n.key)" :rows="n.rows" maxlength="2000" :placeholder="n.placeholder" />
            </FormField>
          </div>
        </section>

        <!-- Sticky action bar -->
        <div class="bar">
          <p v-if="submitted && totalErrors" class="bar__msg" role="alert">
            {{ totalErrors }} {{ totalErrors === 1 ? 'field needs' : 'fields need' }} attention
          </p>
          <p v-else class="bar__msg bar__msg--muted">{{ dirty ? 'Unsaved changes' : 'No changes yet' }}</p>
          <button type="button" class="btn btn--ghost" :disabled="saving" @click="onCancel">Cancel</button>
          <button type="submit" class="btn btn--primary" :disabled="saving">
            <span v-if="saving" class="spinner" aria-hidden="true" />
            {{ saving ? 'Saving…' : mode === 'edit' ? 'Save changes' : 'Save property' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.pf {
  --c-bg: #f3f5f7; --c-surface: #fff; --c-border: #dfe4ea; --c-border-strong: #c5cdd6;
  --c-text: #17212b; --c-muted: #5f6b7a; --c-primary: #0f766e; --c-primary-dark: #0b5e57;
  --c-primary-soft: #e3f4f1; --c-danger: #dc2626; --c-danger-soft: #fef2f2;
  background: var(--c-bg); color: var(--c-text); min-height: 100%;
  padding: 28px clamp(16px, 3vw, 40px) 0; font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
}
.pf__top h1 { margin: 0; font-size: 24px; letter-spacing: -.01em; }
.pf__top p { margin: 6px 0 0; color: var(--c-muted); font-size: 14px; }
.req { color: var(--c-danger); font-weight: 700; }

.pf__layout { display: grid; grid-template-columns: 220px minmax(0, 1fr); gap: 28px; margin-top: 24px; align-items: start; }
.pf__nav { position: sticky; top: 20px; display: flex; flex-direction: column; gap: 2px; }
.nav-item {
  display: flex; align-items: center; gap: 10px; padding: 9px 10px; border: 0; border-radius: 9px;
  background: transparent; color: var(--c-muted); font: inherit; font-size: 13.5px; font-weight: 500; text-align: left; cursor: pointer;
}
.nav-item:hover { background: rgba(15, 118, 110, .07); color: var(--c-text); }
.nav-item.is-active { background: var(--c-surface); color: var(--c-primary-dark); font-weight: 600; box-shadow: 0 0 0 1px var(--c-border); }
.nav-item:focus-visible { outline: 2px solid var(--c-primary); outline-offset: 1px; }
.nav-item__letter {
  display: grid; place-items: center; width: 22px; height: 22px; border-radius: 6px; flex: none;
  background: #e6eaef; color: var(--c-muted); font-size: 12px; font-weight: 700;
}
.nav-item.is-active .nav-item__letter { background: var(--c-primary); color: #fff; }
.nav-item__label { flex: 1; }
.nav-item__err { min-width: 20px; padding: 1px 6px; border-radius: 999px; background: var(--c-danger); color: #fff; font-size: 11px; font-weight: 700; text-align: center; }

.pf__form { display: flex; flex-direction: column; gap: 20px; min-width: 0; padding-bottom: 96px; }
.card { background: var(--c-surface); border: 1px solid var(--c-border); border-radius: 14px; padding: 22px 24px 26px; scroll-margin-top: 20px; }
.card--restricted { border-color: #f0d9a8; background: linear-gradient(#fffdf7, #fff 120px); }
.card__head { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
.card__head > div { flex: 1; }
.card__head h2 { margin: 0; font-size: 16.5px; }
.card__head p { margin: 3px 0 0; font-size: 13px; color: var(--c-muted); }
.card__letter { display: grid; place-items: center; width: 32px; height: 32px; flex: none; border-radius: 9px; background: var(--c-primary-soft); color: var(--c-primary-dark); font-weight: 700; }
.chip-lock { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; color: #8a5a00; background: #fff1cf; white-space: nowrap; }

.grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px 20px; }
.notice { margin: 0; padding: 14px 16px; border-radius: 10px; background: #f3f6f9; color: var(--c-muted); font-size: 13.5px; }
.notice--lock { background: #fff6e0; color: #7a5200; }

/* Inputs */
.input {
  width: 100%; min-height: 40px; padding: 9px 12px; font: inherit; font-size: 14px; color: var(--c-text);
  background: var(--c-surface); border: 1px solid var(--c-border-strong); border-radius: 9px;
  transition: border-color .15s, box-shadow .15s; box-sizing: border-box;
}
.input::placeholder, .affix input::placeholder { color: #9aa5b1; }
.input:hover:not(:disabled) { border-color: #9fb0bf; }
.input:focus, .affix:focus-within { outline: none; border-color: var(--c-primary); box-shadow: 0 0 0 3px rgba(15, 118, 110, .15); }
.input:disabled { background: #f3f5f7; color: #7b8794; cursor: not-allowed; }
.input.is-invalid, .affix.is-invalid { border-color: var(--c-danger); background: var(--c-danger-soft); }
.input.is-invalid:focus, .affix.is-invalid:focus-within { box-shadow: 0 0 0 3px rgba(220, 38, 38, .15); }
.textarea { resize: vertical; line-height: 1.5; min-height: 64px; }
.select {
  appearance: none; padding-right: 34px; cursor: pointer;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' fill='none' stroke='%235f6b7a' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M1 1.5l5 5 5-5'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 12px center;
}
input[type='number'] { -moz-appearance: textfield; appearance: textfield; }
input[type='number']::-webkit-outer-spin-button, input[type='number']::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

.affix {
  display: flex; align-items: stretch; min-height: 40px; background: var(--c-surface);
  border: 1px solid var(--c-border-strong); border-radius: 9px; overflow: hidden; transition: border-color .15s, box-shadow .15s;
}
.affix input { flex: 1; min-width: 0; padding: 9px 12px; border: 0; background: transparent; font: inherit; font-size: 14px; color: var(--c-text); outline: none; }
.affix input:disabled { cursor: not-allowed; color: #7b8794; background: #f3f5f7; }
.affix__pre, .affix__post { display: grid; place-items: center; padding: 0 12px; background: #f3f5f7; color: var(--c-muted); font-size: 13px; font-weight: 600; white-space: nowrap; }
.affix__pre { border-right: 1px solid var(--c-border); }
.affix__post { border-left: 1px solid var(--c-border); }
.affix__select { border: 0; border-left: 1px solid var(--c-border); background: #f3f5f7; color: var(--c-text); font: inherit; font-size: 13px; font-weight: 600; padding: 0 10px; cursor: pointer; outline: none; }

/* Action bar */
.bar {
  position: sticky; bottom: 0; z-index: 5; display: flex; align-items: center; justify-content: flex-end; gap: 12px;
  margin: 0 -8px; padding: 14px 20px; background: rgba(255, 255, 255, .92); backdrop-filter: blur(8px);
  border: 1px solid var(--c-border); border-radius: 14px 14px 0 0; box-shadow: 0 -6px 20px rgba(16, 24, 40, .06);
}
.bar__msg { margin: 0 auto 0 0; font-size: 13px; font-weight: 600; color: var(--c-danger); }
.bar__msg--muted { color: var(--c-muted); font-weight: 500; }
.btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 40px; padding: 0 20px;
  border-radius: 9px; font: inherit; font-size: 14px; font-weight: 600; cursor: pointer; border: 1px solid transparent; transition: background .15s, border-color .15s;
}
.btn:disabled { opacity: .6; cursor: not-allowed; }
.btn:focus-visible { outline: 2px solid var(--c-primary); outline-offset: 2px; }
.btn--ghost { background: var(--c-surface); border-color: var(--c-border-strong); color: var(--c-text); }
.btn--ghost:hover:not(:disabled) { background: #f3f5f7; }
.btn--primary { background: var(--c-primary); color: #fff; }
.btn--primary:hover:not(:disabled) { background: var(--c-primary-dark); }
.spinner { width: 14px; height: 14px; border: 2px solid rgba(255, 255, 255, .4); border-top-color: #fff; border-radius: 50%; animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.pd__back, .pd__link { border: 0; background: none; padding: 0; font: inherit; font-weight: 600; color: var(--c-primary); cursor: pointer; }
.pd__back:hover, .pd__link:hover { text-decoration: underline; }
.pd__back:focus-visible { outline: 2px solid var(--c-primary); outline-offset: 2px; }

@media (max-width: 1100px) {
  .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 900px) {
  .pf__layout { grid-template-columns: 1fr; gap: 16px; }
  .pf__nav { position: sticky; top: 0; z-index: 6; flex-direction: row; overflow-x: auto; background: var(--c-bg); padding: 8px 0; }
  .nav-item { white-space: nowrap; }
  .grid { grid-template-columns: 1fr; }
  .card { padding: 18px 16px 22px; }
}
@media (prefers-reduced-motion: reduce) {
  .spinner { animation-duration: 2s; }
  * { transition: none !important; scroll-behavior: auto !important; }
}
</style>