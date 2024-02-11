<?php

namespace TomShaw\ElectricGrid\Exceptions;

use InvalidArgumentException;

class InvalidFilterHandler extends InvalidArgumentException
{
    public static function make(string $requestingFilterName): self
    {
        return new static("The required filter `{$requestingFilterName}` does not exist.");
    }
}
