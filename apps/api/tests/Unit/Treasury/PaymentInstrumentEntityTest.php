<?php

declare(strict_types=1);

namespace Tests\Unit\Treasury;

use App\Modules\Treasury\Domain\Enums\InstrumentStatus;
use App\Modules\Treasury\Domain\PaymentInstrument;
use Tests\TestCase;

class PaymentInstrumentEntityTest extends TestCase
{
    public function test_payment_instrument_class_exists(): void
    {
        $this->assertTrue(class_exists(PaymentInstrument::class));
    }

    public function test_instrument_status_enum_exists(): void
    {
        $this->assertTrue(enum_exists(InstrumentStatus::class));
    }

    public function test_instrument_status_has_required_cases(): void
    {
        $cases = InstrumentStatus::cases();
        $caseValues = array_map(fn ($case) => $case->value, $cases);

        $this->assertContains('received', $caseValues);
        $this->assertContains('in_transit', $caseValues);
        $this->assertContains('deposited', $caseValues);
        $this->assertContains('clearing', $caseValues);
        $this->assertContains('cleared', $caseValues);
        $this->assertContains('bounced', $caseValues);
        $this->assertContains('expired', $caseValues);
        $this->assertContains('cancelled', $caseValues);
    }

    public function test_payment_instrument_has_required_properties(): void
    {
        $instrument = new PaymentInstrument;
        $fillable = $instrument->getFillable();

        $this->assertContains('tenant_id', $fillable);
        $this->assertContains('payment_method_id', $fillable);
        $this->assertContains('reference', $fillable);
        $this->assertContains('amount', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('repository_id', $fillable);
    }

    public function test_payment_instrument_has_date_properties(): void
    {
        $instrument = new PaymentInstrument;
        $fillable = $instrument->getFillable();

        $this->assertContains('received_date', $fillable);
        $this->assertContains('maturity_date', $fillable);
        $this->assertContains('expiry_date', $fillable);
    }

    public function test_payment_instrument_has_bank_properties(): void
    {
        $instrument = new PaymentInstrument;
        $fillable = $instrument->getFillable();

        $this->assertContains('drawer_name', $fillable);
        $this->assertContains('bank_name', $fillable);
    }
}
