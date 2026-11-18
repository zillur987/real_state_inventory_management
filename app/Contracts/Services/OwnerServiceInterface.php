<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\Owner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OwnerServiceInterface
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator;
    public function create(array $attributes, int $actorId, ?string $ip): Owner;
    public function find(int|string $id, int $actorId, ?string $ip, bool $viewContact): Owner;
    public function update(int|string $id, array $attributes, int $actorId, ?string $ip): Owner;
    public function delete(int|string $id, int $actorId, ?string $ip): void;
}
