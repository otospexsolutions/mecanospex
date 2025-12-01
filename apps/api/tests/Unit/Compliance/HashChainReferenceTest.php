<?php

declare(strict_types=1);

namespace Tests\Unit\Compliance;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\HashChainReference;

/**
 * Tests for the HashChainReference fixture.
 *
 * These tests verify that the ground truth test vectors are self-consistent,
 * ensuring that the FiscalHashService implementation can be tested against them.
 */
class HashChainReferenceTest extends TestCase
{
    public function test_vectors_are_self_consistent(): void
    {
        $this->assertTrue(
            HashChainReference::verifySelfConsistency(),
            'Hash chain reference vectors are not self-consistent'
        );
    }

    public function test_genesis_hash_is_calculated_correctly(): void
    {
        $vector = HashChainReference::VECTORS[0];

        $calculated = HashChainReference::calculateHash($vector['input'], $vector['previous_hash']);

        $this->assertSame(
            $vector['expected_hash'],
            $calculated,
            'Genesis hash does not match expected value'
        );
    }

    public function test_chained_hash_is_calculated_correctly(): void
    {
        $vector = HashChainReference::VECTORS[1];

        $calculated = HashChainReference::calculateHash($vector['input'], $vector['previous_hash']);

        $this->assertSame(
            $vector['expected_hash'],
            $calculated,
            'Chained hash does not match expected value'
        );
    }

    public function test_chain_integrity(): void
    {
        $previousHash = null;

        foreach (HashChainReference::VECTORS as $index => $vector) {
            // Verify the previous hash matches what we expect
            $this->assertSame(
                $vector['previous_hash'],
                $previousHash,
                "Vector {$index} has incorrect previous_hash reference"
            );

            // Calculate and verify the hash
            $calculated = HashChainReference::calculateHash($vector['input'], $previousHash);
            $this->assertSame(
                $vector['expected_hash'],
                $calculated,
                "Vector {$index} hash calculation failed"
            );

            // Set up for next iteration
            $previousHash = $calculated;
        }
    }

    public function test_hash_format_is_lowercase_hex(): void
    {
        foreach (HashChainReference::VECTORS as $index => $vector) {
            $this->assertMatchesRegularExpression(
                '/^[a-f0-9]{64}$/',
                $vector['expected_hash'],
                "Vector {$index} expected_hash is not valid SHA-256 format"
            );
        }
    }
}
