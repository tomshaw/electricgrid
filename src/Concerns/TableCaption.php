<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Concerns;

/**
 * Fluent configuration for the table caption text and its placement.
 */
trait TableCaption
{
    public ?string $captionText = null;

    public string $captionSide = 'top';

    public function caption(?string $text = null, string $side = 'top'): static
    {
        $this->captionText = $text;
        $this->captionSide = $side === 'bottom' ? 'bottom' : 'top';

        return $this;
    }
}
