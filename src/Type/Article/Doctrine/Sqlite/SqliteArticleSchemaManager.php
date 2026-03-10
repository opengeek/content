<?php

declare(strict_types=1);

namespace Opengeek\Content\Type\Article\Doctrine\Sqlite;

use Doctrine\DBAL\Connection;

/**
 * Idempotent schema manager for Article storage in SQLite.
 */
final readonly class SqliteArticleSchemaManager
{
    public function __construct(
        private Connection $connection
    ) {
    }

    /**
     * Initialize the articles table if it does not exist.
     */
    public function initializeSchema(): void
    {
        $this->connection->executeStatement("
            CREATE TABLE IF NOT EXISTS articles (
                slug TEXT PRIMARY KEY,
                title TEXT NOT NULL,
                publish_date TEXT NOT NULL,
                markdown_content TEXT NOT NULL,
                subtitle TEXT DEFAULT '',
                summary TEXT DEFAULT '',
                image TEXT DEFAULT '',
                categories TEXT DEFAULT '[]',
                tags TEXT DEFAULT '[]'
            )
        ");

        $this->connection->executeStatement("
            CREATE INDEX IF NOT EXISTS idx_articles_publish_date 
            ON articles (publish_date DESC)
        ");
    }
}
