<?php

declare(strict_types=1);

namespace App\Modules\Treasury\Domain\Enums;

enum AllocationType: string
{
    // Standard payment allocation
    case INVOICE_PAYMENT = 'invoice_payment';

    // Credit balance usage
    case CREDIT_APPLICATION = 'credit_application';

    // Credit note applied to invoice
    case CREDIT_NOTE_APPLICATION = 'credit_note_application';

    // Small difference written off (tolerance)
    case TOLERANCE_WRITEOFF = 'tolerance_writeoff';

    // Extensibility: Phase 2
    // case CASH_DISCOUNT = 'cash_discount';

    public function label(): string
    {
        return match ($this) {
            self::INVOICE_PAYMENT => 'Invoice Payment',
            self::CREDIT_APPLICATION => 'Credit Application',
            self::CREDIT_NOTE_APPLICATION => 'Credit Note Application',
            self::TOLERANCE_WRITEOFF => 'Tolerance Write-off',
        };
    }
}
