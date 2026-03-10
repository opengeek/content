<?php

declare(strict_types=1);

namespace Opengeek\Content\Type\Article;

/**
 * Combined contract for writable Article repositories.
 */
interface WritableArticleRepositoryInterface extends ArticleRepositoryInterface, ArticlePersisterInterface
{
}
