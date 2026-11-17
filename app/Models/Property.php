<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Property extends Model
{
    protected $fillable = [
        'property_code', 'name', 'category', 'market_type',
        'division', 'district', 'area', 'detailed_address', 'developer',
        'property_status', 'apartment_size_sqft', 'land_size', 'land_size_unit',
        'building_ground_plus', 'units_per_floor', 'available_floor', 'facing',
        'bedrooms', 'bathrooms', 'balconies', 'drawing_room', 'dining_space',
        'parking_spaces', 'gas_connection', 'has_lift', 'has_generator',
        'handover_year', 'handover_status', 'paper_status', 'legal_verification',
        'mortgage_status', 'ownership_type', 'inventory_status',
        'asking_price', 'is_negotiable', 'description',
    ];

    protected function casts(): array
    {
        return [
            'apartment_size_sqft' => 'decimal:2',
            'land_size' => 'decimal:2',
            'asking_price' => 'decimal:2',
            'drawing_room' => 'boolean',
            'dining_space' => 'boolean',
            'has_lift' => 'boolean',
            'has_generator' => 'boolean',
            'is_negotiable' => 'boolean',
            'handover_year' => 'integer',
            'building_ground_plus' => 'integer',
            'units_per_floor' => 'integer',
            'bedrooms' => 'integer',
            'bathrooms' => 'integer',
            'balconies' => 'integer',
            'parking_spaces' => 'integer',
        ];
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(PropertyVerification::class)->orderBy('id');
    }
}
