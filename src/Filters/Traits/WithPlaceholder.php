<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Filters\Traits;

trait WithPlaceholder
{
    /**
     * The placeholder for the filter.
     */
    public string $placeholder = '';

    /**
     * The placeholders for the filter.
     *
     * @var array<string, string>
     */
    public array $placeholders = [
        'min' => 'Min',
        'max' => 'Max',
    ];

    /**
     * Set the placeholder for the filter.
     *
     * @return $this
     */
    public function placeholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * Set the placeholders for the filter.
     *
     * @return $this
     */
    public function placeholders(string $min, string $max): self
    {
        $this->placeholders = [
            'min' => $min,
            'max' => $max,
        ];

        return $this;
    }
}
