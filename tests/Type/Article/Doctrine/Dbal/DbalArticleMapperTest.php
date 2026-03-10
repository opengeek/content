<?php

declare(strict_types=1);

namespace Opengeek\Content\Tests\Type\Article\Doctrine\Dbal;

use Opengeek\Content\Type\Article\Article;
use Opengeek\Content\Type\Article\Doctrine\Dbal\DbalArticleMapper;
use PHPUnit\Framework\TestCase;

final class DbalArticleMapperTest extends TestCase
{
    private DbalArticleMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new DbalArticleMapper();
    }

    public function testMapToDto(): void
    {
        $row = [
            'slug' => 'test-slug',
            'title' => 'Test Title',
            'publish_date' => '2024-01-01 12:00:00',
            'markdown_content' => '# Content',
            'subtitle' => 'Test Subtitle',
            'summary' => 'Test Summary',
            'image' => 'test.jpg',
            'categories' => json_encode(['cat1', 'cat2']),
            'tags' => json_encode(['tag1', 'tag2']),
        ];

        $article = $this->mapper->mapToDto($row);

        $this->assertInstanceOf(Article::class, $article);
        $this->assertEquals('test-slug', $article->slug);
        $this->assertEquals('Test Title', $article->title);
        $this->assertEquals('2024-01-01 12:00:00', $article->publishDate);
        $this->assertEquals('# Content', $article->markdownContent);
        $this->assertEquals('Test Subtitle', $article->subtitle);
        $this->assertEquals('Test Summary', $article->summary);
        $this->assertEquals('test.jpg', $article->image);
        $this->assertEquals(['cat1', 'cat2'], $article->categories);
        $this->assertEquals(['tag1', 'tag2'], $article->tags);
    }

    public function testMapToDtoWithMissingOptionalFields(): void
    {
        $row = [
            'slug' => 'test-slug',
            'title' => 'Test Title',
            'publish_date' => '2024-01-01 12:00:00',
            'markdown_content' => '# Content',
        ];

        $article = $this->mapper->mapToDto($row);

        $this->assertEquals('', $article->subtitle);
        $this->assertEquals('', $article->summary);
        $this->assertEquals('', $article->image);
        $this->assertEquals([], $article->categories);
        $this->assertEquals([], $article->tags);
    }

    public function testMapToRow(): void
    {
        $article = new Article(
            slug: 'test-slug',
            title: 'Test Title',
            publishDate: '2024-01-01 12:00:00',
            markdownContent: '# Content',
            subtitle: 'Test Subtitle',
            summary: 'Test Summary',
            image: 'test.jpg',
            categories: ['cat1', 'cat2'],
            tags: ['tag1', 'tag2']
        );

        $row = $this->mapper->mapToRow($article);

        $this->assertEquals('test-slug', $row['slug']);
        $this->assertEquals('Test Title', $row['title']);
        $this->assertEquals('2024-01-01 12:00:00', $row['publish_date']);
        $this->assertEquals('# Content', $row['markdown_content']);
        $this->assertEquals('Test Subtitle', $row['subtitle']);
        $this->assertEquals('Test Summary', $row['summary']);
        $this->assertEquals('test.jpg', $row['image']);
        $this->assertEquals(json_encode(['cat1', 'cat2']), $row['categories']);
        $this->assertEquals(json_encode(['tag1', 'tag2']), $row['tags']);
    }
}
