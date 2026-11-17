<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PropertyVerification */
final class PropertyVerificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'stage' => $this->stage,
            'status' => $this->status,
            'checklist' => $this->checklist ?? [],
            'notes' => $this->notes,
            'acted_at' => $this->acted_at,
            'actor' => $this->whenLoaded('actor', fn () => [
                'id' => $this->actor->id,
                'name' => $this->actor->name,
            ]),
        ];
    }
}
