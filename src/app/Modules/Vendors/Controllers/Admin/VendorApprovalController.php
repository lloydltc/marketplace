<?php

namespace App\Modules\Vendors\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Modules\Vendors\Requests\Admin\ApproveVendorRequest;
use App\Modules\Vendors\Requests\Admin\RejectVendorRequest;
use App\Modules\Vendors\Requests\Admin\SuspendVendorRequest;
use App\Modules\Vendors\Services\VendorApprovalService;
use Illuminate\Http\RedirectResponse;

class VendorApprovalController extends Controller
{
    public function __construct(
        private readonly VendorApprovalService $approvalService
    ) {}

    public function approve(ApproveVendorRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->approvalService->approve($vendor, $request->user());

        return redirect()
            ->route('admin.vendors.show', $vendor)
            ->with('status', 'Vendor approved successfully.');
    }

    public function reject(RejectVendorRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->approvalService->reject($vendor, $request->user(), $request->validated('reason'));

        return redirect()
            ->route('admin.vendors.show', $vendor)
            ->with('status', 'Vendor application rejected.');
    }

    public function suspend(SuspendVendorRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->approvalService->suspend($vendor, $request->user(), $request->validated('reason'));

        return redirect()
            ->route('admin.vendors.show', $vendor)
            ->with('status', 'Vendor account suspended.');
    }

    public function reactivate(ApproveVendorRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->approvalService->reactivate($vendor, $request->user());

        return redirect()
            ->route('admin.vendors.show', $vendor)
            ->with('status', 'Vendor account reactivated.');
    }
}
