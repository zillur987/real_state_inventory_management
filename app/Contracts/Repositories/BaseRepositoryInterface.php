<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    /** @param  array<int, string>  $columns */
    public function all(array $columns = ['*']): Collection;

    /** @param  array<int, string>  $columns */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /** @param  array<int, string>  $columns */
    public function find(int|string $id, array $columns = ['*']): ?Model;

    /** @param  array<int, string>  $columns */
    public function findOrFail(int|string $id, array $columns = ['*']): Model;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): Model;

    /** @param  array<string, mixed>  $attributes */
    public function update(int|string $id, array $attributes): Model;

    public function delete(int|string $id): bool;
}
