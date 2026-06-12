<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Filters;

use TomShaw\ElectricGrid\Filters\Traits\WithPlaceholder;

class FilterText extends FilterBase
{
    use WithPlaceholder;

    public function __construct(string $column)
    {
        parent::__construct($column);

        $this->placeholder('Search');
    }
}
