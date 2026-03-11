<?php

declare(strict_types=1);

namespace Opengeek\Content\Caching;

use Opengeek\Content\Contracts\ContentRepositoryInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * A generic decorator that adds caching to any ContentRepositoryInterface.
 *
 * @template TDto
 * @template TCollection
 * @implements ContentRepositoryInterface<TDto, TCollection>
 */
class CachingContentRepository implements ContentRepositoryInterface
{
    /**
     * @param ContentRepositoryInterface<TDto, TCollection> $repository
     */
    public function __construct(
        protected readonly ContentRepositoryInterface $repository,
        protected readonly CacheInterface $cache,
        protected readonly string $cachePrefix,
        protected readonly int $ttl = 3600,
    ) {
    }

    public function findAll(): mixed
    {
        return $this->cache->get($this->cachePrefix . '.all', function (ItemInterface $item) {
            $item->expiresAfter($this->ttl);
            return $this->repository->findAll();
        });
    }

    public function findBySlug(string $slug): mixed
    {
        $key = sprintf('%s.slug.%s', $this->cachePrefix, md5($slug));

        return $this->cache->get($key, function (ItemInterface $item) use ($slug) {
            $item->expiresAfter($this->ttl);
            return $this->repository->findBySlug($slug);
        });
    }

    public function findPublished(?\DateTimeImmutable $now = null): mixed
    {
        $now ??= new \DateTimeImmutable();
        $key = sprintf('%s.published.%s', $this->cachePrefix, $now->format('YmdH'));

        return $this->cache->get($key, function (ItemInterface $item) use ($now) {
            $item->expiresAfter($this->ttl);
            return $this->repository->findPublished($now);
        });
    }
}
