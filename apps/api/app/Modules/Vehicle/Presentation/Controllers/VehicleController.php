<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Domain\User;
use App\Modules\Vehicle\Application\DTOs\VehicleData;
use App\Modules\Vehicle\Domain\Vehicle;
use App\Modules\Vehicle\Presentation\Requests\CreateVehicleRequest;
use App\Modules\Vehicle\Presentation\Requests\UpdateVehicleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VehicleController extends Controller
{
    /**
     * List all vehicles for the current tenant
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = Vehicle::forTenant($user->tenant_id);

        // Filter by partner
        $partnerId = $request->query('partner_id');
        if (is_string($partnerId) && $partnerId !== '') {
            $query->where('partner_id', $partnerId);
        }

        // Search by license plate, VIN, or brand
        $search = $request->query('search');
        if (is_string($search) && $search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('license_plate', 'like', "%{$search}%")
                    ->orWhere('vin', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
            });
        }

        $vehicles = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'data' => $vehicles->map(fn (Vehicle $vehicle): VehicleData => VehicleData::fromModel($vehicle)),
            'meta' => [
                'current_page' => $vehicles->currentPage(),
                'per_page' => $vehicles->perPage(),
                'total' => $vehicles->total(),
            ],
        ]);
    }

    /**
     * Get a single vehicle
     */
    public function show(Request $request, string $vehicle): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $vehicleModel = Vehicle::forTenant($user->tenant_id)->find($vehicle);

        if ($vehicleModel === null) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Vehicle not found',
                ],
            ], 404);
        }

        return response()->json([
            'data' => VehicleData::fromModel($vehicleModel),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create a new vehicle
     */
    public function store(CreateVehicleRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array<string, mixed> $validated */
        $validated = $request->validated();

        $vehicleModel = Vehicle::create([
            ...$validated,
            'tenant_id' => $user->tenant_id,
        ]);

        /** @var Vehicle $freshVehicle */
        $freshVehicle = $vehicleModel->fresh();

        return response()->json([
            'data' => VehicleData::fromModel($freshVehicle),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Update an existing vehicle
     */
    public function update(UpdateVehicleRequest $request, string $vehicle): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $vehicleModel = Vehicle::forTenant($user->tenant_id)->find($vehicle);

        if ($vehicleModel === null) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Vehicle not found',
                ],
            ], 404);
        }

        /** @var array<string, mixed> $validated */
        $validated = $request->validated();

        $vehicleModel->update($validated);

        /** @var Vehicle $freshVehicle */
        $freshVehicle = $vehicleModel->fresh();

        return response()->json([
            'data' => VehicleData::fromModel($freshVehicle),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Delete a vehicle (soft delete)
     */
    public function destroy(Request $request, string $vehicle): Response|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $vehicleModel = Vehicle::forTenant($user->tenant_id)->find($vehicle);

        if ($vehicleModel === null) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Vehicle not found',
                ],
            ], 404);
        }

        $vehicleModel->delete();

        return response()->noContent();
    }
}
