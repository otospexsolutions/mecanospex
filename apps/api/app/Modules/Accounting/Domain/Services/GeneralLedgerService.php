<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Domain\Services;

use App\Modules\Accounting\Domain\Account;
use App\Modules\Accounting\Domain\Enums\JournalEntryStatus;
use App\Modules\Accounting\Domain\JournalEntry;
use App\Modules\Accounting\Domain\JournalLine;
use App\Modules\Document\Domain\Document;
use App\Modules\Identity\Domain\User;
use Illuminate\Support\Facades\DB;

final class GeneralLedgerService
{
    private const SCALE = 2;

    /**
     * Create journal entry from a posted invoice.
     * Debit: Accounts Receivable (total)
     * Credit: Sales Revenue (subtotal)
     * Credit: VAT Payable (tax)
     */
    public function createFromInvoice(Document $invoice, User $user): JournalEntry
    {
        return DB::transaction(function () use ($invoice): JournalEntry {
            $tenantId = $invoice->tenant_id;

            // Get or create required accounts
            $receivableAccount = $this->getAccountByCode($tenantId, '1200');
            $revenueAccount = $this->getAccountByCode($tenantId, '4000');
            $taxAccount = $this->getAccountByCode($tenantId, '2100');

            $entryNumber = $this->generateEntryNumber($tenantId);

            $entry = JournalEntry::create([
                'tenant_id' => $tenantId,
                'entry_number' => $entryNumber,
                'entry_date' => $invoice->document_date,
                'description' => "Invoice {$invoice->document_number}",
                'status' => JournalEntryStatus::Draft,
                'source_type' => 'invoice',
                'source_id' => $invoice->id,
            ]);

            $lineOrder = 0;

            // Debit: Accounts Receivable (total amount)
            JournalLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $receivableAccount->id,
                'debit' => $invoice->total ?? '0.00',
                'credit' => '0.00',
                'description' => 'Accounts receivable',
                'line_order' => $lineOrder++,
            ]);

            // Credit: Sales Revenue (subtotal)
            JournalLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $revenueAccount->id,
                'debit' => '0.00',
                'credit' => $invoice->subtotal ?? '0.00',
                'description' => 'Sales revenue',
                'line_order' => $lineOrder++,
            ]);

            // Credit: VAT Payable (tax amount) - only if there's tax
            $taxAmount = $invoice->tax_amount ?? '0.00';
            if (bccomp($taxAmount, '0.00', self::SCALE) > 0) {
                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $taxAccount->id,
                    'debit' => '0.00',
                    'credit' => $taxAmount,
                    'description' => 'VAT payable',
                    'line_order' => $lineOrder,
                ]);
            }

            return $entry->load('lines');
        });
    }

    /**
     * Create journal entry from a credit note.
     * This is the reverse of an invoice:
     * Debit: Sales Revenue (subtotal)
     * Debit: VAT Payable (tax)
     * Credit: Accounts Receivable (total)
     */
    public function createFromCreditNote(Document $creditNote, User $user): JournalEntry
    {
        return DB::transaction(function () use ($creditNote): JournalEntry {
            $tenantId = $creditNote->tenant_id;

            // Get or create required accounts
            $receivableAccount = $this->getAccountByCode($tenantId, '1200');
            $revenueAccount = $this->getAccountByCode($tenantId, '4000');
            $taxAccount = $this->getAccountByCode($tenantId, '2100');

            $entryNumber = $this->generateEntryNumber($tenantId);

            $entry = JournalEntry::create([
                'tenant_id' => $tenantId,
                'entry_number' => $entryNumber,
                'entry_date' => $creditNote->document_date,
                'description' => "Credit Note {$creditNote->document_number}",
                'status' => JournalEntryStatus::Draft,
                'source_type' => 'credit_note',
                'source_id' => $creditNote->id,
            ]);

            $lineOrder = 0;

            // Debit: Sales Revenue (subtotal) - reduces revenue
            JournalLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $revenueAccount->id,
                'debit' => $creditNote->subtotal ?? '0.00',
                'credit' => '0.00',
                'description' => 'Sales revenue reversal',
                'line_order' => $lineOrder++,
            ]);

            // Debit: VAT Payable (tax amount) - only if there's tax
            $taxAmount = $creditNote->tax_amount ?? '0.00';
            if (bccomp($taxAmount, '0.00', self::SCALE) > 0) {
                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $taxAccount->id,
                    'debit' => $taxAmount,
                    'credit' => '0.00',
                    'description' => 'VAT payable reversal',
                    'line_order' => $lineOrder++,
                ]);
            }

            // Credit: Accounts Receivable (total) - reduces receivable
            JournalLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $receivableAccount->id,
                'debit' => '0.00',
                'credit' => $creditNote->total ?? '0.00',
                'description' => 'Accounts receivable reduction',
                'line_order' => $lineOrder,
            ]);

            return $entry->load('lines');
        });
    }

    /**
     * Create a payment journal entry.
     * Debit: Cash/Bank account
     * Credit: Accounts Receivable
     */
    public function createPaymentEntry(
        string $tenantId,
        string $amount,
        string $debitAccountId,
        string $creditAccountId,
        string $description,
        User $user,
    ): JournalEntry {
        return DB::transaction(function () use ($tenantId, $amount, $debitAccountId, $creditAccountId, $description): JournalEntry {
            $entryNumber = $this->generateEntryNumber($tenantId);

            $entry = JournalEntry::create([
                'tenant_id' => $tenantId,
                'entry_number' => $entryNumber,
                'entry_date' => now()->toDateString(),
                'description' => $description,
                'status' => JournalEntryStatus::Draft,
                'source_type' => 'payment',
            ]);

            // Debit: Cash/Bank
            JournalLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $debitAccountId,
                'debit' => $amount,
                'credit' => '0.00',
                'description' => 'Cash received',
                'line_order' => 0,
            ]);

            // Credit: Accounts Receivable
            JournalLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $creditAccountId,
                'debit' => '0.00',
                'credit' => $amount,
                'description' => 'Receivable cleared',
                'line_order' => 1,
            ]);

            return $entry->load('lines');
        });
    }

    /**
     * Post a journal entry (make it permanent with hash).
     */
    public function postEntry(JournalEntry $entry, User $user): void
    {
        if ($entry->status !== JournalEntryStatus::Draft) {
            throw new \InvalidArgumentException('Only draft entries can be posted');
        }

        $previousHash = $this->getPreviousHash($entry->tenant_id);
        $hash = $this->calculateHash($entry, $previousHash);

        $entry->update([
            'status' => JournalEntryStatus::Posted,
            'hash' => $hash,
            'previous_hash' => $previousHash,
            'posted_at' => now(),
            'posted_by' => $user->id,
        ]);
    }

    private function getAccountByCode(string $tenantId, string $code): Account
    {
        $account = Account::query()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();

        if ($account === null) {
            throw new \RuntimeException("Account with code {$code} not found for tenant");
        }

        return $account;
    }

    private function generateEntryNumber(string $tenantId): string
    {
        $year = date('Y');
        $lastEntry = JournalEntry::query()
            ->where('tenant_id', $tenantId)
            ->where('entry_number', 'like', "JE-{$year}-%")
            ->orderByDesc('entry_number')
            ->first();

        if ($lastEntry !== null) {
            $lastNumber = (int) substr($lastEntry->entry_number, -6);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('JE-%s-%06d', $year, $nextNumber);
    }

    private function calculateHash(JournalEntry $entry, string $previousHash): string
    {
        $data = json_encode([
            'entry_number' => $entry->entry_number,
            'entry_date' => $entry->entry_date->toDateString(),
            'description' => $entry->description,
            'lines' => $entry->lines->map(fn ($line) => [
                'account_id' => $line->account_id,
                'debit' => $line->debit,
                'credit' => $line->credit,
            ])->toArray(),
        ]);

        return hash('sha256', $previousHash.'|'.$data);
    }

    private function getPreviousHash(string $tenantId): string
    {
        $lastPosted = JournalEntry::query()
            ->where('tenant_id', $tenantId)
            ->where('status', JournalEntryStatus::Posted)
            ->whereNotNull('hash')
            ->orderByDesc('posted_at')
            ->first();

        return $lastPosted !== null ? $lastPosted->hash ?? '' : '';
    }
}
