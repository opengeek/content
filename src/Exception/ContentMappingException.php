<?php

declare(strict_types=1);

namespace Opengeek\Content\Exception;

class ContentMappingException extends ContentException
{
    public static function missingField(string $field, string $source): self
    {
        return new self(sprintf(
            'Required field "%s" is missing or invalid in content source: %s',
            $field,
            $source
        ));
    }
}
