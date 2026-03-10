<?php

declare(strict_types=1);

namespace Opengeek\Content\Type\Article\Doctrine\Dbal;

use Doctrine\DBAL\Connection;
use Opengeek\Content\Exception\ContentPersistenceException;
use Opengeek\Content\Type\Article\Article;
use Opengeek\Content\Type\Article\ArticlePersisterInterface;

/**
 * Article persister implementation using Doctrine DBAL.
 */
final readonly class DbalArticlePersister implements ArticlePersisterInterface
{
    public function __construct(
        private Connection $connection,
        private DbalArticleMapper $mapper = new DbalArticleMapper()
    ) {
    }

    public function save(mixed $content): void
    {
        if (!$content instanceof Article) {
            throw new \InvalidArgumentException(sprintf(
                'DbalArticlePersister::save() expects %s, got %s',
                Article::class,
                get_debug_type($content)
            ));
        }

        $row = $this->mapper->mapToRow($content);

        try {
            // Check if it exists to decide between insert or update
            $exists = $this->connection->fetchOne(
                'SELECT 1 FROM articles WHERE slug = ?',
                [$content->slug]
            );

            if ($exists) {
                $this->connection->update('articles', $row, ['slug' => $content->slug]);
            } else {
                $this->connection->insert('articles', $row);
            }
        } catch (\Throwable $e) {
            throw ContentPersistenceException::forSave($content->slug, $e);
        }
    }

    public function delete(string $slug): void
    {
        try {
            $this->connection->delete('articles', ['slug' => $slug]);
        } catch (\Throwable $e) {
            throw ContentPersistenceException::forDelete($slug, $e);
        }
    }
}
