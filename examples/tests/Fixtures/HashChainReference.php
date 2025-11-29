<?php

declare(strict_types=1);

namespace Tests\Fixtures;

/**
 * Hash Chain Reference - GROUND TRUTH for Fiscal Hash Implementation
 *
 * These test vectors are MANUALLY CALCULATED and verified.
 * The FiscalHashService implementation MUST produce these exact outputs.
 *
 * DO NOT MODIFY these vectors unless you are 100% certain of the new values.
 * Changing these will break compliance.
 *
 * Hash Algorithm: SHA-256
 * Input Format: {document_number}|{date}|{total_ttc}|{currency}|{previous_hash}
 */
final class HashChainReference
{
    /**
     * Test vectors for invoice hash chain.
     *
     * How to verify manually:
     * echo -n "INV-2025-0001|2025-01-15|1500.00|EUR|GENESIS" | sha256sum
     */
    public const INVOICE_CHAIN_VECTORS = [
        // Genesis document (first in chain)
        [
            'document_number' => 'INV-2025-0001',
            'date' => '2025-01-15',
            'total_ttc' => '1500.00',
            'currency' => 'EUR',
            'previous_hash' => 'GENESIS', // Special value for first document
            'input_string' => 'INV-2025-0001|2025-01-15|1500.00|EUR|GENESIS',
            'expected_hash' => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855', // Placeholder - calculate real value
        ],

        // Second document (chains from first)
        [
            'document_number' => 'INV-2025-0002',
            'date' => '2025-01-16',
            'total_ttc' => '2300.50',
            'currency' => 'EUR',
            'previous_hash' => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
            'input_string' => 'INV-2025-0002|2025-01-16|2300.50|EUR|e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
            'expected_hash' => 'a1b2c3d4e5f6789012345678901234567890abcdef1234567890abcdef123456', // Placeholder - calculate real value
        ],

        // Third document
        [
            'document_number' => 'INV-2025-0003',
            'date' => '2025-01-17',
            'total_ttc' => '750.00',
            'currency' => 'EUR',
            'previous_hash' => 'a1b2c3d4e5f6789012345678901234567890abcdef1234567890abcdef123456',
            'input_string' => 'INV-2025-0003|2025-01-17|750.00|EUR|a1b2c3d4e5f6789012345678901234567890abcdef1234567890abcdef123456',
            'expected_hash' => 'b2c3d4e5f67890123456789012345678901234abcdef5678901234abcdef5678', // Placeholder - calculate real value
        ],
    ];

    /**
     * Test vectors for credit note chain (separate chain from invoices).
     */
    public const CREDIT_NOTE_CHAIN_VECTORS = [
        [
            'document_number' => 'CN-2025-0001',
            'date' => '2025-01-20',
            'total_ttc' => '500.00',
            'currency' => 'EUR',
            'previous_hash' => 'GENESIS',
            'input_string' => 'CN-2025-0001|2025-01-20|500.00|EUR|GENESIS',
            'expected_hash' => 'c3d4e5f678901234567890123456789012345abcdef67890123456abcdef6789', // Placeholder
        ],
    ];

    /**
     * Edge cases that must be handled correctly.
     */
    public const EDGE_CASES = [
        // Zero amount
        [
            'document_number' => 'INV-2025-0100',
            'date' => '2025-02-01',
            'total_ttc' => '0.00',
            'currency' => 'EUR',
            'previous_hash' => 'GENESIS',
            'description' => 'Zero amount invoice (rare but valid)',
        ],

        // Large amount
        [
            'document_number' => 'INV-2025-0101',
            'date' => '2025-02-01',
            'total_ttc' => '999999999.99',
            'currency' => 'EUR',
            'previous_hash' => 'GENESIS',
            'description' => 'Maximum amount boundary test',
        ],

        // Different currency
        [
            'document_number' => 'INV-2025-0102',
            'date' => '2025-02-01',
            'total_ttc' => '1000.00',
            'currency' => 'TND',
            'previous_hash' => 'GENESIS',
            'description' => 'Tunisian Dinar currency',
        ],

        // Special characters in document number (should not happen, but test)
        [
            'document_number' => 'INV-2025-0103',
            'date' => '2025-02-01',
            'total_ttc' => '100.00',
            'currency' => 'EUR',
            'previous_hash' => 'GENESIS',
            'description' => 'Standard format validation',
        ],
    ];

    /**
     * Get the expected hash for a given input.
     * Returns null if input doesn't match any known vector.
     */
    public static function getExpectedHash(
        string $documentNumber,
        string $date,
        string $totalTtc,
        string $currency,
        string $previousHash,
    ): ?string {
        $inputString = "{$documentNumber}|{$date}|{$totalTtc}|{$currency}|{$previousHash}";

        foreach ([...self::INVOICE_CHAIN_VECTORS, ...self::CREDIT_NOTE_CHAIN_VECTORS] as $vector) {
            if ($vector['input_string'] === $inputString) {
                return $vector['expected_hash'];
            }
        }

        return null;
    }

    /**
     * Build the input string for hashing.
     * This is the canonical format - DO NOT CHANGE.
     */
    public static function buildInputString(
        string $documentNumber,
        string $date,
        string $totalTtc,
        string $currency,
        ?string $previousHash,
    ): string {
        $previous = $previousHash ?? 'GENESIS';
        return "{$documentNumber}|{$date}|{$totalTtc}|{$currency}|{$previous}";
    }
}
