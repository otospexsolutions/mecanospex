<?php

declare(strict_types=1);

namespace Tests\Unit\Accounting;

use App\Modules\Accounting\Domain\Services\DoubleEntryValidator;
use PHPUnit\Framework\TestCase;

class DoubleEntryValidationTest extends TestCase
{
    public function test_validator_class_exists(): void
    {
        $this->assertTrue(class_exists(DoubleEntryValidator::class));
    }

    public function test_balanced_entry_is_valid(): void
    {
        $validator = new DoubleEntryValidator();

        $lines = [
            ['debit' => '100.00', 'credit' => '0.00'],
            ['debit' => '0.00', 'credit' => '100.00'],
        ];

        $this->assertTrue($validator->isBalanced($lines));
    }

    public function test_unbalanced_entry_is_invalid(): void
    {
        $validator = new DoubleEntryValidator();

        $lines = [
            ['debit' => '100.00', 'credit' => '0.00'],
            ['debit' => '0.00', 'credit' => '50.00'],
        ];

        $this->assertFalse($validator->isBalanced($lines));
    }

    public function test_empty_lines_is_invalid(): void
    {
        $validator = new DoubleEntryValidator();

        $this->assertFalse($validator->isBalanced([]));
    }

    public function test_single_line_is_invalid(): void
    {
        $validator = new DoubleEntryValidator();

        $lines = [
            ['debit' => '100.00', 'credit' => '0.00'],
        ];

        $this->assertFalse($validator->isBalanced($lines));
    }

    public function test_line_with_both_debit_and_credit_is_invalid(): void
    {
        $validator = new DoubleEntryValidator();

        $lines = [
            ['debit' => '100.00', 'credit' => '50.00'],
            ['debit' => '0.00', 'credit' => '50.00'],
        ];

        $this->assertFalse($validator->hasValidLines($lines));
    }

    public function test_line_with_zero_amount_is_invalid(): void
    {
        $validator = new DoubleEntryValidator();

        $lines = [
            ['debit' => '0.00', 'credit' => '0.00'],
            ['debit' => '100.00', 'credit' => '0.00'],
        ];

        $this->assertFalse($validator->hasValidLines($lines));
    }

    public function test_multi_line_balanced_entry(): void
    {
        $validator = new DoubleEntryValidator();

        // Example: Sale with tax
        // Debit: Cash 120
        // Credit: Revenue 100
        // Credit: Tax Payable 20
        $lines = [
            ['debit' => '120.00', 'credit' => '0.00'],
            ['debit' => '0.00', 'credit' => '100.00'],
            ['debit' => '0.00', 'credit' => '20.00'],
        ];

        $this->assertTrue($validator->isBalanced($lines));
        $this->assertTrue($validator->hasValidLines($lines));
    }

    public function test_validates_precision_to_two_decimals(): void
    {
        $validator = new DoubleEntryValidator();

        // Should handle precision correctly
        $lines = [
            ['debit' => '33.33', 'credit' => '0.00'],
            ['debit' => '33.33', 'credit' => '0.00'],
            ['debit' => '33.34', 'credit' => '0.00'],
            ['debit' => '0.00', 'credit' => '100.00'],
        ];

        $this->assertTrue($validator->isBalanced($lines));
    }
}
