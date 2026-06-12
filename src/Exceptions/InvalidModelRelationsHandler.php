<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Exceptions;

use InvalidArgumentException;

/** @phpstan-consistent-constructor */
class InvalidModelRelationsHandler extends InvalidArgumentException
{
    public static function make(string $errorMessage): self
    {
        return new static('An error occured in your model relations: '.$errorMessage);
    }
}
