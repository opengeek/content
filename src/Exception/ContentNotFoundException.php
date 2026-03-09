<?php

declare(strict_types=1);

namespace Opengeek\Content\Exception;

class ContentNotFoundException extends ContentException
{
    public static function forSlug(string $slug): self
    {
        return new self(sprintf('No content found for slug: "%s"', $slug));
    }
}
