<?php

declare(strict_types=1);

namespace Opengeek\Content\Renderer;

/**
 * Converts a Markdown string to HTML.
 *
 * Inject this into controllers or Twig extensions that need rendered HTML.
 * The ArticleDto intentionally stores raw Markdown so that consumers
 * not needing HTML (RSS feeds, search indexers, etc.) pay no rendering cost.
 */
interface MarkdownRendererInterface
{
    /**
     * Render the given Markdown string as HTML.
     */
    public function render(string $markdown): string;
}
