<?php

declare(strict_types=1);

namespace Opengeek\Content\Tests;

use Opengeek\Content\Article;
use PHPUnit\Framework\TestCase;

final class ArticleTest extends TestCase
{
    private function makeDto(string $publishDate = '2024-01-15 09:00am'): Article
    {
        return new Article(
            slug: '2024/01/hello-world',
            title: 'Hello World',
            publishDate: $publishDate,
            markdownContent: '## Hello',
            subtitle: 'The subtitle',
            summary: 'A summary',
            image: '/images/test.jpg',
            categories: ['General'],
            tags: ['intro'],
        );
    }

    public function testGetPublishDateTimeReturnsDateTimeImmutable(): void
    {
        $dto = $this->makeDto('2024-01-15 09:00am');
        $dt = $dto->getPublishDateTime();

        self::assertInstanceOf(\DateTimeImmutable::class, $dt);
        self::assertSame('2024-01-15', $dt->format('Y-m-d'));
    }

    public function testIsPublishedReturnsTrueForPastDate(): void
    {
        $dto = $this->makeDto('2000-01-01 00:00:00');

        self::assertTrue($dto->isPublished());
    }

    public function testIsPublishedReturnsFalseForFutureDate(): void
    {
        $dto = $this->makeDto('2099-12-31 00:00:00');

        self::assertFalse($dto->isPublished());
    }

    public function testIsPublishedUsesProvidedNow(): void
    {
        $dto = $this->makeDto('2024-06-01 00:00:00');
        $beforePublish = new \DateTimeImmutable('2024-05-01');
        $afterPublish = new \DateTimeImmutable('2024-07-01');

        self::assertFalse($dto->isPublished($beforePublish));
        self::assertTrue($dto->isPublished($afterPublish));
    }

    public function testOptionalFieldsHaveDefaults(): void
    {
        $dto = new Article(
            slug: 'test',
            title: 'Test',
            publishDate: '2024-01-01',
            markdownContent: '',
        );

        self::assertSame('', $dto->subtitle);
        self::assertSame('', $dto->summary);
        self::assertSame('', $dto->image);
        self::assertSame([], $dto->categories);
        self::assertSame([], $dto->tags);
    }
}
