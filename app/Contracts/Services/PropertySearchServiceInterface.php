<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PropertySearchServiceInterface
{
    /** @param array<string, mixed> $filters */
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator;
}
