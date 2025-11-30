<?php

declare(strict_types=1);

namespace Tests\Unit\Inventory;

use App\Modules\Inventory\Domain\Services\StockAdjustmentService;
use Tests\TestCase;

class StockAdjustmentServiceTest extends TestCase
{
    public function test_stock_adjustment_service_exists(): void
    {
        $this->assertTrue(class_exists(StockAdjustmentService::class));
    }

    public function test_service_has_adjust_method(): void
    {
        $this->assertTrue(method_exists(StockAdjustmentService::class, 'adjust'));
    }

    public function test_service_has_receive_method(): void
    {
        $this->assertTrue(method_exists(StockAdjustmentService::class, 'receive'));
    }

    public function test_service_has_issue_method(): void
    {
        $this->assertTrue(method_exists(StockAdjustmentService::class, 'issue'));
    }

    public function test_service_has_transfer_method(): void
    {
        $this->assertTrue(method_exists(StockAdjustmentService::class, 'transfer'));
    }

    public function test_service_has_reserve_method(): void
    {
        $this->assertTrue(method_exists(StockAdjustmentService::class, 'reserve'));
    }

    public function test_service_has_release_reservation_method(): void
    {
        $this->assertTrue(method_exists(StockAdjustmentService::class, 'releaseReservation'));
    }
}
