<?php

declare(strict_types=1);

namespace Tests\Unit\Inventory;

use App\Modules\Document\Domain\Document;
use App\Modules\Document\Domain\DocumentAdditionalCost;
use App\Modules\Document\Domain\DocumentLine;
use App\Modules\Inventory\Application\Services\LandedCostService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class LandedCostServiceTest extends TestCase
{
    private LandedCostService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LandedCostService();
    }

    public function test_calculate_allocated_cost_with_valid_inputs(): void
    {
        $result = $this->service->calculateAllocatedCost(
            lineTotal: 100.00,
            subtotal: 500.00,
            additionalCostsTotal: 50.00
        );

        // 100/500 = 0.2, 0.2 * 50 = 10
        $this->assertEquals(10.00, $result);
    }

    public function test_calculate_allocated_cost_with_zero_subtotal(): void
    {
        $result = $this->service->calculateAllocatedCost(
            lineTotal: 100.00,
            subtotal: 0.00,
            additionalCostsTotal: 50.00
        );

        $this->assertEquals(0.00, $result);
    }

    public function test_calculate_allocated_cost_with_zero_additional_costs(): void
    {
        $result = $this->service->calculateAllocatedCost(
            lineTotal: 100.00,
            subtotal: 500.00,
            additionalCostsTotal: 0.00
        );

        $this->assertEquals(0.00, $result);
    }

    public function test_calculate_landed_unit_cost(): void
    {
        $result = $this->service->calculateLandedUnitCost(
            lineTotal: 100.00,
            allocatedCost: 10.00,
            quantity: 5.00
        );

        // (100 + 10) / 5 = 22
        $this->assertEquals(22.00, $result);
    }

    public function test_calculate_landed_unit_cost_with_zero_quantity(): void
    {
        $result = $this->service->calculateLandedUnitCost(
            lineTotal: 100.00,
            allocatedCost: 10.00,
            quantity: 0.00
        );

        $this->assertEquals(0.00, $result);
    }

    public function test_allocate_costs_with_single_line(): void
    {
        // Mock line
        $line = Mockery::mock(DocumentLine::class);
        $line->shouldReceive('getAttribute')->with('total')->andReturn('100.00');
        $line->shouldReceive('getAttribute')->with('quantity')->andReturn('5.00');
        $line->shouldReceive('getAttribute')->with('unit_price')->andReturn('20.00');
        $line->shouldReceive('setAttribute')->with('allocated_costs', '50.00')->once();
        $line->shouldReceive('setAttribute')->with('landed_unit_cost', '30.00')->once();
        $line->shouldReceive('save')->once()->andReturn(true);

        // Mock lines collection
        $lines = new Collection([$line]);

        // Mock additional costs query
        $additionalCostsQuery = Mockery::mock();
        $additionalCostsQuery->shouldReceive('sum')->with('amount')->andReturn(50.00);

        // Mock document
        $document = Mockery::mock(Document::class);
        $document->shouldReceive('getAttribute')->with('lines')->andReturn($lines);
        $document->shouldReceive('additionalCosts')->andReturn($additionalCostsQuery);

        // Execute
        $this->service->allocateCosts($document);

        // Assertions handled by Mockery expectations
        $this->assertTrue(true);
    }

    public function test_allocate_costs_proportionally_to_multiple_lines(): void
    {
        // Line 1: 300 out of 500 total (60%) -> should get 60% of 100 = 60
        $line1 = Mockery::mock(DocumentLine::class);
        $line1->shouldReceive('getAttribute')->with('total')->andReturn('300.00');
        $line1->shouldReceive('getAttribute')->with('quantity')->andReturn('10.00');
        $line1->shouldReceive('getAttribute')->with('unit_price')->andReturn('30.00');
        $line1->shouldReceive('setAttribute')->with('allocated_costs', '60.00')->once();
        $line1->shouldReceive('setAttribute')->with('landed_unit_cost', '36.00')->once();
        $line1->shouldReceive('save')->once();

        // Line 2: 200 out of 500 total (40%) -> should get 40% of 100 = 40
        $line2 = Mockery::mock(DocumentLine::class);
        $line2->shouldReceive('getAttribute')->with('total')->andReturn('200.00');
        $line2->shouldReceive('getAttribute')->with('quantity')->andReturn('5.00');
        $line2->shouldReceive('getAttribute')->with('unit_price')->andReturn('40.00');
        $line2->shouldReceive('setAttribute')->with('allocated_costs', '40.00')->once();
        $line2->shouldReceive('setAttribute')->with('landed_unit_cost', '48.00')->once();
        $line2->shouldReceive('save')->once();

        $lines = new Collection([$line1, $line2]);

        $additionalCostsQuery = Mockery::mock();
        $additionalCostsQuery->shouldReceive('sum')->with('amount')->andReturn(100.00);

        $document = Mockery::mock(Document::class);
        $document->shouldReceive('getAttribute')->with('lines')->andReturn($lines);
        $document->shouldReceive('additionalCosts')->andReturn($additionalCostsQuery);

        $this->service->allocateCosts($document);

        $this->assertTrue(true);
    }

    public function test_get_allocation_breakdown(): void
    {
        // Create mock product
        $product = Mockery::mock();
        $product->shouldReceive('getAttribute')->with('name')->andReturn('Test Product');

        // Create mock line
        $line = Mockery::mock(DocumentLine::class);
        $line->shouldReceive('getAttribute')->with('id')->andReturn('line-1');
        $line->shouldReceive('getAttribute')->with('product')->andReturn($product);
        $line->shouldReceive('getAttribute')->with('description')->andReturn('Test Description');
        $line->shouldReceive('getAttribute')->with('quantity')->andReturn('5.00');
        $line->shouldReceive('getAttribute')->with('unit_price')->andReturn('20.00');
        $line->shouldReceive('getAttribute')->with('total')->andReturn('100.00');
        $line->shouldReceive('getAttribute')->with('allocated_costs')->andReturn('10.00');
        $line->shouldReceive('getAttribute')->with('landed_unit_cost')->andReturn('22.00');

        $lines = new Collection([$line]);

        $document = Mockery::mock(Document::class);
        $document->shouldReceive('getAttribute')->with('lines')->andReturn($lines);

        $result = $this->service->getAllocationBreakdown($document);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('line-1', $result[0]['line_id']);
        $this->assertEquals('Test Product', $result[0]['product_name']);
        $this->assertEquals('5.00', $result[0]['quantity']);
        $this->assertEquals('20.00', $result[0]['unit_price']);
        $this->assertEquals('100.00', $result[0]['line_total']);
        $this->assertEquals('10.00', $result[0]['allocated_costs']);
        $this->assertEquals('22.00', $result[0]['landed_unit_cost']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
