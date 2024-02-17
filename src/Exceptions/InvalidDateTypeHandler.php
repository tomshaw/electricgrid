<?php

namespace TomShaw\ElectricGrid\Exceptions;

use InvalidArgumentException;

class InvalidDateTypeHandler extends InvalidArgumentException
{
    public static function make(string $type): self
    {
        return new static("An internal error occured using filter type : $type");
    }
}
