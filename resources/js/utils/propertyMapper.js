/**
 * Translates between the UI form values (labels such as "Semi-ready", "Yes", "3+")
 * and the API values (lowercase snake_case enums, booleans, integers).
 * Shared by the index, create, edit and show pages so the mapping lives in one place.
 */

const snake = (v) => String(v).trim().toLowerCase().replace(/[\s-]+/g, '_')
const cap = (v) => (v ? String(v).charAt(0).toUpperCase() + String(v).slice(1) : '')
const enumOf = (v) => (v ? snake(v) : null)
const yesNo = (v) => (v === 'Yes' ? true : v === 'No' ? false : null)
const fromBool = (v) => (v === true || v === 1 ? 'Yes' : v === false || v === 0 ? 'No' : '')
const num = (v) => (v === null || v === undefined || v === '' ? '' : Number(v))

export const LABELS = {
  property_status: { new: 'New', used: 'Used', semi_ready: 'Semi-ready', under_construction: 'Under-construction' },
  handover_status: { handed_over: 'Handed over', upcoming: 'Upcoming' },
  paper_status: { registered: 'Registered', non_registered: 'Non-registered', on_process: 'On process' },
  legal_verification: { verified: 'Verified', non_verified: 'Non-verified', pending: 'Pending' },
  mortgage_status: { mortgaged: 'Mortgaged', non_mortgaged: 'Non-mortgaged', unknown: 'Unknown' },
  gas_connection: { titas: 'Titas', lpg: 'LPG', none: 'No Gas' },
}
const label = (group, v) => (v ? LABELS[group][v] ?? cap(v) : '')

const PARKING_TO_API = { No: 0, 1: 1, 2: 2, '3+': 3 }
const GAS_TO_API = { Titas: 'titas', LPG: 'lpg', 'No Gas': 'none' }
const BOOLEAN_KEYS = ['drawing_room', 'dining_space', 'has_lift', 'has_generator', 'is_negotiable']

/** API validation error keys -> form field keys */
export const API_TO_FORM = {
  name: 'property_name', market_type: 'type', detailed_address: 'address', property_status: 'status',
  apartment_size_sqft: 'apartment_size', land_size_unit: 'land_unit', building_ground_plus: 'building_height',
  parking_spaces: 'parking', gas_connection: 'gas', has_lift: 'lift', has_generator: 'generator', is_negotiable: 'negotiable',
}

export function mapServerErrors(errors = {}) {
  return Object.fromEntries(
    Object.entries(errors)
      .filter(([k]) => k !== 'general')
      .map(([k, v]) => [API_TO_FORM[k.split('.')[0]] ?? k.split('.')[0], v]),
  )
}

/** Form payload -> API body. With keepNulls (edit), cleared optional fields are sent as null. */
export function toApi(p, { keepNulls = false } = {}) {
  const body = {
    name: p.property_name,
    category: enumOf(p.category),
    market_type: enumOf(p.type),
    division: p.division,
    district: p.district,
    area: p.area,
    detailed_address: p.address,
    developer: p.developer,
    property_status: enumOf(p.status),
    apartment_size_sqft: p.apartment_size,
    land_size: p.land_size,
    land_size_unit: enumOf(p.land_unit),
    building_ground_plus: p.building_height,
    units_per_floor: p.units_per_floor,
    available_floor: p.available_floor,
    facing: enumOf(p.facing),
    bedrooms: p.bedrooms,
    bathrooms: p.bathrooms,
    balconies: p.balconies,
    drawing_room: yesNo(p.drawing_room),
    dining_space: yesNo(p.dining_space),
    parking_spaces: p.parking != null ? PARKING_TO_API[p.parking] ?? null : null,
    gas_connection: p.gas ? GAS_TO_API[p.gas] ?? null : null,
    has_lift: yesNo(p.lift),
    has_generator: yesNo(p.generator),
    handover_year: p.handover_year,
    handover_status: enumOf(p.handover_status),
    paper_status: enumOf(p.paper_status),
    legal_verification: enumOf(p.legal_verification),
    mortgage_status: enumOf(p.mortgage_status),
    ownership_type: enumOf(p.ownership_type),
    inventory_status: enumOf(p.inventory_status),
    asking_price: p.asking_price,
    is_negotiable: yesNo(p.negotiable),
    description: p.description,
  }

  return Object.fromEntries(
    Object.entries(body).filter(([key, v]) => {
      if (v === undefined) return false
      if (v === null || v === '') return keepNulls && !BOOLEAN_KEYS.includes(key)
      return true
    }).map(([key, v]) => [key, v === '' ? null : v]),
  )
}

