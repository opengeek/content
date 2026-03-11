<?php

declare(strict_types=1);

namespace Opengeek\Content;

use Opengeek\Content\Contracts\ContentCollectionInterface;
use Opengeek\Content\Support\ContentCollectionTrait;
use ArrayAccess;
use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use LogicException;
use Traversable;

/**
 * A typed, immutable, iterable collection of Article objects.
 *
 * Methods that return a filtered or sorted subset always return a new
 * ArticleCollection instance rather than mutating the receiver.
 *
 * @implements ContentCollectionInterface<int, Article>
 * @implements ArrayAccess<int, Article>
 */
final class ArticleCollection implements ContentCollectionInterface, ArrayAccess
{
    use ContentCollectionTrait;

    /** @var Article[] */
    private array $items;

    /**
     * @param Article[] $items
     *
     * @throws InvalidArgumentException if any element is not an Article
     */
    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            if (!$item instanceof Article) {
                throw new InvalidArgumentException(sprintf(
                    'ArticleCollection only accepts %s instances, got %s',
                    Article::class,
                    get_debug_type($item)
                ));
            }
        }

        $this->items = array_values($items);
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

    /** @return Traversable<int, Article> */
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

    public function offsetGet(mixed $offset): Article
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
