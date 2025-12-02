<?php

declare(strict_types=1);

namespace Tests\Unit\Inventory;

use App\Modules\Inventory\Application\Services\WeightedAverageCostService;
use Tests\TestCase;

class WeightedAverageCostServiceTest extends TestCase
{
    private WeightedAverageCostService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WeightedAverageCostService();
    }

    public function test_calculate_new_wac_with_first_purchase(): void
    {
        $result = $this->service->calculateNewWAC(
            currentQty: 0,
            currentCost: 0,
            newQty: 10,
            newCost: 50.00
        );

        // First purchase: WAC = new cost
        $this->assertEquals(50.00, $result);
    }

    public function test_calculate_new_wac_with_second_purchase(): void
    {
        $result = $this->service->calculateNewWAC(
            currentQty: 10,
            currentCost: 50.00,
            newQty: 10,
            newCost: 60.00
        );

        // (10*50 + 10*60) / 20 = 1100/20 = 55
        $this->assertEquals(55.00, $result);
    }

    public function test_calculate_new_wac_with_different_quantities(): void
    {
        $result = $this->service->calculateNewWAC(
            currentQty: 5,
            currentCost: 40.00,
            newQty: 15,
            newCost: 60.00
        );

        // (5*40 + 15*60) / 20 = (200 + 900)/20 = 1100/20 = 55
        $this->assertEquals(55.00, $result);
    }

    public function test_calculate_new_wac_with_zero_total_quantity(): void
    {
        $result = $this->service->calculateNewWAC(
            currentQty: 0,
            currentCost: 0,
            newQty: 0,
            newCost: 0
        );

        $this->assertEquals(0.00, $result);
    }

    public function test_service_exists(): void
    {
        $this->assertTrue(class_exists(WeightedAverageCostService::class));
    }

    public function test_service_has_record_purchase_method(): void
    {
        $this->assertTrue(method_exists(WeightedAverageCostService::class, 'recordPurchase'));
    }

    public function test_service_has_record_sale_method(): void
    {
        $this->assertTrue(method_exists(WeightedAverageCostService::class, 'recordSale'));
    }

    public function test_service_has_record_return_method(): void
    {
        $this->assertTrue(method_exists(WeightedAverageCostService::class, 'recordReturn'));
    }
}
