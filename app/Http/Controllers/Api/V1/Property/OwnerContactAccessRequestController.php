<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Property;

use App\Http\Controllers\Controller;
use App\Http\Requests\Property\StoreOwnerContactAccessRequest;
use App\Models\OwnerContactAccessRequest;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class OwnerContactAccessRequestController extends Controller
{
    public function store(StoreOwnerContactAccessRequest $request, Property $property): JsonResponse
    {
        Gate::authorize('viewOwnerContact', $property);

        $accessRequest = OwnerContactAccessRequest::query()->create([
            'property_id' => $property->id,
            'user_id' => $request->user()->id,
            'reason' => $request->validated('reason'),
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Owner contact access request submitted.',
            'data' => ['id' => $accessRequest->id, 'status' => $accessRequest->status],
        ], 201);
    }
}
