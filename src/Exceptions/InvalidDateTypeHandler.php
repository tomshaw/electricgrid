<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Exceptions;

use InvalidArgumentException;

/** @phpstan-consistent-constructor */
class InvalidDateTypeHandler extends InvalidArgumentException
{
    public static function make(string $type): self
    {
        return new static("An internal error occured using filter type : $type");
    }
}
