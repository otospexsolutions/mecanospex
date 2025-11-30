<?php

declare(strict_types=1);

namespace Tests\Unit\Treasury;

use App\Modules\Treasury\Domain\Enums\FeeType;
use App\Modules\Treasury\Domain\PaymentMethod;
use Tests\TestCase;

class PaymentMethodEntityTest extends TestCase
{
    public function test_payment_method_class_exists(): void
    {
        $this->assertTrue(class_exists(PaymentMethod::class));
    }

    public function test_fee_type_enum_exists(): void
    {
        $this->assertTrue(enum_exists(FeeType::class));
    }

    public function test_fee_type_has_required_cases(): void
    {
        $cases = FeeType::cases();
        $caseValues = array_map(fn ($case) => $case->value, $cases);

        $this->assertContains('none', $caseValues);
        $this->assertContains('fixed', $caseValues);
        $this->assertContains('percentage', $caseValues);
        $this->assertContains('mixed', $caseValues);
    }

    public function test_payment_method_has_required_properties(): void
    {
        $method = new PaymentMethod;
        $fillable = $method->getFillable();

        $this->assertContains('tenant_id', $fillable);
        $this->assertContains('code', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('is_physical', $fillable);
        $this->assertContains('has_maturity', $fillable);
        $this->assertContains('requires_third_party', $fillable);
        $this->assertContains('is_push', $fillable);
        $this->assertContains('has_deducted_fees', $fillable);
        $this->assertContains('is_restricted', $fillable);
    }

    public function test_payment_method_has_fee_properties(): void
    {
        $method = new PaymentMethod;
        $fillable = $method->getFillable();

        $this->assertContains('fee_type', $fillable);
        $this->assertContains('fee_fixed', $fillable);
        $this->assertContains('fee_percent', $fillable);
    }

    public function test_payment_method_has_calculate_fee_method(): void
    {
        $this->assertTrue(method_exists(PaymentMethod::class, 'calculateFee'));
    }

    public function test_payment_method_has_calculate_net_amount_method(): void
    {
        $this->assertTrue(method_exists(PaymentMethod::class, 'calculateNetAmount'));
    }
}
