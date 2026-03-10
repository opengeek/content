<?php

declare(strict_types=1);

namespace Opengeek\Content\Tests\Type\Article\Doctrine\Sqlite;

use Doctrine\DBAL\DriverManager;
use Opengeek\Content\Type\Article\Doctrine\Sqlite\SqliteArticleSchemaManager;
use PHPUnit\Framework\TestCase;

final class SqliteArticleSchemaManagerTest extends TestCase
{
    public function testInitializeSchemaCreatesTableAndIndex(): void
    {
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        $schemaManager = new SqliteArticleSchemaManager($connection);
        $schemaManager->initializeSchema();

        $sm = $connection->createSchemaManager();
        $this->assertTrue($sm->tablesExist(['articles']));

        $columns = $sm->listTableColumns('articles');
        $this->assertArrayHasKey('slug', $columns);
        $this->assertArrayHasKey('title', $columns);
        $this->assertArrayHasKey('publish_date', $columns);
        $this->assertArrayHasKey('markdown_content', $columns);

        $indexes = $sm->listTableIndexes('articles');
        $this->assertArrayHasKey('idx_articles_publish_date', $indexes);
    }

    public function testInitializeSchemaIsIdempotent(): void
    {
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        $schemaManager = new SqliteArticleSchemaManager($connection);
        
        // Run twice
        $schemaManager->initializeSchema();
        $schemaManager->initializeSchema();

        $sm = $connection->createSchemaManager();
        $this->assertTrue($sm->tablesExist(['articles']));
    }
}
