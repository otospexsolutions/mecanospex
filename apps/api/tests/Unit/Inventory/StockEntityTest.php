<?php

declare(strict_types=1);

namespace Tests\Unit\Inventory;

use App\Modules\Company\Domain\Location;
use App\Modules\Inventory\Domain\Enums\MovementType;
use App\Modules\Inventory\Domain\StockLevel;
use App\Modules\Inventory\Domain\StockMovement;
use Tests\TestCase;

class StockEntityTest extends TestCase
{
    public function test_stock_level_class_exists(): void
    {
        $this->assertTrue(class_exists(StockLevel::class));
    }

    public function test_location_class_exists(): void
    {
        $this->assertTrue(class_exists(Location::class));
    }

    public function test_stock_movement_class_exists(): void
    {
        $this->assertTrue(class_exists(StockMovement::class));
    }

    public function test_movement_type_enum_exists(): void
    {
        $this->assertTrue(enum_exists(MovementType::class));
    }

    public function test_movement_type_has_required_cases(): void
    {
        $cases = MovementType::cases();
        $caseValues = array_map(fn ($case) => $case->value, $cases);

        $this->assertContains('receipt', $caseValues);
        $this->assertContains('issue', $caseValues);
        $this->assertContains('transfer_in', $caseValues);
        $this->assertContains('transfer_out', $caseValues);
        $this->assertContains('adjustment', $caseValues);
    }

    public function test_stock_level_has_required_properties(): void
    {
        $stockLevel = new StockLevel;
        $fillable = $stockLevel->getFillable();

        $this->assertContains('tenant_id', $fillable);
        $this->assertContains('product_id', $fillable);
        $this->assertContains('location_id', $fillable);
        $this->assertContains('quantity', $fillable);
        $this->assertContains('reserved', $fillable);
    }

    public function test_location_has_required_properties(): void
    {
        $location = new Location;
        $fillable = $location->getFillable();

        $this->assertContains('company_id', $fillable);
        $this->assertContains('code', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('is_active', $fillable);
    }

    public function test_stock_movement_has_required_properties(): void
    {
        $movement = new StockMovement;
        $fillable = $movement->getFillable();

        $this->assertContains('tenant_id', $fillable);
        $this->assertContains('product_id', $fillable);
        $this->assertContains('location_id', $fillable);
        $this->assertContains('movement_type', $fillable);
        $this->assertContains('quantity', $fillable);
        $this->assertContains('reference', $fillable);
    }

    public function test_stock_level_has_available_quantity_method(): void
    {
        $this->assertTrue(method_exists(StockLevel::class, 'getAvailableQuantity'));
    }
}
