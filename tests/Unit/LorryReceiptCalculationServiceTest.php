<?php

namespace Tests\Unit;

use App\Services\LorryReceiptCalculationService;
use PHPUnit\Framework\TestCase;

class LorryReceiptCalculationServiceTest extends TestCase
{
    private LorryReceiptCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LorryReceiptCalculationService;
    }

    public function test_store_fixed_amount_sets_actual_from_amount_and_nets_shortage_damage(): void
    {
        $result = $this->service->resolveForStore(
            true,
            10.0,
            10.0,
            100.0,
            5000.0,
            null,
            100.0,
            50.0,
        );

        $this->assertSame(5000.0, $result['actual_amount']);
        $this->assertSame(4850.0, $result['amount']);
    }

    public function test_store_variable_different_weights_uses_min_weight_times_rate(): void
    {
        $result = $this->service->resolveForStore(
            false,
            10.0,
            8.0,
            100.0,
            0.0,
            null,
            0.0,
            0.0,
        );

        $this->assertSame(800.0, $result['actual_amount']);
        $this->assertSame(800.0, $result['amount']);
    }

    public function test_store_variable_equal_weights_without_manual_actual_calculates_from_rate(): void
    {
        $result = $this->service->resolveForStore(
            false,
            10.0,
            10.0,
            100.0,
            999.0,
            null,
            0.0,
            0.0,
        );

        $this->assertSame(1000.0, $result['actual_amount']);
        $this->assertSame(1000.0, $result['amount']);
    }

    public function test_store_variable_equal_weights_with_manual_actual_uses_manual(): void
    {
        $result = $this->service->resolveForStore(
            false,
            10.0,
            10.0,
            100.0,
            999.0,
            950.0,
            0.0,
            0.0,
        );

        $this->assertSame(950.0, $result['actual_amount']);
        $this->assertSame(950.0, $result['amount']);
    }

    public function test_store_net_amount_never_negative(): void
    {
        $result = $this->service->resolveForStore(
            true,
            10.0,
            10.0,
            100.0,
            100.0,
            null,
            80.0,
            50.0,
        );

        $this->assertSame(100.0, $result['actual_amount']);
        $this->assertSame(0.0, $result['amount']);
    }

    public function test_update_fixed_uses_submitted_actual_amount(): void
    {
        $result = $this->service->resolveForUpdate(
            true,
            10.0,
            10.0,
            100.0,
            4200.0,
            null,
            200.0,
            100.0,
        );

        $this->assertSame(4200.0, $result['actual_amount']);
        $this->assertSame(3900.0, $result['amount']);
    }

    public function test_update_variable_equal_weights_keeps_manual_actual(): void
    {
        $result = $this->service->resolveForUpdate(
            false,
            10.0,
            10.0,
            100.0,
            880.0,
            880.0,
            0.0,
            0.0,
        );

        $this->assertSame(880.0, $result['actual_amount']);
        $this->assertSame(880.0, $result['amount']);
    }

    public function test_update_variable_different_weights_recalculates_actual(): void
    {
        $result = $this->service->resolveForUpdate(
            false,
            12.0,
            10.0,
            50.0,
            999.0,
            999.0,
            0.0,
            0.0,
        );

        $this->assertSame(500.0, $result['actual_amount']);
        $this->assertSame(500.0, $result['amount']);
    }

    public function test_store_linked_batch_style_variable_with_unloading_weight_and_deductions(): void
    {
        $result = $this->service->resolveForStore(
            false,
            10.0,
            9.0,
            100.0,
            0.0,
            null,
            50.0,
            25.0,
        );

        $this->assertSame(900.0, $result['actual_amount']);
        $this->assertSame(825.0, $result['amount']);
    }
}
