<?php

namespace App\Modules\Categories\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Categories\Models\Category;
use App\Modules\Categories\Repositories\CategoryRepositoryInterface;
use App\Modules\Categories\Requests\Admin\StoreCategoryRequest;
use App\Modules\Categories\Requests\Admin\UpdateCategoryRequest;
use App\Modules\Categories\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryRepositoryInterface $repository,
        private readonly CategoryService $service
    ) {}

    public function index(): View
    {
        $categories = $this->repository->allWithChildren();

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $parentOptions = $this->repository->allRoots();

        return view('admin.categories.create', compact('parentOptions'));
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Category created successfully.');
    }

    public function edit(Category $category): View
    {
        $parentOptions = $this->repository->allRoots()
            ->reject(fn ($c) => $c->id === $category->id);

        return view('admin.categories.edit', compact('category', 'parentOptions'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->service->update($category, $request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->service->delete($category);

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Category deleted.');
    }
}
