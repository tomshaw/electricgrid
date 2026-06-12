<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Exceptions;

use InvalidArgumentException;

/** @phpstan-consistent-constructor */
class RequiredColumnsHandler extends InvalidArgumentException
{
    /**
     * @param  array<int, string>  $missingColumns
     */
    public static function make(array $missingColumns): self
    {
        return new static('The following columns are missing from the query: '.implode(', ', $missingColumns).'. Please add them to the query.');
    }
}
