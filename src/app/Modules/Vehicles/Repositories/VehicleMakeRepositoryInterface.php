<?php

namespace App\Modules\Vehicles\Repositories;

use App\Modules\Vehicles\Models\VehicleMake;
use Illuminate\Database\Eloquent\Collection;

interface VehicleMakeRepositoryInterface
{
    public function allWithModels(): Collection;

    public function find(string $id): ?VehicleMake;
}
