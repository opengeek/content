# RFC 0001 Implementation Plan: Optional Persistence for Content Types

- **Status:** Ready for Implementation
- **Related RFC:** `docs/rfc/0001-optional-content-persistence.md`
- **Target:** Next minor release

## Purpose

Implement the optional persistence architecture described in RFC 0001 using the clean long-term design:

- repositories remain read-focused
- persistence is modeled separately
- article write support is type-specific
- Doctrine DBAL support is split into repository, persister, mapper, and SQLite schema manager

This document is implementation-oriented and intended to guide task execution.

---

## High-Level Outcome

After this work is complete, the library should support:

- read-only article repositories for Markdown-backed content
- optional writable article persistence via Doctrine DBAL
- SQLite schema setup for article storage
- clear public contracts for read and write responsibilities
- tests and documentation covering the new architecture

---

## Scope

### In Scope

- Add generic persistence contract
- Add article-specific persistence contracts
- Add persistence exception
- Add Doctrine DBAL article mapper
- Add Doctrine DBAL article repository
- Add Doctrine DBAL article persister
- Add SQLite article schema manager
- Add tests for all new behavior
- Update documentation to explain read vs write responsibilities

### Out of Scope

- Full migration framework
- Editorial workflows, drafts, revisions, approvals
- Additional content types beyond articles
- Markdown write support
- Admin UI or CLI tooling
- Packaging DBAL integration into a separate package

---

## Architectural Rules

Junie should follow these rules during implementation:

1. Do not add write methods to the generic read repository contract.
2. Do not require Markdown-backed repositories to support persistence.
3. Prefer type-specific write contracts over generic object-based write APIs in consumer-facing code.
4. Keep Doctrine DBAL read and write implementations separate.
5. Keep SQLite-specific schema logic out of repository and persister classes.
6. Preserve backward compatibility for existing read-only usage as much as possible.
7. Follow PHP 8.3 compatibility.
8. Keep naming aligned with the RFC:
    - `DbalArticleRepository`
    - `DbalArticlePersister`
    - `DbalArticleMapper`
    - `SqliteArticleSchemaManager`

---

## Deliverables

Junie should produce the following deliverables.

### New Contracts

- `src/Contracts/ContentPersisterInterface.php`
- `src/Type/Article/ArticlePersisterInterface.php`
- `src/Type/Article/WritableArticleRepositoryInterface.php`

### New Exception

- `src/Exception/ContentPersistenceException.php`

### New Doctrine DBAL Article Classes

- `src/Type/Article/Doctrine/Dbal/DbalArticleMapper.php`
- `src/Type/Article/Doctrine/Dbal/DbalArticleRepository.php`
- `src/Type/Article/Doctrine/Dbal/DbalArticlePersister.php`

### New SQLite Helper

- `src/Type/Article/Doctrine/Sqlite/SqliteArticleSchemaManager.php`

### Tests

Add or update tests covering:

- new contracts
- DBAL repository behavior
- DBAL persister behavior
- SQLite schema manager behavior
- compatibility of Markdown read-only behavior

### Documentation

Update `README.md` to explain:

- repositories are read-focused
- persistence is optional
- Markdown is read-only
- DBAL + SQLite can provide persistence
- consumers should use repository interfaces for reads and persister interfaces for writes

---

## File-by-File Plan

## 1. Add generic persistence contract

### File
`src/Contracts/ContentPersisterInterface.php`

### Responsibilities
- define generic optional persistence operations
- include `save()` and `delete()`
- remain generic and minimal

### Acceptance Criteria
- interface exists
- namespace matches project conventions
- method signatures are stable and minimal
- no read methods are added here

---

## 2. Add article-specific persister contract

### File
`src/Type/Article/ArticlePersisterInterface.php`

### Responsibilities
- define strongly typed article write operations
- accept `Article` in `save()`
- delete by slug

### Acceptance Criteria
- interface exists
- uses `Article` type directly
- no generic `object` parameter is exposed here
- contract is suitable for direct consumer use

---

## 3. Add combined writable article contract

### File
`src/Type/Article/WritableArticleRepositoryInterface.php`

### Responsibilities
- combine `ArticleRepositoryInterface` and `ArticlePersisterInterface`
- serve as convenience contract for writable backends

