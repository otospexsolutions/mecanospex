<?php

declare(strict_types=1);

namespace Tests\Fixtures;

/**
 * HashChainReference - Ground Truth Test Vectors
 *
 * CRITICAL: These are compliance-critical test vectors for the fiscal hash chain.
 * They were manually calculated using SHA-256 and verified.
 *
 * Hash Chain Format: SHA256(previous_hash | document_number | date | amount | currency)
 * - Genesis (first document): previous_hash is empty string
 * - Subsequent documents: previous_hash is the hash of the previous document
 *
 * DO NOT MODIFY these values without recalculating and verifying the entire chain.
 *
 * Calculation method:
 * - echo -n "|INV-2025-0001|2025-01-15|1500.00|EUR" | shasum -a 256
 * - echo -n "PREV_HASH|INV-2025-0002|2025-01-16|2300.50|EUR" | shasum -a 256
 */
final class HashChainReference
{
    /**
     * Ground truth test vectors for fiscal hash chain verification.
     * Each vector contains:
     * - input: The document data to be hashed (number|date|amount|currency)
     * - previous_hash: The hash of the previous document (null for genesis)
     * - expected_hash: The expected SHA-256 hash output
     *
     * @var array<int, array{input: string, previous_hash: string|null, expected_hash: string}>
     */
    public const VECTORS = [
        [
            // Genesis document - first in chain (no previous hash)
            'input' => 'INV-2025-0001|2025-01-15|1500.00|EUR',
            'previous_hash' => null,
            'expected_hash' => '6a523adf3b63c08ade1c582f242aedebcea0aeed07b4a8e2b02d1e9e0eedd24b',
        ],
        [
            // Second document - chained to first
            'input' => 'INV-2025-0002|2025-01-16|2300.50|EUR',
            'previous_hash' => '6a523adf3b63c08ade1c582f242aedebcea0aeed07b4a8e2b02d1e9e0eedd24b',
            'expected_hash' => '9a7863739c0953fc23284da38464f606fa4c2690e44bda07a802efa3e030e0d4',
        ],
        [
            // Third document - chained to second
            'input' => 'INV-2025-0003|2025-01-17|750.25|EUR',
            'previous_hash' => '9a7863739c0953fc23284da38464f606fa4c2690e44bda07a802efa3e030e0d4',
            'expected_hash' => '57147b899c70a9b137ae15a6902bdc41e988536552b838399f29dd69de8ac588',
        ],
    ];

    /**
     * Verify that the hash chain calculation is correct.
     * This method demonstrates the expected hashing algorithm.
     *
     * @param  string  $input  The document data
     * @param  string|null  $previousHash  The previous document's hash
     * @return string The calculated hash
     */
    public static function calculateHash(string $input, ?string $previousHash): string
    {
        $payload = ($previousHash ?? '').'|'.$input;

        return hash('sha256', $payload);
    }

    /**
     * Verify all test vectors are consistent.
     *
     * @return bool True if all vectors are valid
     */
    public static function verifySelfConsistency(): bool
    {
        foreach (self::VECTORS as $vector) {
            $calculated = self::calculateHash($vector['input'], $vector['previous_hash']);
            if ($calculated !== $vector['expected_hash']) {
                return false;
            }
        }

        return true;
    }
}
