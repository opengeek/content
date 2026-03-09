<?php

declare(strict_types=1);

namespace Opengeek\Content\Article;

use Opengeek\Content\Contracts\ContentRepositoryInterface;
use Opengeek\Content\Exception\ContentNotFoundException;

/**
 * Repository contract for Article content items.
 *
 * Implementations may back this with Markdown files, a relational database,
 * a document store, a REST API, or a search index such as OpenSearch.
 * The returned types are always ArticleDto / ArticleCollection regardless
 * of the backing store.
 *
 * @extends ContentRepositoryInterface<ArticleDto, ArticleCollection>
 */
interface ArticleRepositoryInterface extends ContentRepositoryInterface
{
    /**
     * Return all articles from the backing store, unsorted and unfiltered.
     */
    public function findAll(): ArticleCollection;

    /**
     * Return only published articles, sorted newest-first.
     *
     * Implementations backed by a query engine (SQL, OpenSearch, etc.) SHOULD
     * push the publish-date filter and sort to the backing store rather than
     * loading all records into memory.
     */
    public function findPublished(): ArticleCollection;

    /**
     * Find a single article by its slug.
     *
     * @throws ContentNotFoundException if no article matches the given slug
     */
    public function findBySlug(string $slug): ArticleDto;
}
