<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\OwnerRepositoryInterface;
use App\Models\Owner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class OwnerRepository implements OwnerRepositoryInterface
{
    public function __construct(private readonly Owner $model)
    {
    }

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(['relationshipManager:id,name'])
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('owner_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['owner_type'] ?? null, fn ($query, $type) => $query->where('owner_type', $type))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $attributes): Owner
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function findOrFail(int|string $id): Owner
    {
        return $this->model->newQuery()
            ->with(['relationshipManager:id,name', 'properties:id,owner_id,property_code,name,area,inventory_status,asking_price', 'contactRequests:id,owner_id,requested_by,status'])
            ->findOrFail($id);
    }

    public function update(Owner $owner, array $attributes): Owner
    {
        $owner->fill($attributes);
        $owner->save();

        return $owner->fresh([
            'relationshipManager:id,name',
            'properties:id,owner_id,property_code,name,area,inventory_status,asking_price',
        ]);
    }

    public function delete(Owner $owner): void
    {
        $owner->delete();
    }
}
