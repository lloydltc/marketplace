<?php

namespace App\Modules\Vehicles\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Requests\Admin\ApproveVehicleRequest;
use App\Modules\Vehicles\Requests\Admin\RejectVehicleRequest;
use App\Modules\Vehicles\Services\VehicleService;
use Illuminate\Http\RedirectResponse;

class VehicleApprovalController extends Controller
{
    public function __construct(
        private readonly VehicleService $service
    ) {}

    public function approve(ApproveVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $this->service->approve($vehicle, $request->user());

        return redirect()
            ->route('admin.vehicles.show', $vehicle)
            ->with('status', 'Vehicle approved and is now live.');
    }

    public function reject(RejectVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $this->service->reject($vehicle, $request->user(), $request->validated('reason'));

        return redirect()
            ->route('admin.vehicles.show', $vehicle)
            ->with('status', 'Vehicle rejected. The seller has been notified.');
    }
}
