<?php

declare(strict_types=1);

namespace Opengeek\Content\Type\Article;

use Opengeek\Content\Contracts\ContentPersisterInterface;
use Opengeek\Content\Exception\ContentPersistenceException;

/**
 * Persister contract for Article content items.
 *
 * @extends ContentPersisterInterface<Article>
 */
interface ArticlePersisterInterface extends ContentPersisterInterface
{
    /**
     * Persist an article to the backing store.
     *
     * Implementations should use insert-or-update (upsert) semantics
     * based on the article's slug.
     *
     * @param Article $content
     *
     * @throws ContentPersistenceException if the article cannot be saved
     */
    public function save(mixed $content): void;

    /**
     * Delete an article from the backing store by its slug.
     *
     * @param string $slug
     *
     * @throws ContentPersistenceException if the article cannot be deleted
     */
    public function delete(string $slug): void;
}
