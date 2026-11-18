<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Owner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OwnerRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator;
    public function create(array $attributes): Owner;
    public function findOrFail(int|string $id): Owner;
    public function update(Owner $owner, array $attributes): Owner;
    public function delete(Owner $owner): void;
}
