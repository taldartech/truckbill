<?php

namespace Tests\Unit;

use App\Services\FreightBookingCalculationService;
use App\Services\LorryReceiptCalculationService;
use PHPUnit\Framework\TestCase;

class LorryReceiptCalculationServiceTest extends TestCase
{
    private LorryReceiptCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LorryReceiptCalculationService(new FreightBookingCalculationService);
    }

    public function test_store_fixed_amount_sets_actual_from_amount(): void
    {
        $result = $this->service->resolveForStore(
            true,
            10.0,
            100.0,
            5000.0,
        );

        $this->assertSame(5000.0, $result['actual_amount']);
        $this->assertSame(5000.0, $result['amount']);
    }

    public function test_store_variable_calculates_actual_from_weight_times_rate(): void
    {
        $result = $this->service->resolveForStore(
            false,
            8.0,
            100.0,
            0.0,
        );

        $this->assertSame(800.0, $result['actual_amount']);
        $this->assertSame(800.0, $result['amount']);
    }

    public function test_update_fixed_uses_submitted_actual_amount_and_deducts_shortage_damage(): void
    {
        $result = $this->service->resolveForUpdate(
            true,
            10.0,
            10.0,
            100.0,
            4200.0,
            200.0,
            100.0,
        );

        $this->assertSame(4200.0, $result['actual_amount']);
        $this->assertSame(3900.0, $result['amount']);
    }

    public function test_update_variable_equal_weights_uses_weight_times_rate(): void
    {
        $result = $this->service->resolveForUpdate(
            false,
            10.0,
            10.0,
            100.0,
            0.0,
            0.0,
            0.0,
        );

        $this->assertSame(1000.0, $result['actual_amount']);
        $this->assertSame(1000.0, $result['amount']);
    }

    public function test_update_variable_different_weights_uses_min_weight(): void
    {
        $result = $this->service->resolveForUpdate(
            false,
            12.0,
            10.0,
            50.0,
            0.0,
            0.0,
            0.0,
        );

        $this->assertSame(500.0, $result['actual_amount']);
        $this->assertSame(500.0, $result['amount']);
    }

    public function test_update_variable_with_shortage_and_damage_deductions(): void
    {
        $result = $this->service->resolveForUpdate(
            false,
            10.0,
            9.0,
            100.0,
            0.0,
            50.0,
            25.0,
        );

        $this->assertSame(900.0, $result['actual_amount']);
        $this->assertSame(825.0, $result['amount']);
    }
}
