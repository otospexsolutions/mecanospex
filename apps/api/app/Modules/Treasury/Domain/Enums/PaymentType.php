<?php

declare(strict_types=1);

namespace App\Modules\Treasury\Domain\Enums;

enum PaymentType: string
{
    // Standard payment applied to one or more invoices
    case DocumentPayment = 'document_payment';

    // Advance/prepayment before invoice exists (creates customer credit)
    case Advance = 'advance';

    // Refund returned to customer
    case Refund = 'refund';

    // Applying existing credit balance to pay an invoice
    case CreditApplication = 'credit_application';

    // Supplier payment (we pay them)
    case SupplierPayment = 'supplier_payment';

    public function label(): string
    {
        return match ($this) {
            self::DocumentPayment => 'Invoice Payment',
            self::Advance => 'Advance Payment',
            self::Refund => 'Refund',
            self::CreditApplication => 'Credit Application',
            self::SupplierPayment => 'Supplier Payment',
        };
    }

    /**
     * Does this payment type increase what the customer owes us?
     */
    public function increasesReceivable(): bool
    {
        return match ($this) {
            self::Refund => true,  // Refund re-creates receivable if from credit
            default => false,
        };
    }

    /**
     * Does this payment type decrease what the customer owes us?
     */
    public function decreasesReceivable(): bool
    {
        return match ($this) {
            self::DocumentPayment => true,
            self::CreditApplication => true,
            default => false,
        };
    }

    /**
     * Does this payment type create/increase customer credit?
     */
    public function createsCredit(): bool
    {
        return match ($this) {
            self::Advance => true,
            default => false,
        };
    }

    /**
     * Is this an incoming payment (money comes to us)?
     */
    public function isIncoming(): bool
    {
        return match ($this) {
            self::DocumentPayment => true,
            self::Advance => true,
            self::CreditApplication => false, // No money moves, just accounting
            self::Refund => false,
            self::SupplierPayment => false,
        };
    }

    /**
     * Is this an outgoing payment (money leaves us)?
     */
    public function isOutgoing(): bool
    {
        return match ($this) {
            self::Refund => true,
            self::SupplierPayment => true,
            default => false,
        };
    }
}
