<?php

namespace App\Modules\Vendors\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Vendors\Repositories\VendorRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function __construct(
        private readonly VendorRepositoryInterface $repository
    ) {}

    public function index(Request $request): View
    {
        $vendors = $this->repository->paginate(
            filters: $request->only('status', 'search', 'tier'),
            perPage: 20
        );

        return view('admin.vendors.index', compact('vendors'));
    }

    public function show(string $id): View
    {
        $vendor = $this->repository->findWithDetails($id);

        abort_if($vendor === null, 404, 'Vendor not found.');

        return view('admin.vendors.show', compact('vendor'));
    }
}
