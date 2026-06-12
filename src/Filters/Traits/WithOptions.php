<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Filters\Traits;

trait WithOptions
{
    /**
     * The options for the filter.
     *
     * @var array<int|string, string>
     */
    public array $options = [];

    /**
     * Set the options for the filter.
     *
     * @param  array<int|string, string>  $options
     * @return $this
     */
    public function options(array $options): self
    {
        $this->options = $options;

        return $this;
    }
}
