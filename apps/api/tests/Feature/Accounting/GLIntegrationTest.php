<?php

declare(strict_types=1);

namespace Tests\Feature\Accounting;

use App\Modules\Accounting\Domain\Account;
use App\Modules\Accounting\Domain\Enums\AccountType;
use App\Modules\Accounting\Domain\Enums\JournalEntryStatus;
use App\Modules\Accounting\Domain\JournalEntry;
use App\Modules\Accounting\Domain\Services\GeneralLedgerService;
use App\Modules\Company\Domain\Company;
use App\Modules\Company\Domain\UserCompanyMembership;
use App\Modules\Company\Services\CompanyContext;
use App\Modules\Document\Domain\Document;
use App\Modules\Document\Domain\DocumentLine;
use App\Modules\Document\Domain\Enums\DocumentStatus;
use App\Modules\Document\Domain\Enums\DocumentType;
use App\Modules\Identity\Domain\Enums\UserStatus;
use App\Modules\Identity\Domain\User;
use App\Modules\Partner\Domain\Enums\PartnerType;
use App\Modules\Partner\Domain\Partner;
use App\Modules\Tenant\Domain\Enums\SubscriptionPlan;
use App\Modules\Tenant\Domain\Enums\TenantStatus;
use App\Modules\Tenant\Domain\Tenant;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class GLIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Company $company;

    private User $user;

    private Partner $partner;

    private Account $cashAccount;

    private Account $revenueAccount;

    private Account $receivableAccount;

    private Account $taxAccount;

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
            'status' => \App\Modules\Company\Domain\Enums\CompanyStatus::Active,
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatus::Active,
        ]);
        $this->user->givePermissionTo(['invoices.view', 'invoices.create', 'invoices.post', 'journal.view', 'journal.create', 'journal.post']);

        UserCompanyMembership::create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'role' => 'admin',
        ]);

        app(CompanyContext::class)->setCompanyId($this->company->id);

        $this->partner = Partner::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'type' => PartnerType::Customer,
        ]);

        // Create standard accounts
        $this->cashAccount = Account::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'code' => '1100',
            'name' => 'Cash',
            'type' => AccountType::Asset,
        ]);

        $this->receivableAccount = Account::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'code' => '1200',
            'name' => 'Accounts Receivable',
            'type' => AccountType::Asset,
        ]);

        $this->revenueAccount = Account::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'code' => '4000',
            'name' => 'Sales Revenue',
            'type' => AccountType::Revenue,
        ]);

        $this->taxAccount = Account::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'code' => '2100',
            'name' => 'VAT Payable',
            'type' => AccountType::Liability,
        ]);
    }

    public function test_general_ledger_service_exists(): void
    {
        $this->assertTrue(class_exists(GeneralLedgerService::class));
    }

    public function test_posting_invoice_creates_journal_entry(): void
    {
        $invoice = $this->createInvoice([
            'status' => DocumentStatus::Confirmed,
            'total' => '120.00',
        ]);

        $service = app(GeneralLedgerService::class);
        $journalEntry = $service->createFromInvoice($invoice, $this->user);

        $this->assertNotNull($journalEntry);
        $this->assertInstanceOf(JournalEntry::class, $journalEntry);
        $this->assertEquals('invoice', $journalEntry->source_type);
        $this->assertEquals($invoice->id, $journalEntry->source_id);
    }

    public function test_invoice_journal_entry_has_correct_debits_and_credits(): void
    {
        $invoice = $this->createInvoice([
            'status' => DocumentStatus::Confirmed,
            'subtotal' => '100.00',
            'tax_amount' => '20.00',
            'total' => '120.00',
        ]);

        $service = app(GeneralLedgerService::class);
        $journalEntry = $service->createFromInvoice($invoice, $this->user);

        $lines = $journalEntry->lines;

        // Accounts Receivable should be debited (asset increases)
        $receivableLine = $lines->firstWhere('account_id', $this->receivableAccount->id);
        $this->assertEquals('120.00', $receivableLine->debit);
        $this->assertEquals('0.00', $receivableLine->credit);

        // Revenue should be credited (revenue increases)
        $revenueLine = $lines->firstWhere('account_id', $this->revenueAccount->id);
        $this->assertEquals('0.00', $revenueLine->debit);
        $this->assertEquals('100.00', $revenueLine->credit);

        // Tax Payable should be credited (liability increases)
        $taxLine = $lines->firstWhere('account_id', $this->taxAccount->id);
        $this->assertEquals('0.00', $taxLine->debit);
        $this->assertEquals('20.00', $taxLine->credit);
    }

    public function test_invoice_journal_entry_is_balanced(): void
    {
        $invoice = $this->createInvoice([
            'status' => DocumentStatus::Confirmed,
            'subtotal' => '100.00',
            'tax_amount' => '20.00',
            'total' => '120.00',
        ]);

        $service = app(GeneralLedgerService::class);
        $journalEntry = $service->createFromInvoice($invoice, $this->user);

        $totalDebits = $journalEntry->lines->sum('debit');
        $totalCredits = $journalEntry->lines->sum('credit');

        $this->assertEquals($totalDebits, $totalCredits);
    }

    public function test_credit_note_creates_reversal_entry(): void
    {
        $creditNote = $this->createCreditNote([
            'status' => DocumentStatus::Confirmed,
            'subtotal' => '50.00',
            'tax_amount' => '10.00',
            'total' => '60.00',
        ]);

        $service = app(GeneralLedgerService::class);
        $journalEntry = $service->createFromCreditNote($creditNote, $this->user);

        $lines = $journalEntry->lines;

        // For credit notes, receivable should be credited (reduces receivable)
        $receivableLine = $lines->firstWhere('account_id', $this->receivableAccount->id);
        $this->assertEquals('0.00', $receivableLine->debit);
        $this->assertEquals('60.00', $receivableLine->credit);

        // Revenue should be debited (reduces revenue)
        $revenueLine = $lines->firstWhere('account_id', $this->revenueAccount->id);
        $this->assertEquals('50.00', $revenueLine->debit);
        $this->assertEquals('0.00', $revenueLine->credit);
    }

    public function test_cash_payment_creates_journal_entry(): void
    {
        $service = app(GeneralLedgerService::class);

        $journalEntry = $service->createPaymentEntry(
            companyId: $this->company->id,
            amount: '120.00',
            debitAccountId: $this->cashAccount->id,
            creditAccountId: $this->receivableAccount->id,
            description: 'Payment received',
            user: $this->user,
        );

        $lines = $journalEntry->lines;

        // Cash is debited (asset increases)
        $cashLine = $lines->firstWhere('account_id', $this->cashAccount->id);
        $this->assertEquals('120.00', $cashLine->debit);
        $this->assertEquals('0.00', $cashLine->credit);

        // Receivable is credited (asset decreases)
        $receivableLine = $lines->firstWhere('account_id', $this->receivableAccount->id);
        $this->assertEquals('0.00', $receivableLine->debit);
        $this->assertEquals('120.00', $receivableLine->credit);
    }

    public function test_journal_entry_has_draft_status_initially(): void
    {
        $invoice = $this->createInvoice([
            'status' => DocumentStatus::Confirmed,
            'total' => '120.00',
        ]);

        $service = app(GeneralLedgerService::class);
        $journalEntry = $service->createFromInvoice($invoice, $this->user);

        $this->assertEquals(JournalEntryStatus::Draft, $journalEntry->status);
    }

    public function test_posting_journal_entry_adds_hash(): void
    {
        $invoice = $this->createInvoice([
            'status' => DocumentStatus::Confirmed,
            'total' => '120.00',
        ]);

        $service = app(GeneralLedgerService::class);
        $journalEntry = $service->createFromInvoice($invoice, $this->user);
        $service->postEntry($journalEntry, $this->user);

        $journalEntry->refresh();

        $this->assertEquals(JournalEntryStatus::Posted, $journalEntry->status);
        $this->assertNotNull($journalEntry->hash);
        $this->assertNotNull($journalEntry->posted_at);
        $this->assertEquals($this->user->id, $journalEntry->posted_by);
    }

    private function createInvoice(array $attributes): Document
    {
        $document = Document::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'partner_id' => $this->partner->id,
            'type' => DocumentType::Invoice,
            'document_number' => 'INV-2025-000001',
            'document_date' => now()->toDateString(),
            'status' => DocumentStatus::Draft,
            'subtotal' => '100.00',
            'tax_amount' => '0.00',
            'total' => '100.00',
            'currency' => 'EUR',
        ], $attributes));

        // Create a line for the invoice
        DocumentLine::create([
            'document_id' => $document->id,
            'line_number' => 1,
            'description' => 'Test product',
            'quantity' => '1.00',
            'unit_price' => $attributes['subtotal'] ?? '100.00',
            'tax_rate' => isset($attributes['tax_amount']) && $attributes['tax_amount'] !== '0.00' ? '20.00' : '0.00',
            'line_total' => $attributes['subtotal'] ?? '100.00',
        ]);

        return $document;
    }

    private function createCreditNote(array $attributes): Document
    {
        return Document::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'partner_id' => $this->partner->id,
            'type' => DocumentType::CreditNote,
            'document_number' => 'CN-2025-000001',
            'document_date' => now()->toDateString(),
            'status' => DocumentStatus::Draft,
            'subtotal' => '50.00',
            'tax_amount' => '0.00',
            'total' => '50.00',
            'currency' => 'EUR',
        ], $attributes));
    }
}
