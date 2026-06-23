<?php

namespace App\Modules\Vehicles\Controllers\PrivateSeller;

use App\Http\Controllers\Controller;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Repositories\VehicleMakeRepositoryInterface;
use App\Modules\Vehicles\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Requests\PrivateSeller\StoreVehicleRequest;
use App\Modules\Vehicles\Requests\PrivateSeller\UpdateVehicleRequest;
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
        $user     = $request->user();
        $vehicles = $this->repository->paginateForSeller($user->id, [
            'status' => $request->get('status'),
        ]);
        $remainingSlots = $this->tierService->sellerRemainingVehicleSlots($user);
        $vehicleLimit   = $this->tierService->sellerVehicleLimit($user);

        return view('seller.vehicles.index', compact('vehicles', 'remainingSlots', 'vehicleLimit'));
    }

    public function create(Request $request): View
    {
        $makes = $this->makeRepository->allWithModels();

        // H0: pick a listing type first; the editor adapts to it.
        $type = $request->query('type');
        if ($type !== null && ! in_array($type, Vehicle::types(), true)) {
            $type = null;
        }

        return view('seller.vehicles.create', compact('makes', 'type'));
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $status  = $request->input('action') === 'draft' ? 'draft' : 'pending';
        $vehicle = $this->service->createForSeller($request->user(), $request->validated(), $status);

        $imageNote = '';
        foreach ($request->file('images', []) as $file) {
            try {
                $this->imageUploadService->uploadForVehicleBySeller($request->user(), $vehicle, $file);
            } catch (ListingLimitExceededException | ImageUploadException $e) {
                $imageNote = ' Some images were not added: ' . $e->getMessage();
                break;
            }
        }

        $msg = $status === 'draft' ? 'Draft saved — finish it any time.' : 'Vehicle submitted for admin review.';

        return redirect()
            ->route('seller.vehicles.show', $vehicle)
            ->with('status', $msg . $imageNote);
    }

    public function show(Request $request, Vehicle $vehicle): View
    {
        abort_unless($vehicle->user_id === $request->user()->id, 403);

        $vehicle->load(['make', 'vehicleModel']);

        return view('seller.vehicles.show', compact('vehicle'));
    }

    public function edit(Request $request, Vehicle $vehicle): View
    {
        abort_unless($vehicle->user_id === $request->user()->id, 403);
        abort_unless($vehicle->canBeEdited(), 403, 'Only pending, inactive, or rejected vehicles can be edited.');

        $makes      = $this->makeRepository->allWithModels();
        $images     = $vehicle->images()->orderBy('display_order')->get();
        $imageLimit = $this->tierService->sellerVehicleImageLimit($request->user());

        return view('seller.vehicles.edit', compact('vehicle', 'makes', 'images', 'imageLimit'));
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        abort_unless($vehicle->user_id === $request->user()->id, 403);

        $publishAs = $request->filled('action')
            ? ($request->input('action') === 'draft' ? 'draft' : 'pending')
            : null;

        $this->service->update($vehicle, $request->validated(), $publishAs);

        $msg = $publishAs === 'draft' ? 'Draft saved.' : 'Vehicle updated and submitted for review.';

        return redirect()
            ->route('seller.vehicles.show', $vehicle)
            ->with('status', $msg);
    }

    public function destroy(Request $request, Vehicle $vehicle): RedirectResponse
    {
        abort_unless($vehicle->user_id === $request->user()->id, 403);

        $this->service->delete($vehicle);

        return redirect()
            ->route('seller.vehicles.index')
            ->with('status', 'Vehicle listing deleted.');
    }

    public function renew(Request $request, Vehicle $vehicle): RedirectResponse
    {
        abort_unless($vehicle->user_id === $request->user()->id, 403);

        $this->service->renew($vehicle);

        return back()->with('status', 'Listing renewed — it’s live again.');
    }
}
