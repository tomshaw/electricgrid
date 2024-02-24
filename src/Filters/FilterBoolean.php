<?php

namespace TomShaw\ElectricGrid\Filters;

use TomShaw\ElectricGrid\Filters\Traits\WithBoolean;

class FilterBoolean extends FilterBase
{
    use WithBoolean;

    public function __construct(string $column)
    {
        parent::__construct($column);

        $this->options = [
            'true' => __('electricgrid::locale.general.yes'),
            'false' => __('electricgrid::locale.general.no'),
        ];
    }
}
