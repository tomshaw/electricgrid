<?php

namespace TomShaw\ElectricGrid\Filters\Traits;

trait WithAttributes
{
    /**
     * The attributes for the filter.
     */
    public array $attributes = [];

    /**
     * Add a data attribute to the filter.
     *
     * @return $this
     */
    public function addDataAttribute(string $name, string $value): self
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    public function getDataAttributes(): string
    {
        $result = '';

        foreach ($this->attributes as $name => $value) {
            $result .= 'data-'.e($name).'="'.e($value).'" ';
        }

        return $result;
    }
}
