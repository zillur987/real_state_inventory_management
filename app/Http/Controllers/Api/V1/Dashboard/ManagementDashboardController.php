<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ManagementDashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('properties.dashboard.view'), 403);

        $base = $this->dateFilteredProperties($request);
        $total = (clone $base)->count();

        $inventory = (clone $base)
            ->selectRaw("LOWER(COALESCE(inventory_status, 'unknown')) as status, COUNT(*) as total")
            ->groupBy('status')->pluck('total', 'status');

        $market = (clone $base)
            ->selectRaw("LOWER(COALESCE(market_type, 'unknown')) as market, COUNT(*) as total")
            ->groupBy('market')->pluck('total', 'market');

        $verification = $this->latestStageCounts($base, 'verification');
        $approval = $this->latestStageCounts($base, 'approval');

        return response()->json(['data' => [
            'total_properties' => $total,
            'available' => (int) ($inventory['available'] ?? 0),
            'reserved' => (int) ($inventory['reserved'] ?? 0),
            'sold' => (int) ($inventory['sold'] ?? 0),
            'primary' => (int) ($market['primary'] ?? 0),
            'secondary' => (int) ($market['secondary'] ?? 0),
            'verification' => $verification,
            'approval' => $approval,
        ]]);
    }

    public function reports(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('properties.reports.view'), 403);

        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $base = $this->dateFilteredProperties($request);

        $byArea = (clone $base)
            ->selectRaw("COALESCE(NULLIF(area, ''), 'Unspecified') as label, COUNT(*) as total")
            ->groupBy('label')->orderByDesc('total')->limit(12)->get();

        $byDeveloper = (clone $base)
            ->selectRaw("COALESCE(NULLIF(developer, ''), 'Unspecified') as label, COUNT(*) as total")
            ->groupBy('label')->orderByDesc('total')->limit(12)->get();

        $averagePriceByArea = (clone $base)
            ->whereNotNull('asking_price')
            ->selectRaw("COALESCE(NULLIF(area, ''), 'Unspecified') as label, ROUND(AVG(asking_price), 2) as average_asking_price, COUNT(*) as property_count")
            ->groupBy('label')->orderByDesc('property_count')->limit(12)->get();

        $marketMix = (clone $base)
            ->selectRaw("LOWER(COALESCE(market_type, 'unknown')) as label, COUNT(*) as total")
            ->groupBy('label')->orderByDesc('total')->get();

        $inventoryStatus = (clone $base)
            ->selectRaw("LOWER(COALESCE(inventory_status, 'unknown')) as label, COUNT(*) as total")
            ->groupBy('label')->orderByDesc('total')->get();

        $verification = $this->latestStageCounts($base, 'verification');
        $approval = $this->latestStageCounts($base, 'approval');

        $aging = (clone $base)->selectRaw(
            "SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as days_0_30, " .
            "SUM(CASE WHEN created_at < ? AND created_at >= ? THEN 1 ELSE 0 END) as days_31_60, " .
            "SUM(CASE WHEN created_at < ? AND created_at >= ? THEN 1 ELSE 0 END) as days_61_90, " .
            "SUM(CASE WHEN created_at < ? THEN 1 ELSE 0 END) as days_90_plus",
            [now()->subDays(30), now()->subDays(30), now()->subDays(60), now()->subDays(60), now()->subDays(90), now()->subDays(90)]
        )->first();

        return response()->json(['data' => [
            'filters' => ['from' => $validated['from'] ?? null, 'to' => $validated['to'] ?? null],
            'inventory_by_area' => $byArea,
            'inventory_by_developer' => $byDeveloper,
            'average_asking_price_by_area' => $averagePriceByArea,
            'market_mix' => $marketMix,
            'inventory_status' => $inventoryStatus,
            'verification_status' => $verification,
            'approval_status' => $approval,
            'inventory_aging' => [
                ['label' => '0–30 days', 'total' => (int) ($aging->days_0_30 ?? 0)],
                ['label' => '31–60 days', 'total' => (int) ($aging->days_31_60 ?? 0)],
                ['label' => '61–90 days', 'total' => (int) ($aging->days_61_90 ?? 0)],
                ['label' => '90+ days', 'total' => (int) ($aging->days_90_plus ?? 0)],
            ],
            'not_available_yet' => [
                ['key' => 'inventory_by_employee', 'reason' => 'The current property schema does not identify the collecting employee/creator.'],
                ['key' => 'owner_wise_portfolio', 'reason' => 'No owner relationship/table is included in the supplied Step 3/4 schema.'],
                ['key' => 'sales_conversion', 'reason' => 'Client, visit, reservation and closing records are not part of the current Step 3/4 schema.'],
                ['key' => 'commission_reports', 'reason' => 'Commission and final negotiated price fields are not available in the current general property schema.'],
            ],
        ]]);
    }

    private function dateFilteredProperties(Request $request): Builder
    {
        $query = DB::table('properties');
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }
        return $query;
    }

    /** @return array<string, int> */
    private function latestStageCounts(Builder $properties, string $stage): array
    {
        $latest = DB::table('property_verifications as pv')
            ->selectRaw('MAX(pv.id)')
            ->whereColumn('pv.property_id', 'properties.id')
            ->where('pv.stage', $stage);

        $counts = (clone $properties)
            ->leftJoin('property_verifications as latest_workflow', function ($join) use ($stage, $latest): void {
                $join->on('latest_workflow.property_id', '=', 'properties.id')
                    ->where('latest_workflow.stage', '=', $stage)
                    ->whereRaw('latest_workflow.id = (' . $latest->toSql() . ')')
                    ->addBinding($latest->getBindings(), 'join');
            })
            ->selectRaw("COALESCE(LOWER(latest_workflow.status), 'not_submitted') as status, COUNT(DISTINCT properties.id) as total")
            ->groupBy('status')->pluck('total', 'status');

        return [
            'not_submitted' => (int) ($counts['not_submitted'] ?? 0),
            'submitted' => (int) ($counts['submitted'] ?? 0),
            'verified' => (int) ($counts['verified'] ?? 0),
            'approved' => (int) ($counts['approved'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
            'changes_requested' => (int) ($counts['changes_requested'] ?? 0),
        ];
    }
}
