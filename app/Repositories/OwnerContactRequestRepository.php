<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\OwnerContactRequestRepositoryInterface;
use App\Models\OwnerContactRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class OwnerContactRequestRepository implements OwnerContactRequestRepositoryInterface
{
    public function __construct(private readonly OwnerContactRequest $model)
    {
    }

    public function paginateForUser(int $userId, bool $reviewer, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(['owner:id,owner_code,name', 'requester:id,name', 'reviewer:id,name'])
            ->when(!$reviewer, fn ($query) => $query->where('requested_by', $userId))
            ->latest()
            ->paginate(min(max($perPage, 1), 100));
    }

    public function create(array $attributes): OwnerContactRequest
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function findOrFail(int|string $id): OwnerContactRequest
    {
        return $this->model->newQuery()->findOrFail($id);
    }

    public function save(OwnerContactRequest $request, array $attributes): OwnerContactRequest
    {
        $request->fill($attributes)->save();

        return $request->fresh(['owner:id,owner_code,name', 'requester:id,name', 'reviewer:id,name']);
    }
}