/** API property -> PropertyForm initial values (also used for the details page). */
export function formFromApi(p) {
  const parking = p.parking_spaces
  return {
    property_code: p.property_code ?? '',
    property_name: p.name ?? '',
    category: cap(p.category),
    type: cap(p.market_type),
    division: p.division ?? '',
    district: p.district ?? '',
    area: p.area ?? '',
    address: p.detailed_address ?? '',
    developer: p.developer ?? '',
    status: label('property_status', p.property_status),
    apartment_size: num(p.apartment_size_sqft),
    land_size: num(p.land_size),
    land_unit: p.land_size_unit ?? 'katha',
    building_height: num(p.building_ground_plus),
    units_per_floor: num(p.units_per_floor),
    available_floor: p.available_floor ?? '',
    facing: cap(p.facing),
    bedrooms: num(p.bedrooms),
    bathrooms: num(p.bathrooms),
    balconies: num(p.balconies),
    drawing_room: fromBool(p.drawing_room),
    dining_space: fromBool(p.dining_space),
    parking: parking == null ? '' : parking >= 3 ? '3+' : parking === 0 ? 'No' : String(parking),
    gas: label('gas_connection', p.gas_connection),
    lift: fromBool(p.has_lift),
    generator: fromBool(p.has_generator),
    handover_year: num(p.handover_year),
    handover_status: label('handover_status', p.handover_status),
    paper_status: label('paper_status', p.paper_status),
    legal_verification: label('legal_verification', p.legal_verification),
    mortgage_status: label('mortgage_status', p.mortgage_status),
    ownership_type: cap(p.ownership_type),
    inventory_status: cap(p.inventory_status),
    asking_price: num(p.asking_price),
    negotiable: fromBool(p.is_negotiable),
    description: p.description ?? '',
    created_at: p.created_at,
    updated_at: p.updated_at ?? p.created_at,
  }
}

/** API property -> row for PropertyList. */
export function listItemFromApi(p) {
  const price = Number(p.asking_price)
  const size = Number(p.apartment_size_sqft)
  return {
    id: p.id,
    property_code: p.property_code,
    property_name: p.name,
    category: cap(p.category),
    type: cap(p.market_type),
    division: p.division,
    district: p.district,
    area: p.area,
    apartment_size: p.apartment_size_sqft,
    land_size: p.land_size,
    land_unit: p.land_size_unit,
    bedrooms: p.bedrooms,
    bathrooms: p.bathrooms,
    asking_price: p.asking_price,
    price_per_sqft: price > 0 && size > 0 ? Math.round(price / size) : null,
    inventory_status: cap(p.inventory_status),
    legal_verification: label('legal_verification', p.legal_verification),
    cover_photo: null,
    updated_at: p.updated_at ?? p.created_at,
  }
}

/** UI filter/sort query -> API params. */
const SORT_KEYS = { property_name: 'name', updated_at: 'created_at' }
export function queryToParams(query = {}) {
  const params = { page: query.page ?? 1, per_page: query.per_page ?? 15 }
  if (query.search) params.search = query.search
  if (query.category) params.category = snake(query.category)
  if (query.legal_verification) params.legal_verification = snake(query.legal_verification)
  if (query.inventory_status) params.inventory_status = snake(query.inventory_status)
  if (query.sort_by) {
    params.sort_by = SORT_KEYS[query.sort_by] ?? query.sort_by
    params.sort_dir = query.sort_dir
  }
  return params
}