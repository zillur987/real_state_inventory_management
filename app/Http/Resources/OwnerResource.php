<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OwnerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id;
        $hasApprovedRequest = $userId && $this->resource->relationLoaded('contactRequests')
            ? $this->contactRequests->contains(fn ($item) => (int) $item->requested_by === (int) $userId && $item->status === 'approved')
            : false;
        $canViewContact = ($request->user()?->can('owners.view_contact') ?? false)
            || ($request->user()?->can('owners.update') ?? false)
            || $hasApprovedRequest;
        $canViewFinalPrice = $request->user()?->can('owners.view_final_price') ?? false;

        return [
            'id' => $this->id,
            'owner_code' => $this->owner_code,
            'name' => $this->name,
            'owner_type' => $this->owner_type,
            'status' => $this->status,
            'phone' => $this->when($canViewContact, $this->phone),
            'email' => $this->when($canViewContact, $this->email),
            'profession' => $this->when($canViewContact, $this->profession),
            'detailed_address' => $this->when($canViewContact, $this->detailed_address),
            'legal_status_note' => $this->when($canViewContact, $this->legal_status_note),
            'expected_price' => $this->when($canViewContact, $this->expected_price),
            'final_negotiated_price' => $this->when($canViewFinalPrice, $this->final_negotiated_price),
            'relationship_manager_id' => $this->when($canViewContact, $this->relationship_manager_id),
            'notes' => $this->when($canViewContact, $this->notes),
            'relationship_manager' => $this->when($canViewContact, $this->whenLoaded('relationshipManager', fn () => $this->relationshipManager ? [
                'id' => $this->relationshipManager->id,
                'name' => $this->relationshipManager->name,
            ] : null)),
            'properties' => PropertySummaryResource::collection($this->whenLoaded('properties')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
