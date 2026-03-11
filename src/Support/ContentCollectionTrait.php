<?php

declare(strict_types=1);

namespace Opengeek\Content\Support;

use DateTimeImmutable;

/**
 * Common implementation for ContentCollectionInterface.
 */
trait ContentCollectionTrait
{
    /**
     * @var array<int|string, mixed>
     */
    private array $items;

    /**
     * @return static
     */
    public function slice(int $offset, int $length): self
    {
        return new self(array_slice($this->items, $offset, $length));
    }

    /**
     * @return static
     */
    public function filterPublished(?DateTimeImmutable $now = null): self
    {
        $now ??= new DateTimeImmutable();

        return new self(array_values(
            array_filter($this->items, static fn(mixed $dto) => (
                method_exists($dto, 'isPublished') && $dto->isPublished($now)
            ))
        ));
    }

    /**
     * @return static
     */
    public function sortByPublishDateDescending(): self
    {
        $items = $this->items;
        usort($items, static function (mixed $a, mixed $b): int {
            if (!method_exists($a, 'getPublishDateTime') || !method_exists($b, 'getPublishDateTime')) {
                return 0;
            }

            return $b->getPublishDateTime() <=> $a->getPublishDateTime();
        });

        return new self($items);
    }
}
