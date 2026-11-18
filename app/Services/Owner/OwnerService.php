<?php

declare(strict_types=1);

namespace App\Services\Owner;

use App\Contracts\Repositories\OwnerRepositoryInterface;
use App\Contracts\Services\OwnerServiceInterface;
use App\Models\Owner;
use App\Models\OwnerActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

final class OwnerService implements OwnerServiceInterface
{
    public function __construct(private readonly OwnerRepositoryInterface $owners)
    {
    }

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->owners->paginate($filters, min(max($perPage, 1), 100));
    }

    public function create(array $attributes, int $actorId, ?string $ip): Owner
    {
        $attributes['owner_code'] = 'OWN-' . Str::upper((string) Str::ulid());
        $owner = $this->owners->create($attributes);
        $this->log($owner, $actorId, 'created', 'Owner profile created', $ip);

        return $owner;
    }

    public function find(int|string $id, int $actorId, ?string $ip, bool $viewContact): Owner
    {
        $owner = $this->owners->findOrFail($id);

        $approvedRequest = $owner->contactRequests()
            ->where('requested_by', $actorId)
            ->where('status', 'approved')
            ->exists();

        if ($viewContact || $approvedRequest) {
            $this->log($owner, $actorId, 'sensitive_viewed', 'Restricted owner profile/contact information viewed', $ip);
        }

        return $owner;
    }

    public function update(
        int|string $id,
        array $attributes,
        int $actorId,
        ?string $ip
    ): Owner {
        $owner = $this->owners->findOrFail($id);

        $updated = $this->owners->update($owner, $attributes);

        $this->log(
            $updated,
            $actorId,
            'updated',
            'Owner profile updated',
            $ip
        );

        return $updated->refresh();
    }

    public function delete(int|string $id, int $actorId, ?string $ip): void
    {
        $owner = $this->owners->findOrFail($id);
        $this->owners->delete($owner);
        $this->log($owner, $actorId, 'archived', 'Owner profile archived', $ip);
    }

    private function log(Owner $owner, int $actorId, string $action, string $description, ?string $ip): void
    {
        OwnerActivityLog::query()->create([
            'owner_id' => $owner->id,
            'user_id' => $actorId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $ip,
            'created_at' => now(),
        ]);
    }
}
