# Search filter reference

| UI filter | API parameter | Property column / logic |
|---|---|---|
| Keyword | q | name, property_code, area, district, developer |
| Developer | developer | developer |
| Area | area | area, district |
| Floor | floor | available_floor contains |
| Market | market_type | market_type |
| Handover from/to | handover_year_from/to | handover_year |
| Building G+ min/max | building_height_min/max | building_ground_plus |
| Minimum parking | parking_min | parking_spaces >= |
| Gas | gas_connection | gas_connection |
| Paper | paper_status | paper_status |
| Verification | legal_verification | legal_verification |
| Asking price | asking_price_min/max | asking_price |
| Price / sqft | price_per_sqft_min/max | asking_price / apartment_size_sqft |
| Owner direct | owner_direct | is_owner_direct |
| Recently added | recent_days | created_at since N days |
| Sort | sort | newest / price_low / price_high / size_large |

All provided filters are AND-combined. Blank values are omitted.
