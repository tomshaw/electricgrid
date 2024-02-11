<?php

namespace TomShaw\ElectricGrid\Filters;

class FilterManager
{
    public function number(string $column): FilterNumber
    {
        return new FilterNumber($column);
    }

    public function text(string $column): FilterText
    {
        return new FilterText($column);
    }

    public function select(string $column): FilterSelect
    {
        return new FilterSelect($column);
    }

    public function multiselect(string $column): FilterMultiSelect
    {
        return new FilterMultiSelect($column);
    }

    public function timepicker(string $column): FilterTimePicker
    {
        return new FilterTimePicker($column);
    }

    public function datepicker(string $column): FilterDatePicker
    {
        return new FilterDatePicker($column);
    }

    public function datetimepicker(string $column): FilterDateTimePicker
    {
        return new FilterDateTimePicker($column);
    }

    public function boolean(string $column): FilterBoolean
    {
        return new FilterBoolean($column);
    }
}
