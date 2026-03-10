<?php

declare(strict_types=1);

namespace Opengeek\Content\Tests\Type\Article\Doctrine\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Opengeek\Content\Exception\ContentPersistenceException;
use Opengeek\Content\Type\Article\Article;
use Opengeek\Content\Type\Article\Doctrine\Dbal\DbalArticlePersister;
use Opengeek\Content\Type\Article\Doctrine\Sqlite\SqliteArticleSchemaManager;
use PHPUnit\Framework\TestCase;

final class DbalArticlePersisterTest extends TestCase
{
    private Connection $connection;
    private DbalArticlePersister $persister;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        $schemaManager = new SqliteArticleSchemaManager($this->connection);
        $schemaManager->initializeSchema();

        $this->persister = new DbalArticlePersister($this->connection);
    }

    public function testSaveInsertsNewArticle(): void
    {
        $article = new Article(
            slug: 'new-article',
            title: 'New Article',
            publishDate: '2024-01-01 12:00:00',
            markdownContent: 'Content'
        );

        $this->persister->save($article);

        $count = $this->connection->fetchOne('SELECT COUNT(*) FROM articles');
        $this->assertEquals(1, $count);

        $row = $this->connection->fetchAssociative('SELECT * FROM articles WHERE slug = ?', ['new-article']);
        $this->assertEquals('New Article', $row['title']);
    }

    public function testSaveUpdatesExistingArticle(): void
    {
        $article1 = new Article(
            slug: 'test',
            title: 'Original Title',
            publishDate: '2024-01-01 12:00:00',
            markdownContent: 'Original Content'
        );
        $this->persister->save($article1);

        $article2 = new Article(
            slug: 'test',
            title: 'Updated Title',
            publishDate: '2024-01-01 12:00:00',
            markdownContent: 'Updated Content'
        );
        $this->persister->save($article2);

        $count = $this->connection->fetchOne('SELECT COUNT(*) FROM articles');
        $this->assertEquals(1, $count);

        $row = $this->connection->fetchAssociative('SELECT * FROM articles WHERE slug = ?', ['test']);
        $this->assertEquals('Updated Title', $row['title']);
    }

    public function testDeleteRemovesArticle(): void
    {
        $article = new Article(
            slug: 'to-delete',
            title: 'To Delete',
            publishDate: '2024-01-01 12:00:00',
            markdownContent: 'Content'
        );
        $this->persister->save($article);

        $this->persister->delete('to-delete');

        $count = $this->connection->fetchOne('SELECT COUNT(*) FROM articles');
        $this->assertEquals(0, $count);
    }

    public function testDeleteIsSilentIfSlugDoesNotExist(): void
    {
        $this->persister->delete('non-existent');
        $this->assertTrue(true); // Should not throw exception
    }

    public function testSaveThrowsExceptionOnDbalError(): void
    {
        // Drop table to cause error
        $this->connection->executeStatement('DROP TABLE articles');

        $article = new Article(
            slug: 'test',
            title: 'Title',
            publishDate: '2024-01-01 12:00:00',
            markdownContent: 'Content'
        );

        $this->expectException(ContentPersistenceException::class);
        $this->persister->save($article);
    }
}
