<?php

namespace App\Modules\Categories\Repositories;

use App\Modules\Categories\Models\Category;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepositoryInterface
{
    public function find(string $id): ?Category;

    public function allRoots(): Collection;

    public function allWithChildren(): Collection;

    public function create(array $data): Category;

    public function update(Category $category, array $data): Category;

    public function delete(Category $category): bool;
}
