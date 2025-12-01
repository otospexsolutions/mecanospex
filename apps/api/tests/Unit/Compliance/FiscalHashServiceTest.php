<?php

declare(strict_types=1);

namespace Tests\Unit\Compliance;

use App\Modules\Compliance\Services\FiscalHashService;
use Tests\Fixtures\HashChainReference;
use Tests\TestCase;

class FiscalHashServiceTest extends TestCase
{
    private FiscalHashService $hashService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hashService = new FiscalHashService;
    }

    public function test_fiscal_hash_service_class_exists(): void
    {
        $this->assertTrue(class_exists(FiscalHashService::class));
    }

    public function test_reference_fixture_is_self_consistent(): void
    {
        // This verifies our test vectors are correct
        $this->assertTrue(HashChainReference::verifySelfConsistency());
    }

    public function test_calculates_genesis_hash_correctly(): void
    {
        $vector = HashChainReference::VECTORS[0];

        $hash = $this->hashService->calculateHash($vector['input'], $vector['previous_hash']);

        $this->assertEquals($vector['expected_hash'], $hash);
    }

    public function test_calculates_chained_hash_correctly(): void
    {
        $vector = HashChainReference::VECTORS[1];

        $hash = $this->hashService->calculateHash($vector['input'], $vector['previous_hash']);

        $this->assertEquals($vector['expected_hash'], $hash);
    }

    public function test_calculates_all_test_vectors_correctly(): void
    {
        foreach (HashChainReference::VECTORS as $index => $vector) {
            $hash = $this->hashService->calculateHash($vector['input'], $vector['previous_hash']);

            $this->assertEquals(
                $vector['expected_hash'],
                $hash,
                "Vector {$index} failed: expected {$vector['expected_hash']}, got {$hash}"
            );
        }
    }

    public function test_verify_chain_returns_true_for_valid_chain(): void
    {
        $documents = [];
        $previousHash = null;

        foreach (HashChainReference::VECTORS as $vector) {
            $hash = $this->hashService->calculateHash($vector['input'], $previousHash);
            $documents[] = [
                'input' => $vector['input'],
                'hash' => $hash,
                'previous_hash' => $previousHash,
            ];
            $previousHash = $hash;
        }

        $this->assertTrue($this->hashService->verifyChain($documents));
    }

    public function test_verify_chain_returns_false_for_tampered_document(): void
    {
        $documents = [];
        $previousHash = null;

        foreach (HashChainReference::VECTORS as $vector) {
            $hash = $this->hashService->calculateHash($vector['input'], $previousHash);
            $documents[] = [
                'input' => $vector['input'],
                'hash' => $hash,
                'previous_hash' => $previousHash,
            ];
            $previousHash = $hash;
        }

        // Tamper with the second document's hash
        $documents[1]['hash'] = 'tampered_hash_value_that_is_invalid';

        $this->assertFalse($this->hashService->verifyChain($documents));
    }

    public function test_verify_chain_returns_false_for_broken_chain(): void
    {
        $documents = [];
        $previousHash = null;

        foreach (HashChainReference::VECTORS as $vector) {
            $hash = $this->hashService->calculateHash($vector['input'], $previousHash);
            $documents[] = [
                'input' => $vector['input'],
                'hash' => $hash,
                'previous_hash' => $previousHash,
            ];
            $previousHash = $hash;
        }

        // Break the chain by modifying previous_hash reference
        $documents[2]['previous_hash'] = 'wrong_previous_hash';

        $this->assertFalse($this->hashService->verifyChain($documents));
    }

    public function test_serialize_document_for_hashing(): void
    {
        $documentData = [
            'document_number' => 'INV-2025-0001',
            'posted_at' => '2025-01-15',
            'total' => '1500.00',
            'currency' => 'EUR',
        ];

        $serialized = $this->hashService->serializeForHashing($documentData);

        $this->assertEquals('INV-2025-0001|2025-01-15|1500.00|EUR', $serialized);
    }
}
