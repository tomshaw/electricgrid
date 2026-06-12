<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Exceptions;

use InvalidArgumentException;

/** @phpstan-consistent-constructor */
class InvalidDateFormatHandler extends InvalidArgumentException
{
    public static function make(string $key, string $value): self
    {
        return new static("Invalid date format using $key: $value");
    }
}
