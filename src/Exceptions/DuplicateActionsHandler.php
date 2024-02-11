<?php

namespace TomShaw\ElectricGrid\Exceptions;

use InvalidArgumentException;

class DuplicateActionsHandler extends InvalidArgumentException
{
    public static function make(array $duplicateMassActions): self
    {
        return new static('Unable to process the following duplicated mass actions: '.implode(', ', $duplicateMassActions));
    }
}
