<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Services;

use App\Modules\Company\Domain\Location;
use App\Modules\Inventory\Domain\StockLevel;
use App\Modules\Inventory\Domain\StockMovement;
use App\Modules\Product\Domain\Product;

/**
 * WeightedAverageCostService - Manages weighted average cost calculations for inventory
 */
class WeightedAverageCostService
{
    /**
     * Record a purchase and update weighted average cost
     */
    public function recordPurchase(
        Product $product,
        Location $location,
        float $quantity,
        float $landedUnitCost,
        ?string $reference = null
    ): StockMovement {
        $stockLevel = StockLevel::firstOrCreate(
            [
                'product_id' => $product->id,
                'location_id' => $location->id,
                'tenant_id' => $product->tenant_id,
            ],
            ['quantity' => 0]
        );

        $currentQty = (float) $stockLevel->quantity;
        $currentCostPrice = (float) ($product->cost_price ?? 0);
        $currentValue = $currentQty * $currentCostPrice;

        $newQty = $currentQty + $quantity;
        $newValue = $currentValue + ($quantity * $landedUnitCost);

        $newAvgCost = $newQty > 0 ? round($newValue / $newQty, 2) : 0;

        // Record movement
        $movement = StockMovement::create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->id,
            'location_id' => $location->id,
            'company_id' => $location->company_id,
            'movement_type' => 'purchase',
            'quantity' => $quantity,
            'quantity_before' => $currentQty,
            'quantity_after' => $newQty,
            'unit_cost' => (string) $landedUnitCost,
            'total_cost' => (string) ($quantity * $landedUnitCost),
            'avg_cost_before' => (string) $currentCostPrice,
            'avg_cost_after' => (string) $newAvgCost,
            'reference' => $reference,
        ]);

        // Update stock level
        $stockLevel->quantity = (string) $newQty;
        $stockLevel->save();

        // Update product cost
        $product->cost_price = (string) $newAvgCost;
        $product->last_purchase_cost = (string) $landedUnitCost;
        $product->cost_updated_at = now();
        $product->save();

        return $movement;
    }

    /**
     * Record a sale (cost comes out at current average)
     */
    public function recordSale(
        Product $product,
        Location $location,
        float $quantity,
        ?string $reference = null
    ): StockMovement {
        $stockLevel = StockLevel::where('product_id', $product->id)
            ->where('location_id', $location->id)
            ->where('tenant_id', $product->tenant_id)
            ->firstOrFail();

        $costPrice = (float) ($product->cost_price ?? 0);
        $currentQty = (float) $stockLevel->quantity;

        $movement = StockMovement::create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->id,
            'location_id' => $location->id,
            'company_id' => $location->company_id,
            'movement_type' => 'sale',
            'quantity' => -$quantity,
            'quantity_before' => $currentQty,
            'quantity_after' => $currentQty - $quantity,
            'unit_cost' => (string) $costPrice,
            'total_cost' => (string) ($quantity * $costPrice),
            'avg_cost_before' => (string) $costPrice,
            'avg_cost_after' => (string) $costPrice, // WAC doesn't change on sale
            'reference' => $reference,
        ]);

        // Update stock level (cost stays same)
        $stockLevel->quantity = (string) ($currentQty - $quantity);
        $stockLevel->save();

        return $movement;
    }

    /**
     * Record a return (stock comes back at original cost)
     */
    public function recordReturn(
        Product $product,
        Location $location,
        float $quantity,
        float $originalCost,
        ?string $reference = null
    ): StockMovement {
        // Similar to purchase but with type 'return'
        $stockLevel = StockLevel::firstOrCreate(
            [
                'product_id' => $product->id,
                'location_id' => $location->id,
                'tenant_id' => $product->tenant_id,
            ],
            ['quantity' => 0]
        );

        $currentQty = (float) $stockLevel->quantity;
        $currentCostPrice = (float) ($product->cost_price ?? 0);
        $currentValue = $currentQty * $currentCostPrice;

        $newQty = $currentQty + $quantity;
        $newValue = $currentValue + ($quantity * $originalCost);

        $newAvgCost = $newQty > 0 ? round($newValue / $newQty, 2) : 0;

        // Record movement
        $movement = StockMovement::create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->id,
            'location_id' => $location->id,
            'company_id' => $location->company_id,
            'movement_type' => 'return',
            'quantity' => $quantity,
            'quantity_before' => $currentQty,
            'quantity_after' => $newQty,
            'unit_cost' => (string) $originalCost,
            'total_cost' => (string) ($quantity * $originalCost),
            'avg_cost_before' => (string) $currentCostPrice,
            'avg_cost_after' => (string) $newAvgCost,
            'reference' => $reference,
        ]);

        // Update stock level
        $stockLevel->quantity = (string) $newQty;
        $stockLevel->save();

        // Update product cost
        $product->cost_price = (string) $newAvgCost;
        $product->cost_updated_at = now();
        $product->save();

        return $movement;
    }

    /**
     * Calculate what the new weighted average cost would be without recording
     */
    public function calculateNewWAC(
        float $currentQty,
        float $currentCost,
        float $newQty,
        float $newCost
    ): float {
        $currentValue = $currentQty * $currentCost;
        $newValue = $newQty * $newCost;
        $totalQty = $currentQty + $newQty;

        if ($totalQty <= 0) {
            return 0;
        }

        return round(($currentValue + $newValue) / $totalQty, 2);
    }
}
