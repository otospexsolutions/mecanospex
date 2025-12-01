<?php

declare(strict_types=1);

namespace App\Modules\Partner\Presentation\Controllers;

use App\Modules\Company\Services\CompanyContext;
use App\Modules\Identity\Domain\User;
use App\Modules\Partner\Application\DTOs\PartnerData;
use App\Modules\Partner\Domain\Partner;
use App\Modules\Partner\Presentation\Requests\CreatePartnerRequest;
use App\Modules\Partner\Presentation\Requests\UpdatePartnerRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PartnerController extends Controller
{
    public function __construct(
        private readonly CompanyContext $companyContext,
    ) {}

    /**
     * List all partners for the current company.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->companyContext->requireCompanyId();

        $query = Partner::query()
            ->where('company_id', $companyId);

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        // Search by name, email, or VAT number
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('vat_number', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $partners = $query->orderBy('name')->paginate($perPage);

        $data = $partners->getCollection()->map(
            fn (Partner $partner) => PartnerData::fromModel($partner)
        );

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $partners->currentPage(),
                'per_page' => $partners->perPage(),
                'total' => $partners->total(),
                'last_page' => $partners->lastPage(),
                'timestamp' => now()->toIso8601String(),
                'request_id' => $request->header('X-Request-ID', (string) uuid_create()),
            ],
        ]);
    }

    /**
     * Get a single partner.
     */
    public function show(Request $request, string $partner): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $partnerModel = Partner::where('company_id', $this->companyContext->requireCompanyId())
            ->where('id', $partner)
            ->first();

        if (! $partnerModel) {
            return response()->json([
                'error' => [
                    'code' => 'PARTNER_NOT_FOUND',
                    'message' => 'Partner not found',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                    'request_id' => $request->header('X-Request-ID', (string) uuid_create()),
                ],
            ], 404);
        }

        return response()->json([
            'data' => PartnerData::fromModel($partnerModel),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'request_id' => $request->header('X-Request-ID', (string) uuid_create()),
            ],
        ]);
    }

    /**
     * Create a new partner.
     */
    public function store(CreatePartnerRequest $request): JsonResponse
    {
        $companyId = $this->companyContext->requireCompanyId();
        $company = $this->companyContext->requireCompany();
        $tenantId = $company->tenant_id;

        /** @var array<string, mixed> $validated */
        $validated = $request->validated();

        $partner = Partner::create([
            'tenant_id' => $tenantId,
            'company_id' => $companyId,
            ...$validated,
        ]);

        return response()->json([
            'data' => PartnerData::fromModel($partner),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'request_id' => $request->header('X-Request-ID', (string) uuid_create()),
            ],
        ], 201);
    }

    /**
     * Update an existing partner.
     */
    public function update(UpdatePartnerRequest $request, string $partner): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $partnerModel = Partner::where('company_id', $this->companyContext->requireCompanyId())
            ->where('id', $partner)
            ->first();

        if (! $partnerModel) {
            return response()->json([
                'error' => [
                    'code' => 'PARTNER_NOT_FOUND',
                    'message' => 'Partner not found',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                    'request_id' => $request->header('X-Request-ID', (string) uuid_create()),
                ],
            ], 404);
        }

        /** @var array<string, mixed> $validated */
        $validated = $request->validated();
        $partnerModel->update($validated);

        /** @var Partner $freshPartner */
        $freshPartner = $partnerModel->fresh();

        return response()->json([
            'data' => PartnerData::fromModel($freshPartner),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'request_id' => $request->header('X-Request-ID', (string) uuid_create()),
            ],
        ]);
    }

    /**
     * Delete a partner (soft delete).
     */
    public function destroy(Request $request, string $partner): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $partnerModel = Partner::where('company_id', $this->companyContext->requireCompanyId())
            ->where('id', $partner)
            ->first();

        if (! $partnerModel) {
            return response()->json([
                'error' => [
                    'code' => 'PARTNER_NOT_FOUND',
                    'message' => 'Partner not found',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                    'request_id' => $request->header('X-Request-ID', (string) uuid_create()),
                ],
            ], 404);
        }

        $partnerModel->delete();

        return response()->json(null, 204);
    }
}
