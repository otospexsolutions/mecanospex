<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Presentation\Controllers;

use App\Modules\Identity\Domain\User;
use App\Modules\Inventory\Domain\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $locations = Location::query()
            ->where('tenant_id', $user->tenant_id)
            ->orderBy('code')
            ->get();

        return response()->json([
            'data' => $locations->map(fn (Location $location) => [
                'id' => $location->id,
                'code' => $location->code,
                'name' => $location->name,
                'address' => $location->address,
                'is_active' => $location->is_active,
                'is_default' => $location->is_default,
            ]),
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $location = Location::query()
            ->where('tenant_id', $user->tenant_id)
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $location->id,
                'code' => $location->code,
                'name' => $location->name,
                'address' => $location->address,
                'is_active' => $location->is_active,
                'is_default' => $location->is_default,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $location = Location::create([
            'tenant_id' => $user->tenant_id,
            'code' => $validated['code'],
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'is_active' => true,
            'is_default' => $validated['is_default'] ?? false,
        ]);

        return response()->json([
            'data' => [
                'id' => $location->id,
                'code' => $location->code,
                'name' => $location->name,
            ],
        ], 201);
    }
}
