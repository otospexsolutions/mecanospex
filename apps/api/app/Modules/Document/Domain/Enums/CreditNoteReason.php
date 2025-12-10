<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\Enums;

enum CreditNoteReason: string
{
    case RETURN = 'return';
    case PRICE_ADJUSTMENT = 'price_adjustment';
    case BILLING_ERROR = 'billing_error';
    case DAMAGED_GOODS = 'damaged_goods';
    case SERVICE_ISSUE = 'service_issue';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::RETURN => 'Product Return',
            self::PRICE_ADJUSTMENT => 'Price Adjustment',
            self::BILLING_ERROR => 'Billing Error',
            self::DAMAGED_GOODS => 'Damaged Goods',
            self::SERVICE_ISSUE => 'Service Issue',
            self::OTHER => 'Other',
        };
    }

    public function requiresComment(): bool
    {
        return $this === self::OTHER;
    }
}
