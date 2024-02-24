<?php

namespace TomShaw\ElectricGrid\Filters;

use TomShaw\ElectricGrid\Filters\Traits\WithPlaceholder;

class FilterNumber extends FilterBase
{
    use WithPlaceholder;

    public function __construct(string $column)
    {
        parent::__construct($column);

        $this->placeholders = [
            'min' => __('electricgrid::locale.range.min'),
            'max' => __('electricgrid::locale.range.max'),
        ];
    }
}
