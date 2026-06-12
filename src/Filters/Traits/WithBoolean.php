<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Filters\Traits;

trait WithBoolean
{
    /**
     * The options for the boolean filter.
     *
     * @var array<string, string>
     */
    public array $options = [
        'true' => 'Yes',
        'false' => 'No',
    ];

    /**
     * Set the labels for the boolean filter.
     *
     * @return $this
     */
    public function labels(string $trueLabel = 'Yes', string $falseLabel = 'No'): self
    {
        $this->options['true'] = $trueLabel;
        $this->options['false'] = $falseLabel;

        return $this;
    }
}
