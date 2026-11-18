<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Property;

use App\Http\Controllers\Controller;
use App\Http\Requests\Property\ApprovePropertyRequest;
use App\Http\Requests\Property\ReviewPropertyVerificationRequest;
use App\Http\Requests\Property\SubmitPropertyForVerificationRequest;
use App\Http\Resources\PropertyVerificationResource;
use App\Models\Property;
use App\Models\PropertyVerification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class PropertyWorkflowController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'properties.workflow.view');
        $query = Property::query()->with(['images', 'verifications.actor'])->withCount('images')->latest();
        if ($request->filled('status')) {
            $status = (string) $request->string('status');
            $query->whereIn('id', function ($sub) use ($status) {
                $sub->select('property_id')->from('property_verifications as pv')
                    ->where('pv.id', function ($latest) {
                        $latest->selectRaw('MAX(pv2.id)')->from('property_verifications as pv2')
                            ->whereColumn('pv2.property_id', 'pv.property_id')->where('pv2.stage', 'approval');
                    })->where('pv.status', $status);
            });
        }
        $properties = $query->paginate(min(max($request->integer('per_page', 15), 1), 100));
        $properties->getCollection()->transform(fn (Property $property) => $this->propertySummary($property));
        return response()->json(['data' => $properties->items(), 'meta' => [
            'current_page' => $properties->currentPage(), 'last_page' => $properties->lastPage(),
            'per_page' => $properties->perPage(), 'total' => $properties->total(),
        ]]);
    }

    public function show(Request $request, Property $property): JsonResponse
    {
        $this->authorizePermission($request, 'properties.workflow.view');

        $history = $property->verifications()
            ->with('actor')
            ->orderBy('id')
            ->get();

        $latestVerification = $history
            ->where('stage', 'verification')
            ->last();

        $latestApproval = $history
            ->where('stage', 'approval')
            ->last();

        return response()->json([
            'data' => $this->propertySummary($property),

            'history' => PropertyVerificationResource::collection($history),

            'latest_verification' => $latestVerification
                ? (new PropertyVerificationResource($latestVerification))->resolve()
                : null,

            'latest_approval' => $latestApproval
                ? (new PropertyVerificationResource($latestApproval))->resolve()
                : null,
        ]);
    }

    public function submit(SubmitPropertyForVerificationRequest $request, Property $property): JsonResponse
    {
        $this->authorizePermission($request, 'properties.workflow.submit');
        $latestVerification = $property->verifications()->where('stage', 'verification')->latest('id')->first();
        $latestApproval = $property->verifications()->where('stage', 'approval')->latest('id')->first();
        if ($latestApproval?->status === 'approved') {
            throw ValidationException::withMessages(['workflow' => 'An approved property cannot be submitted again.']);
        }
        if ($latestVerification?->status === 'submitted') {
            throw ValidationException::withMessages(['workflow' => 'This property is already awaiting verification.']);
        }

        $record = $property->verifications()->create([
            'actor_id' => $request->user()->id, 'stage' => 'verification', 'status' => 'submitted',
            'notes' => $request->validated('notes'), 'checklist' => null, 'acted_at' => now(),
        ]);
        return (new PropertyVerificationResource($record->load('actor')))->response()->setStatusCode(201);
    }

    public function review(ReviewPropertyVerificationRequest $request, Property $property): JsonResponse
    {
        $this->authorizePermission($request, 'properties.workflow.verify');
        $latest = $property->verifications()->where('stage', 'verification')->latest('id')->first();
        if (!$latest || $latest->status !== 'submitted') {
            throw ValidationException::withMessages(['workflow' => 'Only submitted properties can be reviewed.']);
        }
        $record = $property->verifications()->create([
            'actor_id' => $request->user()->id, 'stage' => 'verification',
            'status' => $request->validated('status'), 'notes' => $request->validated('notes'),
            'checklist' => $request->validated('checklist', []), 'acted_at' => now(),
        ]);
        return (new PropertyVerificationResource($record->load('actor')))->response()->setStatusCode(201);
    }

    public function decide(ApprovePropertyRequest $request, Property $property): JsonResponse
    {
        $this->authorizePermission($request, 'properties.workflow.approve');
        $latestVerification = $property->verifications()->where('stage', 'verification')->latest('id')->first();
        if (!$latestVerification || $latestVerification->status !== 'verified') {
            throw ValidationException::withMessages(['workflow' => 'A property must be verified before manager approval.']);
        }
        $latestApproval = $property->verifications()->where('stage', 'approval')->latest('id')->first();
        if ($latestApproval?->status === 'approved') {
            throw ValidationException::withMessages(['workflow' => 'This property is already approved.']);
        }
        $record = $property->verifications()->create([
            'actor_id' => $request->user()->id, 'stage' => 'approval',
            'status' => $request->validated('status'), 'notes' => $request->validated('notes'),
            'checklist' => null, 'acted_at' => now(),
        ]);
        return (new PropertyVerificationResource($record->load('actor')))->response()->setStatusCode(201);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, 'You are not authorized to perform this workflow action.');
    }

    /** @return array<string, mixed> */
    private function propertySummary(Property $property): array
    {
        $verification = $property->verifications->where('stage', 'verification')->last();
        $approval = $property->verifications->where('stage', 'approval')->last();
        return [
            'id' => $property->id, 'property_code' => $property->property_code, 'name' => $property->name,
            'category' => $property->category, 'area' => $property->area, 'district' => $property->district,
            'developer' => $property->developer, 'property_status' => $property->property_status,
            'inventory_status' => $property->inventory_status, 'images_count' => $property->images_count ?? $property->images->count(),
            'verification_status' => $verification?->status ?? 'not_submitted',
            'approval_status' => $approval?->status ?? 'not_submitted',
            'latest_verification' => $verification ? (new PropertyVerificationResource($verification->loadMissing('actor')))->resolve() : null,
            'latest_approval' => $approval ? (new PropertyVerificationResource($approval->loadMissing('actor')))->resolve() : null,
        ];
    }
}
