<?php

declare(strict_types=1);

namespace Opengeek\Content\Tests\Type\Article\Doctrine\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Opengeek\Content\Exception\ContentNotFoundException;
use Opengeek\Content\Type\Article\Article;
use Opengeek\Content\Type\Article\Doctrine\Dbal\DbalArticleRepository;
use Opengeek\Content\Type\Article\Doctrine\Sqlite\SqliteArticleSchemaManager;
use PHPUnit\Framework\TestCase;

final class DbalArticleRepositoryTest extends TestCase
{
    private Connection $connection;
    private DbalArticleRepository $repository;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        $schemaManager = new SqliteArticleSchemaManager($this->connection);
        $schemaManager->initializeSchema();

        $this->repository = new DbalArticleRepository($this->connection);
    }

    private function insertArticle(array $data): void
    {
        $defaults = [
            'subtitle' => '',
            'summary' => '',
            'image' => '',
            'categories' => '[]',
            'tags' => '[]',
        ];
        $this->connection->insert('articles', array_merge($defaults, $data));
    }

    public function testFindAllReturnsArticlesSortedByDate(): void
    {
        $this->insertArticle([
            'slug' => 'old',
            'title' => 'Old',
            'publish_date' => '2024-01-01 12:00:00',
            'markdown_content' => 'Old',
        ]);
        $this->insertArticle([
            'slug' => 'new',
            'title' => 'New',
            'publish_date' => '2024-01-02 12:00:00',
            'markdown_content' => 'New',
        ]);

        $articles = $this->repository->findAll();

        $this->assertCount(2, $articles);
        $this->assertEquals('new', $articles[0]->slug);
        $this->assertEquals('old', $articles[1]->slug);
    }

    public function testFindBySlugReturnsArticle(): void
    {
        $this->insertArticle([
            'slug' => 'test',
            'title' => 'Test',
            'publish_date' => '2024-01-01 12:00:00',
            'markdown_content' => 'Test',
        ]);

        $article = $this->repository->findBySlug('test');

        $this->assertInstanceOf(Article::class, $article);
        $this->assertEquals('test', $article->slug);
    }

    public function testFindBySlugThrowsExceptionIfNotFound(): void
    {
        $this->expectException(ContentNotFoundException::class);
        $this->repository->findBySlug('missing');
    }

    public function testFindPublishedFiltersByDate(): void
    {
        $this->insertArticle([
            'slug' => 'past',
            'title' => 'Past',
            'publish_date' => '2024-01-01 12:00:00',
            'markdown_content' => 'Past',
        ]);
        $this->insertArticle([
            'slug' => 'future',
            'title' => 'Future',
            'publish_date' => '2024-01-03 12:00:00',
            'markdown_content' => 'Future',
        ]);

        $now = new \DateTimeImmutable('2024-01-02 12:00:00');
        $articles = $this->repository->findPublished($now);

        $this->assertCount(1, $articles);
        $this->assertEquals('past', $articles[0]->slug);
    }
}
