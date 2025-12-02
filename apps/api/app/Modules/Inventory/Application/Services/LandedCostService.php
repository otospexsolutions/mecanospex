<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Services;

use App\Modules\Document\Domain\Document;
use App\Modules\Document\Domain\DocumentLine;

/**
 * LandedCostService - Allocates additional costs to purchase order lines
 */
class LandedCostService
{
    /**
     * Allocate additional costs to purchase order lines proportionally by value
     */
    public function allocateCosts(Document $purchaseOrder): void
    {
        $lines = $purchaseOrder->lines;
        $additionalCostsTotal = (float) $purchaseOrder->additionalCosts()->sum('amount');
        $subtotal = (float) $lines->sum('total');

        foreach ($lines as $line) {
            if ($subtotal > 0 && $additionalCostsTotal > 0) {
                $proportion = (float) $line->total / $subtotal;
                $allocatedCost = round($additionalCostsTotal * $proportion, 2);
            } else {
                $allocatedCost = 0;
            }

            $line->allocated_costs = (string) $allocatedCost;
            $line->landed_unit_cost = (float) $line->quantity > 0
                ? (string) round(((float) $line->total + $allocatedCost) / (float) $line->quantity, 2)
                : $line->unit_price;
            $line->save();
        }
    }

    /**
     * Get breakdown of cost allocation for display
     *
     * @return array<int, array{line_id: string, product_name: string, quantity: string, unit_price: string, line_total: string, allocated_costs: string, landed_unit_cost: string}>
     */
    public function getAllocationBreakdown(Document $purchaseOrder): array
    {
        $result = [];

        foreach ($purchaseOrder->lines as $line) {
            $result[] = [
                'line_id' => $line->id,
                'product_name' => $line->product?->name ?? $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'line_total' => $line->total,
                'allocated_costs' => $line->allocated_costs ?? '0.00',
                'landed_unit_cost' => $line->landed_unit_cost ?? $line->unit_price,
            ];
        }

        return $result;
    }

    /**
     * Calculate what the allocated cost would be for a line without saving
     */
    public function calculateAllocatedCost(float $lineTotal, float $subtotal, float $additionalCostsTotal): float
    {
        if ($subtotal <= 0 || $additionalCostsTotal <= 0) {
            return 0;
        }

        $proportion = $lineTotal / $subtotal;

        return round($additionalCostsTotal * $proportion, 2);
    }

    /**
     * Calculate landed unit cost for a line
     */
    public function calculateLandedUnitCost(float $lineTotal, float $allocatedCost, float $quantity): float
    {
        if ($quantity <= 0) {
            return 0;
        }

        return round(($lineTotal + $allocatedCost) / $quantity, 2);
    }
}
