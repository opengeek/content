<?php

declare(strict_types=1);

namespace Opengeek\Content\Type\Article\Doctrine\Dbal;

use Opengeek\Content\Type\Article\Article;

/**
 * Maps DBAL row arrays to Article DTOs and vice versa.
 */
final readonly class DbalArticleMapper
{
    /**
     * Map a database row to an Article DTO.
     *
     * @param array<string, mixed> $row
     *
     * @return Article
     */
    public function mapToDto(array $row): Article
    {
        return new Article(
            slug: (string) $row['slug'],
            title: (string) $row['title'],
            publishDate: (string) $row['publish_date'],
            markdownContent: (string) $row['markdown_content'],
            subtitle: (string) ($row['subtitle'] ?? ''),
            summary: (string) ($row['summary'] ?? ''),
            image: (string) ($row['image'] ?? ''),
            categories: $this->decodeJsonList($row['categories'] ?? '[]'),
            tags: $this->decodeJsonList($row['tags'] ?? '[]'),
        );
    }

    /**
     * Map an Article DTO to a database row array.
     *
     * @param Article $article
     *
     * @return array<string, mixed>
     */
    public function mapToRow(Article $article): array
    {
        return [
            'slug' => $article->slug,
            'title' => $article->title,
            'publish_date' => $article->publishDate,
            'markdown_content' => $article->markdownContent,
            'subtitle' => $article->subtitle,
            'summary' => $article->summary,
            'image' => $article->image,
            'categories' => json_encode($article->categories, JSON_THROW_ON_ERROR),
            'tags' => json_encode($article->tags, JSON_THROW_ON_ERROR),
        ];
    }

    /**
     * @param string $json
     *
     * @return string[]
     */
    private function decodeJsonList(string $json): array
    {
        if (empty($json)) {
            return [];
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? array_map(strval(...), $decoded) : [];
        } catch (\JsonException) {
            return [];
        }
    }
}
