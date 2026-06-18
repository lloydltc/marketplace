<?php

namespace App\Modules\Vehicles\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Repositories\VehicleMakeRepositoryInterface;
use App\Modules\Vehicles\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Requests\Vendor\StoreVehicleRequest;
use App\Modules\Vehicles\Requests\Vendor\UpdateVehicleRequest;
use App\Modules\Media\Exceptions\ImageUploadException;
use App\Modules\Media\Services\ImageUploadService;
use App\Modules\Vehicles\Services\VehicleService;
use App\Modules\Verification\Exceptions\ListingLimitExceededException;
use App\Modules\Verification\Services\TierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function __construct(
        private readonly VehicleRepositoryInterface $repository,
        private readonly VehicleService $service,
        private readonly VehicleMakeRepositoryInterface $makeRepository,
        private readonly TierService $tierService,
        private readonly ImageUploadService $imageUploadService,
    ) {}

    public function index(Request $request): View
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);

        $vehicles         = $this->repository->paginateForVendor($vendor->id, [
            'status' => $request->get('status'),
        ]);
        $remainingSlots   = $this->tierService->vendorRemainingVehicleSlots($vendor);
        $vehicleLimit     = $this->tierService->vendorVehicleLimit($vendor);

        return view('vendor.vehicles.index', compact('vehicles', 'vendor', 'remainingSlots', 'vehicleLimit'));
    }

    public function create(Request $request): View
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);
        abort_unless($vendor->isApproved(), 403, 'Your vendor account must be approved before listing vehicles.');

        $makes = $this->makeRepository->allWithModels();

        return view('vendor.vehicles.create', compact('vendor', 'makes'));
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);

        $vehicle = $this->service->createForVendor($vendor, $request->validated());

        $imageNote = '';
        foreach ($request->file('images', []) as $file) {
            try {
                $this->imageUploadService->uploadForVehicleByVendor($vendor, $vehicle, $file);
            } catch (ListingLimitExceededException | ImageUploadException $e) {
                $imageNote = ' Some images were not added: ' . $e->getMessage();
                break;
            }
        }

        return redirect()
            ->route('vendor.vehicles.show', $vehicle)
            ->with('status', 'Vehicle submitted for admin review.' . $imageNote);
    }

    public function show(Request $request, Vehicle $vehicle): View
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);
        abort_unless($vehicle->vendor_id === $vendor->id, 403);

        $vehicle->load(['make', 'vehicleModel']);

        return view('vendor.vehicles.show', compact('vehicle', 'vendor'));
    }

    public function edit(Request $request, Vehicle $vehicle): View
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);
        abort_unless($vehicle->vendor_id === $vendor->id, 403);
        abort_unless($vehicle->canBeEdited(), 403, 'Only pending, inactive, or rejected vehicles can be edited.');

        $makes      = $this->makeRepository->allWithModels();
        $images     = $vehicle->images()->orderBy('display_order')->get();
        $imageLimit = $this->tierService->vendorVehicleImageLimit($vendor);

        return view('vendor.vehicles.edit', compact('vehicle', 'vendor', 'makes', 'images', 'imageLimit'));
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);
        abort_unless($vehicle->vendor_id === $vendor->id, 403);

        $this->service->update($vehicle, $request->validated());

        return redirect()
            ->route('vendor.vehicles.show', $vehicle)
            ->with('status', 'Vehicle updated and resubmitted for review.');
    }

    public function destroy(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);
        abort_unless($vehicle->vendor_id === $vendor->id, 403);

        $this->service->delete($vehicle);

        return redirect()
            ->route('vendor.vehicles.index')
            ->with('status', 'Vehicle listing deleted.');
    }
}
