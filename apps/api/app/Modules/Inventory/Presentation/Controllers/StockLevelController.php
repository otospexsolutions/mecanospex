<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Presentation\Controllers;

use App\Modules\Identity\Domain\User;
use App\Modules\Inventory\Domain\StockLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class StockLevelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = StockLevel::query()
            ->where('tenant_id', $user->tenant_id)
            ->with(['product', 'location']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        if ($request->has('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        $stockLevels = $query->orderBy('created_at', 'desc')->paginate(20);

        $data = $stockLevels->getCollection()->map(fn (StockLevel $level) => [
            'id' => $level->id,
            'product_id' => $level->product_id,
            'product_name' => $level->product->name ?? null,
            'location_id' => $level->location_id,
            'location_name' => $level->location->name ?? null,
            'quantity' => $level->quantity,
            'reserved' => $level->reserved,
            'available' => $level->getAvailableQuantity(),
            'min_quantity' => $level->min_quantity,
            'max_quantity' => $level->max_quantity,
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $stockLevels->currentPage(),
                'last_page' => $stockLevels->lastPage(),
                'per_page' => $stockLevels->perPage(),
                'total' => $stockLevels->total(),
            ],
        ]);
    }

    public function show(Request $request, string $productId, string $locationId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $stockLevel = StockLevel::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->with(['product', 'location'])
            ->firstOrFail();

        return response()->json([
            'data' => [
                'id' => $stockLevel->id,
                'product_id' => $stockLevel->product_id,
                'product_name' => $stockLevel->product->name ?? null,
                'location_id' => $stockLevel->location_id,
                'location_name' => $stockLevel->location->name ?? null,
                'quantity' => $stockLevel->quantity,
                'reserved' => $stockLevel->reserved,
                'available' => $stockLevel->getAvailableQuantity(),
                'min_quantity' => $stockLevel->min_quantity,
                'max_quantity' => $stockLevel->max_quantity,
            ],
        ]);
    }
}
