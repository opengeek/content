<?php

declare(strict_types=1);

namespace Opengeek\Content\Contracts;

use Opengeek\Content\Exception\ContentPersistenceException;

/**
 * Generic persistence contract for content items.
 *
 * @template TDto
 */
interface ContentPersisterInterface
{
    /**
     * Persist a content item to the backing store.
     *
     * Implementations should use insert-or-update (upsert) semantics.
     *
     * @param TDto $content
     *
     * @throws ContentPersistenceException if the item cannot be saved
     */
    public function save(mixed $content): void;

    /**
     * Delete a content item from the backing store by its unique identifier (slug).
     *
     * @param string $slug
     *
     * @throws ContentPersistenceException if the item cannot be deleted
     */
    public function delete(string $slug): void;
}
