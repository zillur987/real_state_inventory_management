<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Automation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Automation\UpdatePropertyLocationRequest;
use App\Http\Requests\Automation\UpdatePropertyPriceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class PropertyAutomationController extends Controller
{
    public function mapPoints(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('automation.maps'), 403);
        $items = DB::table('properties')->where('inventory_status', 'available')
            ->whereNotNull('latitude')->whereNotNull('longitude')
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')->toString()))
            ->when($request->filled('district'), fn ($q) => $q->where('district', 'like', '%' . trim($request->string('district')->toString()) . '%'))
            ->when($request->filled('price_min'), fn ($q) => $q->where('asking_price', '>=', $request->input('price_min')))
            ->when($request->filled('price_max'), fn ($q) => $q->where('asking_price', '<=', $request->input('price_max')))
            ->select('id', 'property_code', 'name', 'category', 'district', 'area', 'asking_price', 'latitude', 'longitude')
            ->orderByDesc('id')->limit(500)->get();
        return response()->json(['data' => $items, 'meta' => ['total' => $items->count(), 'limit' => 500]]);
    }

    public function updateLocation(UpdatePropertyLocationRequest $request, int $property): JsonResponse
    {
        $record = DB::table('properties')->where('id', $property)->first();
        abort_if(!$record, 404);
        DB::table('properties')->where('id', $property)->update([
            'latitude' => $request->validated('latitude'), 'longitude' => $request->validated('longitude'), 'updated_at' => now(),
        ]);
        return response()->json(['message' => 'Property location updated.']);
    }

    public function updatePrice(UpdatePropertyPriceRequest $request, int $property): JsonResponse
    {
        $record = DB::table('properties')->where('id', $property)->first();
        abort_if(!$record, 404);
        $newPrice = (float) $request->validated('asking_price');
        $oldPrice = $record->asking_price === null ? null : (float) $record->asking_price;
        if ($oldPrice !== $newPrice) {
            DB::transaction(function () use ($property, $oldPrice, $newPrice, $request): void {
                DB::table('properties')->where('id', $property)->update(['asking_price' => $newPrice, 'updated_at' => now()]);
                DB::table('property_price_histories')->insert([
                    'property_id' => $property, 'old_price' => $oldPrice, 'new_price' => $newPrice,
                    'changed_by' => $request->user()->id, 'source' => 'manual', 'created_at' => now(), 'updated_at' => now(),
                ]);
            });
        }
        return response()->json(['message' => 'Property price updated.', 'data' => ['old_price' => $oldPrice, 'new_price' => $newPrice]]);
    }

    public function priceHistory(Request $request, int $property): JsonResponse
    {
        abort_unless($request->user()?->can('automation.price-history'), 403);
        abort_unless(DB::table('properties')->where('id', $property)->exists(), 404);
        $items = DB::table('property_price_histories as h')->leftJoin('users as u', 'u.id', '=', 'h.changed_by')
            ->where('h.property_id', $property)->orderByDesc('h.id')
            ->select('h.*', 'u.name as changed_by_name')->paginate(min(max($request->integer('per_page', 15), 1), 100));
        return response()->json(['data' => $items->items(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
    }

    public function duplicateCandidates(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('automation.duplicates'), 403);
        $properties = DB::table('properties')->select('id', 'property_code', 'name', 'category', 'district', 'area', 'apartment_size_sqft', 'asking_price', 'created_at')
            ->whereIn('inventory_status', ['available', 'reserved'])->orderByDesc('id')->limit(1000)->get();
        $groups = $properties->groupBy(fn ($p) => implode('|', [mb_strtolower(trim((string) $p->name)), mb_strtolower(trim((string) $p->district)), mb_strtolower(trim((string) $p->area)), (string) $p->category, (string) $p->apartment_size_sqft]));
        $duplicates = $groups->filter(fn ($group) => $group->count() > 1)->map(fn ($group) => $group->values())->values();
        return response()->json(['data' => $duplicates, 'meta' => ['groups' => $duplicates->count(), 'scanned' => $properties->count(), 'note' => 'Potential duplicates based on normalized name, district, area, category and size. Review manually before merging.']]);
    }

    public function overview(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('automation.view'), 403);
        $today = now();
        return response()->json(['data' => [
            'expiring_soon' => DB::table('properties')->where('inventory_status', 'available')->whereBetween('inventory_expires_at', [$today, $today->copy()->addDays(7)])->count(),
            'expired' => DB::table('properties')->where('inventory_status', 'available')->whereNotNull('inventory_expires_at')->where('inventory_expires_at', '<', $today)->count(),
            'unmapped' => DB::table('properties')->where('inventory_status', 'available')->where(fn ($q) => $q->whereNull('latitude')->orWhereNull('longitude'))->count(),
            'price_changes' => DB::table('property_price_histories')->whereDate('created_at', $today->toDateString())->count(),
            'pending_alerts' => DB::table('property_automation_logs')->whereNull('processed_at')->count(),
        ]]);
    }

    public function alerts(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('automation.view'), 403);
        $items = DB::table('property_automation_logs')->whereNull('processed_at')->orderBy('due_at')->orderByDesc('id')->paginate(min(max($request->integer('per_page', 20), 1), 100));
        return response()->json(['data' => $items->items(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
    }

    public function acknowledgeAlert(Request $request, int $alert): JsonResponse
    {
        abort_unless($request->user()?->can('automation.manage'), 403);
        $updated = DB::table('property_automation_logs')->where('id', $alert)->whereNull('processed_at')->update(['processed_at' => now(), 'updated_at' => now()]);
        abort_if(!$updated, 404);
        return response()->json(['message' => 'Alert acknowledged.']);
    }
}
