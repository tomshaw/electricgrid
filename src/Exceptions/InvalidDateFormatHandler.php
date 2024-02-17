<?php

namespace TomShaw\ElectricGrid\Exceptions;

use InvalidArgumentException;

class InvalidDateFormatHandler extends InvalidArgumentException
{
    public static function make(string $key, string $value): self
    {
        return new static("Invalid date format using $key: $value");
    }
}
