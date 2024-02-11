<?php

namespace TomShaw\ElectricGrid\Exceptions;

use InvalidArgumentException;

class RequiredColumnsHandler extends InvalidArgumentException
{
    public static function make(array $missingColumns): self
    {
        return new static('The following columns are missing from the query: '.implode(', ', $missingColumns).'. Please add them to the query.');
    }
}
