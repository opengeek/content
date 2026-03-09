# opengeek/content

![PHP ^8.3](https://img.shields.io/badge/PHP-%5E8.3-blue)

A structured content library for PHP applications. Provides a **repository + DTO + explicit mapping** abstraction for content types, with a Markdown file implementation included. The backing store is swappable — the same controller code works whether content comes from Markdown files, a SQL database, a document store, or a REST API.

## Requirements

- PHP ^8.3
- Composer

## Installation

```bash
composer require opengeek/content
```

## Concepts

The library is built on three layers:

| Layer | Class | Purpose |
|-------|-------|---------|
| **DTO** | `ArticleDto` | Readonly value object holding article data. No behaviour — just typed fields. |
| **Mapper** | `MarkdownArticleMapper` | Translates a parsed Markdown + YAML document into an `ArticleDto`. |
| **Repository** | `ArticleRepositoryInterface` | The contract your controllers depend on. `MarkdownArticleRepository` is the bundled implementation. |

Controllers and templates depend only on `ArticleRepositoryInterface` and `ArticleDto`. Swapping the backing store means binding a different repository class in your DI container — nothing else changes.

> **Note on HTML rendering.** `ArticleDto` stores raw Markdown in `$markdownContent`, not HTML. Inject `MarkdownRendererInterface` into controllers that need rendered output, and call `$renderer->render($article->markdownContent)` there. This keeps the DTO serialisation-friendly and avoids paying the rendering cost for consumers that don't need HTML (RSS feeds, search indexers, etc.).

---

## Writing Article Content

Organise article Markdown files under a directory of your choosing. A common convention is `content/articles/YYYY/MM/slug-fragment.md`:

```
content/
└── articles/
    └── 2024/
        └── 01/
            └── my-first-article.md
```

Each file must have a YAML front matter block at the top:

```markdown
---
slug: 2024/01/my-first-article
title: My First Article
publishDate: "2024-01-15 09:00am"
subtitle: A short subtitle
summary: A one-sentence teaser shown in article listings.
image: /images/articles/2024/01/my-first-article/hero.jpg
categories:
  - General
tags:
  - intro
  - news
---

Article body written in **Markdown**.

More paragraphs, lists, code blocks — anything supported by Markdown Extra.
```

### Required fields

| Field | Description |
|-------|-------------|
| `slug` | URL-safe identifier. Can contain slashes, e.g. `2024/01/my-article`. |
| `title` | Article title. |
| `publishDate` | Publication date/time. **Quote the value** (e.g. `"2024-01-15 09:00am"`) to prevent the YAML parser from treating a bare ISO-8601 date as a Unix timestamp. |

### Optional fields

| Field | Default | Description |
|-------|---------|-------------|
| `subtitle` | `''` | Secondary heading. |
| `summary` | `''` | Short teaser text for listing pages. |
| `image` | `''` | Path or URL to a hero image. |
| `categories` | `[]` | List of category strings. |
| `tags` | `[]` | List of tag strings. |

---

## Integration with slim-minimal-website

The steps below assume a project created from the [`opengeek/slim-minimal-website`](https://github.com/opengeek/slim-minimal-website) template.

### Step 1 — Install the library

```bash
composer require opengeek/content
```

### Step 2 — Create the content directory

```bash
mkdir -p content/articles
```

Add your first article Markdown file as described above.

### Step 3 — Add settings

In `config/settings.php`, add a `content` block inside the settings array:

```php
'content' => [
    'articles_path' => __DIR__ . '/../content/articles',
    'recursive'     => true,
],
```

### Step 4 — Wire the DI container

Add the following bindings to `config/dependencies.php`. Place them inside the existing `$containerBuilder->addDefinitions([...])` call.

```php
use Opengeek\Content\Article\ArticleRepositoryInterface;
use Opengeek\Content\Article\Markdown\MarkdownArticleMapper;
use Opengeek\Content\Article\Markdown\MarkdownArticleRepository;
use Opengeek\Content\Article\Markdown\MarkdownArticleRepositoryConfig;
use Opengeek\Content\Renderer\HtmlMarkdownRenderer;
use Opengeek\Content\Renderer\MarkdownRendererInterface;
use Psr\Container\ContainerInterface;

// ... inside addDefinitions([

MarkdownRendererInterface::class => \DI\autowire(HtmlMarkdownRenderer::class),

MarkdownArticleRepositoryConfig::class => function (ContainerInterface $c): MarkdownArticleRepositoryConfig {
    $content = $c->get('settings')['content'];
    return new MarkdownArticleRepositoryConfig(
        contentPath: $content['articles_path'],
        recursive:   $content['recursive'] ?? true,
    );
},

ArticleRepositoryInterface::class => \DI\autowire(MarkdownArticleRepository::class),
```

PHP-DI's autowiring resolves `MarkdownArticleRepositoryConfig` and `MarkdownArticleMapper` automatically from the bindings above.

### Step 5 — Define routes

In `config/routes.php`:

```php
use Opengeek\Controllers\Article;
use Opengeek\Controllers\Articles;

$app->get('/articles', Articles::class);
$app->get('/articles/{slug:.*}', Article::class);
```

The `{slug:.*}` pattern allows slugs that contain slashes (e.g. `2024/01/my-article`).

### Step 6 — Write controllers

Create `src/Controllers/Articles.php` for the listing page:

```php
<?php

declare(strict_types=1);

namespace Opengeek\Controllers;

use Opengeek\Content\Article\ArticleRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;

final readonly class Articles
{
    private const int PER_PAGE = 10;

    public function __construct(
        private Twig $twig,
        private ArticleRepositoryInterface $articles,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $published = $this->articles->findPublished();
        $total     = count($published);
        $pages     = max(1, (int) ceil($total / self::PER_PAGE));
        $page      = max(1, min($pages, (int) ($request->getQueryParams()['page'] ?? 1)));
        $offset    = ($page - 1) * self::PER_PAGE;

        return $this->twig->render(
            $request->getAttribute('response') ?? new \Slim\Psr7\Response(),
            'articles.twig',
            [
                'articles'    => $published->slice($offset, self::PER_PAGE),
                'currentPage' => $page,
                'pages'       => $pages,
            ]
        );
    }
}
```

Create `src/Controllers/Article.php` for a single article:

```php
<?php

declare(strict_types=1);

namespace Opengeek\Controllers;

use Opengeek\Content\Article\ArticleRepositoryInterface;
use Opengeek\Content\Exception\ContentNotFoundException;
use Opengeek\Content\Renderer\MarkdownRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Views\Twig;

final readonly class Article
{
    public function __construct(
        private Twig $twig,
        private ArticleRepositoryInterface $articles,
        private MarkdownRendererInterface $renderer,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $slug = $request->getAttribute('slug', '');

        try {
            $article = $this->articles->findBySlug($slug);
        } catch (ContentNotFoundException) {
            throw new HttpNotFoundException($request);
        }

        $response = new \Slim\Psr7\Response();

        return $this->twig->render($response, 'article.twig', [
            'article'     => $article,
            'htmlContent' => $this->renderer->render($article->markdownContent),
        ]);
    }
}
```

### Step 7 — Write Twig templates

**`templates/articles.twig`** — article listing with pagination:

```twig
{% extends 'base.twig' %}

{% block content %}
<h1>Articles</h1>

{% for article in articles %}
<article>
    <h2><a href="/articles/{{ article.slug }}">{{ article.title }}</a></h2>
    {% if article.subtitle %}<p class="subtitle">{{ article.subtitle }}</p>{% endif %}
    <p class="meta">{{ article.getPublishDateTime().format('F j, Y') }}</p>
    {% if article.summary %}<p>{{ article.summary }}</p>{% endif %}
</article>
{% else %}
<p>No articles published yet.</p>
{% endfor %}

{% if pages > 1 %}
<nav>
    {% if currentPage > 1 %}
        <a href="/articles?page={{ currentPage - 1 }}">Previous</a>
    {% endif %}
    <span>Page {{ currentPage }} of {{ pages }}</span>
    {% if currentPage < pages %}
        <a href="/articles?page={{ currentPage + 1 }}">Next</a>
    {% endif %}
</nav>
{% endif %}
{% endblock %}
```

**`templates/article.twig`** — single article:

```twig
{% extends 'base.twig' %}

{% block content %}
<article>
    <header>
        <h1>{{ article.title }}</h1>
        {% if article.subtitle %}<p class="subtitle">{{ article.subtitle }}</p>{% endif %}
        <p class="meta">{{ article.getPublishDateTime().format('F j, Y') }}</p>
        {% if article.image %}<img src="{{ article.image }}" alt="{{ article.title }}">{% endif %}
    </header>

    <div class="content">
        {{ htmlContent|raw }}
    </div>

    {% if article.tags %}
    <footer>
        Tags:
        {% for tag in article.tags %}
            <span class="tag">{{ tag }}</span>
        {% endfor %}
    </footer>
    {% endif %}
</article>
{% endblock %}
```

---

## `ArticleDto` Reference

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `$slug` | `string` | yes | URL-safe identifier, e.g. `2024/01/my-article` |
| `$title` | `string` | yes | Article title |
| `$publishDate` | `string` | yes | Raw date string from YAML front matter |
| `$markdownContent` | `string` | yes | Raw Markdown body (not HTML) |
| `$subtitle` | `string` | no | Secondary heading (default `''`) |
| `$summary` | `string` | no | Short teaser text (default `''`) |
| `$image` | `string` | no | Hero image path or URL (default `''`) |
| `$categories` | `string[]` | no | Category labels (default `[]`) |
| `$tags` | `string[]` | no | Tag labels (default `[]`) |

| Method | Returns | Description |
|--------|---------|-------------|
| `getPublishDateTime()` | `\DateTimeImmutable` | Parses `$publishDate` into an immutable date object. |
| `isPublished(?DateTimeImmutable $now)` | `bool` | Returns `true` if `$publishDate` is on or before `$now` (defaults to current time). |

---

## `ArticleCollection` Methods

`ArticleRepositoryInterface::findAll()` and `findPublished()` both return an `ArticleCollection`. The collection is **immutable** — filtering and sorting return new instances.

| Method | Returns | Description |
|--------|---------|-------------|
| `filterPublished(?DateTimeImmutable $now)` | `ArticleCollection` | Returns a new collection containing only published articles. |
| `sortByPublishDateDescending()` | `ArticleCollection` | Returns a new collection sorted newest-first. |
| `slice(int $offset, int $length)` | `ArticleCollection` | Returns a new collection containing `$length` items starting at `$offset`. Use for pagination. |
| `count()` | `int` | Total number of items. |

`ArticleCollection` implements `IteratorAggregate` (usable in `foreach`), `Countable`, and `ArrayAccess` (read-only).

---

## Custom Backend Implementations

To swap out Markdown files for a different storage backend, implement `ArticleRepositoryInterface` and a corresponding mapper:

```php
<?php

declare(strict_types=1);

namespace Opengeek\Repositories;

use Doctrine\DBAL\Connection;
use Opengeek\Content\Article\ArticleCollection;
use Opengeek\Content\Article\ArticleDto;
use Opengeek\Content\Article\ArticleRepositoryInterface;
use Opengeek\Content\Exception\ContentNotFoundException;

final readonly class DoctrineArticleRepository implements ArticleRepositoryInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public function findAll(): ArticleCollection
    {
        $rows = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('articles')
            ->fetchAllAssociative();

        return new ArticleCollection(array_map($this->toDto(...), $rows));
    }

    public function findPublished(): ArticleCollection
    {
        $rows = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('articles')
            ->where('publish_date <= :now')
            ->orderBy('publish_date', 'DESC')
            ->setParameter('now', (new \DateTimeImmutable())->format('Y-m-d H:i:s'))
            ->fetchAllAssociative();

        return new ArticleCollection(array_map($this->toDto(...), $rows));
    }

    public function findBySlug(string $slug): ArticleDto
    {
        $row = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('articles')
            ->where('slug = :slug')
            ->setParameter('slug', $slug)
            ->fetchAssociative();

        if ($row === false) {
            throw ContentNotFoundException::forSlug($slug);
        }

        return $this->toDto($row);
    }

    private function toDto(array $row): ArticleDto
    {
        return new ArticleDto(
            slug:            $row['slug'],
            title:           $row['title'],
            publishDate:     $row['publish_date'],
            markdownContent: $row['body'],
            subtitle:        $row['subtitle'] ?? '',
            summary:         $row['summary'] ?? '',
            image:           $row['image'] ?? '',
            categories:      json_decode($row['categories'] ?? '[]', true),
            tags:            json_decode($row['tags'] ?? '[]', true),
        );
    }
}
```

Then rebind in `config/dependencies.php`:

```php
ArticleRepositoryInterface::class => \DI\autowire(DoctrineArticleRepository::class),
```

Controllers and templates require no changes.

---

## Running the Test Suite

```bash
composer install
./vendor/bin/phpunit
```

---

## License

Proprietary. All rights reserved.
