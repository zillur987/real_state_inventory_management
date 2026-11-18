<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PropertySearchRepositoryInterface
{
    /** @param array<string, mixed> $filters */
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator;
}
