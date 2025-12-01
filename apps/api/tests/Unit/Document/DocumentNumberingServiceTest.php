<?php

declare(strict_types=1);

namespace Tests\Unit\Document;

use App\Modules\Company\Domain\Company;
use App\Modules\Company\Domain\Enums\CompanyStatus;
use App\Modules\Document\Domain\Enums\DocumentType;
use App\Modules\Document\Domain\Services\DocumentNumberingService;
use App\Modules\Tenant\Domain\Enums\SubscriptionPlan;
use App\Modules\Tenant\Domain\Enums\TenantStatus;
use App\Modules\Tenant\Domain\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentNumberingServiceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Company $company;

    private DocumentNumberingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Professional,
        ]);

        $this->company = Company::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Company',
            'legal_name' => 'Test Company LLC',
            'tax_id' => 'TAX123',
            'country_code' => 'FR',
            'locale' => 'fr_FR',
            'timezone' => 'Europe/Paris',
            'currency' => 'EUR',
            'status' => CompanyStatus::Active,
        ]);

        $this->service = new DocumentNumberingService;
    }

    public function test_generates_quote_number(): void
    {
        $number = $this->service->generateNumber($this->tenant->id, $this->company->id, DocumentType::Quote);

        $this->assertStringStartsWith('QT-', $number);
        $this->assertMatchesRegularExpression('/^QT-\d{4}-\d{4}$/', $number);
    }

    public function test_generates_sales_order_number(): void
    {
        $number = $this->service->generateNumber($this->tenant->id, $this->company->id, DocumentType::SalesOrder);

        $this->assertStringStartsWith('SO-', $number);
        $this->assertMatchesRegularExpression('/^SO-\d{4}-\d{4}$/', $number);
    }

    public function test_generates_invoice_number(): void
    {
        $number = $this->service->generateNumber($this->tenant->id, $this->company->id, DocumentType::Invoice);

        $this->assertStringStartsWith('INV-', $number);
        $this->assertMatchesRegularExpression('/^INV-\d{4}-\d{4}$/', $number);
    }

    public function test_generates_credit_note_number(): void
    {
        $number = $this->service->generateNumber($this->tenant->id, $this->company->id, DocumentType::CreditNote);

        $this->assertStringStartsWith('CN-', $number);
        $this->assertMatchesRegularExpression('/^CN-\d{4}-\d{4}$/', $number);
    }

    public function test_generates_delivery_note_number(): void
    {
        $number = $this->service->generateNumber($this->tenant->id, $this->company->id, DocumentType::DeliveryNote);

        $this->assertStringStartsWith('DN-', $number);
        $this->assertMatchesRegularExpression('/^DN-\d{4}-\d{4}$/', $number);
    }

    public function test_generates_sequential_numbers(): void
    {
        $number1 = $this->service->generateNumber($this->tenant->id, $this->company->id, DocumentType::Invoice);
        $number2 = $this->service->generateNumber($this->tenant->id, $this->company->id, DocumentType::Invoice);
        $number3 = $this->service->generateNumber($this->tenant->id, $this->company->id, DocumentType::Invoice);

        // Extract sequence numbers
        preg_match('/INV-\d{4}-(\d{4})/', $number1, $matches1);
        preg_match('/INV-\d{4}-(\d{4})/', $number2, $matches2);
        preg_match('/INV-\d{4}-(\d{4})/', $number3, $matches3);

        $seq1 = (int) $matches1[1];
        $seq2 = (int) $matches2[1];
        $seq3 = (int) $matches3[1];

        $this->assertEquals($seq1 + 1, $seq2);
        $this->assertEquals($seq2 + 1, $seq3);
    }

    public function test_different_companies_have_separate_sequences(): void
    {
        $otherCompany = Company::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Other Company',
            'legal_name' => 'Other Company LLC',
            'tax_id' => 'TAX456',
            'country_code' => 'FR',
            'locale' => 'fr_FR',
            'timezone' => 'Europe/Paris',
            'currency' => 'EUR',
            'status' => CompanyStatus::Active,
        ]);

        // Generate numbers for first company
        $this->service->generateNumber($this->tenant->id, $this->company->id, DocumentType::Invoice);
        $this->service->generateNumber($this->tenant->id, $this->company->id, DocumentType::Invoice);
        $number3Company1 = $this->service->generateNumber($this->tenant->id, $this->company->id, DocumentType::Invoice);

        // Generate first number for second company (should start fresh)
        $number1Company2 = $this->service->generateNumber($this->tenant->id, $otherCompany->id, DocumentType::Invoice);

        preg_match('/INV-\d{4}-(\d{4})/', $number3Company1, $matches1);
        preg_match('/INV-\d{4}-(\d{4})/', $number1Company2, $matches2);

        $seq3Company1 = (int) $matches1[1];
        $seq1Company2 = (int) $matches2[1];

        // Second company's first number should be 0001, not 0004
        $this->assertEquals(1, $seq1Company2);
        $this->assertGreaterThan($seq1Company2, $seq3Company1);
    }

    public function test_different_document_types_have_separate_sequences(): void
    {
        // Generate invoice numbers
        $this->service->generateNumber($this->tenant->id, $this->company->id, DocumentType::Invoice);
        $this->service->generateNumber($this->tenant->id, $this->company->id, DocumentType::Invoice);
        $invoice3 = $this->service->generateNumber($this->tenant->id, $this->company->id, DocumentType::Invoice);

        // Generate first quote (should start fresh)
        $quote1 = $this->service->generateNumber($this->tenant->id, $this->company->id, DocumentType::Quote);

        preg_match('/INV-\d{4}-(\d{4})/', $invoice3, $matchesInv);
        preg_match('/QT-\d{4}-(\d{4})/', $quote1, $matchesQt);

        $seqInv = (int) $matchesInv[1];
        $seqQt = (int) $matchesQt[1];

        $this->assertEquals(3, $seqInv);
        $this->assertEquals(1, $seqQt);
    }

    public function test_number_includes_current_year(): void
    {
        $number = $this->service->generateNumber($this->tenant->id, $this->company->id, DocumentType::Invoice);

        $currentYear = date('Y');
        $this->assertStringContainsString($currentYear, $number);
    }
}
