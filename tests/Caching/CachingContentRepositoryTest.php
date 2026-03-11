<?php

declare(strict_types=1);

namespace Opengeek\Content\Tests\Caching;

use DateTimeImmutable;
use Opengeek\Content\Caching\CachingContentRepository;
use Opengeek\Content\Contracts\ContentRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class CachingContentRepositoryTest extends TestCase
{
    private $repository;
    private $cache;
    private $cachingRepository;
    private string $cachePrefix = 'test';

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ContentRepositoryInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->cachingRepository = new CachingContentRepository(
            $this->repository,
            $this->cache,
            $this->cachePrefix,
            3600
        );
    }

    public function testFindAllUsesCache(): void
    {
        $items = ['item1', 'item2'];
        $this->cache->expects(self::once())
            ->method('get')
            ->with($this->cachePrefix . '.all', self::isType('callable'))
            ->willReturnCallback(function (string $key, callable $callback) use ($items) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects(self::once())->method('expiresAfter')->with(3600);
                return $callback($item);
            });

        $this->repository->expects(self::once())
            ->method('findAll')
            ->willReturn($items);

        $result = $this->cachingRepository->findAll();
        self::assertSame($items, $result);
    }

    public function testFindBySlugUsesCache(): void
    {
        $slug = 'hello-world';
        $key = sprintf('%s.slug.%s', $this->cachePrefix, md5($slug));
        $dto = (object) ['slug' => $slug];

        $this->cache->expects(self::once())
            ->method('get')
            ->with($key, self::isType('callable'))
            ->willReturnCallback(function (string $key, callable $callback) use ($dto) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects(self::once())->method('expiresAfter')->with(3600);
                return $callback($item);
            });

        $this->repository->expects(self::once())
            ->method('findBySlug')
            ->with($slug)
            ->willReturn($dto);

        $result = $this->cachingRepository->findBySlug($slug);
        self::assertSame($dto, $result);
    }

    public function testFindPublishedUsesCache(): void
    {
        $now = new DateTimeImmutable('2024-01-01 12:00:00');
        $key = sprintf('%s.published.%s', $this->cachePrefix, $now->format('YmdH'));
        $items = ['published-item'];

        $this->cache->expects(self::once())
            ->method('get')
            ->with($key, self::isType('callable'))
            ->willReturnCallback(function (string $key, callable $callback) use ($items) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects(self::once())->method('expiresAfter')->with(3600);
                return $callback($item);
            });

        $this->repository->expects(self::once())
            ->method('findPublished')
            ->with($now)
            ->willReturn($items);

        $result = $this->cachingRepository->findPublished($now);
        self::assertSame($items, $result);
    }

    public function testFindPublishedUsesCurrentTimeWhenNull(): void
    {
        $this->cache->expects(self::once())
            ->method('get')
            ->with(self::stringStartsWith($this->cachePrefix . '.published.'), self::isType('callable'))
            ->willReturnCallback(function (string $key, callable $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $this->repository->expects(self::once())
            ->method('findPublished')
            ->with(self::isInstanceOf(DateTimeImmutable::class));

        $this->cachingRepository->findPublished(null);
    }
}
