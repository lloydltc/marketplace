<?php

namespace App\Modules\Vehicles\Services;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Vehicles\Events\VehicleApprovedEvent;
use App\Modules\Vehicles\Events\VehicleCreatedEvent;
use App\Modules\Vehicles\Events\VehicleRejectedEvent;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleFeatureValue;
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
        private readonly \App\Modules\Settings\Services\SettingsService $settings,
    ) {}

    public function createForVendor(Vendor $vendor, array $data, string $status = 'pending'): Vehicle
    {
        $this->wallet->assertCanList($vendor);
        $this->tierService->assertCanCreateVehicleForVendor($vendor);
        $features = $this->pullFeatures($data);
        // Drafts may be partial — only fully validate when actually publishing.
        if ($status !== 'draft') {
            $this->conditionService->validate($data);
        }

        $data['vendor_id'] = $vendor->id;
        $data['user_id']   = null;
        $data['status']    = $status;

        $vehicle = $this->repository->create($data);
        $this->syncFeatures($vehicle, $features);

        Log::info('Vehicle listed by vendor', ['vehicle_id' => $vehicle->id, 'vendor_id' => $vendor->id, 'status' => $status]);

        // Only a real submission (not a draft) enters the review pipeline.
        if ($status !== 'draft') {
            event(new VehicleCreatedEvent($vehicle));
        }

        return $vehicle;
    }

    public function createForSeller(User $user, array $data, string $status = 'pending'): Vehicle
    {
        $this->tierService->assertCanCreateVehicleForSeller($user);
        $features = $this->pullFeatures($data);
        if ($status !== 'draft') {
            $this->conditionService->validate($data);
        }

        $data['user_id']   = $user->id;
        $data['vendor_id'] = null;
        $data['status']    = $status;

        $vehicle = $this->repository->create($data);
        $this->syncFeatures($vehicle, $features);

        Log::info('Vehicle listed by private seller', ['vehicle_id' => $vehicle->id, 'user_id' => $user->id, 'status' => $status]);

        if ($status !== 'draft') {
            event(new VehicleCreatedEvent($vehicle));
        }

        return $vehicle;
    }

    /**
     * @param  ?string  $publishAs  'draft' to keep/return to draft, 'pending' to
     *   submit for review, or null to leave the status rules as-is.
     */
    public function update(Vehicle $vehicle, array $data, ?string $publishAs = null): Vehicle
    {
        $features = $this->pullFeatures($data);
        if ($publishAs !== 'draft') {
            $this->conditionService->validate($data);
        }

        if ($publishAs === 'draft') {
            $data['status'] = 'draft';
        } elseif ($publishAs === 'pending' || $vehicle->isDraft() || $vehicle->isRejected()) {
            // Publishing a draft (or resubmitting a rejected listing) enters review.
            $data['status'] = 'pending';
        }

        $wasDraft = $vehicle->isDraft();
        $vehicle  = $this->repository->update($vehicle, $data);
        $this->syncFeatures($vehicle, $features);

        // A draft being published for the first time enters the review pipeline.
        if ($wasDraft && ($data['status'] ?? null) === 'pending') {
            event(new VehicleCreatedEvent($vehicle));
        }

        return $vehicle;
    }

    /** Extract the dynamic feature map from request data so it isn't mass-assigned. */
    private function pullFeatures(array &$data): ?array
    {
        if (! array_key_exists('features', $data)) {
            return null; // not submitted → leave existing values untouched
        }

        $features = $data['features'] ?? [];
        unset($data['features']);

        return is_array($features) ? $features : [];
    }

    /**
     * Persist dynamic feature values. A blank value clears that feature; a null
     * $features (not submitted) leaves existing values untouched.
     *
     * @param  array<string, mixed>|null  $features  [definition_id => value]
     */
    private function syncFeatures(Vehicle $vehicle, ?array $features): void
    {
        if ($features === null) {
            return;
        }

        foreach ($features as $definitionId => $value) {
            $value = is_string($value) ? trim($value) : $value;

            if ($value === null || $value === '') {
                VehicleFeatureValue::where('vehicle_id', $vehicle->id)
                    ->where('feature_definition_id', $definitionId)->delete();
                continue;
            }

            VehicleFeatureValue::updateOrCreate(
                ['vehicle_id' => $vehicle->id, 'feature_definition_id' => $definitionId],
                ['value' => (string) $value],
            );
        }
    }

    public function approve(Vehicle $vehicle, User $admin): void
    {
        // D5: publishing starts the lead-gen expiry clock (config-driven).
        $this->repository->update($vehicle, array_merge(
            ['status' => 'active'],
            $this->lifecycleOnPublish(),
        ));

        Log::info('Vehicle approved', ['vehicle_id' => $vehicle->id, 'by' => $admin->id]);

        event(new VehicleApprovedEvent($vehicle->refresh()));
    }

    /**
     * D5: renew an expired (or active) vehicle listing — resets the expiry clock,
     * stamps renewed_at, increments the count, returns it to active. Free at
     * launch (renewal fee hook lives in platform_settings).
     */
    public function renew(Vehicle $vehicle): Vehicle
    {
        $days = max(1, $this->settings->getInt('listings.vehicle_expiry_days', 60));

        $vehicle = $this->repository->update($vehicle, [
            'status'       => 'active',
            'published_at' => $vehicle->published_at ?? now(),
            'expires_at'   => now()->addDays($days),
            'renewed_at'   => now(),
            'expiry_count' => ($vehicle->expiry_count ?? 0) + 1,
        ]);

        Log::info('Vehicle listing renewed', ['vehicle_id' => $vehicle->id, 'count' => $vehicle->expiry_count]);

        return $vehicle;
    }

    /** @return array{published_at?: \Illuminate\Support\Carbon, expires_at?: \Illuminate\Support\Carbon} */
    private function lifecycleOnPublish(): array
    {
        if (! $this->settings->getBool('listings.vehicle_expiry_enabled', true)) {
            return ['published_at' => now()];
        }

        $days = max(1, $this->settings->getInt('listings.vehicle_expiry_days', 60));

        return ['published_at' => now(), 'expires_at' => now()->addDays($days)];
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
