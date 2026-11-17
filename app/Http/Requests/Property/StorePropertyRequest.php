<?php

declare(strict_types=1);

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:180'],
            'category' => ['required', Rule::in(['apartment', 'flat', 'duplex', 'penthouse', 'land', 'plot', 'commercial'])],
            'market_type' => ['required', Rule::in(['primary', 'secondary'])],
            'division' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'area' => ['required', 'string', 'max:150'],
            'detailed_address' => ['nullable', 'string', 'max:2000'],
            'developer' => ['nullable', 'string', 'max:180'],
            'property_status' => ['required', Rule::in(['new', 'used', 'semi_ready', 'under_construction'])],
            'apartment_size_sqft' => ['nullable', 'numeric', 'min:0'],
            'land_size' => ['nullable', 'numeric', 'min:0'],
            'land_size_unit' => ['nullable', Rule::in(['katha', 'decimal', 'bigha'])],
            'building_ground_plus' => ['nullable', 'integer', 'min:0', 'max:200'],
            'units_per_floor' => ['nullable', 'integer', 'min:0', 'max:500'],
            'available_floor' => ['nullable', 'string', 'max:100'],
            'facing' => ['nullable', Rule::in(['north', 'south', 'east', 'west', 'corner'])],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:50'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:50'],
            'balconies' => ['nullable', 'integer', 'min:0', 'max:50'],
            'drawing_room' => ['sometimes', 'boolean'],
            'dining_space' => ['sometimes', 'boolean'],
            'parking_spaces' => ['nullable', 'integer', 'min:0', 'max:20'],
            'gas_connection' => ['nullable', Rule::in(['titas', 'lpg', 'none'])],
            'has_lift' => ['sometimes', 'boolean'],
            'has_generator' => ['sometimes', 'boolean'],
            'handover_year' => ['nullable', 'integer', 'min:1950', 'max:2200'],
            'handover_status' => ['nullable', Rule::in(['handed_over', 'upcoming'])],
            'paper_status' => ['nullable', Rule::in(['registered', 'non_registered', 'on_process'])],
            'legal_verification' => ['nullable', Rule::in(['verified', 'non_verified', 'pending'])],
            'mortgage_status' => ['nullable', Rule::in(['mortgaged', 'non_mortgaged', 'unknown'])],
            'ownership_type' => ['nullable', Rule::in(['individual', 'joint', 'company'])],
            'inventory_status' => ['required', Rule::in(['available', 'reserved', 'sold', 'withdrawn'])],
            'asking_price' => ['nullable', 'numeric', 'min:0'],
            'is_negotiable' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
