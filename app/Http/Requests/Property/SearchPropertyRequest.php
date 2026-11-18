<?php

declare(strict_types=1);

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SearchPropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:180'],
            'developer' => ['nullable', 'string', 'max:180'],
            'area' => ['nullable', 'string', 'max:150'],
            'market_type' => ['nullable', Rule::in(['primary', 'secondary'])],
            'floor' => ['nullable', 'string', 'max:100'],
            'handover_year_from' => ['nullable', 'integer', 'min:1950', 'max:2200'],
            'handover_year_to' => ['nullable', 'integer', 'min:1950', 'max:2200', 'gte:handover_year_from'],
            'building_height_min' => ['nullable', 'integer', 'min:0', 'max:200'],
            'building_height_max' => ['nullable', 'integer', 'min:0', 'max:200', 'gte:building_height_min'],
            'parking_min' => ['nullable', 'integer', 'min:0', 'max:20'],
            'gas_connection' => ['nullable', Rule::in(['titas', 'lpg', 'none'])],
            'paper_status' => ['nullable', Rule::in(['registered', 'non_registered', 'on_process'])],
            'legal_verification' => ['nullable', Rule::in(['verified', 'non_verified', 'pending'])],
            'asking_price_min' => ['nullable', 'numeric', 'min:0'],
            'asking_price_max' => ['nullable', 'numeric', 'min:0', 'gte:asking_price_min'],
            'price_per_sqft_min' => ['nullable', 'numeric', 'min:0'],
            'price_per_sqft_max' => ['nullable', 'numeric', 'min:0', 'gte:price_per_sqft_min'],
            'owner_direct' => ['nullable', Rule::in(['0', '1', 0, 1, true, false])],
            'recent_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'sort' => ['nullable', Rule::in(['newest', 'price_low', 'price_high', 'size_large'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
