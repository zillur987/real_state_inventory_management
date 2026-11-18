<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\OwnerContactRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OwnerContactRequestRepositoryInterface
{
    public function paginateForUser(int $userId, bool $reviewer, int $perPage = 15): LengthAwarePaginator;
    public function create(array $attributes): OwnerContactRequest;
    public function findOrFail(int|string $id): OwnerContactRequest;
    public function save(OwnerContactRequest $request, array $attributes): OwnerContactRequest;
}
