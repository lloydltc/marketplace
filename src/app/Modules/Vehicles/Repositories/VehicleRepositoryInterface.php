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
     * H6: count active (unexpired) public listings matching the given filters —
     * powers the live "show N results" count without paying for hydration.
     */
    public function countPublic(array $filters = []): int;

    /**
     * H6: active public listing counts keyed by vehicle_type (for the type tabs).
     *
     * @return array<string, int>
     */
    public function countByType(): array;

    /**
     * H6: active public listing counts keyed by body_type, optionally scoped to a
     * single vehicle_type (for "browse by body type").
     *
     * @return array<string, int>
     */
    public function countByBodyType(?string $vehicleType = null): array;

    /**
     * H6: makes that have at least one active public listing, with their counts,
     * ordered by inventory depth (for "browse by make").
     *
     * @return \Illuminate\Support\Collection<int, object{id: string, name: string, total: int}>
     */
    public function popularMakes(int $limit = 12): \Illuminate\Support\Collection;

    /**
     * H7: currently-sponsored (featured & unexpired) active listings, newest
     * promotion first — for "Sponsored" promo rows.
     *
     * @return \Illuminate\Support\Collection<int, Vehicle>
     */
    public function sponsored(int $limit = 4): \Illuminate\Support\Collection;

    /**
     * @return array<int, string>
     */
    public function suggest(string $term, int $limit = 8): array;

    public function create(array $data): Vehicle;

    public function update(Vehicle $vehicle, array $data): Vehicle;

    public function delete(Vehicle $vehicle): void;
}
