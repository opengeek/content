<?php

declare(strict_types=1);

namespace Opengeek\Content\Contracts;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Interface for a collection of content items.
 *
 * @template TKey of int|string
 * @template TValue
 * @extends IteratorAggregate<TKey, TValue>
 */
interface ContentCollectionInterface extends IteratorAggregate, Countable
{
    /**
     * @return Traversable<TKey, TValue>
     */
    public function getIterator(): Traversable;

    public function count(): int;

    /**
     * Return a new collection containing $length items starting at $offset.
     *
     * @return static
     */
    public function slice(int $offset, int $length): self;

    /**
     * Return a new collection containing only published items as of $now.
     *
     * @return static
     */
    public function filterPublished(?\DateTimeImmutable $now = null): self;

    /**
     * Return a new collection sorted by publish date, newest first.
     *
     * @return static
     */
    public function sortByPublishDateDescending(): self;
}
