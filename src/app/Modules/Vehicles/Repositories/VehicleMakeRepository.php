<?php

namespace App\Modules\Vehicles\Repositories;

use App\Modules\Vehicles\Models\VehicleMake;
use Illuminate\Database\Eloquent\Collection;

class VehicleMakeRepository implements VehicleMakeRepositoryInterface
{
    public function __construct(private readonly VehicleMake $model) {}

    public function allWithModels(): Collection
    {
        return $this->model
            ->orderBy('sort_order')
            ->orderBy('name')
            ->with(['models' => fn ($q) => $q->orderBy('name')])
            ->get();
    }

    public function find(string $id): ?VehicleMake
    {
        return $this->model->with('models')->find($id);
    }
}
