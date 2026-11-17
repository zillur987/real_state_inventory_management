<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\Property;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PropertyServiceInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Property;

    public function find(int|string $id): Property;

    /** @param array<string, mixed> $attributes */
    public function update(int|string $id, array $attributes): Property;
}
