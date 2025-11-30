<?php

declare(strict_types=1);

namespace App\Modules\Treasury\Domain\Enums;

enum RepositoryType: string
{
    case CashRegister = 'cash_register';
    case Safe = 'safe';
    case BankAccount = 'bank_account';
    case Virtual = 'virtual';
}
