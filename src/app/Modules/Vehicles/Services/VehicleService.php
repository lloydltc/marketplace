<?php

namespace App\Modules\Vehicles\Services;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Vehicles\Events\VehicleApprovedEvent;
use App\Modules\Vehicles\Events\VehicleCreatedEvent;
use App\Modules\Vehicles\Events\VehicleRejectedEvent;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Repositories\VehicleRepositoryInterface;
use App\Modules\Verification\Services\TierService;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Support\Facades\Log;

class VehicleService
{
    public function __construct(
        private readonly VehicleRepositoryInterface $repository,
        private readonly VehicleConditionService $conditionService,
        private readonly TierService $tierService,
        private readonly WalletService $wallet,
    ) {}

    public function createForVendor(Vendor $vendor, array $data): Vehicle
    {
        $this->wallet->assertCanList($vendor);
        $this->tierService->assertCanCreateVehicleForVendor($vendor);
        $this->conditionService->validate($data);

        $data['vendor_id'] = $vendor->id;
        $data['user_id']   = null;
        $data['status']    = 'pending';

        $vehicle = $this->repository->create($data);

        Log::info('Vehicle listed by vendor', ['vehicle_id' => $vehicle->id, 'vendor_id' => $vendor->id]);

        event(new VehicleCreatedEvent($vehicle));

        return $vehicle;
    }

    public function createForSeller(User $user, array $data): Vehicle
    {
        $this->tierService->assertCanCreateVehicleForSeller($user);
        $this->conditionService->validate($data);

        $data['user_id']   = $user->id;
        $data['vendor_id'] = null;
        $data['status']    = 'pending';

        $vehicle = $this->repository->create($data);

        Log::info('Vehicle listed by private seller', ['vehicle_id' => $vehicle->id, 'user_id' => $user->id]);

        event(new VehicleCreatedEvent($vehicle));

        return $vehicle;
    }

    public function update(Vehicle $vehicle, array $data): Vehicle
    {
        $this->conditionService->validate($data);

        // Resubmitting a rejected vehicle resets to pending for re-review
        if ($vehicle->isRejected()) {
            $data['status'] = 'pending';
        }

        return $this->repository->update($vehicle, $data);
    }

    public function approve(Vehicle $vehicle, User $admin): void
    {
        $this->repository->update($vehicle, ['status' => 'active']);

        Log::info('Vehicle approved', ['vehicle_id' => $vehicle->id, 'by' => $admin->id]);

        event(new VehicleApprovedEvent($vehicle->refresh()));
    }

    public function reject(Vehicle $vehicle, User $admin, string $reason): void
    {
        $this->repository->update($vehicle, ['status' => 'rejected']);

        Log::info('Vehicle rejected', ['vehicle_id' => $vehicle->id, 'by' => $admin->id]);

        event(new VehicleRejectedEvent($vehicle->refresh(), $reason));
    }

    public function deactivate(Vehicle $vehicle): void
    {
        $this->repository->update($vehicle, ['status' => 'inactive']);
    }

    public function delete(Vehicle $vehicle): void
    {
        $this->repository->delete($vehicle);
    }
}
