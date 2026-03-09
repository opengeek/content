<?php

declare(strict_types=1);

namespace Opengeek\Content\Contracts;

use Opengeek\Content\Exception\ContentNotFoundException;

/**
 * Generic repository contract for content items.
 *
 * @template TDto
 * @template TCollection
 */
interface ContentRepositoryInterface
{
    /**
     * Return all content items from the backing store.
     *
     * @return TCollection
     */
    public function findAll(): mixed;

    /**
     * Find a single content item by its slug.
     *
     * @return TDto
     *
     * @throws ContentNotFoundException
     */
    public function findBySlug(string $slug): mixed;
}
