<?php

declare(strict_types=1);

namespace Tests\Unit\Treasury;

use App\Modules\Treasury\Domain\Enums\PaymentStatus;
use App\Modules\Treasury\Domain\Payment;
use Tests\TestCase;

class PaymentEntityTest extends TestCase
{
    public function test_payment_class_exists(): void
    {
        $this->assertTrue(class_exists(Payment::class));
    }

    public function test_payment_status_enum_exists(): void
    {
        $this->assertTrue(enum_exists(PaymentStatus::class));
    }

    public function test_payment_status_has_required_cases(): void
    {
        $cases = PaymentStatus::cases();
        $caseValues = array_map(fn ($case) => $case->value, $cases);

        $this->assertContains('pending', $caseValues);
        $this->assertContains('completed', $caseValues);
        $this->assertContains('failed', $caseValues);
        $this->assertContains('reversed', $caseValues);
    }

    public function test_payment_has_required_properties(): void
    {
        $payment = new Payment;
        $fillable = $payment->getFillable();

        $this->assertContains('tenant_id', $fillable);
        $this->assertContains('company_id', $fillable);
        $this->assertContains('partner_id', $fillable);
        $this->assertContains('payment_method_id', $fillable);
        $this->assertContains('amount', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('payment_date', $fillable);
    }

    public function test_payment_has_reference_properties(): void
    {
        $payment = new Payment;
        $fillable = $payment->getFillable();

        $this->assertContains('reference', $fillable);
        $this->assertContains('notes', $fillable);
    }

    public function test_payment_has_instrument_reference(): void
    {
        $payment = new Payment;
        $fillable = $payment->getFillable();

        $this->assertContains('instrument_id', $fillable);
    }
}
