<?php

namespace App\Modules\Products\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Repositories\ProductRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository
    ) {}

    public function index(Request $request): View
    {
        $products = $this->repository->paginate([
            'status'      => $request->get('status'),
            'vendor_id'   => $request->get('vendor_id'),
            'category_id' => $request->get('category_id'),
            'search'      => $request->get('search'),
        ]);

        return view('admin.products.index', compact('products'));
    }

    public function show(Product $product): View
    {
        $product->load(['vendor', 'category']);

        return view('admin.products.show', compact('product'));
    }
}
