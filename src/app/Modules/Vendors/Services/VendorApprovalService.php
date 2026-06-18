<?php

namespace App\Modules\Vendors\Services;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Vendors\Events\VendorApprovedEvent;
use App\Modules\Vendors\Events\VendorRejectedEvent;
use App\Modules\Vendors\Events\VendorSuspendedEvent;
use App\Modules\Vendors\Repositories\VendorRepositoryInterface;
use Illuminate\Support\Facades\Log;

class VendorApprovalService
{
    public function __construct(
        private readonly VendorRepositoryInterface $repository
    ) {}

    /**
     * Approve a pending vendor account.
     */
    public function approve(Vendor $vendor, User $admin): void
    {
        $this->repository->update($vendor, [
            'status'      => 'approved',
            'verified_at' => now(),
        ]);

        Log::info('Vendor approved', ['vendor_id' => $vendor->id, 'by' => $admin->id]);

        event(new VendorApprovedEvent($vendor->refresh()));
    }

    /**
     * Reject a vendor application with a reason.
     */
    public function reject(Vendor $vendor, User $admin, string $reason): void
    {
        $this->repository->update($vendor, ['status' => 'pending']);

        Log::info('Vendor rejected', ['vendor_id' => $vendor->id, 'by' => $admin->id]);

        event(new VendorRejectedEvent($vendor->refresh(), $reason));
    }

    /**
     * Suspend an active vendor account.
     */
    public function suspend(Vendor $vendor, User $admin, string $reason): void
    {
        $this->repository->update($vendor, [
            'status'       => 'suspended',
            'suspended_at' => now(),
        ]);

        Log::warning('Vendor suspended', ['vendor_id' => $vendor->id, 'by' => $admin->id]);

        event(new VendorSuspendedEvent($vendor->refresh(), $reason));
    }

    /**
     * Reactivate a suspended vendor account.
     */
    public function reactivate(Vendor $vendor, User $admin): void
    {
        $this->repository->update($vendor, [
            'status'       => 'approved',
            'suspended_at' => null,
        ]);

        Log::info('Vendor reactivated', ['vendor_id' => $vendor->id, 'by' => $admin->id]);
    }
}
