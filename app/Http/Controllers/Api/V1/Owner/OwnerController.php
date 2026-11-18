<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Owner;

use App\Contracts\Services\OwnerServiceInterface;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreOwnerRequest;
use App\Http\Resources\OwnerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class OwnerController extends Controller
{
    public function __construct(
        private readonly OwnerServiceInterface $owners
    ) {
    }

    /**
     * Display paginated owners.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeRoles($request, [
            UserRole::Admin,
            UserRole::Manager,
            UserRole::SalesAgent,
            UserRole::LegalOfficer,
        ]);

        $filters = $request->only([
            'search',
            'status',
            'owner_type',
        ]);

        $perPage = $request->integer('per_page', 15);

        $owners = $this->owners->paginate(
            $filters,
            $perPage
        );

        return OwnerResource::collection($owners);
    }

    /**
     * Create owner.
     */
    public function store(StoreOwnerRequest $request): JsonResponse
    {
        $user = $request->user();

        $this->authorizeRoles($request, [
            UserRole::Admin,
            UserRole::Manager,
            UserRole::SalesAgent,
        ]);

        $attributes = $request->safe()->except([
            'final_price_editable',
            'final_negotiated_price',
        ]);

        $owner = $this->owners->create(
            $attributes,
            (int) $user->id,
            $request->ip()
        );

        return (new OwnerResource($owner))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display a specific owner.
     */
    public function show(Request $request, string $owner): OwnerResource
    {
        $this->authorizeRoles($request, [
            UserRole::Admin,
            UserRole::Manager,
            UserRole::SalesAgent,
            UserRole::LegalOfficer,
        ]);

        $user = $request->user();

        $canViewContact = $this->hasRole($user, [
            UserRole::Admin,
            UserRole::Manager,
            UserRole::SalesAgent,
        ]);

        $model = $this->owners->find(
            $owner,
            (int) $user->id,
            $request->ip(),
            $canViewContact
        );

        return new OwnerResource($model);
    }

    /**
     * Update owner.
     */
    public function update(
        StoreOwnerRequest $request,
        string $owner
    ): OwnerResource {
        $user = $request->user();

        $this->authorizeRoles($request, [
            UserRole::Admin,
            UserRole::Manager,
        ]);

        $attributes = $request->safe()->except([
            'final_price_editable',
            'final_negotiated_price',
        ]);

        return new OwnerResource(
            $this->owners->update(
                $owner,
                $attributes,
                (int) $user->id,
                $request->ip()
            )
        );
    }

    /**
     * Archive owner.
     */
    public function destroy(
        Request $request,
        string $owner
    ): JsonResponse {
        $this->authorizeRoles($request, [
            UserRole::Admin,
            UserRole::Manager,
        ]);

        $user = $request->user();

        $this->owners->delete(
            $owner,
            (int) $user->id,
            $request->ip()
        );

        return response()->json([
            'message' => 'Owner archived successfully.',
        ]);
    }

    /**
     * Authorize user roles.
     */
    private function authorizeRoles(
        Request $request,
        array $allowedRoles
    ): void {
        abort_unless(
            $this->hasRole($request->user(), $allowedRoles),
            403,
            'You are not authorized to perform this action.'
        );
    }

    /**
     * Support enum-cast and string-based roles.
     */
    private function hasRole(
        ?object $user,
        array $allowedRoles
    ): bool {
        if (!$user) {
            return false;
        }

        $role = $user->role;

        if (is_string($role)) {
            $role = UserRole::tryFrom($role);
        }

        return $role instanceof UserRole
            && in_array($role, $allowedRoles, true);
    }
}
