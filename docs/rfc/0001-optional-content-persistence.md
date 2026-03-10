# RFC 0001: Optional Persistence for Content Types

- **Status:** Proposed
- **Author:** Opengeek
- **Created:** 2026-03-10
- **Target Version:** Next minor release
- **Discussion:** TBD

## Summary

Introduce a clean long-term architecture for optional content persistence by separating **read/query responsibilities** from **write/persistence responsibilities**.

The library currently models content retrieval well. This RFC proposes extending the design so that some backends can support persistence while others remain intentionally read-only.

The core design is:

- keep repositories focused on **retrieval**
- add separate **persister** contracts for optional write support
- define write support at the **content-type level**
- support backend-specific writable implementations such as **Doctrine DBAL + SQLite**
- avoid forcing all backends to implement write methods

This allows:

- Markdown-backed content to remain read-only
- SQL-backed content to support reads and writes
- future backends to opt into persistence only when appropriate

---

## Motivation

Not all content backends have the same capabilities.

Examples:

- A Markdown file backend is naturally read-only in many applications
- A Doctrine DBAL + SQLite backend is a strong fit for persistence
- A future REST-backed implementation may support reads only or both reads and writes
- A search index backend may support neither canonical reads nor writes directly

If write methods are added to a single generic repository contract, then every implementation is forced to either:

- implement write support even when it makes no sense, or
- throw runtime exceptions for unsupported operations

That leads to a brittle API and unclear expectations for consumers.

This RFC proposes a capability-based design:

- **repositories** are for reading
- **persisters** are for writing
- writable backends may implement both
- read-only backends implement only the repository contract

This follows interface segregation, keeps type safety strong, and scales well as the library grows.

---

## Goals

- Preserve the current retrieval-oriented design
- Add optional persistence support without breaking read-only backends
- Keep public APIs strongly typed per content type
- Support a clean Doctrine DBAL implementation
- Allow SQLite-specific helpers without coupling the entire persistence model to SQLite
- Establish a namespace and naming strategy that will age well

---

## Non-Goals

- Introduce a full ORM model
- Introduce drafts, publishing workflows, revision history, or editorial state management
- Require every content type to become writable
- Replace DTO-style content objects with entities
- Add migrations tooling in this RFC
- Redesign rendering concerns

---

## Design Principles

1. **Read and write concerns must be separated**
2. **Backends should implement only the capabilities they support**
3. **Type-specific contracts are preferred over weakly typed generic writes**
4. **Backend implementation names should reflect the PHP integration boundary**
5. **Database-specific concerns should be isolated from generic repository logic**

---

## Proposed Architecture

### 1. Keep generic repository contracts read-focused

The existing generic repository abstraction should remain retrieval-oriented.

Proposed generic contract:

    interface ContentRepositoryInterface
    {
        public function findAll(): mixed;
        public function findBySlug(string $slug): mixed;
    }

This remains the shared baseline abstraction for read access.

---

### 2. Introduce a generic optional persister contract

A generic persister contract should exist for consistency and shared patterns, but application code should generally prefer type-specific persister contracts.

Proposed generic persister contract:

    interface ContentPersisterInterface
    {
        public function save(object $content): void;
        public function delete(string $slug): void;
    }

Notes:

- This contract is intentionally generic
- It is not the preferred API for day-to-day consumer code
- Type-specific persister contracts provide better type safety

---

### 3. Define type-specific article contracts

The article content type should gain a dedicated persistence contract.

Proposed article repository contract:

    interface ArticleRepositoryInterface extends ContentRepositoryInterface
    {
        public function findAll(): ArticleCollection;
        public function findBySlug(string $slug): Article;
        public function findPublished(?\DateTimeImmutable $now = null): ArticleCollection;
    }

Proposed article persister contract:

    interface ArticlePersisterInterface
    {
        public function save(Article $article): void;
        public function delete(string $slug): void;
    }

Optional combined writable repository contract:

    interface WritableArticleRepositoryInterface extends ArticleRepositoryInterface, ArticlePersisterInterface
    {
    }

Rationale:

- strong typing
- a clean read contract
- optional write support
- a convenient combined contract for backends that supports both capabilities

---

### 4. Prefer separate repository and persister implementations for long-term cleanliness

For the cleanest long-term design, Doctrine DBAL support should be split into:

- a **repository** for reads
- a **persister** for writes
- a **mapper** for storage translation
- a **schema manager** for SQLite-specific setup

This is preferable to a single class doing everything because it keeps responsibilities narrow and allows public-read/admin-write concerns to evolve independently later.

---

