<?php

declare(strict_types=1);

namespace Opengeek\Content\Article;

/**
 * Data Transfer Object representing an article content item.
 *
 * This is a pure value container. HTML rendering of the markdown content
 * is the responsibility of the consuming application via MarkdownRendererInterface.
 */
final readonly class ArticleDto
{
    /**
     * @param string   $slug            URL-safe identifier, e.g. "2024/01/my-article"
     * @param string   $title           Article title
     * @param string   $publishDate     Date string parseable by DateTimeImmutable, e.g. "2024-01-10 12:00pm"
     * @param string   $markdownContent Raw Markdown body (not HTML)
     * @param string   $subtitle        Optional subtitle
     * @param string   $summary         Short summary / teaser text
     * @param string   $image           Path or URL to a hero image
     * @param string[] $categories      Category labels
     * @param string[] $tags            Tag labels
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $title,
        public readonly string $publishDate,
        public readonly string $markdownContent,
        public readonly string $subtitle = '',
        public readonly string $summary = '',
        public readonly string $image = '',
        public readonly array $categories = [],
        public readonly array $tags = [],
    ) {
    }

    public function getPublishDateTime(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->publishDate);
    }

    public function isPublished(\DateTimeImmutable $now = new \DateTimeImmutable()): bool
    {
        return $this->getPublishDateTime() <= $now;
    }
}
