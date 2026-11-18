<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\OwnerContactRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OwnerContactRequestServiceInterface
{
    public function paginateForUser(int $userId, bool $reviewer, int $perPage = 15): LengthAwarePaginator;
    public function create(int $ownerId, int $requesterId, string $reason): OwnerContactRequest;
    public function review(int|string $id, int $reviewerId, string $status, ?string $note): OwnerContactRequest;
}
