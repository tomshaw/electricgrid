<?php

namespace TomShaw\ElectricGrid\Filters;

class Filter
{
    public static function number(string $column): FilterNumber
    {
        return new FilterNumber($column);
    }

    public static function text(string $column): FilterText
    {
        return new FilterText($column);
    }

    public static function select(string $column): FilterSelect
    {
        return new FilterSelect($column);
    }

    public static function multiselect(string $column): FilterMultiSelect
    {
        return new FilterMultiSelect($column);
    }

    public static function timepicker(string $column): FilterTimePicker
    {
        return new FilterTimePicker($column);
    }

    public static function datepicker(string $column): FilterDatePicker
    {
        return new FilterDatePicker($column);
    }

    public static function datetimepicker(string $column): FilterDateTimePicker
    {
        return new FilterDateTimePicker($column);
    }

    public static function boolean(string $column): FilterBoolean
    {
        return new FilterBoolean($column);
    }
}
