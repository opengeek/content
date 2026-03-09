<?php

declare(strict_types=1);

namespace Opengeek\Content\Article;

use ArrayAccess;
use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use LogicException;
use Traversable;

/**
 * A typed, immutable, iterable collection of ArticleDto objects.
 *
 * Methods that return a filtered or sorted subset always return a new
 * ArticleCollection instance rather than mutating the receiver.
 *
 * @implements IteratorAggregate<int, ArticleDto>
 * @implements ArrayAccess<int, ArticleDto>
 */
final class ArticleCollection implements IteratorAggregate, Countable, ArrayAccess
{
    /** @var ArticleDto[] */
    private array $items;

    /**
     * @param ArticleDto[] $items
     *
     * @throws InvalidArgumentException if any element is not an ArticleDto
     */
    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            if (!$item instanceof ArticleDto) {
                throw new InvalidArgumentException(sprintf(
                    'ArticleCollection only accepts %s instances, got %s',
                    ArticleDto::class,
                    get_debug_type($item)
                ));
            }
        }

        $this->items = array_values($items);
    }

    /**
     * Return a new collection containing only published articles as of $now.
     */
    public function filterPublished(\DateTimeImmutable $now = new \DateTimeImmutable()): self
    {
        return new self(array_values(
            array_filter($this->items, static fn(ArticleDto $dto) => $dto->isPublished($now))
        ));
    }

    /**
     * Return a new collection sorted by publish date, newest first.
     */
    public function sortByPublishDateDescending(): self
    {
        $items = $this->items;
        usort($items, static fn(ArticleDto $a, ArticleDto $b): int => (
            $b->getPublishDateTime() <=> $a->getPublishDateTime()
        ));

        return new self($items);
    }

    /**
     * Return a new collection containing $length items starting at $offset.
     */
    public function slice(int $offset, int $length): self
    {
        return new self(array_slice($this->items, $offset, $length));
    }

    // -----------------------------------------------------------------------
    // Countable
    // -----------------------------------------------------------------------

    public function count(): int
    {
        return count($this->items);
    }

    // -----------------------------------------------------------------------
    // IteratorAggregate
    // -----------------------------------------------------------------------

    /** @return Traversable<int, ArticleDto> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    // -----------------------------------------------------------------------
    // ArrayAccess (read-only; mutations throw)
    // -----------------------------------------------------------------------

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): ArticleDto
    {
        return $this->items[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('ArticleCollection is immutable');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('ArticleCollection is immutable');
    }
}
