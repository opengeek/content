<?php

declare(strict_types=1);

namespace Opengeek\Content\Contracts;

use Opengeek\Content\Exception\ContentMappingException;

/**
 * Maps a raw source value of type TSource to a DTO of type TDto.
 *
 * @template TSource
 * @template TDto
 */
interface ContentMapperInterface
{
    /**
     * Map raw source data to a DTO.
     *
     * @param TSource $source
     * @return TDto
     *
     * @throws ContentMappingException
     */
    public function map(mixed $source): mixed;
}
