<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Document\Domain\Document;
use App\Modules\Document\Domain\DocumentAdditionalCost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocumentAdditionalCostController extends Controller
{
    /**
     * List additional costs for a document
     */
    public function index(Document $document): JsonResponse
    {
        $costs = $document->additionalCosts()->get();

        return response()->json([
            'data' => $costs,
        ]);
    }

    /**
     * Store a new additional cost
     */
    public function store(Request $request, Document $document): JsonResponse
    {
        $validated = $request->validate([
            'cost_type' => 'required|string|in:transport,shipping,insurance,customs,handling,other',
            'description' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_document_id' => 'nullable|uuid|exists:documents,id',
        ]);

        $cost = DocumentAdditionalCost::create([
            'id' => Str::uuid()->toString(),
            'document_id' => $document->id,
            'cost_type' => $validated['cost_type'],
            'description' => $validated['description'] ?? null,
            'amount' => $validated['amount'],
            'expense_document_id' => $validated['expense_document_id'] ?? null,
        ]);

        return response()->json([
            'data' => $cost,
        ], 201);
    }

    /**
     * Update an additional cost
     */
    public function update(Request $request, Document $document, DocumentAdditionalCost $cost): JsonResponse
    {
        // Ensure cost belongs to document
        if ($cost->document_id !== $document->id) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_COST',
                    'message' => 'Cost does not belong to this document',
                ],
            ], 404);
        }

        $validated = $request->validate([
            'cost_type' => 'sometimes|required|string|in:transport,shipping,insurance,customs,handling,other',
            'description' => 'nullable|string|max:255',
            'amount' => 'sometimes|required|numeric|min:0',
            'expense_document_id' => 'nullable|uuid|exists:documents,id',
        ]);

        $cost->update($validated);

        return response()->json([
            'data' => $cost->fresh(),
        ]);
    }

    /**
     * Delete an additional cost
     */
    public function destroy(Document $document, DocumentAdditionalCost $cost): JsonResponse
    {
        // Ensure cost belongs to document
        if ($cost->document_id !== $document->id) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_COST',
                    'message' => 'Cost does not belong to this document',
                ],
            ], 404);
        }

        $cost->delete();

        return response()->json(null, 204);
    }
}
