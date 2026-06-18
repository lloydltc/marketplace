<?php

namespace App\Modules\Products\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Categories\Repositories\CategoryRepositoryInterface;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Repositories\ProductRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
        private readonly CategoryRepositoryInterface $categoryRepository
    ) {}

    public function index(Request $request): View
    {
        $products   = $this->repository->paginatePublic([
            'category_id' => $request->input('category'),
            'min_price'   => $request->input('min_price'),
            'max_price'   => $request->input('max_price'),
            'fulfilment'  => $request->input('fulfilment'),
            'search'      => $request->input('q'),
            'sort'        => $request->input('sort', 'latest'),
        ]);

        $categories = $this->categoryRepository->allWithChildren();

        return view('products.index', compact('products', 'categories'));
    }

    public function show(Product $product): View
    {
        abort_unless($product->isActive(), 404);

        $product->load(['vendor', 'category']);

        return view('products.show', compact('product'));
    }
}
