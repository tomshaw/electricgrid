<?php

namespace TomShaw\ElectricGrid\Exceptions;

use InvalidArgumentException;

class RequiredMethodHandler extends InvalidArgumentException
{
    public static function make(string $requiredMethodName): self
    {
        return new static("The required method `{$requiredMethodName}` must be implemented.");
    }
}
