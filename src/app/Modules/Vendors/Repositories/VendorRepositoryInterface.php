<?php

namespace App\Modules\Vendors\Repositories;

use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface VendorRepositoryInterface
{
    public function find(string $id): ?Vendor;

    public function findWithDetails(string $id): ?Vendor;

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function findByStatus(string $status): Collection;

    public function create(array $data): Vendor;

    public function update(Vendor $vendor, array $data): Vendor;
}
