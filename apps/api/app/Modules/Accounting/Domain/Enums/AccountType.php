<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Domain\Enums;

enum AccountType: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Revenue = 'revenue';
    case Expense = 'expense';

    /**
     * Get the normal balance direction for this account type
     * Assets and Expenses have debit normal balance
     * Liabilities, Equity, and Revenue have credit normal balance
     */
    public function getNormalBalance(): string
    {
        return match ($this) {
            self::Asset, self::Expense => 'debit',
            self::Liability, self::Equity, self::Revenue => 'credit',
        };
    }

    /**
     * Check if this type increases with debits
     */
    public function increasesWithDebit(): bool
    {
        return $this->getNormalBalance() === 'debit';
    }

    /**
     * Check if this type increases with credits
     */
    public function increasesWithCredit(): bool
    {
        return $this->getNormalBalance() === 'credit';
    }

    /**
     * Get human-readable label
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Asset => 'Asset',
            self::Liability => 'Liability',
            self::Equity => 'Equity',
            self::Revenue => 'Revenue',
            self::Expense => 'Expense',
        };
    }
}
