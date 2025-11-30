<?php

declare(strict_types=1);

namespace App\Modules\Treasury\Presentation\Controllers;

use App\Modules\Identity\Domain\User;
use App\Modules\Treasury\Domain\PaymentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class PaymentRepositoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $repositories = PaymentRepository::query()
            ->where('tenant_id', $user->tenant_id)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $repositories->map(fn (PaymentRepository $repo) => $this->formatRepository($repo)),
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $repository = PaymentRepository::query()
            ->where('tenant_id', $user->tenant_id)
            ->findOrFail($id);

        return response()->json([
            'data' => $this->formatRepository($repository),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('payment_repositories', 'code')->where('tenant_id', $user->tenant_id),
            ],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', Rule::in(['cash_register', 'safe', 'bank_account', 'virtual'])],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'iban' => ['nullable', 'string', 'max:50'],
            'bic' => ['nullable', 'string', 'max:20'],
            'location_id' => ['nullable', 'uuid'],
            'responsible_user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'account_id' => ['nullable', 'uuid'],
        ]);

        $repository = PaymentRepository::create([
            'tenant_id' => $user->tenant_id,
            'code' => $validated['code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'bank_name' => $validated['bank_name'] ?? null,
            'account_number' => $validated['account_number'] ?? null,
            'iban' => $validated['iban'] ?? null,
            'bic' => $validated['bic'] ?? null,
            'balance' => '0.00',
            'location_id' => $validated['location_id'] ?? null,
            'responsible_user_id' => $validated['responsible_user_id'] ?? null,
            'account_id' => $validated['account_id'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'data' => $this->formatRepository($repository),
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $repository = PaymentRepository::query()
            ->where('tenant_id', $user->tenant_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'code' => [
                'sometimes',
                'string',
                'max:30',
                Rule::unique('payment_repositories', 'code')
                    ->where('tenant_id', $user->tenant_id)
                    ->ignore($repository->id),
            ],
            'name' => ['sometimes', 'string', 'max:100'],
            'type' => ['sometimes', 'string', Rule::in(['cash_register', 'safe', 'bank_account', 'virtual'])],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'iban' => ['nullable', 'string', 'max:50'],
            'bic' => ['nullable', 'string', 'max:20'],
            'location_id' => ['nullable', 'uuid'],
            'responsible_user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'account_id' => ['nullable', 'uuid'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $repository->update($validated);

        /** @var PaymentRepository $freshRepository */
        $freshRepository = $repository->fresh();

        return response()->json([
            'data' => $this->formatRepository($freshRepository),
        ]);
    }

    public function balance(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $repository = PaymentRepository::query()
            ->where('tenant_id', $user->tenant_id)
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $repository->id,
                'code' => $repository->code,
                'name' => $repository->name,
                'balance' => $repository->balance,
                'last_reconciled_at' => $repository->last_reconciled_at?->toIso8601String(),
                'last_reconciled_balance' => $repository->last_reconciled_balance,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatRepository(PaymentRepository $repository): array
    {
        return [
            'id' => $repository->id,
            'code' => $repository->code,
            'name' => $repository->name,
            'type' => $repository->type->value,
            'bank_name' => $repository->bank_name,
            'account_number' => $repository->account_number,
            'iban' => $repository->iban,
            'bic' => $repository->bic,
            'balance' => $repository->balance,
            'is_active' => $repository->is_active,
        ];
    }
}
