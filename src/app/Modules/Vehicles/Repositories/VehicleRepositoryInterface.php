<?php

namespace App\Modules\Vehicles\Repositories;

use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface VehicleRepositoryInterface
{
    public function find(string $id): ?Vehicle;

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function paginateForVendor(string $vendorId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function paginateForSeller(string $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function paginatePublic(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * @return array<int, string>
     */
    public function suggest(string $term, int $limit = 8): array;

    public function create(array $data): Vehicle;

    public function update(Vehicle $vehicle, array $data): Vehicle;

    public function delete(Vehicle $vehicle): void;
}
