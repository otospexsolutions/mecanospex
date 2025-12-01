<?php

declare(strict_types=1);

namespace Tests\Unit\Accounting;

use App\Modules\Accounting\Domain\Account;
use App\Modules\Accounting\Domain\Enums\AccountType;
use PHPUnit\Framework\TestCase;

class AccountEntityTest extends TestCase
{
    public function test_account_has_required_properties(): void
    {
        $this->assertTrue(class_exists(Account::class));
        // Eloquent properties are defined as fillable attributes, not PHP properties
        $account = new Account;
        $fillable = $account->getFillable();
        $this->assertContains('code', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('type', $fillable);
    }

    public function test_account_type_enum_exists(): void
    {
        $this->assertTrue(enum_exists(AccountType::class));
    }

    public function test_account_type_has_required_cases(): void
    {
        $cases = AccountType::cases();
        $caseValues = array_map(fn ($case) => $case->value, $cases);

        $this->assertContains('asset', $caseValues);
        $this->assertContains('liability', $caseValues);
        $this->assertContains('equity', $caseValues);
        $this->assertContains('revenue', $caseValues);
        $this->assertContains('expense', $caseValues);
    }

    public function test_account_type_knows_normal_balance(): void
    {
        // Assets and Expenses have debit normal balance
        $this->assertEquals('debit', AccountType::Asset->getNormalBalance());
        $this->assertEquals('debit', AccountType::Expense->getNormalBalance());

        // Liabilities, Equity, and Revenue have credit normal balance
        $this->assertEquals('credit', AccountType::Liability->getNormalBalance());
        $this->assertEquals('credit', AccountType::Equity->getNormalBalance());
        $this->assertEquals('credit', AccountType::Revenue->getNormalBalance());
    }

    public function test_account_can_have_parent(): void
    {
        $this->assertTrue(method_exists(Account::class, 'parent'));
    }

    public function test_account_can_have_children(): void
    {
        $this->assertTrue(method_exists(Account::class, 'children'));
    }

    public function test_account_has_is_active_flag(): void
    {
        $account = new Account;
        $fillable = $account->getFillable();
        $this->assertContains('is_active', $fillable);
    }

    public function test_account_has_is_system_flag(): void
    {
        $account = new Account;
        $fillable = $account->getFillable();
        $this->assertContains('is_system', $fillable);
    }
}
