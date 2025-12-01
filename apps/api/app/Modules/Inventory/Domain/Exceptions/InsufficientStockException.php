<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(
        public readonly string $productId,
        public readonly string $locationId,
        public readonly string $requested,
        public readonly string $available,
    ) {
        parent::__construct(
            "Insufficient stock: requested {$requested}, available {$available}"
        );
    }
}
