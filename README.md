# opengeek/content

![PHP ^8.3](https://img.shields.io/badge/PHP-%5E8.3-blue)

The core package for the Opengeek Content library. Provides backend-agnostic contracts, types, collections, and shared exceptions for content management.

## Installation

```bash
composer require opengeek/content
```

## What's Included
- **Contracts**: Generic interfaces for repositories (`ContentRepositoryInterface`) and collections (`ContentCollectionInterface`).
- **Support**: `ContentCollectionTrait` for rapid collection implementation with filtering, sorting, and slicing.
- **Caching**: `CachingContentRepository` decorator to add PSR-6/Symfony Cache support to any repository.
- **Article DTO**: A pure value container for article data.
- **ArticleCollection**: A typed, immutable, and searchable collection of `Article` objects.
- **ArticleRepositoryInterface**: Specialized contract for reading articles.
- **ArticlePersisterInterface**: Contract for writing articles.
- **MarkdownRendererInterface**: Implementation-agnostic interface for rendering Markdown to HTML.
- **Shared Exceptions**: `ContentException`, `ContentNotFoundException`, `ContentMappingException`, etc.

## Usage

This package provides the interfaces you should depend on in your application logic. To actually load or save content, you will need one of the implementation packages:

- `opengeek/content-markdown`: Read-only Markdown file repository.
- `opengeek/content-dbal`: Read/write SQL persistence via Doctrine DBAL.
- `opengeek/content-markdown-renderer`: HTML rendering for Markdown content.