## Proposed Namespace Layout

    src/
    ├── Contracts/
    │   ├── ContentMapperInterface.php
    │   ├── ContentRepositoryInterface.php
    │   └── ContentPersisterInterface.php
    ├── Exception/
    │   └── ContentPersistenceException.php
    └── Type/
        └── Article/
            ├── Article.php
            ├── ArticleCollection.php
            ├── ArticleRepositoryInterface.php
            ├── ArticlePersisterInterface.php
            ├── WritableArticleRepositoryInterface.php
            ├── Markdown/
            │   ├── MarkdownArticleMapper.php
            │   └── MarkdownArticleRepository.php
            └── Doctrine/
                ├── Dbal/
                │   ├── DbalArticleMapper.php
                │   ├── DbalArticleRepository.php
                │   └── DbalArticlePersister.php
                └── Sqlite/
                    └── SqliteArticleSchemaManager.php

---

## Naming Strategy

### Doctrine DBAL classes should be named after DBAL, not SQLite

Preferred names:

- `DbalArticleRepository`
- `DbalArticlePersister`
- `DbalArticleMapper`

Reason:

- DBAL is the true integration boundary in application code
- SQLite is just one supported platform beneath DBAL
- the same repository/persister may later support MySQL or PostgreSQL without renaming core classes

### SQLite-specific classes should be named after SQLite only where platform-specific behavior exists

Preferred names:

- `SqliteArticleSchemaManager`

Reason:

- schema setup and platform-specific SQL are true SQLite concerns
- keeping SQLite-specific code isolated avoids coupling the whole design to one database engine

---

## Implementation Details

### 1. Markdown backend remains read-only

The Markdown backend should continue implementing only the article repository contract.

Expected behavior:

- supports `findAll()`
- supports `findBySlug()`
- supports `findPublished()`
- does **not** implement article persistence

Rationale:

Markdown-backed content is often intentionally managed outside the library via files and version control. Forcing write support would be unnecessary and misleading.

---

### 2. Doctrine DBAL backend supports both read and write

The Doctrine DBAL backend should implement both retrieval and persistence using separate services.

`DbalArticleRepository` responsibilities:

- query all articles
- query published articles
- query article by slug
- convert storage rows to `Article` DTOs through a mapper

`DbalArticlePersister` responsibilities:

- save an article using insert-or-update semantics
- delete an article by slug
- convert `Article` DTOs into storage rows through a mapper

`DbalArticleMapper` responsibilities:

- map database row arrays to `Article`
- map `Article` objects to database row arrays

`SqliteArticleSchemaManager` responsibilities:

- create article table if missing
- create indexes if missing
- encapsulate SQLite-specific schema setup logic

---

### 3. Storage mapping

The Doctrine DBAL article mapper should support the following row shape.

| Column             | Type   | Notes                         |
|--------------------|--------|-------------------------------|
| `slug`             | `TEXT` | Primary key                   |
| `title`            | `TEXT` | Required                      |
| `publish_date`     | `TEXT` | Store raw publish date string |
| `markdown_content` | `TEXT` | Required                      |
| `subtitle`         | `TEXT` | Default empty string          |
| `summary`          | `TEXT` | Default empty string          |
| `image`            | `TEXT` | Default empty string          |
| `categories`       | `TEXT` | JSON-encoded string array     |
| `tags`             | `TEXT` | JSON-encoded string array     |

Rationale:

- `publishDate` remains a string in the DTO
- content remains Markdown rather than rendered HTML
- arrays such as categories and tags remain simple JSON payloads

---

### 4. Upsert semantics

The `save()` method on persisters should use insert-or-update semantics.

Required behavior:

- if the slug does not exist, insert a new record
- if the slug exists, update the existing record
- the slug is the canonical identity key

Rationale:

This is the least surprising persistence behavior for content records and keeps the contract simple.

---

### 5. Exception strategy

Persistence-related failures should be wrapped in a library-specific exception.

Proposed exception shape:

    final class ContentPersistenceException extends RuntimeException
    {
        public static function forSlug(string $slug, \Throwable $previous): self
        {
            // ...
        }

        public static function forDeletion(string $slug, \Throwable $previous): self
        {
            // ...
        }
    }

Rationale:

- prevents leaking low-level DBAL details through the public API
- keeps exception handling library-focused
- provides stable semantics to consumers

---

## Example Interfaces and Skeletons

`ContentPersisterInterface`:

    interface ContentPersisterInterface
    {
        public function save(object $content): void;
        public function delete(string $slug): void;
    }

`ArticlePersisterInterface`:

    interface ArticlePersisterInterface
    {
        public function save(Article $article): void;
        public function delete(string $slug): void;
    }

`WritableArticleRepositoryInterface`:

    interface WritableArticleRepositoryInterface extends ArticleRepositoryInterface, ArticlePersisterInterface
    {
    }

`DbalArticleMapper` responsibilities:

- map row arrays to `Article`
- map `Article` to row arrays
- JSON encode/decode categories and tags safely

`DbalArticleRepository` responsibilities:

- return all articles ordered by publishDate descending
- return published articles filtered by the current or supplied date
- return a single article by slug
- throw notFound when missing

