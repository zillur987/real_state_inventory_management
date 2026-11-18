<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Owner;

use App\Contracts\Services\OwnerContactRequestServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\ReviewOwnerContactRequest;
use App\Http\Requests\Owner\StoreOwnerContactRequest;
use App\Http\Resources\OwnerContactRequestResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class OwnerContactRequestController extends Controller
{
    public function __construct(private readonly OwnerContactRequestServiceInterface $requests)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $reviewer = (bool) $request->user()?->can('owners.review_contact_requests');

        return OwnerContactRequestResource::collection(
            $this->requests->paginateForUser(
                (int) $request->user()->id,
                $reviewer,
                (int) $request->integer('per_page', 15),
            ),
        );
    }

    public function store(StoreOwnerContactRequest $request, string $owner): OwnerContactRequestResource
    {
        $created = $this->requests->create(
            (int) $owner,
            (int) $request->user()->id,
            $request->validated('reason'),
        );

        return new OwnerContactRequestResource($created->load(['owner', 'requester']));
    }

    public function review(ReviewOwnerContactRequest $request, string $contactRequest): OwnerContactRequestResource
    {
        $updated = $this->requests->review(
            $contactRequest,
            (int) $request->user()->id,
            $request->validated('status'),
            $request->validated('review_note'),
        );

        return new OwnerContactRequestResource($updated);
    }
}
