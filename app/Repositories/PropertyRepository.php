<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\PropertyRepositoryInterface;
use App\Models\Property;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class PropertyRepository extends BaseRepository implements PropertyRepositoryInterface
{
    private const SORTABLE = [
        'name', 'category', 'district', 'asking_price',
        'inventory_status', 'legal_verification', 'created_at',
    ];

    private const SEARCHABLE = ['name', 'property_code', 'area', 'district', 'developer'];

    public function __construct(Property $model)
    {
        parent::__construct($model);
    }

    public function paginateLatest(int $perPage = 15): LengthAwarePaginator
    {
        return $this->paginateFiltered([], $perPage);
    }

    public function paginateFiltered(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (filled($filters['search'] ?? null)) {
            $term = '%' . mb_strtolower(trim((string) $filters['search'])) . '%';

            $query->where(function ($q) use ($term): void {
                foreach (self::SEARCHABLE as $column) {
                    $q->orWhereRaw("LOWER({$column}) LIKE ?", [$term]);
                }
            });
        }

        foreach (['category', 'inventory_status', 'legal_verification'] as $column) {
            if (filled($filters[$column] ?? null)) {
                $query->where($column, (string) $filters[$column]);
            }
        }

        $sortBy = in_array($filters['sort_by'] ?? null, self::SORTABLE, true) ? $filters['sort_by'] : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)->orderByDesc('id')->paginate($perPage);
    }

    public function findPropertyOrFail(int|string $id): Property
    {
        /** @var Property */
        return $this->model->newQuery()->findOrFail($id);
    }
}