<?php

namespace TomShaw\ElectricGrid\Filters;

use TomShaw\ElectricGrid\Filters\Traits\WithAttributes;

class FilterBase
{
    use WithAttributes;

    public function __construct(
        public string $column
    ) {
    }

    public function type(string $name): bool
    {
        return basename(get_called_class()) === $name;
    }
}
