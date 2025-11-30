<?php

declare(strict_types=1);

namespace App\Modules\Document\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Document\Application\DTOs\DocumentData;
use App\Modules\Document\Domain\Document;
use App\Modules\Document\Domain\DocumentLine;
use App\Modules\Document\Domain\Enums\DocumentStatus;
use App\Modules\Document\Domain\Enums\DocumentType;
use App\Modules\Document\Domain\Services\DocumentNumberingService;
use App\Modules\Document\Presentation\Requests\CreateDocumentRequest;
use App\Modules\Document\Presentation\Requests\UpdateDocumentRequest;
use App\Modules\Identity\Domain\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentNumberingService $numberingService,
    ) {}

    /**
     * List documents of a specific type
     */
    public function index(Request $request, DocumentType $type): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = Document::forTenant($user->tenant_id)->ofType($type);

        // Filter by status
        $status = $request->query('status');
        if (is_string($status) && $status !== '') {
            $statusEnum = DocumentStatus::tryFrom($status);
            if ($statusEnum !== null) {
                $query->inStatus($statusEnum);
            }
        }

        // Filter by partner
        $partnerId = $request->query('partner_id');
        if (is_string($partnerId) && $partnerId !== '') {
            $query->where('partner_id', $partnerId);
        }

        // Search by document number
        $search = $request->query('search');
        if (is_string($search) && $search !== '') {
            $query->where('document_number', 'like', "%{$search}%");
        }

        $documents = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'data' => $documents->map(fn (Document $doc): DocumentData => DocumentData::fromModel($doc, false)),
            'meta' => [
                'current_page' => $documents->currentPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ],
        ]);
    }

    /**
     * Get a single document
     */
    public function show(Request $request, DocumentType $type, string $document): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $documentModel = Document::forTenant($user->tenant_id)
            ->ofType($type)
            ->with('lines')
            ->find($document);

        if ($documentModel === null) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Document not found',
                ],
            ], 404);
        }

        return response()->json([
            'data' => DocumentData::fromModel($documentModel),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create a new document
     */
    public function store(CreateDocumentRequest $request, DocumentType $type): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array<string, mixed> $validated */
        $validated = $request->validated();

        /** @var array<int, array{description: string, quantity: string, unit_price: string, product_id?: string, tax_rate?: string, discount_percent?: string, discount_amount?: string, notes?: string}> $lines */
        $lines = $validated['lines'] ?? [];
        unset($validated['lines']);

        return DB::transaction(function () use ($user, $type, $validated, $lines): JsonResponse {
            // Generate document number
            $documentNumber = $this->numberingService->generateNumber($user->tenant_id, $type);

            // Calculate totals from lines
            $subtotal = '0.00';
            $taxAmount = '0.00';

            foreach ($lines as $line) {
                /** @var numeric-string $quantity */
                $quantity = $line['quantity'];
                /** @var numeric-string $unitPrice */
                $unitPrice = $line['unit_price'];
                /** @var numeric-string $taxRate */
                $taxRate = $line['tax_rate'] ?? '0';

                $lineSubtotal = bcmul($quantity, $unitPrice, 2);
                $lineTax = bcmul($lineSubtotal, bcdiv($taxRate, '100', 4), 2);

                $subtotal = bcadd($subtotal, $lineSubtotal, 2);
                $taxAmount = bcadd($taxAmount, $lineTax, 2);
            }

            $total = bcadd($subtotal, $taxAmount, 2);

            // Create document
            $document = Document::create([
                ...$validated,
                'tenant_id' => $user->tenant_id,
                'type' => $type,
                'status' => DocumentStatus::Draft,
                'document_number' => $documentNumber,
                'currency' => $validated['currency'] ?? 'EUR',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
            ]);

            // Create lines
            foreach ($lines as $index => $lineData) {
                /** @var numeric-string $quantity */
                $quantity = $lineData['quantity'];
                /** @var numeric-string $unitPrice */
                $unitPrice = $lineData['unit_price'];
                $lineTotal = bcmul($quantity, $unitPrice, 2);

                DocumentLine::create([
                    'document_id' => $document->id,
                    'product_id' => $lineData['product_id'] ?? null,
                    'line_number' => $index + 1,
                    'description' => $lineData['description'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_percent' => $lineData['discount_percent'] ?? null,
                    'discount_amount' => $lineData['discount_amount'] ?? null,
                    'tax_rate' => $lineData['tax_rate'] ?? null,
                    'line_total' => $lineTotal,
                    'notes' => $lineData['notes'] ?? null,
                ]);
            }

            /** @var Document $freshDocument */
            $freshDocument = $document->fresh(['lines']);

            return response()->json([
                'data' => DocumentData::fromModel($freshDocument),
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 201);
        });
    }

    /**
     * Update an existing document
     */
    public function update(UpdateDocumentRequest $request, DocumentType $type, string $document): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $documentModel = Document::forTenant($user->tenant_id)
            ->ofType($type)
            ->find($document);

        if ($documentModel === null) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Document not found',
                ],
            ], 404);
        }

        if (! $documentModel->isEditable()) {
            return response()->json([
                'error' => [
                    'code' => 'DOCUMENT_NOT_EDITABLE',
                    'message' => 'Posted documents cannot be modified',
                ],
            ], 422);
        }

        /** @var array<string, mixed> $validated */
        $validated = $request->validated();

        /** @var array<int, array{description: string, quantity: string, unit_price: string, product_id?: string, tax_rate?: string, discount_percent?: string, discount_amount?: string, notes?: string}>|null $lines */
        $lines = $validated['lines'] ?? null;
        unset($validated['lines']);

        return DB::transaction(function () use ($documentModel, $validated, $lines): JsonResponse {
            // Update document fields (excluding lines)
            $documentModel->update($validated);

            // If lines are provided, replace all lines
            if ($lines !== null) {
                // Delete existing lines
                $documentModel->lines()->delete();

                // Calculate totals from new lines
                $subtotal = '0.00';
                $taxAmount = '0.00';

                foreach ($lines as $index => $lineData) {
                    /** @var numeric-string $quantity */
                    $quantity = $lineData['quantity'];
                    /** @var numeric-string $unitPrice */
                    $unitPrice = $lineData['unit_price'];
                    /** @var numeric-string $taxRate */
                    $taxRate = $lineData['tax_rate'] ?? '0';

                    $lineSubtotal = bcmul($quantity, $unitPrice, 2);
                    $lineTax = bcmul($lineSubtotal, bcdiv($taxRate, '100', 4), 2);

                    $subtotal = bcadd($subtotal, $lineSubtotal, 2);
                    $taxAmount = bcadd($taxAmount, $lineTax, 2);

                    DocumentLine::create([
                        'document_id' => $documentModel->id,
                        'product_id' => $lineData['product_id'] ?? null,
                        'line_number' => $index + 1,
                        'description' => $lineData['description'],
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'discount_percent' => $lineData['discount_percent'] ?? null,
                        'discount_amount' => $lineData['discount_amount'] ?? null,
                        'tax_rate' => $taxRate,
                        'line_total' => $lineSubtotal,
                        'notes' => $lineData['notes'] ?? null,
                    ]);
                }

                $total = bcadd($subtotal, $taxAmount, 2);

                $documentModel->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total' => $total,
                ]);
            }

            /** @var Document $freshDocument */
            $freshDocument = $documentModel->fresh(['lines']);

            return response()->json([
                'data' => DocumentData::fromModel($freshDocument),
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        });
    }

    /**
     * Delete a document (soft delete)
     */
    public function destroy(Request $request, DocumentType $type, string $document): Response|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $documentModel = Document::forTenant($user->tenant_id)
            ->ofType($type)
            ->find($document);

        if ($documentModel === null) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Document not found',
                ],
            ], 404);
        }

        if (! $documentModel->isDeletable()) {
            return response()->json([
                'error' => [
                    'code' => 'DOCUMENT_NOT_DELETABLE',
                    'message' => 'Posted documents cannot be deleted. Use cancellation instead.',
                ],
            ], 422);
        }

        $documentModel->delete();

        return response()->noContent();
    }
}
