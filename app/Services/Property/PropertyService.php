<?php

declare(strict_types=1);

namespace App\Services\Property;

use App\Contracts\Repositories\PropertyRepositoryInterface;
use App\Contracts\Services\PropertyServiceInterface;
use App\Models\Property;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

final class PropertyService implements PropertyServiceInterface
{
    public function __construct(private readonly PropertyRepositoryInterface $properties)
    {
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->properties->paginateFiltered($filters, min(max($perPage, 1), 100));
    }

    public function create(array $attributes): Property
    {
        $attributes['property_code'] = 'P-' . Str::upper((string) Str::ulid());

        /** @var Property */
        return $this->properties->create($attributes);
    }

    public function find(int|string $id): Property
    {
        return $this->properties->findPropertyOrFail($id);
    }

    public function update(int|string $id, array $attributes): Property
    {
        unset($attributes['property_code']);

        /** @var Property */
        return $this->properties->update($id, $attributes);
    }
}