### Acceptance Criteria
- interface exists
- extends both read and write article contracts
- does not add extra methods

---

## 4. Add persistence exception

### File
`src/Exception/ContentPersistenceException.php`

### Responsibilities
- wrap low-level persistence failures
- expose stable library-level exception semantics
- provide factory helpers for save and delete failures

### Acceptance Criteria
- exception exists
- extends a reasonable SPL base exception
- supports wrapping previous throwable
- error messages are clear and backend-agnostic

---

## 5. Add DBAL article mapper

### File
`src/Type/Article/Doctrine/Dbal/DbalArticleMapper.php`

### Responsibilities
- map DBAL row arrays to `Article`
- map `Article` to database row arrays
- encode/decode categories and tags as JSON strings
- preserve current DTO shape

### Acceptance Criteria
- maps all article fields correctly
- supports empty optional fields safely
- handles categories and tags as string lists
- throws predictable errors if JSON encoding or decoding fails
- does not perform querying or persistence itself

### Notes
Expected storage keys:

- `slug`
- `title`
- `publish_date`
- `markdown_content`
- `subtitle`
- `summary`
- `image`
- `categories`
- `tags`

---

## 6. Add DBAL article repository

### File
`src/Type/Article/Doctrine/Dbal/DbalArticleRepository.php`

### Responsibilities
- implement read-only article retrieval via Doctrine DBAL
- support `findAll()`
- support `findPublished()`
- support `findBySlug()`
- use `DbalArticleMapper` for mapping

### Acceptance Criteria
- implements `ArticleRepositoryInterface`
- returns `ArticleCollection` where appropriate
- throws content not found exception when slug does not exist
- orders results consistently by publish date descending where applicable
- does not implement write methods
- contains no SQLite-specific schema creation logic

---

## 7. Add DBAL article persister

### File
`src/Type/Article/Doctrine/Dbal/DbalArticlePersister.php`

### Responsibilities
- implement article persistence via Doctrine DBAL
- support insert-or-update behavior by slug
- support delete by slug
- use `DbalArticleMapper` for row conversion
- wrap low-level persistence failures in `ContentPersistenceException`

### Acceptance Criteria
- implements `ArticlePersisterInterface`
- `save()` performs upsert semantics
- `delete()` removes by slug
- exceptions are wrapped consistently
- class contains no read-query responsibilities
- class contains no SQLite-specific schema creation logic

---

## 8. Add SQLite schema manager

### File
`src/Type/Article/Doctrine/Sqlite/SqliteArticleSchemaManager.php`

### Responsibilities
- create articles table if not present
- create useful index or indexes if not present
- provide idempotent schema setup for SQLite

### Acceptance Criteria
- schema creation works on empty SQLite database
- repeated runs are safe
- table structure matches expected DBAL mapper fields
- SQLite-specific SQL is isolated here
- repository and persister do not create schema directly

### Minimum Schema Expectations

The `articles` table should support:

- slug as primary key
- title
- publish date
- markdown content
- subtitle
- summary
- image
- categories JSON text
- tags JSON text

At least one publish-date-related index should be added if appropriate.

---

## 9. Verify Markdown remains read-only

### Files
Existing Markdown article repository and related tests

### Responsibilities
- ensure existing Markdown implementation remains read-only
- do not introduce persistence requirements into Markdown flow

### Acceptance Criteria
- no write contract is added to Markdown repository
- existing read behavior remains intact
- tests continue to validate read-only behavior

---

## 10. Update README documentation

### File
`README.md`

### Responsibilities
- document read vs write separation
- document optional persistence capability
- explain Markdown is read-only
- explain DBAL + SQLite support path
- explain which interfaces consumers should depend on

### Acceptance Criteria
- read usage examples remain clear
- write usage examples, if added, use `ArticlePersisterInterface`
- backend capability expectations are documented
- naming aligns with implementation

---

## Testing Plan

Junie should add or update tests covering the following.

## Contract-Level Tests

### Goals
- confirm read and write responsibilities are separate by design
- confirm article persistence contracts are strongly typed

### Acceptance Criteria
- interfaces compile and autoload correctly
- no accidental write methods appear on read-only contracts

---

## Markdown Backend Tests

