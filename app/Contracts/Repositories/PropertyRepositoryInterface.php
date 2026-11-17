<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Property;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PropertyRepositoryInterface extends BaseRepositoryInterface
{
    public function paginateLatest(int $perPage = 15): LengthAwarePaginator;

    public function findPropertyOrFail(int|string $id): Property;
}
