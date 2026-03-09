<?php

declare(strict_types=1);

namespace Opengeek\Content\Tests\Article;

use LogicException;
use Opengeek\Content\Article\ArticleCollection;
use Opengeek\Content\Article\ArticleDto;
use PHPUnit\Framework\TestCase;

final class ArticleCollectionTest extends TestCase
{
    private function makeDto(string $slug, string $publishDate): ArticleDto
    {
        return new ArticleDto(
            slug: $slug,
            title: ucwords(str_replace('-', ' ', $slug)),
            publishDate: $publishDate,
            markdownContent: '## ' . $slug,
        );
    }

    public function testEmptyCollectionHasZeroCount(): void
    {
        $collection = new ArticleCollection();

        self::assertCount(0, $collection);
    }

    public function testCountReflectsItems(): void
    {
        $collection = new ArticleCollection([
            $this->makeDto('a', '2024-01-01'),
            $this->makeDto('b', '2024-01-02'),
        ]);

        self::assertCount(2, $collection);
    }

    public function testIterationYieldsAllItems(): void
    {
        $dtos = [
            $this->makeDto('a', '2024-01-01'),
            $this->makeDto('b', '2024-01-02'),
        ];
        $collection = new ArticleCollection($dtos);

        $slugs = [];
        foreach ($collection as $dto) {
            $slugs[] = $dto->slug;
        }

        self::assertSame(['a', 'b'], $slugs);
    }

    public function testArrayAccessOffsetGet(): void
    {
        $dto = $this->makeDto('a', '2024-01-01');
        $collection = new ArticleCollection([$dto]);

        self::assertSame($dto, $collection[0]);
    }

    public function testOffsetSetThrowsLogicException(): void
    {
        $collection = new ArticleCollection();

        $this->expectException(LogicException::class);
        $collection[0] = $this->makeDto('x', '2024-01-01'); // @phpstan-ignore-line
    }

    public function testOffsetUnsetThrowsLogicException(): void
    {
        $collection = new ArticleCollection([$this->makeDto('a', '2024-01-01')]);

        $this->expectException(LogicException::class);
        unset($collection[0]);
    }

    public function testFilterPublishedRemovesFutureArticles(): void
    {
        $past = $this->makeDto('past', '2000-01-01');
        $future = $this->makeDto('future', '2099-12-31');
        $collection = new ArticleCollection([$past, $future]);

        $published = $collection->filterPublished(new \DateTimeImmutable('2024-06-01'));

        self::assertCount(1, $published);
        self::assertSame('past', $published[0]->slug);
    }

    public function testFilterPublishedReturnsNewInstance(): void
    {
        $collection = new ArticleCollection([$this->makeDto('a', '2000-01-01')]);
        $filtered = $collection->filterPublished();

        self::assertNotSame($collection, $filtered);
    }

    public function testSortByPublishDateDescending(): void
    {
        $old = $this->makeDto('old', '2024-01-01');
        $new = $this->makeDto('new', '2024-06-01');
        $mid = $this->makeDto('mid', '2024-03-01');
        $collection = new ArticleCollection([$old, $new, $mid]);

        $sorted = $collection->sortByPublishDateDescending();

        self::assertSame('new', $sorted[0]->slug);
        self::assertSame('mid', $sorted[1]->slug);
        self::assertSame('old', $sorted[2]->slug);
    }

    public function testSortByPublishDateDescendingReturnsNewInstance(): void
    {
        $collection = new ArticleCollection([$this->makeDto('a', '2024-01-01')]);
        $sorted = $collection->sortByPublishDateDescending();

        self::assertNotSame($collection, $sorted);
    }

    public function testSliceReturnsSubset(): void
    {
        $items = array_map(
            fn(int $i) => $this->makeDto("item-{$i}", "2024-01-0{$i}"),
            range(1, 5)
        );
        $collection = new ArticleCollection($items);

        $page = $collection->slice(1, 2);

        self::assertCount(2, $page);
        self::assertSame('item-2', $page[0]->slug);
        self::assertSame('item-3', $page[1]->slug);
    }

    public function testConstructorRejectsNonDtoItems(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ArticleCollection(['not-a-dto']); // @phpstan-ignore-line
    }
}
