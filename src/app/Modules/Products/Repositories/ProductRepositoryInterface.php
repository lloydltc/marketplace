<?php

namespace App\Modules\Products\Repositories;

use App\Modules\Products\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    public function find(string $id): ?Product;

    public function findForVendor(string $id, string $vendorId): ?Product;

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function paginateForVendor(string $vendorId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function paginatePublic(array $filters = [], int $perPage = 24): LengthAwarePaginator;

    /**
     * @return array<int, string>
     */
    public function suggest(string $term, int $limit = 8): array;

    public function create(array $data): Product;

    public function update(Product $product, array $data): Product;

    public function delete(Product $product): void;
}
