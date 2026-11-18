<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\PropertySearchRepositoryInterface;
use App\Models\Property;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class PropertySearchRepository implements PropertySearchRepositoryInterface
{
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Property::query()
            ->where('inventory_status', 'available');

        $this->applyTextFilters($query, $filters);
        $this->applyExactFilters($query, $filters);
        $this->applyRangeFilters($query, $filters);
        $this->applyRecentFilter($query, $filters);
        $this->applyOwnerDirectFilter($query, $filters);

        $sort = $filters['sort'] ?? 'newest';

        match ($sort) {
            'price_low' => $query->orderBy('asking_price')->orderByDesc('id'),
            'price_high' => $query->orderByDesc('asking_price')->orderByDesc('id'),
            'size_large' => $query->orderByDesc('apartment_size_sqft')->orderByDesc('id'),
            default => $query->latest('created_at'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    private function applyTextFilters(Builder $query, array $filters): void
    {
        foreach (['q' => ['name', 'property_code', 'area', 'district', 'developer'], 'developer' => ['developer'], 'area' => ['area', 'district']] as $key => $columns) {
            $value = trim((string) ($filters[$key] ?? ''));

            if ($value === '') {
                continue;
            }

            $query->where(function (Builder $nested) use ($columns, $value): void {
                foreach ($columns as $column) {
                    $nested->orWhere($column, 'like', '%' . $value . '%');
                }
            });
        }

        if (!empty($filters['floor'])) {
            $query->where('available_floor', 'like', '%' . trim((string) $filters['floor']) . '%');
        }
    }

    private function applyExactFilters(Builder $query, array $filters): void
    {
        $map = [
            'market_type' => 'market_type',
            'gas_connection' => 'gas_connection',
            'paper_status' => 'paper_status',
            'legal_verification' => 'legal_verification',
        ];

        foreach ($map as $filter => $column) {
            if (($filters[$filter] ?? '') !== '') {
                $query->where($column, $filters[$filter]);
            }
        }
    }

    private function applyRangeFilters(Builder $query, array $filters): void
    {
        $ranges = [
            'handover_year' => ['handover_year_from', 'handover_year_to'],
            'building_ground_plus' => ['building_height_min', 'building_height_max'],
            'parking_spaces' => ['parking_min', null],
            'asking_price' => ['asking_price_min', 'asking_price_max'],
        ];

        foreach ($ranges as $column => [$minKey, $maxKey]) {
            if ($minKey && ($filters[$minKey] ?? '') !== '') {
                $query->where($column, '>=', $filters[$minKey]);
            }
            if ($maxKey && ($filters[$maxKey] ?? '') !== '') {
                $query->where($column, '<=', $filters[$maxKey]);
            }
        }

        $hasPricePerSqft = ($filters['price_per_sqft_min'] ?? '') !== ''
            || ($filters['price_per_sqft_max'] ?? '') !== '';

        if ($hasPricePerSqft) {
            $query->whereNotNull('asking_price')
                ->whereNotNull('apartment_size_sqft')
                ->where('apartment_size_sqft', '>', 0);

            if (($filters['price_per_sqft_min'] ?? '') !== '') {
                $query->whereRaw('(asking_price / NULLIF(apartment_size_sqft, 0)) >= ?', [$filters['price_per_sqft_min']]);
            }
            if (($filters['price_per_sqft_max'] ?? '') !== '') {
                $query->whereRaw('(asking_price / NULLIF(apartment_size_sqft, 0)) <= ?', [$filters['price_per_sqft_max']]);
            }
        }
    }

    private function applyRecentFilter(Builder $query, array $filters): void
    {
        $days = (int) ($filters['recent_days'] ?? 0);

        if ($days > 0) {
            $query->where('created_at', '>=', now()->subDays(min($days, 365)));
        }
    }

    private function applyOwnerDirectFilter(Builder $query, array $filters): void
    {
        if (($filters['owner_direct'] ?? '') !== '') {
            $query->where('is_owner_direct', (bool) $filters['owner_direct']);
        }
    }
}
