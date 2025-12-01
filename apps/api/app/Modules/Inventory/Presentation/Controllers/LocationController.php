<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Presentation\Controllers;

use App\Modules\Company\Domain\Location;
use App\Modules\Company\Services\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LocationController extends Controller
{
    public function __construct(
        private readonly CompanyContext $companyContext,
    ) {}

    public function index(): JsonResponse
    {
        $companyId = $this->companyContext->requireCompanyId();

        $locations = Location::query()
            ->where('company_id', $companyId)
            ->orderBy('code')
            ->get();

        return response()->json([
            'data' => $locations->map(fn (Location $location): array => [
                'id' => $location->id,
                'code' => $location->code,
                'name' => $location->name,
                'address' => $location->full_address,
                'is_active' => $location->is_active,
                'is_default' => $location->is_default,
            ]),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $companyId = $this->companyContext->requireCompanyId();

        $location = Location::query()
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $location->id,
                'code' => $location->code,
                'name' => $location->name,
                'address' => $location->full_address,
                'is_active' => $location->is_active,
                'is_default' => $location->is_default,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->companyContext->requireCompanyId();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'address_street' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $location = Location::create([
            'company_id' => $companyId,
            'code' => $validated['code'],
            'name' => $validated['name'],
            'address_street' => $validated['address_street'] ?? null,
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
