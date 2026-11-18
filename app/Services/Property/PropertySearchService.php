<?php

declare(strict_types=1);

namespace App\Services\Property;

use App\Contracts\Repositories\PropertySearchRepositoryInterface;
use App\Contracts\Services\PropertySearchServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class PropertySearchService implements PropertySearchServiceInterface
{
    public function __construct(private readonly PropertySearchRepositoryInterface $repository)
    {
    }

    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->search($filters, min(max($perPage, 1), 100));
    }
}
