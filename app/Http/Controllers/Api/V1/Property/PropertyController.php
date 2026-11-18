<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Property;

use App\Contracts\Services\PropertyServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Resources\PropertyResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class PropertyController extends Controller
{
    public function __construct(private readonly PropertyServiceInterface $propertyService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'search', 'category', 'inventory_status', 'legal_verification', 'sort_by', 'sort_dir',
        ]);

        $properties = $this->propertyService->paginate($filters, (int) $request->integer('per_page', 15));

        return PropertyResource::collection($properties);
    }

    public function store(StorePropertyRequest $request): JsonResponse
    {
        $property = $this->propertyService->create($request->validated());

        return (new PropertyResource($property))->response()->setStatusCode(201);
    }

    public function show(string $property): PropertyResource
    {
        return new PropertyResource($this->propertyService->find($property));
    }

    public function update(StorePropertyRequest $request, string $property): PropertyResource
    {
        $updated = $this->propertyService->update($property, $request->validated());

        return new PropertyResource($updated);
    }
}