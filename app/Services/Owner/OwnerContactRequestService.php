<?php

declare(strict_types=1);

namespace App\Services\Owner;

use App\Contracts\Repositories\OwnerContactRequestRepositoryInterface;
use App\Contracts\Services\OwnerContactRequestServiceInterface;
use App\Models\OwnerActivityLog;
use App\Models\OwnerContactRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

final class OwnerContactRequestService implements OwnerContactRequestServiceInterface
{
    public function __construct(private readonly OwnerContactRequestRepositoryInterface $requests)
    {
    }

    public function paginateForUser(int $userId, bool $reviewer, int $perPage = 15): LengthAwarePaginator
    {
        return $this->requests->paginateForUser($userId, $reviewer, $perPage);
    }

    public function create(int $ownerId, int $requesterId, string $reason): OwnerContactRequest
    {
        $existing = OwnerContactRequest::query()
            ->where('owner_id', $ownerId)
            ->where('requested_by', $requesterId)
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'owner' => ['You already have a pending contact request for this owner.'],
            ]);
        }

        $request = $this->requests->create([
            'owner_id' => $ownerId,
            'requested_by' => $requesterId,
            'reason' => $reason,
            'status' => 'pending',
        ]);

        OwnerActivityLog::query()->create([
            'owner_id' => $ownerId,
            'user_id' => $requesterId,
            'action' => 'contact_requested',
            'description' => 'Owner contact access requested',
            'created_at' => now(),
        ]);

        return $request;
    }

    public function review(int|string $id, int $reviewerId, string $status, ?string $note): OwnerContactRequest
    {
        $request = $this->requests->findOrFail($id);

        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => ['Only pending requests can be reviewed.'],
            ]);
        }

        $updated = $this->requests->save($request, [
            'status' => $status,
            'reviewed_by' => $reviewerId,
            'review_note' => $note,
            'reviewed_at' => now(),
        ]);

        OwnerActivityLog::query()->create([
            'owner_id' => $request->owner_id,
            'user_id' => $reviewerId,
            'action' => 'contact_request_' . $status,
            'description' => 'Owner contact request ' . $status,
            'created_at' => now(),
        ]);

        return $updated;
    }
}
