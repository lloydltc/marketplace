<?php

namespace App\Modules\Categories\Services;

use App\Modules\Categories\Models\Category;
use App\Modules\Categories\Repositories\CategoryRepositoryInterface;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public function __construct(
        private readonly CategoryRepositoryInterface $repository
    ) {}

    /**
     * Create a new category.
     */
    public function create(array $data): Category
    {
        return $this->repository->create($data);
    }

    /**
     * Update an existing category.
     */
    public function update(Category $category, array $data): Category
    {
        // Prevent circular parent assignment (category cannot be its own ancestor)
        if (! empty($data['parent_id'])) {
            $this->ensureNotCircularParent($category, $data['parent_id']);
        }

        return $this->repository->update($category, $data);
    }

    /**
     * Delete a category. Blocked if it has active children.
     *
     * @throws ValidationException
     */
    public function delete(Category $category): void
    {
        if ($category->hasChildren()) {
            throw ValidationException::withMessages([
                'category' => 'Cannot delete a category that has sub-categories. Move or delete the sub-categories first.',
            ]);
        }

        $this->repository->delete($category);
    }

    /**
     * Reorder a batch of categories by updating their sort_order.
     *
     * @param array<string, int> $order  [category_id => sort_order]
     */
    public function reorder(array $order): void
    {
        foreach ($order as $id => $sortOrder) {
            $category = $this->repository->find($id);

            if ($category) {
                $this->repository->update($category, ['sort_order' => (int) $sortOrder]);
            }
        }
    }

    private function ensureNotCircularParent(Category $category, string $newParentId): void
    {
        if ($newParentId === $category->id) {
            throw ValidationException::withMessages([
                'parent_id' => 'A category cannot be its own parent.',
            ]);
        }

        // Walk up the proposed parent's ancestry to detect cycles
        $ancestor = $this->repository->find($newParentId);

        while ($ancestor && $ancestor->parent_id) {
            if ($ancestor->parent_id === $category->id) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Setting this parent would create a circular hierarchy.',
                ]);
            }

            $ancestor = $this->repository->find($ancestor->parent_id);
        }
    }
}
