<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\Property */
final class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_code' => $this->property_code,
            'name' => $this->name,
            'category' => $this->category,
            'market_type' => $this->market_type,
            'division' => $this->division,
            'district' => $this->district,
            'area' => $this->area,
            'detailed_address' => $this->detailed_address,
            'developer' => $this->developer,
            'property_status' => $this->property_status,
            'apartment_size_sqft' => $this->apartment_size_sqft,
            'land_size' => $this->land_size,
            'land_size_unit' => $this->land_size_unit,
            'building_ground_plus' => $this->building_ground_plus,
            'units_per_floor' => $this->units_per_floor,
            'available_floor' => $this->available_floor,
            'facing' => $this->facing,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'balconies' => $this->balconies,
            'drawing_room' => $this->drawing_room,
            'dining_space' => $this->dining_space,
            'parking_spaces' => $this->parking_spaces,
            'gas_connection' => $this->gas_connection,
            'has_lift' => $this->has_lift,
            'has_generator' => $this->has_generator,
            'handover_year' => $this->handover_year,
            'handover_status' => $this->handover_status,
            'paper_status' => $this->paper_status,
            'legal_verification' => $this->legal_verification,
            'mortgage_status' => $this->mortgage_status,
            'ownership_type' => $this->ownership_type,
            'inventory_status' => $this->inventory_status,
            'asking_price' => $this->asking_price,
            'is_negotiable' => $this->is_negotiable,
            'description' => $this->description,
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($image) => [
                'id' => $image->id,
                'original_name' => $image->original_name,
                'url' => Storage::disk($image->disk)->url($image->path),
                'mime_type' => $image->mime_type,
                'size' => $image->size,
            ])->values()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
