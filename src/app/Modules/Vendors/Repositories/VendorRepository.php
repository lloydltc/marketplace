<?php

namespace App\Modules\Vendors\Repositories;

use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class VendorRepository implements VendorRepositoryInterface
{
    public function __construct(private readonly Vendor $model) {}

    public function find(string $id): ?Vendor
    {
        return $this->model->withTrashed(false)->find($id);
    }

    public function findWithDetails(string $id): ?Vendor
    {
        return $this->model
            ->with(['admins', 'workers', 'bankAccounts', 'documents'])
            ->find($id);
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->query()->with(['admins']);

        if (! empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', $search)
                  ->orWhere('contact_email', 'ilike', $search)
                  ->orWhere('tax_id', 'ilike', $search);
            });
        }

        if (! empty($filters['tier'])) {
            $query->where('tier', $filters['tier']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function findByStatus(string $status): Collection
    {
        return $this->model->byStatus($status)->get();
    }

    public function create(array $data): Vendor
    {
        return $this->model->create($data);
    }

    public function update(Vendor $vendor, array $data): Vendor
    {
        $vendor->update($data);

        return $vendor->refresh();
    }
}
