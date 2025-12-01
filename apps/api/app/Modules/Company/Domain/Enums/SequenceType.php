<?php

declare(strict_types=1);

namespace App\Modules\Company\Domain\Enums;

/**
 * Types of document sequences.
 *
 * Each company can have multiple sequences for different document types,
 * each with its own prefix and numbering scheme.
 */
enum SequenceType: string
{
    case Invoice = 'invoice';
    case CreditNote = 'credit_note';
    case Quote = 'quote';
    case SalesOrder = 'sales_order';
    case PurchaseOrder = 'purchase_order';
    case DeliveryNote = 'delivery_note';
    case Receipt = 'receipt';
    case JournalEntry = 'journal_entry';
}
