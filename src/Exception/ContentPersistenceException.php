<?php

declare(strict_types=1);

namespace Opengeek\Content\Exception;

/**
 * Exception thrown when a content persistence operation fails.
 */
class ContentPersistenceException extends ContentException
{
    public static function forSave(string $slug, ?\Throwable $previous = null): self
    {
        return new self(
            sprintf('Failed to save content with slug: "%s"', $slug),
            0,
            $previous
        );
    }

    public static function forDelete(string $slug, ?\Throwable $previous = null): self
    {
        return new self(
            sprintf('Failed to delete content with slug: "%s"', $slug),
            0,
            $previous
        );
    }
}
