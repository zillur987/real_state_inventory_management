<?php

declare(strict_types=1);

namespace App\Http\Requests\Clients;

use Illuminate\Foundation\Http\FormRequest;

final class SaveClientRequirementRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('clients.match') ?? false; }

    public function rules(): array
    {
        return [
            'category' => ['nullable', 'string', 'max:60'],
            'market_type' => ['nullable', 'in:primary,secondary'],
            'division' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:150'],
            'budget_min' => ['nullable', 'numeric', 'min:0'],
            'budget_max' => ['nullable', 'numeric', 'gte:budget_min'],
            'size_min_sqft' => ['nullable', 'numeric', 'min:0'],
            'size_max_sqft' => ['nullable', 'numeric', 'gte:size_min_sqft'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'parking_spaces' => ['nullable', 'integer', 'min:0', 'max:20'],
            'facing' => ['nullable', 'in:North,South,East,West,Corner'],
            'handover_year' => ['nullable', 'integer', 'min:2000', 'max:2150'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
