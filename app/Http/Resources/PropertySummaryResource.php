<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PropertySummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_code' => $this->property_code,
            'name' => $this->name,
            'area' => $this->area,
            'inventory_status' => $this->inventory_status,
            'asking_price' => $this->asking_price,
        ];
    }
}
