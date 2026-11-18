<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Clients;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clients\SaveClientRequirementRequest;
use App\Http\Requests\Clients\StoreClientActivityRequest;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Http\Requests\Clients\UpdateClientRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ClientMatchingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('clients.view'), 403);

        $query = DB::table('clients as c')
            ->leftJoin('users as assignee', 'assignee.id', '=', 'c.assigned_to')
            ->select('c.id', 'c.client_code', 'c.name', 'c.phone', 'c.email', 'c.source', 'c.status', 'c.assigned_to', 'c.created_at', 'assignee.name as assigned_to_name')
            ->when($request->filled('search'), function ($q) use ($request): void {
                $term = '%' . trim((string) $request->input('search')) . '%';
                $q->where(function ($nested) use ($term): void {
                    $nested->where('c.name', 'like', $term)->orWhere('c.client_code', 'like', $term)
                        ->orWhere('c.phone', 'like', $term)->orWhere('c.email', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('c.status', $request->input('status')))
            ->orderByDesc('c.id');

        $clients = $query->paginate(min(max($request->integer('per_page', 15), 1), 100));
        return response()->json(['data' => $clients->items(), 'meta' => [
            'current_page' => $clients->currentPage(), 'last_page' => $clients->lastPage(),
            'per_page' => $clients->perPage(), 'total' => $clients->total(),
        ]]);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $data = $request->validated();
        $id = DB::table('clients')->insertGetId([
            ...$data,
            'client_code' => 'TMP-' . strtoupper(bin2hex(random_bytes(5))),
            'created_by' => $request->user()->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $code = 'CL-' . now()->format('ym') . '-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
        DB::table('clients')->where('id', $id)->update(['client_code' => $code]);
        return response()->json(['data' => $this->clientPayload($id)], 201);
    }

    public function show(Request $request, int $client): JsonResponse
    {
        abort_unless($request->user()?->can('clients.view'), 403);
        return response()->json(['data' => $this->clientPayload($client)]);
    }

    public function update(UpdateClientRequest $request, int $client): JsonResponse
    {
        $this->ensureClientExists($client);
        DB::table('clients')->where('id', $client)->update([...$request->validated(), 'updated_at' => now()]);
        return response()->json(['data' => $this->clientPayload($client)]);
    }

    public function saveRequirement(SaveClientRequirementRequest $request, int $client): JsonResponse
    {
        $this->ensureClientExists($client);
        $data = $request->validated();
        $createdAt = DB::table('client_requirements')->where('client_id', $client)->value('created_at') ?? now();
        DB::table('client_requirements')->updateOrInsert(
            ['client_id' => $client], [...$data, 'updated_at' => now(), 'created_at' => $createdAt]
        );
        return response()->json(['data' => DB::table('client_requirements')->where('client_id', $client)->first()]);
    }

    public function matches(Request $request, int $client): JsonResponse
    {
        abort_unless($request->user()?->can('clients.match'), 403);
        $this->ensureClientExists($client);
        $requirementRow = DB::table('client_requirements')->where('client_id', $client)->first();
        $criteria = ['category', 'market_type', 'division', 'district', 'area', 'budget_min', 'budget_max',
            'size_min_sqft', 'size_max_sqft', 'bedrooms', 'bathrooms', 'parking_spaces', 'facing', 'handover_year'];
        $requirement = $requirementRow ? array_intersect_key((array) $requirementRow, array_flip($criteria)) : [];
        $requirement = array_filter($requirement, static fn ($value) => $value !== null && $value !== '');
        if (!$requirement) {
            throw ValidationException::withMessages(['requirement' => 'Save at least one matching criterion before generating property matches.']);
        }

        $query = DB::table('properties as p')
            ->where('p.inventory_status', 'available')
            ->whereExists(function ($q): void {
                $q->selectRaw('1')->from('property_verifications as pv')
                    ->whereColumn('pv.property_id', 'p.id')->where('pv.stage', 'verification')
                    ->where('pv.status', 'verified')
                    ->whereRaw('pv.id = (select max(pv2.id) from property_verifications as pv2 where pv2.property_id = p.id and pv2.stage = ?)', ['verification']);
            })
            ->whereExists(function ($q): void {
                $q->selectRaw('1')->from('property_verifications as pa')
                    ->whereColumn('pa.property_id', 'p.id')->where('pa.stage', 'approval')
                    ->where('pa.status', 'approved')
                    ->whereRaw('pa.id = (select max(pa2.id) from property_verifications as pa2 where pa2.property_id = p.id and pa2.stage = ?)', ['approval']);
            });

        $exact = ['category', 'market_type', 'division', 'district', 'facing'];
        foreach ($exact as $field) {
            if (!empty($requirement[$field])) $query->where("p.{$field}", $requirement[$field]);
        }
        if (!empty($requirement['area'])) $query->where('p.area', 'like', '%' . $requirement['area'] . '%');
        if (isset($requirement['budget_min'])) $query->where('p.asking_price', '>=', $requirement['budget_min']);
        if (isset($requirement['budget_max'])) $query->where('p.asking_price', '<=', $requirement['budget_max']);
        if (isset($requirement['size_min_sqft'])) $query->where('p.apartment_size_sqft', '>=', $requirement['size_min_sqft']);
        if (isset($requirement['size_max_sqft'])) $query->where('p.apartment_size_sqft', '<=', $requirement['size_max_sqft']);
        foreach (['bedrooms', 'bathrooms', 'parking_spaces'] as $field) {
            if (isset($requirement[$field])) $query->where("p.{$field}", '>=', $requirement[$field]);
        }
        if (isset($requirement['handover_year'])) $query->where('p.handover_year', '>=', $requirement['handover_year']);

        $properties = $query->select([
            'p.id', 'p.property_code', 'p.name', 'p.category', 'p.market_type', 'p.division', 'p.district',
            'p.area', 'p.developer', 'p.apartment_size_sqft', 'p.bedrooms', 'p.bathrooms', 'p.parking_spaces',
            'p.facing', 'p.handover_year', 'p.asking_price', 'p.is_negotiable',
        ])->orderBy('p.asking_price')->limit(50)->get();

        $matches = $properties->map(function ($property) use ($requirement, $client, $request): array {
            $reasons = [];
            foreach (['category' => 'Category', 'market_type' => 'Market type', 'division' => 'Division', 'district' => 'District', 'facing' => 'Facing'] as $field => $label) {
                if (!empty($requirement[$field]) && (string) $property->{$field} === (string) $requirement[$field]) $reasons[] = $label;
            }
            if (!empty($requirement['area']) && stripos((string) $property->area, (string) $requirement['area']) !== false) $reasons[] = 'Area';
            if (isset($requirement['budget_min'])) $reasons[] = 'Minimum budget';
            if (isset($requirement['budget_max'])) $reasons[] = 'Maximum budget';
            if (isset($requirement['size_min_sqft'])) $reasons[] = 'Minimum size';
            if (isset($requirement['size_max_sqft'])) $reasons[] = 'Maximum size';
            foreach (['bedrooms' => 'Bedrooms', 'bathrooms' => 'Bathrooms', 'parking_spaces' => 'Parking', 'handover_year' => 'Handover'] as $field => $label) {
                if (isset($requirement[$field])) $reasons[] = $label;
            }
            $score = min(100, count($reasons) * 100 / max(1, count(array_filter($requirement, fn ($v) => $v !== null && $v !== ''))));
            $saved = DB::table('client_property_matches')->where('client_id', $client)->where('property_id', $property->id)->first();
            return ['property' => $property, 'match_score' => (int) round($score), 'match_reasons' => $reasons,
                'shortlisted' => (bool) $saved, 'shortlist_status' => $saved->status ?? null];
        })->values();

        return response()->json(['data' => $matches, 'meta' => ['total' => $matches->count(), 'eligible_only' => true]]);
    }

    public function shortlist(Request $request, int $client, int $property): JsonResponse
    {
        abort_unless($request->user()?->can('clients.match'), 403);
        $this->ensureClientExists($client);
        $eligible = DB::table('properties as p')->where('p.id', $property)->where('p.inventory_status', 'available')
            ->whereExists(function ($q): void {
                $q->selectRaw('1')->from('property_verifications as pv')->whereColumn('pv.property_id', 'p.id')
                    ->where('pv.stage', 'verification')->where('pv.status', 'verified')
                    ->whereRaw('pv.id = (select max(pv2.id) from property_verifications as pv2 where pv2.property_id = p.id and pv2.stage = ?)', ['verification']);
            })->whereExists(function ($q): void {
                $q->selectRaw('1')->from('property_verifications as pa')->whereColumn('pa.property_id', 'p.id')
                    ->where('pa.stage', 'approval')->where('pa.status', 'approved')
                    ->whereRaw('pa.id = (select max(pa2.id) from property_verifications as pa2 where pa2.property_id = p.id and pa2.stage = ?)', ['approval']);
            })->exists();
        abort_unless($eligible, 422, 'Only available, verified and manager-approved properties can be shortlisted.');
        DB::table('client_property_matches')->updateOrInsert(
            ['client_id' => $client, 'property_id' => $property],
            ['created_by' => $request->user()->id, 'status' => 'shortlisted', 'updated_at' => now(), 'created_at' => now()]
        );
        return response()->json(['message' => 'Property added to the client shortlist.']);
    }

    public function removeShortlist(Request $request, int $client, int $property): JsonResponse
    {
        abort_unless($request->user()?->can('clients.match'), 403);
        DB::table('client_property_matches')->where('client_id', $client)->where('property_id', $property)->delete();
        return response()->json(['message' => 'Property removed from the client shortlist.']);
    }

    public function activities(Request $request, int $client): JsonResponse
    {
        abort_unless($request->user()?->can('clients.view'), 403);
        $this->ensureClientExists($client);
        $items = DB::table('client_activities as a')->leftJoin('users as u', 'u.id', '=', 'a.created_by')
            ->where('a.client_id', $client)->orderByDesc('a.id')
            ->select('a.*', 'u.name as created_by_name')->limit(100)->get();
        return response()->json(['data' => $items]);
    }

    public function storeActivity(StoreClientActivityRequest $request, int $client): JsonResponse
    {
        $this->ensureClientExists($client);
        $data = $request->validated();
        $id = DB::table('client_activities')->insertGetId([
            'client_id' => $client, 'created_by' => $request->user()->id,
            'type' => $data['type'], 'subject' => $data['subject'], 'description' => $data['description'] ?? null,
            'due_at' => $data['due_at'] ?? null, 'completed_at' => ($data['completed'] ?? false) ? now() : null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return response()->json(['data' => DB::table('client_activities')->where('id', $id)->first()], 201);
    }

    private function clientPayload(int $client): array
    {
        $record = DB::table('clients as c')->leftJoin('users as u', 'u.id', '=', 'c.assigned_to')
            ->where('c.id', $client)->select('c.*', 'u.name as assigned_to_name')->first();
        abort_if(!$record, 404);
        return [
            'client' => $record,
            'requirement' => DB::table('client_requirements')->where('client_id', $client)->first(),
            'shortlisted_properties' => DB::table('client_property_matches as m')->join('properties as p', 'p.id', '=', 'm.property_id')
                ->where('m.client_id', $client)->select('m.status as shortlist_status', 'm.match_score', 'm.notes as match_notes',
                    'p.id', 'p.property_code', 'p.name', 'p.area', 'p.district', 'p.asking_price', 'p.category')->get(),
            'activities' => DB::table('client_activities')->where('client_id', $client)->orderByDesc('id')->limit(20)->get(),
        ];
    }

    private function ensureClientExists(int $client): void
    {
        abort_unless(DB::table('clients')->where('id', $client)->exists(), 404);
    }
}
