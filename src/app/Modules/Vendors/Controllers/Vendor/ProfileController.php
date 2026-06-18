<?php

namespace App\Modules\Vendors\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Modules\Vendors\Repositories\VendorRepositoryInterface;
use App\Modules\Vendors\Requests\Vendor\UpdateVendorProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private readonly VendorRepositoryInterface $repository
    ) {}

    public function show(Request $request): View
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);

        $vendor->load(['admins', 'workers', 'documents', 'bankAccounts']);

        return view('vendor.profile.show', compact('vendor'));
    }

    public function edit(Request $request): View
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);

        return view('vendor.profile.edit', compact('vendor'));
    }

    public function update(UpdateVendorProfileRequest $request): RedirectResponse
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);

        $this->repository->update($vendor, $request->validated());

        return redirect()
            ->route('vendor.profile.show')
            ->with('status', 'Profile updated successfully.');
    }
}
