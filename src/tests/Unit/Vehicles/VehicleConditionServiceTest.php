<?php

namespace Tests\Unit\Vehicles;

use App\Modules\Vehicles\Services\VehicleConditionService;
use PHPUnit\Framework\TestCase;

class VehicleConditionServiceTest extends TestCase
{
    private VehicleConditionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new VehicleConditionService();
    }

    // VIN validation
    public function test_valid_vin_passes(): void
    {
        $this->assertTrue($this->service->validateVin('1HGCM82633A123456'));
    }

    public function test_vin_with_invalid_character_I_fails(): void
    {
        $this->assertFalse($this->service->validateVin('1HGCM82633AI23456'));
    }

    public function test_vin_with_invalid_character_O_fails(): void
    {
        $this->assertFalse($this->service->validateVin('1HGCM82633AO23456'));
    }

    public function test_vin_with_invalid_character_Q_fails(): void
    {
        $this->assertFalse($this->service->validateVin('1HGCM82633AQ23456'));
    }

    public function test_vin_less_than_17_chars_fails(): void
    {
        $this->assertFalse($this->service->validateVin('1HGCM8263'));
    }

    public function test_vin_more_than_17_chars_fails(): void
    {
        $this->assertFalse($this->service->validateVin('1HGCM82633A1234567890'));
    }

    // Year validation
    public function test_valid_year_passes(): void
    {
        $this->assertTrue($this->service->validateYear(2020));
    }

    public function test_year_below_1900_fails(): void
    {
        $this->assertFalse($this->service->validateYear(1899));
    }

    public function test_year_too_far_in_future_fails(): void
    {
        $this->assertFalse($this->service->validateYear((int) date('Y') + 2));
    }

    public function test_next_year_passes(): void
    {
        $this->assertTrue($this->service->validateYear((int) date('Y') + 1));
    }

    // Mileage / condition validation
    public function test_new_vehicle_with_zero_mileage_passes(): void
    {
        $this->assertTrue($this->service->validateMileageForCondition('new', 0));
    }

    public function test_new_vehicle_with_positive_mileage_fails(): void
    {
        $this->assertFalse($this->service->validateMileageForCondition('new', 100));
    }

    public function test_used_vehicle_with_positive_mileage_passes(): void
    {
        $this->assertTrue($this->service->validateMileageForCondition('used', 50000));
    }

    public function test_used_vehicle_with_zero_mileage_passes(): void
    {
        $this->assertTrue($this->service->validateMileageForCondition('used', 0));
    }

    public function test_negative_mileage_fails_for_used(): void
    {
        $this->assertFalse($this->service->validateMileageForCondition('used', -1));
    }
}
