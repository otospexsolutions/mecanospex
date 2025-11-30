<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\Enums;

enum DocumentType: string
{
    case Quote = 'quote';
    case SalesOrder = 'sales_order';
    case Invoice = 'invoice';
    case CreditNote = 'credit_note';
    case DeliveryNote = 'delivery_note';

    /**
     * Get the prefix for document numbering
     */
    public function getPrefix(): string
    {
        return match ($this) {
            self::Quote => 'QT',
            self::SalesOrder => 'SO',
            self::Invoice => 'INV',
            self::CreditNote => 'CN',
            self::DeliveryNote => 'DN',
        };
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::Quote => 'Quote',
            self::SalesOrder => 'Sales Order',
            self::Invoice => 'Invoice',
            self::CreditNote => 'Credit Note',
            self::DeliveryNote => 'Delivery Note',
        };
    }
}
