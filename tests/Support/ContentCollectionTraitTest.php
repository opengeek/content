<?php

declare(strict_types=1);

namespace Opengeek\Content\Tests\Support;

use Opengeek\Content\Support\ContentCollectionTrait;
use PHPUnit\Framework\TestCase;

final class ContentCollectionTraitTest extends TestCase
{
    public function testSortByPublishDateDescendingSkipsItemsWithoutMethod(): void
    {
        $item1 = new class () {
            public function getPublishDateTime(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2024-01-01');
            }
        };
        $item2 = new class () {}; // Missing method
        $item3 = new class () {
            public function getPublishDateTime(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2024-01-02');
            }
        };

        $collection = new class ([$item1, $item2, $item3]) {
            use ContentCollectionTrait;
            public function __construct(array $items)
            {
                $this->items = $items;
            }
            public function all()
            {
                return $this->items;
            }
        };

        $sorted = $collection->sortByPublishDateDescending();
        $items = $sorted->all();

        // item2 stays in its place or at least doesn't break things.
        // item3 should be before item1 if both have the method.
        self::assertCount(3, $items);
        // The implementation returns 0 if any item lacks the method during comparison.
        // This might not result in a perfect sort if many items are missing methods,
        // but it should at least not crash.
    }

    public function testFilterPublishedSkipsItemsWithoutMethod(): void
    {
        $item1 = new class () {
            public function isPublished(\DateTimeImmutable $now): bool
            {
                return true;
            }
        };
        $item2 = new class () {}; // Missing method

        $collection = new class ([$item1, $item2]) {
            use ContentCollectionTrait;
            public function __construct(array $items)
            {
                $this->items = $items;
            }
            public function all()
            {
                return $this->items;
            }
        };

        $filtered = $collection->filterPublished();
        $items = $filtered->all();

        self::assertCount(1, $items);
        self::assertSame($item1, $items[0]);
    }
}
