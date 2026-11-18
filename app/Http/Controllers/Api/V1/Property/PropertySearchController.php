<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Property;

use App\Contracts\Services\PropertySearchServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\SearchPropertyRequest;
use App\Http\Resources\PropertyResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class PropertySearchController extends Controller
{
    public function __construct(private readonly PropertySearchServiceInterface $searchService)
    {
    }

    public function __invoke(SearchPropertyRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 15);

        return PropertyResource::collection(
            $this->searchService->search($filters, $perPage)
        );
    }
}
