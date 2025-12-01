<?php

declare(strict_types=1);

namespace App\Shared\Contracts;

/**
 * Base interface for all repository contracts.
 *
 * Repositories provide collection-like access to aggregates.
 * Domain layer defines interfaces, infrastructure layer provides implementations.
 *
 * Module boundaries are sacred:
 * Cross-module communication ONLY via interfaces in Shared/Contracts/
 */
interface RepositoryInterface
{
    /**
     * Find an entity by its unique identifier.
     *
     * @param  string  $id  The unique identifier
     * @return object|null The entity or null if not found
     */
    public function find(string $id): ?object;

    /**
     * Save an entity to the repository.
     *
     * @param  object  $entity  The entity to save
     */
    public function save(object $entity): void;

    /**
     * Remove an entity from the repository.
     *
     * @param  object  $entity  The entity to remove
     */
    public function delete(object $entity): void;
}
