<?php

namespace App\Modules\Vehicles\Services;

use InvalidArgumentException;

class VehicleConditionService
{
    // Valid body types
    public const BODY_TYPES = [
        'sedan', 'hatchback', 'suv', 'pickup', 'van', 'minivan',
        'wagon', 'coupe', 'convertible', 'bus', 'truck', 'other',
    ];

    // Valid transmissions (must match DB enum)
    public const TRANSMISSIONS = ['manual', 'automatic', 'cvt'];

    // Valid fuel types (must match DB enum)
    public const FUEL_TYPES = ['petrol', 'diesel', 'electric', 'hybrid'];

    // Valid conditions (must match DB enum)
    public const CONDITIONS = ['new', 'used', 'salvage', 'rebuilt'];

    public function validateMileageForCondition(string $condition, int $mileage): bool
    {
        if ($condition === 'new' && $mileage !== 0) {
            return false;
        }

        if (in_array($condition, ['used', 'salvage', 'rebuilt'], true) && $mileage < 0) {
            return false;
        }

        return true;
    }

    public function validateVin(?string $vin): bool
    {
        if ($vin === null) {
            return true;
        }

        return (bool) preg_match('/^[A-HJ-NPR-Z0-9]{17}$/i', $vin);
    }

    public function validateYear(int $year): bool
    {
        $currentYear = (int) date('Y');

        return $year >= 1900 && $year <= $currentYear + 1;
    }

    public function validate(array $data): void
    {
        $currentYear = (int) date('Y');

        if (! $this->validateYear((int) $data['year'])) {
            throw new InvalidArgumentException(
                'Year must be between 1900 and ' . ($currentYear + 1) . '.'
            );
        }

        $condition = $data['condition'];
        $mileage   = (int) ($data['mileage'] ?? 0);

        if (! $this->validateMileageForCondition($condition, $mileage)) {
            throw new InvalidArgumentException(
                $condition === 'new' ? 'New vehicles must have 0 mileage.' : 'Mileage cannot be negative.'
            );
        }

        if (isset($data['vin']) && ! $this->validateVin($data['vin'])) {
            throw new InvalidArgumentException(
                'VIN must be exactly 17 characters using letters and digits (I, O, Q not allowed).'
            );
        }
    }
}
