<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\PropertyRepositoryInterface;
use App\Models\Property;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class PropertyRepository extends BaseRepository implements PropertyRepositoryInterface
{
    public function __construct(Property $model)
    {
        parent::__construct($model);
    }

    public function paginateLatest(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->latest()->paginate($perPage);
    }

    public function findPropertyOrFail(int|string $id): Property
    {
        /** @var Property */
        return $this->model->newQuery()->findOrFail($id);
    }
}
