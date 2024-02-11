<?php

namespace TomShaw\ElectricGrid\Filters\Traits;

trait WithOptions
{
    /**
     * The options for the filter.
     */
    public array $options;

    /**
     * Set the options for the filter.
     *
     * @return $this
     */
    public function options(array $options): self
    {
        $this->options = $options;

        return $this;
    }
}
