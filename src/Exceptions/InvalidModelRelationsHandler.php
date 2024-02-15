<?php

namespace TomShaw\ElectricGrid\Exceptions;

use InvalidArgumentException;

class InvalidModelRelationsHandler extends InvalidArgumentException
{
    public static function make(string $errorMessage): self
    {
        return new static('An error occured in your model relations: '.$errorMessage);
    }
}
