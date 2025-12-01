<?php

declare(strict_types=1);

namespace App\Shared\Application\DTOs;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final readonly class PaginationData
{
    public function __construct(
        public int $page,
        public int $perPage,
        public int $total,
        public int $totalPages,
        public bool $hasNextPage,
        public bool $hasPreviousPage,
    ) {}

    /**
     * @param  array<string, mixed>  $paginator
     */
    public static function fromPaginator(array $paginator): self
    {
        $total = (int) ($paginator['total'] ?? 0);
        $perPage = (int) ($paginator['per_page'] ?? 15);
        $currentPage = (int) ($paginator['current_page'] ?? 1);
        $totalPages = (int) ceil($total / max($perPage, 1));

        return new self(
            page: $currentPage,
            perPage: $perPage,
            total: $total,
            totalPages: $totalPages,
            hasNextPage: $currentPage < $totalPages,
            hasPreviousPage: $currentPage > 1,
        );
    }
}
