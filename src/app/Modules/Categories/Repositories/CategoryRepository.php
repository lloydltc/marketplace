<?php

namespace App\Modules\Categories\Repositories;

use App\Modules\Categories\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(private readonly Category $model) {}

    public function find(string $id): ?Category
    {
        return $this->model->with('parent', 'children')->find($id);
    }

    public function allRoots(): Collection
    {
        return $this->model->roots()->with('children')->get();
    }

    public function allWithChildren(): Collection
    {
        return $this->model
            ->roots()
            ->with(['children' => fn ($q) => $q->with('children')])
            ->get();
    }

    public function create(array $data): Category
    {
        return $this->model->create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category->refresh();
    }

    public function delete(Category $category): bool
    {
        return (bool) $category->delete();
    }
}
