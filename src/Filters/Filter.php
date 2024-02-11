<?php

namespace TomShaw\ElectricGrid\Filters;

use Illuminate\Support\Facades\Facade;

/**
 * @method static FilterText text(string $column)
 * @method static FilterSelect select(string $column)
 * @method static FilterMultiSelect multiselect(string $column)
 * @method static FilterNumber number(string $column)
 * @method static FilterBoolean boolean(string $column)
 * @method static FilterTimePicker timepicker(string $column)
 * @method static FilterDatePicker datepicker(string $column)
 * @method static FilterDateTimePicker datetimepicker(string $column)
 *
 * @see FilterManager
 */
class Filter extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return FilterManager::class;
    }
}
