/**
 * Field labels for the Property create/edit form (keys match the payload).
 * Keep UI copy here so the form, details page and table stay consistent.
 */
export const PROPERTY_FIELD_LABELS = Object.freeze({
  // A. Basic information
  property_code: 'Property ID',
  property_name: 'Property Name',
  category: 'Property Category',
  type: 'Property Type',
  status: 'Property Status',
  division: 'Division',
  district: 'District',
  area: 'Area',
  address: 'Detailed Address',
  developer: 'Developer',
  photos: 'Property Photos',
  documents: 'Property Documents',

  // B. Physical specifications
  apartment_size: 'Apartment Size',
  land_size: 'Land Size',
  land_unit: 'Land Unit',
  building_height: 'Building Height',
  units_per_floor: 'Unit per Floor',
  available_floor: 'Available Floor',
  facing: 'Facing',
  bedrooms: 'Bedroom',
  bathrooms: 'Bathroom',
  balconies: 'Balcony',
  drawing_room: 'Drawing Room',
  dining_space: 'Dining Space',
  parking: 'Parking',
  gas: 'Gas',
  lift: 'Lift',
  generator: 'Generator',

  // C. Availability and legal
  handover_year: 'Handover Year',
  handover_status: 'Handover Status',
  paper_status: 'Paper Status',
  legal_verification: 'Legal Verification',
  mortgage_status: 'Mortgage Status',
  ownership_type: 'Ownership Type',
  inventory_status: 'Inventory Status',

  // D. Pricing
  asking_price: 'Asking Price',
  negotiable: 'Negotiable',
  final_price: 'Final Price',
  company_commission: 'Company Commission',
  expected_commission: 'Expected Commission',

  // E. Owner information
  owner_name: 'Owner Name',
  owner_phone: 'Owner Number',
  owner_profession: 'Owner Profession',
  owner_address: 'Property Address in Detail',
  legal_status_note: 'Legal Status Note',
  owner_expected_price: "Owner's Expected Price",
  final_negotiated_price: 'Final Negotiated Price',
  relationship_manager_id: 'Assigned Relationship Manager',

  // F. Internal remarks
  description: 'Property Description',
  selling_points: 'Special Selling Points',
  negotiation_history: 'Negotiation History',
  visit_feedback: 'Client Visit Feedback',
  legal_remarks: 'Legal / Document Remarks',
  internal_notes: 'Internal Communication Notes',
})

/** Dynamic label: depends on whether the property is land or a building. */
export const pricePerUnitLabel = (isLand, landUnit) =>
  isLand ? `Price per ${landUnit}` : 'Price per Sq. Ft.'

/** Safe lookup helper with a readable fallback for unknown keys. */
export const labelFor = (key) =>
  PROPERTY_FIELD_LABELS[key] ??
  key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())