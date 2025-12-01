<?php

declare(strict_types=1);

namespace App\Modules\Import\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Company\Services\CompanyContext;
use App\Modules\Identity\Domain\User;
use App\Modules\Import\Domain\Enums\ImportType;
use App\Modules\Import\Services\MigrationWizardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MigrationWizardController extends Controller
{
    public function __construct(
        private readonly MigrationWizardService $wizardService,
        private readonly CompanyContext $companyContext,
    ) {}

    /**
     * Get recommended import order
     */
    public function order(): JsonResponse
    {
        $importOrder = $this->wizardService->getRecommendedImportOrder();

        $data = array_map(
            fn (ImportType $type) => $this->wizardService->getImportTypeMetadata($type),
            $importOrder
        );

        return response()->json(['data' => $data]);
    }

    /**
     * Check dependencies for an import type
     */
    public function dependencies(Request $request, string $type): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $companyId = $this->companyContext->requireCompanyId();
        $company = $this->companyContext->requireCompany();
        $tenantId = $company->tenant_id;

        try {
            $importType = ImportType::from($type);
        } catch (\ValueError) {
            return response()->json(['error' => 'Invalid import type'], 400);
        }

        $result = $this->wizardService->checkDependencies($tenantId, $importType);

        return response()->json(['data' => $result]);
    }

    /**
     * Suggest column mappings
     */
    public function suggestMapping(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string'],
            'headers' => ['required', 'array'],
            'headers.*' => ['string'],
        ]);

        try {
            $importType = ImportType::from($request->input('type'));
        } catch (\ValueError) {
            return response()->json(['error' => 'Invalid import type'], 400);
        }

        /** @var array<string> $headers */
        $headers = $request->input('headers');
        $suggestions = $this->wizardService->suggestColumnMapping($importType, $headers);

        // Determine unmapped columns
        $mappedSource = array_filter($suggestions);
        $unmappedSource = array_diff($headers, $mappedSource);
        $unmappedTarget = array_keys(array_filter($suggestions, fn ($v) => $v === null));

        return response()->json([
            'data' => [
                'suggestions' => $suggestions,
                'unmapped_source' => array_values($unmappedSource),
                'unmapped_target' => $unmappedTarget,
            ],
        ]);
    }

    /**
     * Generate import template
     */
    public function template(Request $request, string $type): Response
    {
        try {
            $importType = ImportType::from($type);
        } catch (\ValueError) {
            return response()->json(['error' => 'Invalid import type'], 400);
        }

        $template = $this->wizardService->generateTemplate($importType);

        return response($template, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$type}_template.csv\"",
        ]);
    }

    /**
     * Get migration status
     */
    public function status(Request $request): JsonResponse
    {
        $companyId = $this->companyContext->requireCompanyId();
        $company = $this->companyContext->requireCompany();
        $tenantId = $company->tenant_id;

        $status = $this->wizardService->getMigrationStatus($tenantId);

        return response()->json(['data' => $status]);
    }
}