`DbalArticlePersister` responsibilities:

- upsert article rows by slug
- delete rows by slug
- wrap persistence failures in `ContentPersistenceException`

`SqliteArticleSchemaManager` responsibilities:

- create `articles` table if it does not exist
- create publish date index if it does not exist
- remains safe to run repeatedly

---

## Why This Is the Preferred Long-Term Design

This design is preferred because it provides:

### Clear capability boundaries
Consumers can tell whether a backend supports writes based on the interfaces it implements.

### Strong typing
Article write operations accept `Article`, not a generic `object`.

### Better extensibility
Other content types can later add their own repository and persister contracts without bending a generic API.

### Better backend flexibility
Reads and writes can evolve independently if needed.

### Better naming longevity
DBAL-based components can remain correctly named even if SQLite is later replaced or supplemented.

---

## Alternatives Considered

### Alternative A: Add `save()` and `delete()` to the generic repository contract

Example:

    interface ContentRepositoryInterface
    {
        public function findAll(): mixed;
        public function findBySlug(string $slug): mixed;
        public function save(object $content): void;
        public function delete(string $slug): void;
    }

Rejected because:

- it forces write methods on read-only backends
- unsupported operations would fail at runtime
- interface intent becomes unclear
- typing remains weak

---

### Alternative B: Use one writable repository class only and skip a separate persister

Example:

- `DbalArticleRepository implements WritableArticleRepositoryInterface`

Pros:

- fewer classes
- simpler short-term implementation

Rejected for long-term preference because:

- query logic and write logic are coupled
- harder to evolve into separate read and admin workflows
- less explicit separation of concerns

This remains an acceptable short-term simplification, but it is not the preferred long-term architecture.

---

### Alternative C: Introduce separate write models or command objects now

Example:

- `save(UpsertArticle $article): void`

Pros:

- clean separation between read models and write models
- good fit for complex editorial workflows later

Rejected for now because:

- introduces more ceremony than needed at current scope
- the current DTO is sufficient as a write payload
- can be added later without invalidating this RFC

---

## Backwards Compatibility

This RFC should be implemented in a largely backward-compatible way.

Expected compatibility impact:

- existing read-only consumers should continue to work unchanged
- Markdown repository behavior remains unchanged
- new persistence support is additive

Possible compatibility concerns:

- if article repository interfaces are modified, dependent implementations may need small updates
- new exceptions and contracts may require documentation updates

---

## Testing Strategy

The implementation should include tests for:

### Generic/API-level tests
- repository contracts remain read-oriented
- persister contracts are optional and separate

### Markdown backend tests
- Markdown repository continues to behave as read-only
- no persistence implementation is required

### DBAL repository tests
- `findAll()` returns mapped articles
- `findPublished()` filters correctly by date
- `findBySlug()` throws when missing

### DBAL persister tests
- `save()` inserts a new article
- `save()` updates an existing article with the same slug
- `delete()` removes an existing article
- persistence exceptions are wrapped in `ContentPersistenceException`

### SQLite schema manager tests
- schema creation succeeds on an empty SQLite database
- repeated schema setup is idempotent

---

## Documentation Changes

Documentation should be updated to clarify:

- repositories are read-focused
- persistence is optional
- Markdown backend is read-only
- DBAL backends may provide persistence
- consumers should depend on `ArticleRepositoryInterface` for reads
- consumers should depend on `ArticlePersisterInterface` for writes

Suggested documentation table:

| Backend                | Read | Write |
|------------------------|-----:|------:|
| Markdown               |  Yes |    No |
| Doctrine DBAL + SQLite |  Yes |   Yes |

---

## Rollout Plan

### Phase 1
Add new contracts and exceptions:

- `ContentPersisterInterface`
- `ArticlePersisterInterface`
- `WritableArticleRepositoryInterface`
- `ContentPersistenceException`

### Phase 2
Add DBAL support:

- `DbalArticleMapper`
- `DbalArticleRepository`
- `DbalArticlePersister`

### Phase 3
Add SQLite-specific schema helper:

- `SqliteArticleSchemaManager`

### Phase 4
Add tests and update documentation

---

## Open Questions

1. Should DBAL support ship in the main package or in a dedicated integration package later?
2. Should schema setup remain manual, or should a lightweight migration helper be added later?
3. Should `delete()` be silent when the slug does not exist, or should it report missing content explicitly?
4. Should future content types mirror this exact pattern, or should some share abstract DBAL helpers?

These questions do not block the adoption of this RFC.

---

## Recommendation

Adopt this RFC.

It provides the cleanest long-term design by:

- preserving a read-first architecture
- making persistence optional
- keeping type-specific APIs strong
- isolating backend concerns cleanly
- supporting Doctrine DBAL + SQLite without imposing write semantics on Markdown

This approach is simple enough to implement now and strong enough to support future growth without architectural regret.