### Goals
- preserve existing Markdown repository behavior
- confirm persistence is not required for Markdown backend

### Acceptance Criteria
- existing Markdown retrieval tests still pass
- no new write-path tests are required for Markdown

---

## DBAL Mapper Tests

### Goals
- validate row-to-article mapping
- validate article-to-row mapping
- validate JSON list handling

### Acceptance Criteria
- all fields map correctly in both directions
- optional fields behave correctly
- categories and tags remain lists of strings

---

## DBAL Repository Tests

### Goals
- verify `findAll()`
- verify `findPublished()`
- verify `findBySlug()`

### Acceptance Criteria
- repository returns correct DTOs and collections
- missing slugs throw content not found exception
- published filtering respects provided or current date
- ordering is consistent

---

## DBAL Persister Tests

### Goals
- verify insert behavior
- verify update behavior
- verify delete behavior
- verify exception wrapping

### Acceptance Criteria
- saving a new article inserts a row
- saving an article with an existing slug updates that row
- deleting an existing slug removes the row
- DBAL failures are wrapped in `ContentPersistenceException`

---

## SQLite Schema Manager Tests

### Goals
- verify schema creation
- verify idempotence

### Acceptance Criteria
- schema manager can initialize an empty SQLite database
- running schema setup more than once does not fail
- resulting schema supports repository and persister tests

---

## Suggested Implementation Order

Junie should implement in this order:

1. Add new contracts
2. Add persistence exception
3. Add DBAL mapper
4. Add SQLite schema manager
5. Add DBAL repository
6. Add DBAL persister
7. Add tests for mapper, repository, persister, and schema
8. Verify Markdown compatibility
9. Update README

This order reduces rework and allows tests to build progressively.

---

## Suggested Commit Breakdown

If Junie works in small commits, use this sequence.

### Commit 1
Add persistence contracts and exception

Includes:
- `ContentPersisterInterface`
- `ArticlePersisterInterface`
- `WritableArticleRepositoryInterface`
- `ContentPersistenceException`

### Commit 2
Add DBAL mapper and SQLite schema manager

Includes:
- `DbalArticleMapper`
- `SqliteArticleSchemaManager`

### Commit 3
Add DBAL read and write implementations

Includes:
- `DbalArticleRepository`
- `DbalArticlePersister`

### Commit 4
Add or update tests

Includes:
- mapper tests
- repository tests
- persister tests
- schema manager tests
- compatibility verification for Markdown

### Commit 5
Update documentation

Includes:
- README updates
- backend capability documentation

---

## Definition of Done

This work is complete when all of the following are true:

- new persistence contracts exist and are correctly namespaced
- article write support is strongly typed
- DBAL read implementation exists and works
- DBAL write implementation exists and works
- SQLite schema setup exists and is idempotent
- Markdown backend remains read-only
- tests cover the new architecture
- README documents the new capability split
- implementation follows the naming and separation rules from RFC 0001

---

## Constraints and Guardrails

Junie must avoid the following:

- adding `save()` or `delete()` to the generic read repository contract
- making Markdown repositories implement write interfaces
- merging DBAL repository and persister into one class unless explicitly instructed otherwise
- embedding SQLite schema creation inside repository or persister classes
- introducing a write model separate from `Article` in this iteration
- introducing unrelated architectural changes

---

## If Trade-Offs Are Needed

If Junie must make a trade-off, prefer:

1. preserving interface clarity over reducing class count
2. preserving long-term separation over short-term convenience
3. preserving backward compatibility over speculative abstractions
4. keeping SQLite-specific logic isolated over convenience shortcuts

---

## Questions to Resolve During Implementation

If ambiguity arises, Junie should use these defaults:

- `save()` uses insert-or-update semantics based on slug
- `delete()` may be silent if the slug does not exist unless tests or existing conventions suggest otherwise
- DBAL implementation should remain reusable beyond SQLite
- SQLite helper is responsible only for schema setup, not migrations policy

---

## Final Instruction to Implementer

Implement the RFC exactly as a capability-based architecture:

- read concerns in repositories
- write concerns in persisters
- article-specific strong typing
- DBAL integration split by responsibility
- SQLite concerns isolated in schema management
- Markdown left intentionally read-only
