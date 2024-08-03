<?php

namespace TomShaw\ElectricGrid\Filters;

use ReflectionClass;
use TomShaw\ElectricGrid\Filters\Traits\WithAttributes;

class FilterBase
{
    use WithAttributes;

    public function __construct(
        public string $column
    ) {}

    public function type(string $name): bool
    {
        $reflection = new ReflectionClass($this);

        return $reflection->getShortName() === $name;
    }
}
